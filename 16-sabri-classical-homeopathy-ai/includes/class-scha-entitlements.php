<?php

defined( 'ABSPATH' ) || exit;

/**
 * Current Founder directive: one complete free tier. Donation/payment never grants AI access,
 * quota, rank, badge, speed or corpus privilege. Fair-use quotas remain transparent safeguards.
 */
final class SCHA_Entitlements {
    public static function current(): array {
        $context = SCHA_Account_Context::current();
        if ( ! $context['authenticated'] ) {
            return array(
                'active'       => $context['approved'],
                'plan'         => 'free-guest-demo',
                'role'         => 'guest',
                'access_class' => array( 'public' ),
                'quota'        => min( 5, absint( SCHA_Settings::get( 'daily_request_quota', 50 ) ) ),
                'user_id'      => 0,
                'locale'       => get_locale(),
                'claims_version' => $context['claims_version'],
            );
        }

        $user = get_userdata( $context['user_id'] );
        $access = array( 'public', 'subscriber' );
        $role = 'member';
        if ( $context['verified_doctor'] ) {
            $access[] = 'doctor';
            $role = 'verified_doctor';
        }
        if ( $context['founder'] ) {
            $access[] = 'founder';
            $access[] = 'internal';
            $role = 'founder';
        }

        $quota = absint( SCHA_Settings::get( 'daily_request_quota', 50 ) );
        $quota = absint( apply_filters( 'scha_fair_use_daily_quota', $quota, $context['user_id'], $context ) );

        return array(
            'active'         => (bool) $context['approved'],
            'plan'           => 'single-free-tier',
            'role'           => $role,
            'access_class'   => array_values( array_unique( $access ) ),
            'quota'          => max( 1, $quota ),
            'user_id'        => $context['user_id'],
            'locale'         => $user && $user->locale ? $user->locale : get_locale(),
            'claims_version' => $context['claims_version'],
            'donor_neutral'  => true,
        );
    }

    public static function require_active(): true|WP_Error {
        $approved = SCHA_Account_Context::require_approved();
        if ( is_wp_error( $approved ) ) return $approved;
        $entitlement = self::current();
        if ( ! $entitlement['active'] ) {
            return new WP_Error( 'scha_free_tier_unavailable', __( 'AI access is unavailable for the current account state.', SCHA_TEXT_DOMAIN ), array( 'status' => 403 ) );
        }
        return true;
    }

    public static function can_access_class( string $access_class, ?array $entitlement = null ): bool {
        $entitlement = $entitlement ?: self::current();
        return in_array( $access_class, $entitlement['access_class'], true );
    }
}
