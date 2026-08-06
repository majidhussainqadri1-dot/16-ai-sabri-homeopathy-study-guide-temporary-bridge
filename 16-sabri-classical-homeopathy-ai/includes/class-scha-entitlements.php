<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Entitlements {
    public static function current(): array {
        $user_id = get_current_user_id();
        if ( 0 === $user_id ) {
            return array(
                'active'       => (bool) SCHA_Settings::get( 'guest_demo', false ),
                'plan'         => 'guest-demo',
                'role'         => 'guest',
                'access_class' => array( 'public' ),
                'quota'        => min( 5, absint( SCHA_Settings::get( 'daily_request_quota', 50 ) ) ),
            );
        }

        $user = get_userdata( $user_id );
        $claims = apply_filters( 'sabri_membership_claims', array(), $user_id );
        $meta_status = (string) get_user_meta( $user_id, 'scha_ai_entitlement_status', true );
        $meta_plan   = (string) get_user_meta( $user_id, 'scha_ai_plan', true );
        $active      = current_user_can( SCHA_Capabilities::USE_AI ) || 'active' === $meta_status || ! empty( $claims['ai_entitlement_active'] );
        $active      = (bool) apply_filters( 'scha_user_has_entitlement', $active, $user_id, $claims );

        $verified_doctor = ! empty( $claims['verified_doctor'] ) || (bool) get_user_meta( $user_id, 'sabri_verified_doctor', true );
        $is_founder      = current_user_can( SCHA_Capabilities::MANAGE_AI ) || ! empty( $claims['institutional_founder'] );
        $access          = array( 'public', 'subscriber' );
        $role            = 'subscriber';
        if ( $verified_doctor ) {
            $access[] = 'doctor';
            $role = 'verified_doctor';
        }
        if ( $is_founder ) {
            $access[] = 'founder';
            $access[] = 'internal';
            $role = 'founder';
            $active = true;
        }

        return array(
            'active'       => $active,
            'plan'         => $meta_plan ?: ( $claims['ai_plan'] ?? 'ai-addon' ),
            'role'         => $role,
            'access_class' => array_values( array_unique( $access ) ),
            'quota'        => absint( apply_filters( 'scha_daily_quota', SCHA_Settings::get( 'daily_request_quota', 50 ), $user_id, $claims ) ),
            'user_id'      => $user_id,
            'locale'       => $user ? $user->locale : get_locale(),
        );
    }

    public static function require_active(): true|WP_Error {
        $entitlement = self::current();
        if ( ! $entitlement['active'] ) {
            return new WP_Error( 'scha_entitlement_required', __( 'A separate Sabri Classical Homeopathy AI entitlement is required. The basic education membership does not automatically include AI access.', SCHA_TEXT_DOMAIN ), array( 'status' => 403 ) );
        }
        return true;
    }

    public static function can_access_class( string $access_class, ?array $entitlement = null ): bool {
        $entitlement = $entitlement ?: self::current();
        return in_array( $access_class, $entitlement['access_class'], true );
    }
}
