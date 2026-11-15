<?php
/**
 * Source/DDL contract, evaluated without live credentials or migrations.
 * Prevents the invalid schema invariant that broke fresh installs.
 */
$path = dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-database.php';
$source = file_get_contents( $path ) ?: '';
$ddl_start = strpos( $source, "self::table( 'outbox' )" );
$ddl_end = strpos( $source, '$charset;', $ddl_start );
$invariant_start = strpos( $source, 'public static function schema_invariants_hold' );
if ( false === $ddl_start || false === $ddl_end || false === $invariant_start ) {
    fwrite( STDERR, "FAIL: unable to locate outbox DDL or schema invariants\n" );
    exit( 1 );
}
$ddl = substr( $source, $ddl_start, $ddl_end - $ddl_start );
$invariants = substr( $source, $invariant_start );
if ( ! preg_match( "/'outbox'\s*=>\s*array\((.*?)\)/s", $invariants, $found ) ) {
    fwrite( STDERR, "FAIL: outbox invariant missing\n" );
    exit( 1 );
}
preg_match_all( "/'([a-z_]+)'/", $found[1], $columns );
$failures = array();
foreach ( $columns[1] as $column ) {
    if ( ! preg_match( '/^\s*' . preg_quote( $column, '/' ) . '\s+[a-z]+\b/im', $ddl ) ) {
        $failures[] = "outbox.$column is required but not created";
    }
}
if ( $failures ) {
    fwrite( STDERR, "FAIL: " . implode( '; ', $failures ) . "\n" );
    exit( 1 );
}
echo "PASS: outbox schema DDL/invariants agree\n";
