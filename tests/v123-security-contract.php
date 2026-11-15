<?php
/**
 * Defensive File 16 regression contracts for V12.3.
 * Source-level checks complement, but do not replace, live provider/DB tests.
 */
require __DIR__ . '/bootstrap.php';
$root = dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/';
$read = static fn( string $path ): string => file_get_contents( $root . $path ) ?: '';
$cases = array(
    'Teacher public-corpus ACL' => array( 'includes/class-scha-ai-teacher.php', "'access_class' => array( 'public' )" ),
    'Teacher non-human class' => array( 'includes/class-scha-ai-teacher.php', "'is_human'             => false" ),
    'Claude source privacy' => array( 'includes/providers/class-scha-provider-claude.php', 'scha_sensitive_source_egress_denied' ),
    'JSON provider source privacy' => array( 'includes/providers/class-scha-provider-http-json.php', 'scha_sensitive_source_egress_denied' ),
    'Profile portfolio privacy' => array( 'includes/class-scha-integration.php', "SCHA_Privacy::inspect_and_redact( \$content )" ),
    'Feedback privacy' => array( 'includes/class-scha-feedback.php', "SCHA_Privacy::inspect_and_redact( \$comment )" ),
    'Immutable cited source version' => array( 'includes/class-scha-corpus.php', 'scha_source_version_conflict' ),
    'Corpus transaction and rollback' => array( 'includes/class-scha-corpus.php', "\$wpdb->query( 'ROLLBACK' )" ),
);
$failures = array();
$json_provider = $read( 'includes/providers/class-scha-provider-http-json.php' );
if ( ! str_contains( $json_provider, 'scha_http_json_pricing_unavailable' )
    || ! str_contains( $json_provider, 'scha_http_json_pricing_v1' )
    || ! str_contains( $json_provider, 'monthly_cost_budget_micros' ) ) {
    $failures[] = 'Cost-capped HTTP JSON pricing guard missing';
}
$claude_provider = $read( 'includes/providers/class-scha-provider-claude.php' );
if ( ! str_contains( $claude_provider, 'scha_claude_pricing_unavailable' )
    || ! str_contains( $claude_provider, 'scha_claude_pricing_v1' ) ) {
    $failures[] = 'Cost-capped Claude pricing guard missing';
}

$styles = $read( 'assets/css/public.css' );
if ( ! str_contains( $styles, '--sabri-visual-primary' ) || str_contains( $styles, '--sabri-brand-primary' ) ) {
    $failures[] = 'File 25 canonical visual token contract';
}

foreach ( $cases as $label => $case ) {
    if ( ! str_contains( $read( $case[0] ), $case[1] ) ) $failures[] = $label;
}
$personal = SCHA_Privacy::inspect_and_redact( 'Email patient@example.com; password=secret; CNIC 35202-1234567-1' );
if ( ! $personal['had_sensitive_data'] || str_contains( $personal['redacted'], 'patient@example.com' ) || str_contains( $personal['redacted'], 'secret' ) ) {
    $failures[] = 'PII and secret redaction';
}
if ( $failures ) {
    fwrite( STDERR, "FAIL V12.3 security contracts:\n" . implode( "\n", $failures ) . "\n" );
    exit( 1 );
}
echo "PASS: " . ( count( $cases ) + 1 ) . " V12.3 source-privacy controls\n";
