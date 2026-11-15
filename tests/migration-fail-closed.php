<?php
/** Fail-closed test: no AI API/scheduler/UI hooks on failed schema migration. */
define( 'ABSPATH', __DIR__ . '/' );
define( 'SCHA_TEXT_DOMAIN', 'sabri-classical-homeopathy-ai' );
define( 'SCHA_PLUGIN_FILE', '/srv/wp/plugins/16-sabri-classical-homeopathy-ai/main.php' );
class WP_Error {
    public function __construct( private string $code, private string $message ) {}
    public function get_error_message(): string { return $this->message; }
}
function is_wp_error( mixed $thing ): bool { return $thing instanceof WP_Error; }
function plugin_basename( string $path ): string { return basename( $path ); }
function load_plugin_textdomain( string $domain, bool $deprecated, string $path ): void { }
function add_action( string $name, callable|array|string $callback, int $priority = 10, int $accepted = 1 ): void {
    $GLOBALS['test_hooks'][] = $name;
}
class SCHA_Database_Migrator {
    public static int $calls = 0;
    public static function maybe_upgrade(): true|WP_Error {
        ++self::$calls;
        return new WP_Error( 'schema_migration_failed', 'Simulated incomplete DB migration' );
    }
}
class SCHA_Observability {
    public static function trace_id(): string { return 'test-trace'; }
    public static function safe_error( string $error, string $trace ): void { $GLOBALS['migration_logged'] = true; }
}
$GLOBALS['test_hooks'] = array();
require dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-plugin.php';
SCHA_Plugin::instance()->boot();
$expected = array( 'admin_notices' );
if ( $GLOBALS['test_hooks'] !== $expected || SCHA_Database_Migrator::$calls !== 1 || empty( $GLOBALS['migration_logged'] ) ) {
    fwrite( STDERR, "FAIL: plugin registered unsafe hooks after migration failure\n" );
    exit( 1 );
}
echo "PASS: no AI routes, cron, publication or REST handlers when schema fails\n";
