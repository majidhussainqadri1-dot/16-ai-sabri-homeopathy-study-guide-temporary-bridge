<?php
/** Simulate File 16 rollback/schema parity without a WordPress DB. */
define( 'ABSPATH', __DIR__ . '/' );
define( 'SCHA_TEXT_DOMAIN', 'sabri-classical-homeopathy-ai' );
define( 'MINUTE_IN_SECONDS', 60 );
class WP_Error {
    public function __construct( private string $code, private string $text ) {}
    public function get_error_code(): string { return $this->code; }
}
function __( string $message, string $domain = '' ): string { return $message; }
function is_wp_error( mixed $thing ): bool { return $thing instanceof WP_Error; }
function get_option( string $key, mixed $default = null ): mixed { return $GLOBALS['current_schema_version'] ?? $default; }
function get_transient( string $key ): mixed { return $GLOBALS['test_schema_transient'] ?? false; }
function set_transient( string $key, mixed $value, int $expiration ): bool {
    $GLOBALS['test_schema_transient'] = $value;
    return true;
}
function home_url( string $route = '/' ): string { return 'https://example.test/'; }
class SCHA_Database {
    public const SCHEMA_VERSION = '2.1.0';
    public static int $installs = 0;
    public static function tables_exist(): bool { return (bool) ( $GLOBALS['test_schema_valid'] ?? true ); }
    public static function schema_invariants_hold(): bool { return (bool) ( $GLOBALS['test_schema_valid'] ?? true ); }
    public static function install(): true {
        ++self::$installs;
        $GLOBALS['current_schema_version'] = self::SCHEMA_VERSION;
        return true;
    }
}
class SCHA_Observability {
    public static function trace_id(): string { return 'schema-test'; }
    public static function safe_error( string $text, string $trace ): void {}
}
class FakeDb {
    public int $lock_calls = 0;
    public function prepare( string $sql, mixed ...$args ): string { return $sql; }
    public function get_var( string $sql ): int {
        ++$this->lock_calls;
        return 1;
    }
}
$wpdb = new FakeDb();
require dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-database-migrator.php';
$GLOBALS['current_schema_version'] = '2.2.0';
$future = SCHA_Database_Migrator::maybe_upgrade();
if ( ! is_wp_error( $future ) || $future->get_error_code() !== 'scha_schema_newer_than_code' || $wpdb->lock_calls !== 0 ) {
    fwrite( STDERR, "FAIL: rollback onto newer schema not stopped\n" ); exit( 1 );
}
$GLOBALS['current_schema_version'] = '2.1.0';
$GLOBALS['test_schema_valid'] = false;
$GLOBALS['test_schema_transient'] = false;
$broken = SCHA_Database_Migrator::maybe_upgrade();
if ( ! is_wp_error( $broken ) || $broken->get_error_code() !== 'scha_schema_incomplete' ) {
    fwrite( STDERR, "FAIL: incomplete same-version schema was accepted\n" ); exit( 1 );
}
$GLOBALS['test_schema_valid'] = true;

$current = SCHA_Database_Migrator::maybe_upgrade();
if ( true !== $current || SCHA_Database::$installs !== 0 ) {
    fwrite( STDERR, "FAIL: matching schema was unnecessarily migrated\n" ); exit( 1 );
}
$GLOBALS['current_schema_version'] = '2.0.0';
$old = SCHA_Database_Migrator::maybe_upgrade();
if ( true !== $old || SCHA_Database::$installs !== 1 || $wpdb->lock_calls !== 2 || $GLOBALS['current_schema_version'] !== '2.1.0' ) {
    fwrite( STDERR, "FAIL: old schema migration/lock release incorrect\n" ); exit( 1 );
}
echo "PASS: newer schema denied, current accepted, older upgraded under lock\n";
