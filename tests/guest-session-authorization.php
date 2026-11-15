<?php
/**
 * Reproduce and prevent guest-session IDOR in File 16:
 * owner_id=0 must NOT authorize all anonymous users.
 */
define( 'ABSPATH', __DIR__ . '/' );
define( 'ARRAY_A', 'ARRAY_A' );

class WP_Error {
    private string $code;
    public function __construct( string $code, string $message, array $data = array() ) { $this->code = $code; }
    public function get_error_code(): string { return $this->code; }
}
function __( string $message, string $domain = '' ): string { return $message; }
function absint( mixed $number ): int { return abs( (int) $number ); }
function get_current_user_id(): int { return (int) ( $GLOBALS['test_user_id'] ?? 0 ); }
function is_wp_error( mixed $value ): bool { return $value instanceof WP_Error; }
class SCHA_Settings {
    public static function get( string $key, mixed $fallback = null ): mixed {
        return 'guest_demo' === $key ? true : $fallback;
    }
}
class SCHA_Guest_Auth {
    public static function binding_hash( bool $create = false ): string {
        return (string) ( $GLOBALS['test_guest_binding'] ?? '' );
    }
}
class SCHA_Account_Context {
    public static function require_approved( int $user = 0 ): true { return true; }
}
class SCHA_Capabilities {
    public static function current_user_can_manage(): bool { return false; }
}
class SCHA_Database {
    public static function table( string $name ): string { return 'wp_scha_' . $name; }
}
class TestWpdb {
    public array $session = array();
    public function prepare( string $sql, mixed ...$args ): string { return $sql; }
    public function get_row( string $sql, string $format ): ?array { return $this->session ?: null; }
}
$wpdb = new TestWpdb();
require dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-session-service.php';
$wpdb->session = array(
    'public_id' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
    'owner_id' => 0,
    'guest_token_hash' => 'real-guest-hmac',
    'status' => 'active',
);
$GLOBALS['test_user_id'] = 0;
$GLOBALS['test_guest_binding'] = 'different-guest-hmac';
$unauthorized = SCHA_Session_Service::owned( $wpdb->session['public_id'] );
if ( ! is_wp_error( $unauthorized ) || 'scha_session_not_found' !== $unauthorized->get_error_code() ) {
    fwrite( STDERR, "FAIL: unrelated anonymous user accessed private guest session\n" );
    exit( 1 );
}
$GLOBALS['test_guest_binding'] = 'real-guest-hmac';
$valid_guest = SCHA_Session_Service::owned( $wpdb->session['public_id'] );
if ( is_wp_error( $valid_guest ) ) {
    fwrite( STDERR, "FAIL: correct guest binding rejected\n" );
    exit( 1 );
}
$GLOBALS['test_user_id'] = 7;
$GLOBALS['test_guest_binding'] = '';
$guest_cross_access = SCHA_Session_Service::owned( $wpdb->session['public_id'] );
if ( ! is_wp_error( $guest_cross_access ) ) {
    fwrite( STDERR, "FAIL: logged-in user accessed another guest's session\n" );
    exit( 1 );
}
$wpdb->session['owner_id'] = 7;
$own_account = SCHA_Session_Service::owned( $wpdb->session['public_id'] );
if ( is_wp_error( $own_account ) ) {
    fwrite( STDERR, "FAIL: authorized account owner rejected\n" );
    exit( 1 );
}
echo "PASS: guest-session binding, cross-account isolation and owner access\n";
