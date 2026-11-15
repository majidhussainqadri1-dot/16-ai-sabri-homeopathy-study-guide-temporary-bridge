<?php
/**
 * Corpus approval failure simulation: source cannot be marked approved if
 * chunk INSERT, durable outbox event, or COMMIT is unsuccessful.
 * No WordPress/Hostinger DB is modified by this isolated contract test.
 */
define( 'ABSPATH', __DIR__ . '/' );
define( 'SCHA_TEXT_DOMAIN', 'sabri-classical-homeopathy-ai' );
define( 'ARRAY_A', 'ARRAY_A' );
class WP_Error {
    private string $code;
    public function __construct( string $code, string $message ) { $this->code = $code; }
    public function get_error_code(): string { return $this->code; }
}
function __( string $message, string $domain = '' ): string { return $message; }
function sanitize_key( string $input ): string { return strtolower( $input ); }
function apply_filters( string $name, mixed $value, mixed ...$args ): mixed { return $value; }
function current_time( string $format, bool $utc = false ): string { return '2026-11-15 15:00:00'; }
function get_current_user_id(): int { return 5; }
function is_wp_error( mixed $value ): bool { return $value instanceof WP_Error; }
function wp_strip_all_tags( string $text ): string { return strip_tags( $text ); }
class SCHA_Database {
    public static function table( string $name ): string { return 'wp_scha_' . $name; }
}
class SCHA_Privacy {
    public static function inspect_and_redact( string $text ): array { return array( 'had_sensitive_data' => false, 'redacted' => $text ); }
}
class SCHA_Usage_Ledger {
    public static function estimate_tokens( string $text ): int { return max( 1, (int) ceil( strlen( $text ) / 4 ) ); }
}
class SCHA_Outbox {
    public static bool $allow = true;
    public static function publish( string $event, string $type, string $id, array $payload, string $key ): bool { return self::$allow; }
}
class SCHA_Observability {
    public static function trace_id(): string { return 'test'; }
    public static function safe_error( mixed $err, string $trace ): void { }
}
class FailureDb {
    public string $failure = '';
    public array $calls = array();
    public function prepare( string $sql, mixed ...$args ): string { return $sql; }
    public function get_row( string $sql, string $format ): array {
        return array(
            'id' => 1, 'owner_file' => '05', 'public_id' => 'source-1',
            'source_content' => 'This is a cited historical text about repertory indexes and teaching methods.',
            'license_name' => 'CC BY', 'approved_use' => 'education',
            'rights_evidence_id' => 'rights-1',
            'rights_reviewed_at' => '2026-11-14 00:00:00',
            'item_version' => '1', 'access_class' => 'public'
        );
    }
    public function query( string $sql ): int|false {
        $this->calls[] = $sql;
        if ( 'COMMIT' === $sql && 'commit' === $this->failure ) return false;
        if ( 'START TRANSACTION' === $sql && 'begin' === $this->failure ) return false;
        return 1;
    }
    public function delete( string $table, array $values, array $formats ): int|false {
        $this->calls[] = 'delete';
        return 'delete' === $this->failure ? false : 1;
    }
    public function insert( string $table, array $values, array $formats ): int|false {
        $this->calls[] = 'insert';
        return 'insert' === $this->failure ? false : 1;
    }
    public function update( string $table, array $values, array $where ): int|false {
        $this->calls[] = 'update';
        return 'update' === $this->failure ? false : 1;
    }
}
require dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-corpus.php';
$errors = array();
foreach ( array( 'begin', 'delete', 'insert', 'update', 'outbox', 'commit' ) as $failure ) {
    $wpdb = new FailureDb();
    $wpdb->failure = $failure;
    SCHA_Outbox::$allow = 'outbox' !== $failure;
    $result = SCHA_Corpus::approve_and_index( 1 );
    if ( ! is_wp_error( $result ) ) $errors[] = "$failure failure was accepted";
    if ( 'begin' !== $failure && ! in_array( 'ROLLBACK', $wpdb->calls, true ) ) $errors[] = "$failure did not roll back";
    if ( 'begin' === $failure && in_array( 'COMMIT', $wpdb->calls, true ) ) $errors[] = 'commit called after begin failure';
}
$wpdb = new FailureDb();
SCHA_Outbox::$allow = true;
$result = SCHA_Corpus::approve_and_index( 1 );
if ( true !== $result || ! in_array( 'COMMIT', $wpdb->calls, true ) ) $errors[] = 'normal source approval rejected';
if ( $errors ) {
    fwrite( STDERR, "FAIL corpus transaction: " . implode( '; ', $errors ) . "\n" );
    exit( 1 );
}
echo "PASS: 6 induced DB/event failures rollback; success commits\n";
