<?php
/** Offline Claude pricing and budget-preflight simulation. */
require __DIR__ . '/bootstrap.php';
define( 'SCHA_ANTHROPIC_API_KEY', 'fixture-only-provider-key' );
class WP_Error {
    public function __construct( public string $code, public string $message ) {}
    public function get_error_code(): string { return $this->code; }
}
function is_wp_error( mixed $value ): bool { return $value instanceof WP_Error; }
function apply_filters( string $name, mixed $value, mixed ...$args ): mixed {
    if ( 'scha_claude_pricing_v1' === $name && ! empty( $GLOBALS['test_price_ready'] ) ) {
        return array( 'input_micros_per_million' => 2000000, 'output_micros_per_million' => 6000000 );
    }
    return $value;
}
function wp_safe_remote_post( string $url, array $args ): array {
    $GLOBALS['test_transport_calls'] = (int) ( $GLOBALS['test_transport_calls'] ?? 0 ) + 1;
    if ( 'https://api.anthropic.com/v1/messages' !== $url ) {
        throw new RuntimeException( 'Unexpected outbound endpoint in fixture' );
    }
    return array(
        'status' => 200,
        'body' => json_encode( array(
            'id' => 'fixture-response',
            'model' => 'fixture-model',
            'content' => array( array( 'type' => 'text', 'text' => 'A repertory indexes symptoms. [S1]' ) ),
            'usage' => array( 'input_tokens' => 500, 'output_tokens' => 100 ),
        ) ),
    );
}
function wp_remote_retrieve_response_code( array $response ): int { return $response['status']; }
function wp_remote_retrieve_body( array $response ): string { return $response['body']; }
$path = dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/providers/';
require $path . 'class-scha-provider-interface.php';
require $path . 'class-scha-provider-claude.php';
$provider = new SCHA_Provider_Claude();
SCHA_Settings::$overrides['monthly_cost_budget_micros'] = 2500;
$request = array(
    'model' => 'fixture-model',
    'prompt' => 'Explain repertory history from the source.',
    'sources' => array( array(
        'title' => 'Historical Source',
        'location' => 'chapter one',
        'content' => 'A repertory indexes historical symptoms and educational rubrics.'
    ) )
);
$GLOBALS['test_price_ready'] = false;
$denied = $provider->generate( $request );
if ( ! is_wp_error( $denied ) || $denied->get_error_code() !== 'scha_claude_pricing_unavailable' || ( $GLOBALS['test_transport_calls'] ?? 0 ) !== 0 ) {
    fwrite( STDERR, "FAIL: capped model without verified rate sent a request\n" ); exit( 1 );
}
$GLOBALS['test_price_ready'] = true;
$approved = $provider->generate( $request );
if ( is_wp_error( $approved ) || ( $approved['cost_micros'] ?? null ) !== 1600 || ( $GLOBALS['test_transport_calls'] ?? 0 ) !== 1 ) {
    fwrite( STDERR, "FAIL: approved model pricing did not calculate the expected usage\n" ); exit( 1 );
}
echo "PASS: price-approved request cost 1600 micro USD, missing price denied before transport\n";
