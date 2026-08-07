<?php
$root = dirname( __DIR__ );
$plugin = $root . '/16-sabri-classical-homeopathy-ai';
$failures = array();
$assert = static function ( bool $condition, string $label ) use ( &$failures ): void { if ( ! $condition ) $failures[] = $label; };
$read = static fn( string $relative ): string => file_get_contents( $plugin . '/' . $relative ) ?: '';
$all = '';
$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $plugin, FilesystemIterator::SKIP_DOTS ) );
foreach ( $iterator as $file ) if ( $file->isFile() && in_array( strtolower( $file->getExtension() ), array( 'php', 'js', 'css' ), true ) ) $all .= "\n" . file_get_contents( $file->getPathname() );

$main = $read( '16-sabri-classical-homeopathy-ai.php' );
$assert( str_contains( $main, 'Version: 2.2.0' ), 'release version not 2.2.0' );
$assert( str_contains( $main, 'Requires at least: 7.0' ) && str_contains( $main, 'Requires PHP: 8.3' ), 'platform baseline missing' );
$assert( str_contains( $read( 'includes/class-scha-entitlements.php' ), 'single-free-tier' ), 'single free tier missing' );
$assert( ! preg_match( '/PKR\s*400|separate AI add-on|ai-addon/iu', $all ), 'superseded paid access language remains in executable/UI code' );
$assert( str_contains( $read( 'includes/class-scha-four-plan-compliance.php' ), 'donor_advantage' ), 'donor neutrality manifest missing' );
$account = $read( 'includes/class-scha-account-context.php' );
$assert( str_contains( $account, 'sabri_membership_claims_v2' ) && str_contains( $account, 'membership-provider-unavailable' ), 'live fail-closed File 00 claims missing' );
$assert( ! str_contains( $account, "current_user_can( SCHA_Capabilities::MANAGE_AI )" ) && ! str_contains( $account, "current_user_can( 'manage_options' )" ), 'local admin capability still substitutes for Founder identity' );
$assert( str_contains( $read( 'includes/class-scha-session-service.php' ), 'SCHA_Crypto::encrypt' ) && str_contains( $read( 'includes/class-scha-session-service.php' ), 'legal_hold' ), 'encrypted sessions/legal hold missing' );
$assert( str_contains( $read( 'includes/class-scha-ai-service.php' ), 'SCHA_Output_Policy::validate' ) && str_contains( $read( 'includes/class-scha-ai-service.php' ), 'SCHA_Citation_Validator::validate' ), 'output/citation gates missing' );
$assert( str_contains( $read( 'includes/class-scha-guest-auth.php' ), 'X-SCHA' ) || str_contains( $read( 'includes/class-scha-rest-controller.php' ), 'X-SCHA-Guest-Token' ), 'guest request token missing' );
$teacher = $read( 'includes/class-scha-ai-teacher.php' );
foreach ( array( 'four governed daily', 'teacher_slots', 'teacher_human_review_days', 'sabri_file21_publish_ai_teacher_post_v1', 'sabri_file22_ai_teacher_draft_ready_v1', 'AITeacherReviewRequired', 'is_verified_doctor' ) as $needle ) {
    $assert( stripos( $teacher . $read( 'includes/class-scha-admin.php' ), $needle ) !== false, 'AI Teacher contract missing: ' . $needle );
}
$assert( str_contains( $read( 'includes/providers/class-scha-provider-claude.php' ), 'api.anthropic.com/v1/messages' ), 'Claude adapter missing' );
$assert( str_contains( $read( 'includes/class-scha-governance-gates.php' ), 'scha_sharia_content_review_v1' ) && str_contains( $teacher, 'rights_gate' ), 'Sharīʿah/rights publication gates missing' );
$corpus = $read( 'includes/class-scha-corpus.php' );
$assert( str_contains( $corpus, 'approved_use' ) && str_contains( $corpus, 'rights_evidence_id' ) && str_contains( $corpus, 'scha_allowed_corpus_owner_files_v1' ), 'corpus approved-use/rights/canonical-owner gates missing' );
$assert( str_contains( $corpus, 'scha_corpus_sensitive_content_authorized_v1' ) && str_contains( $corpus, 'SCHA_Privacy::inspect_and_redact' ), 'private-data corpus exclusion missing' );
$assert( str_contains( $teacher, "status='publishing'" ) && str_contains( $teacher, 'idempotency_key' ) && str_contains( $teacher, 'process_publication_queue' ), 'atomic/idempotent AI Teacher publication retry missing' );
$assert( ! str_contains( $read( 'includes/providers/class-scha-provider-claude.php' ), 'claude-sonnet-4-5' ) && str_contains( $read( 'includes/providers/class-scha-provider-claude.php' ), 'scha_claude_model_required' ), 'stale hard-coded Claude model remains' );
$assert( str_contains( $read( 'includes/class-scha-privacy-tools.php' ), 'remaining_deletable' ) && str_contains( $read( 'includes/class-scha-retention.php' ), "'owner_id' => 0" ), 'legal-hold eraser completion/retention pseudonymization missing' );
$assert( str_contains( $read( 'includes/class-scha-observability.php' ), 'sanitize_payload' ) && str_contains( $read( 'includes/class-scha-outbox.php' ), 'sanitize_payload' ), 'nested audit/outbox privacy scrubbing missing' );
$assert( str_contains( $read( 'includes/providers/class-scha-provider-http-json.php' ), 'DNS_AAAA' ) && str_contains( $read( 'includes/providers/class-scha-provider-http-json.php' ), '443 !== $port' ), 'provider SSRF/DNS/port hardening missing' );
$assert( str_contains( $read( 'includes/class-scha-usage-ledger.php' ), 'session_id > 0' ), 'guest and institutional AI Teacher budgets are not separated' );
$assert( str_contains( $read( 'includes/class-scha-public.php' ), 'sabri_file20_context_controls_markup_v1' ), 'File 20 Back/Home owner contract missing' );
$assert( str_contains( $read( 'includes/class-scha-router.php' ), 'X-Robots-Tag' ) && str_contains( $read( 'includes/class-scha-router.php' ), 'no-store' ), 'private route search/cache protections missing' );
$assert( str_contains( $read( 'includes/class-scha-integration.php' ), 'why_this_result' ) && str_contains( $read( 'includes/class-scha-integration.php' ), 'donor_bias' ), 'Top-20 discovery explanation/no-bias metadata missing' );
$assert( str_contains( $read( 'templates/accessibility.php' ), 'low-bandwidth' ) && str_contains( $read( 'assets/css/public.css' ), 'prefers-reduced-data' ), 'accessibility/low-bandwidth evidence missing' );
$trace = $read( 'docs/TRACEABILITY.md' );
for ( $i = 1; $i <= 19; $i++ ) $assert( str_contains( $trace, sprintf( 'F16-FR-%03d', $i ) ), 'missing FR trace ' . $i );
for ( $i = 1; $i <= 10; $i++ ) $assert( str_contains( $trace, sprintf( 'F16-NFR-%02d', $i ) ), 'missing NFR trace ' . $i );

if ( $failures ) { fwrite( STDERR, "FAIL FOUR-PLAN\n- " . implode( "\n- ", $failures ) . "\n" ); exit( 1 ); }
echo "PASS: File 16 four-plan contracts and negative regressions\n";
