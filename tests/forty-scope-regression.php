<?php
/* Forty named source-to-plan regression checkpoints; not a claim of 40 live/staging runs. */
$root = dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/';
$checks = array(
    array( 'Release entry', '16-sabri-classical-homeopathy-ai.php', 'Version: 2.2.2' ),
    array( 'FR trace', 'docs/TRACEABILITY.md', 'F16-FR-019' ),
    array( 'NFR trace', 'docs/TRACEABILITY.md', 'F16-NFR-10' ),
    array( 'Single free tier', 'includes/class-scha-entitlements.php', 'single-free-tier' ),
    array( 'Donor neutrality', 'includes/class-scha-four-plan-compliance.php', 'donor_advantage' ),
    array( 'File 00 live claims', 'includes/class-scha-account-context.php', 'sabri_membership_claims_v2' ),
    array( 'Fail closed on unavailable membership', 'includes/class-scha-account-context.php', 'membership-provider-unavailable' ),
    array( 'Guest HMAC binding', 'includes/class-scha-guest-auth.php', 'hash_hmac' ),
    array( 'Guest ownership denial', 'includes/class-scha-session-service.php', '0 === $owner_id || $owner_id !== $current' ),
    array( 'Sensitive REST no-store', 'includes/class-scha-rest-controller.php', 'private, no-store, no-cache' ),
    array( 'Encrypted messages', 'includes/class-scha-session-service.php', 'SCHA_Crypto::encrypt' ),
    array( 'Legal hold', 'includes/class-scha-session-service.php', 'legal_hold' ),
    array( 'Prompt boundary', 'includes/class-scha-prompt-policy.php', 'prescription' ),
    array( 'Output boundary', 'includes/class-scha-output-policy.php', 'clinical_directive' ),
    array( 'Citations', 'includes/class-scha-citation-validator.php', 'citation_coverage' ),
    array( 'Source canonical owner', 'includes/class-scha-corpus.php', 'owner_allowed' ),
    array( 'Source rights evidence', 'includes/class-scha-corpus.php', 'rights_evidence_id' ),
    array( 'Corpus privacy screening', 'includes/class-scha-corpus.php', 'inspect_and_redact' ),
    array( 'Retrieval item ACL', 'includes/class-scha-retrieval.php', 'i.access_class' ),
    array( 'Retrieval chunk ACL', 'includes/class-scha-retrieval.php', 'c.access_class' ),
    array( 'Claude adapter', 'includes/providers/class-scha-provider-claude.php', 'api.anthropic.com/v1/messages' ),
    array( 'Provider DNS/SSRF gate', 'includes/providers/class-scha-provider-http-json.php', 'DNS_AAAA' ),
    array( 'Provider registry', 'includes/class-scha-provider-registry.php', 'by_key' ),
    array( 'Rate limit lock', 'includes/class-scha-rate-limiter.php', 'GET_LOCK' ),
    array( 'Usage cost budget', 'includes/class-scha-usage-ledger.php', 'budget_check' ),
    array( 'Four daily teacher slots', 'includes/class-scha-ai-teacher.php', 'teacher_slots' ),
    array( 'Teacher public source restriction', 'includes/class-scha-ai-teacher.php', '\'access_class\' => array( \'public\' )' ),
    array( 'Minimum thirty-day review', 'includes/class-scha-ai-teacher.php', 'max( 30' ),
    array( 'Stale-worker review gate', 'includes/class-scha-ai-teacher.php', 'WHEN reviewed_by > 0 THEN \'approved\' ELSE \'review_required\' END' ),
    array( 'File 21 publishing owner', 'includes/class-scha-ai-teacher.php', 'sabri_file21_publish_ai_teacher_post_v1' ),
    array( 'File 22 draft bridge', 'includes/class-scha-ai-teacher.php', 'sabri_file22_ai_teacher_draft_ready_v1' ),
    array( 'File 20 shell controls', 'includes/class-scha-public.php', 'sabri_file20_context_controls_markup_v1' ),
    array( 'File 19 outbox separation', 'includes/class-scha-outbox.php', 'publish' ),
    array( 'File 26 ranking explanation', 'includes/class-scha-integration.php', 'why_this_result' ),
    array( 'File 03 grounded context', 'includes/class-scha-integration.php', 'public_professional_work' ),
    array( 'File 03 exact URL origin', 'includes/class-scha-integration.php', '$port === $home_port' ),
    array( 'RTL presentation', 'assets/css/public.css', 'direction' ),
    array( 'Private route noindex', 'includes/class-scha-router.php', 'X-Robots-Tag' ),
    array( 'Schema migration serialization', 'includes/class-scha-database-migrator.php', 'GET_LOCK' ),
    array( 'Release evidence separation', 'docs/RELEASE-CHECKLIST.md', 'staging' ),
);
$failures = array();
foreach ( $checks as $index => $check ) {
    [ $label, $file, $needle ] = $check;
    $source = @file_get_contents( $root . $file );
    if ( ! is_string( $source ) || ! str_contains( $source, $needle ) )
        $failures[] = sprintf( 'R%02d %s (%s)', $index + 1, $label, $file );
}
if ( 40 !== count( $checks ) ) $failures[] = 'Expected exactly 40 scope checkpoints';
if ( $failures ) { fwrite( STDERR, "FAIL FORTY SCOPE\n- " . implode( "\n- ", $failures ) . "\n" ); exit( 1 ); }
echo "PASS: 40 independent named cross-file static scope gates\n";
