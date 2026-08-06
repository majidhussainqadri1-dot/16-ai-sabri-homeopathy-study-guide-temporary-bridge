<?php

$root = dirname( __DIR__ );
$plugin = $root . '/16-sabri-classical-homeopathy-ai';
$failures = array();

$required = array(
    '16-sabri-classical-homeopathy-ai.php',
    'uninstall.php',
    'includes/class-scha-ai-service.php',
    'includes/class-scha-entitlements.php',
    'includes/class-scha-session-service.php',
    'includes/class-scha-corpus.php',
    'includes/class-scha-retrieval.php',
    'includes/class-scha-citation-validator.php',
    'includes/class-scha-prompt-policy.php',
    'includes/class-scha-privacy-tools.php',
    'includes/providers/class-scha-provider-bridge.php',
    'includes/providers/class-scha-provider-http-json.php',
    'includes/providers/class-scha-provider-local.php',
    'docs/TRACEABILITY.md',
    'docs/RELEASE-CHECKLIST.md',
);
foreach ( $required as $file ) {
    if ( ! is_file( $plugin . '/' . $file ) ) $failures[] = "missing $file";
}

$all = '';
foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $plugin, FilesystemIterator::SKIP_DOTS ) ) as $file ) {
    if ( $file->isFile() && in_array( $file->getExtension(), array( 'php', 'js', 'md', 'txt' ), true ) ) {
        $all .= "\n" . file_get_contents( $file->getPathname() );
    }
}
foreach ( array( 'TODO', 'FIXME', 'BEGIN PRIVATE KEY', 'AKIA', 'sk-proj-' ) as $forbidden ) {
    if ( str_contains( $all, $forbidden ) ) $failures[] = "forbidden marker/string: $forbidden";
}

$database = file_get_contents( $plugin . '/includes/class-scha-database.php' );
$session  = file_get_contents( $plugin . '/includes/class-scha-session-service.php' );
$admin    = file_get_contents( $plugin . '/includes/class-scha-admin.php' );
$service  = file_get_contents( $plugin . '/includes/class-scha-ai-service.php' );
check_contract( str_contains( $database, 'guest_token_hash' ), 'guest token hash schema missing', $failures );
check_contract( str_contains( $session, 'hash_equals' ) && str_contains( $session, 'httponly' ), 'guest ownership binding missing', $failures );
check_contract( str_contains( $admin, "current_user_can( 'edit_users' )" ), 'entitlement administration restriction missing', $failures );
check_contract( str_contains( $service, 'SCHA_Citation_Validator::validate' ), 'citation gate missing', $failures );
check_contract( str_contains( $service, 'SCHA_Usage_Ledger::budget_check' ), 'cost budget gate missing', $failures );

if ( $failures ) {
    fwrite( STDERR, "CONTRACT FAILURES:\n- " . implode( "\n- ", $failures ) . "\n" );
    exit( 1 );
}
echo "PASS: File 16 structure and safety contracts\n";

function check_contract( bool $condition, string $message, array &$failures ): void {
    if ( ! $condition ) $failures[] = $message;
}
