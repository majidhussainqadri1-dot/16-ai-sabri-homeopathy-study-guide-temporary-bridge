<?php
$root = dirname( __DIR__ );
$plugin = $root . '/16-sabri-classical-homeopathy-ai';
$required = array(
 '16-sabri-classical-homeopathy-ai.php', 'uninstall.php', 'readme.txt',
 'includes/class-scha-ai-service.php', 'includes/class-scha-ai-teacher.php', 'includes/class-scha-account-context.php',
 'includes/class-scha-crypto.php', 'includes/class-scha-output-policy.php', 'includes/class-scha-provider-data-lifecycle.php',
 'includes/providers/class-scha-provider-claude.php', 'docs/FOUR-PLAN-AUDIT-2026-08-07.md',
 'docs/FORTY-ROUND-AUDIT-2026-08-07.md', 'docs/TRACEABILITY.md', 'templates/accessibility.php'
);
$missing = array_filter( $required, static fn( string $f ): bool => ! is_file( $plugin . '/' . $f ) );
if ( $missing ) { fwrite( STDERR, 'Missing: ' . implode( ', ', $missing ) . "\n" ); exit( 1 ); }
$main = file_get_contents( $plugin . '/16-sabri-classical-homeopathy-ai.php' ) ?: '';
$readme = file_get_contents( $plugin . '/readme.txt' ) ?: '';
if ( ! str_contains( $main, 'Version: 2.2.1' ) || ! str_contains( $readme, 'Stable tag: 2.2.1' ) ) { fwrite( STDERR, "Version contract mismatch\n" ); exit( 1 ); }
echo "PASS: package structure/version\n";
