<?php
require __DIR__ . '/bootstrap.php';

define( 'ARRAY_A', 'ARRAY_A' );
function wp_parse_url( string $url ): array|false { return parse_url( $url ); }
function wp_unslash( string $text ): string { return stripslashes( $text ); }
function get_current_user_id(): int { return 0; }
function is_wp_error( mixed $value ): bool { return $value instanceof WP_Error; }
final class WP_Error {
    public function __construct( public string $code, public string $message = '', public array $data = array() ) {}
    public function get_error_code(): string { return $this->code; }
}
final class SCHA_Test_DB {
    public string $prefix = 'wp_';
    public array $row = array();
    public function prepare( string $query, mixed ...$args ): string { return $query; }
    public function get_row( string $sql, string $type ): array { return $this->row; }
}
$GLOBALS['wpdb'] = new SCHA_Test_DB();
require_once dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-database.php';
require_once dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-guest-auth.php';
require_once dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-session-service.php';
require_once dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-integration.php';

$failures = array();
$check = static function ( bool $condition, string $label ) use ( &$failures ): void {
    if ( ! $condition ) $failures[] = $label;
};
SCHA_Settings::$overrides['guest_demo'] = true;
$_COOKIE['scha_guest_session'] = 'guest-alice-token';
$GLOBALS['wpdb']->row = array(
    'id' => 17,
    'public_id' => '00000000-0000-4000-8000-000000000017',
    'owner_id' => 0,
    'status' => 'active',
    'guest_token_hash' => hash_hmac( 'sha256', 'guest-bob-token', wp_salt( 'auth' ) ),
);
$denied = SCHA_Session_Service::owned( $GLOBALS['wpdb']->row['public_id'] );
$check( $denied instanceof WP_Error && 404 === $denied->data['status'], 'Different guest opened another guest session' );
$GLOBALS['wpdb']->row['guest_token_hash'] = SCHA_Guest_Auth::binding_hash( false );
$allowed = SCHA_Session_Service::owned( $GLOBALS['wpdb']->row['public_id'] );
$check( is_array( $allowed ) && 17 === $allowed['id'], 'Matching guest session was denied' );

$origin = new ReflectionMethod( SCHA_Integration::class, 'same_origin_url' );
$check( true === $origin->invoke( null, 'https://example.test/public-source' ), 'Canonical HTTPS URL rejected' );
foreach ( array(
    'http://example.test/public-source',
    'https://example.test:8443/public-source',
    'https://user@example.test/public-source',
    'https://user:password@example.test/public-source',
    'https://another.test/public-source',
    'javascript:alert(1)'
) as $url ) {
    $check( false === $origin->invoke( null, $url ), 'Unsafe source URL accepted: ' . $url );
}
$teacher = file_get_contents( dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-ai-teacher.php' );
$check( str_contains( $teacher, "'access_class' => array( 'public' )" ), 'Public AI Teacher corpus includes nonpublic documents' );
$check( ! str_contains( $teacher, "'access_class' => array( 'public', 'subscriber', 'doctor', 'founder' )" ), 'Restricted AI Teacher corpus regression' );
$check( str_contains( $teacher, "WHEN reviewed_by > 0 THEN 'approved' ELSE 'review_required' END" ), 'Unreviewed stale publication may auto-publish' );
$check( ! str_contains( $teacher, "SET status='publish_pending',error_code='publishing-worker-timeout'" ), 'Unsafe stale-publication recovery remains' );
if ( $failures ) { fwrite( STDERR, "FAIL SECURITY BOUNDARIES\n- " . implode( "\n- ", $failures ) . "\n" ); exit( 1 ); }
echo "PASS: guest IDOR, exact URL origin, AI Teacher corpus, review recovery\n";
