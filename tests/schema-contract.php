<?php
/**
 * Source/DDL schema contract; no live database access or migration changes.
 * Every invariant must name a column physically created by its DDL.
 */
$path = dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-database.php';
$source = file_get_contents( $path ) ?: '';
$schema_start = strpos( $source, 'public static function schema_invariants_hold' );
if ( false === $schema_start ) {
    fwrite( STDERR, "FAIL: schema invariant function missing\n" );
    exit( 1 );
}
$invariants = substr( $source, $schema_start );
preg_match_all( "/'([a-z_]+)'\s*=>\s*array\(([^)]*)\)/s", $invariants, $required, PREG_SET_ORDER );
$failures = array();
if ( count( $required ) < 7 ) $failures[] = 'schema invariant coverage unexpectedly decreased';
$total = 0;
foreach ( $required as $entry ) {
    $table = $entry[1];
    $marker = "self::table( '$table' )";
    $ddl_start = strpos( $source, $marker );
    $ddl_end = false !== $ddl_start ? strpos( $source, '$charset;', $ddl_start ) : false;
    if ( false === $ddl_start || false === $ddl_end || $ddl_end < $ddl_start ) {
        $failures[] = "$table DDL missing";
        continue;
    }
    $ddl = substr( $source, $ddl_start, $ddl_end - $ddl_start );
    preg_match_all( "/'([a-z_]+)'/", $entry[2], $columns );
    foreach ( $columns[1] as $column ) {
        ++$total;
        if ( ! preg_match( '/^\s*' . preg_quote( $column, '/' ) . '\s+[a-z]+\b/im', $ddl ) ) {
            $failures[] = "$table.$column required but not created";
        }
    }
}
if ( $failures ) {
    fwrite( STDERR, "FAIL schema DDL/required columns:\n" . implode( "\n", $failures ) . "\n" );
    exit( 1 );
}
echo "PASS: " . count( $required ) . " table invariants and $total required columns match source DDL\n";
