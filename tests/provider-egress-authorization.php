<?php
/**
 * Provider egress denial before network transport.
 * The test stub throws if a real HTTP request is attempted.
 */
require __DIR__ . '/bootstrap.php';
define( 'SCHA_ANTHROPIC_API_KEY', 'fixture-credential-not-for-network' );
function apply_filters( string $name, mixed $value, mixed ...$args ): mixed { return $value; }
class WP_Error {
    private string $code;
    public function __construct( string $code, string $message ) { $this->code = $code; }
    public function get_error_code(): string { return $this->code; }
}
function wp_safe_remote_post( string $url, array $args ): never {
    throw new RuntimeException( 'An external HTTP call was attempted despite sensitive source metadata.' );
}
$root = dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/providers/';
require $root . 'class-scha-provider-interface.php';
require $root . 'class-scha-provider-claude.php';
$provider = new SCHA_Provider_Claude();
$source = array(
    'title' => 'Contact person@example.test',
    'location' => 'chapter 3',
    'content' => 'Historical educational repertory information only.',
);
$result = $provider->generate( array(
    'model' => 'fixture-model',
    'prompt' => 'Explain the historical text.',
    'sources' => array( $source ),
) );
if ( ! $result instanceof WP_Error || 'scha_sensitive_source_egress_denied' !== $result->get_error_code() ) {
    fwrite( STDERR, "FAIL: sensitive source metadata not denied\n" );
    exit( 1 );
}
$source['title'] = 'Historical Repertory';
$source['content'] = 'Email person@example.test found inside the excerpt.';
$result = $provider->generate( array(
    'model' => 'fixture-model',
    'prompt' => 'Explain the historical text.',
    'sources' => array( $source ),
) );
if ( ! $result instanceof WP_Error || 'scha_sensitive_source_egress_denied' !== $result->get_error_code() ) {
    fwrite( STDERR, "FAIL: sensitive source content not denied\n" );
    exit( 1 );
}
SCHA_Settings::$overrides['monthly_cost_budget_micros'] = 1000;
$source['title'] = 'Historical Repertory';
$source['content'] = 'An approved historical education source without personal data.';
$pricing_guard = $provider->generate( array(
    'model' => 'fixture-model',
    'prompt' => 'Explain the historical text.',
    'sources' => array( $source ),
) );
if ( ! $pricing_guard instanceof WP_Error || 'scha_claude_pricing_unavailable' !== $pricing_guard->get_error_code() ) {
    fwrite( STDERR, "FAIL: cost-capped provider call attempted without verified pricing\n" );
    exit( 1 );
}
SCHA_Settings::$overrides = array();
echo "PASS: Claude sensitive source metadata/content and unpriced capped requests blocked before HTTP\n";
