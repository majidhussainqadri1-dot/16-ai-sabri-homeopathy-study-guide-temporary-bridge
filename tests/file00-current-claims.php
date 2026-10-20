<?php
/** Behavioral integration test for real File 00 smc_membership_assertions contract. */
require __DIR__ . '/bootstrap.php';
$GLOBALS['file00_test_raw'] = array();
$GLOBALS['file00_age_hold'] = false;
function get_current_user_id(): int { return 27; }
function is_wp_error( mixed $value ): bool { return $value instanceof WP_Error; }
final class WP_Error {
    public function __construct( public string $code, public string $message = '', public array $data = array() ) {}
}
function apply_filters( string $hook, mixed $value, mixed ...$args ): mixed {
    if ( 'smc_assertions_v1' === $hook && ! empty( $GLOBALS['file00_age_hold'] ) ) {
        $value['eligible'] = false;
        $value['risk_hold'] = true;
    }
    return $value;
}
function smc_membership_assertions( int $user_id ): array { return $GLOBALS['file00_test_raw']; }
function smc_is_founder( int $user_id ): bool { return false; }
require_once dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-account-context.php';
$raw = array(
    'user_id' => 27, 'contract_version' => '2.1.0', 'status' => 'approved',
    'approved' => true, 'eligible' => true, 'suspended' => false,
    'age_context' => array( 'known' => true, 'minor' => false ),
    'guardian_verified' => false,
    'approved_membership_types' => array( 'doctor' ),
    'professional_verified' => true,
);
$failures = array();
$check = static function ( bool $passed, string $label ) use ( &$failures ): void { if ( ! $passed ) $failures[] = $label; };
$GLOBALS['file00_test_raw'] = $raw;
$normal = SCHA_Account_Context::current();
$check( $normal['approved'] && $normal['verified_doctor'] && ! $normal['founder'], 'Valid File00 adult verified doctor wrongly denied' );
$check( '2.1.0' === $normal['claims_version'], 'File00 contract version lost' );
$GLOBALS['file00_age_hold'] = true;
$check( ! SCHA_Account_Context::current()['approved'], 'File00 smc_assertions_v1 trust/age override ignored' );
$GLOBALS['file00_age_hold'] = false;
$GLOBALS['file00_test_raw'] = array_merge( $raw, array( 'suspended' => true ) );
$check( ! SCHA_Account_Context::current()['approved'], 'Suspended File00 member granted AI' );
$GLOBALS['file00_test_raw'] = array_merge( $raw, array( 'approved' => false ) );
$check( ! SCHA_Account_Context::current()['approved'], 'Unapproved File00 member granted AI' );
$GLOBALS['file00_test_raw'] = array_merge( $raw, array( 'eligible' => false ) );
$check( ! SCHA_Account_Context::current()['approved'], 'Ineligible File00 member granted AI' );
$GLOBALS['file00_test_raw'] = array_merge( $raw, array( 'age_context' => array( 'known' => false ) ) );
$check( ! SCHA_Account_Context::current()['approved'], 'Unknown age treated as adult' );
$GLOBALS['file00_test_raw'] = array_merge( $raw, array( 'age_context' => array( 'known' => true, 'minor' => true ) ) );
$check( ! SCHA_Account_Context::current()['approved'], 'Minor without guardian granted AI' );
$GLOBALS['file00_test_raw'] = array_merge( $raw, array( 'age_context' => array( 'known' => true, 'minor' => true ), 'guardian_verified' => true ) );
$check( SCHA_Account_Context::current()['approved'], 'Verified guardian still incorrectly denied' );
$GLOBALS['file00_test_raw'] = array_merge( $raw, array( 'user_id' => 100 ) );
$check( ! SCHA_Account_Context::current()['approved'], 'Foreign user File00 assertion granted AI' );
$GLOBALS['file00_test_raw'] = array_merge( $raw, array( 'contract_version' => '' ) );
$check( ! SCHA_Account_Context::current()['approved'], 'Unversioned File00 claim granted AI' );
if ( $failures ) { fwrite( STDERR, "FAIL FILE00 INTEGRATION\n- ".implode( "\n- ",$failures )."\n" ); exit( 1 ); }
echo "PASS: 10 File00 owner assertions/role/age/guardian/suspension behavioral cases\n";
