<?php

defined( 'ABSPATH' ) || exit;

/**
 * Resolves current File 00 claims for every protected action.
 * Cached identity labels never override live suspension, guardian, risk or consent state.
 */
final class SCHA_Account_Context {
    public static function current( ?int $user_id = null ): array {
        $user_id = null === $user_id ? get_current_user_id() : max( 0, $user_id );
        if ( 0 === $user_id ) {
            return array(
                'user_id'          => 0,
                'authenticated'    => false,
                'approved'         => (bool) SCHA_Settings::get( 'guest_demo', false ),
                'blocked'          => false,
                'verified_doctor'  => false,
                'founder'          => false,
                'minor'            => false,
                'guardian_ok'      => true,
                'risk_hold'        => false,
                'consent_ok'       => true,
                'claims_version'   => 'guest-v1',
                'reason'           => '',
            );
        }

        // File 00's current canonical source is smc_membership_assertions(), not
        // the historical sabri_membership_claims_v2 filter. Consume owner assertions
        // and their current security/age augmentations without creating identity truth.
        if ( function_exists( 'smc_membership_assertions' ) ) {
            try {
                $owner_claims = smc_membership_assertions( $user_id );
                $owner_claims = is_array( $owner_claims ) ? apply_filters( 'smc_assertions_v1', $owner_claims, $user_id ) : array();
                $claims = self::normalize_file00_claims( $owner_claims, $user_id );
            } catch ( Throwable $error ) {
                $claims = array(); // Missing/broken owner must fail closed.
            }
        } else {
            // Historical integrations are accepted only when the canonical provider
            // function is absent; no local user role or administrator bypass.
            $claims = apply_filters( 'sabri_membership_claims_v2', null, $user_id );
            if ( ! is_array( $claims ) ) {
                $claims = apply_filters( 'sabri_membership_claims', array(), $user_id );
            }
        }
        $claims = is_array( $claims ) ? $claims : array();
        $claims_available = ! empty( $claims );

        $status = sanitize_key( (string) ( $claims['account_status'] ?? $claims['status'] ?? 'unknown' ) );
        $blocked_states = array( 'suspended', 'revoked', 'blocked', 'security_hold', 'deleted', 'rejected', 'expired', 'erased', 'appeal_review', 'erasure_pending', 'invalid_application', 'dependency_missing' );
        $blocked = in_array( $status, $blocked_states, true )
            || ! empty( $claims['suspended'] )
            || ! empty( $claims['revoked'] )
            || ! empty( $claims['security_hold'] );

        $minor = ! empty( $claims['is_minor'] ) || ! empty( $claims['minor'] );
        $guardian_ok = ! $minor || ! empty( $claims['guardian_consent_verified'] ) || ! empty( $claims['guardian_verified'] );
        $risk_hold = ! empty( $claims['risk_hold'] ) || in_array( sanitize_key( (string) ( $claims['risk_state'] ?? '' ) ), array( 'hold', 'blocked', 'critical' ), true );
        $consent_ok = ! array_key_exists( 'ai_processing_consent', $claims ) || ! empty( $claims['ai_processing_consent'] );
        $approved_states = array( 'approved', 'active', 'verified' );
        $approved = $claims_available && ! $blocked && ! $risk_hold && $guardian_ok && $consent_ok && in_array( $status, $approved_states, true )
            && ( ! array_key_exists( 'approved', $claims ) || ! empty( $claims['approved'] ) )
            && ( ! array_key_exists( 'eligible', $claims ) || ! empty( $claims['eligible'] ) );

        // Founder identity is an authoritative File 00 claim, not a local WordPress/admin capability.
        // Administrative capability may govern this module without granting founder/internal corpus access.
        $founder = ! empty( $claims['institutional_founder'] ) || ! empty( $claims['founder'] );
        $verified_doctor = $approved && ! empty( $claims['verified_doctor'] );

        return array(
            'user_id'          => $user_id,
            'authenticated'    => true,
            'approved'         => $approved,
            'blocked'          => $blocked,
            'verified_doctor'  => $verified_doctor,
            'founder'          => $founder,
            'minor'            => $minor,
            'guardian_ok'      => $guardian_ok,
            'risk_hold'        => $risk_hold,
            'consent_ok'       => $consent_ok,
            'claims_version'   => sanitize_text_field( (string) ( $claims['claims_version'] ?? $claims['version'] ?? 'legacy-v1' ) ),
            'reason'           => ! $claims_available ? 'membership-provider-unavailable' : self::reason( $blocked, $risk_hold, $guardian_ok, $consent_ok, $status ),
            'raw'              => $claims,
        );
    }

    /**
     * Map the actual File 00 owner contract (SMC_CONTRACT_VERSION) to File 16's
     * minimal authorization projection. All decisions are derived from current
     * owner assertions; unknown age/guardian state never implies adulthood.
     */
    private static function normalize_file00_claims( mixed $raw, int $user_id ): array {
        if ( ! is_array( $raw ) || absint( $raw['user_id'] ?? 0 ) !== $user_id
            || empty( $raw['contract_version'] ) || ! array_key_exists( 'eligible', $raw )
            || ! array_key_exists( 'approved', $raw ) || ! array_key_exists( 'suspended', $raw ) ) {
            return array();
        }
        $age = is_array( $raw['age_context'] ?? null ) ? $raw['age_context'] : array();
        $institutional = ! empty( $raw['institutional_account'] );
        $age_known = ! empty( $age['known'] );
        $minor = ! $institutional && ( ! $age_known || ! empty( $age['minor'] ) );
        $guardian_ok = $institutional || ( $age_known && ( ! $minor || ! empty( $raw['guardian_verified'] ) ) );
        $approved_types = (array) ( $raw['approved_membership_types'] ?? array() );
        $founder = function_exists( 'smc_is_founder' ) && (bool) smc_is_founder( $user_id );
        return array(
            'user_id'            => $user_id,
            'account_status'     => sanitize_key( (string) ( $raw['status'] ?? 'unknown' ) ),
            'approved'           => ! empty( $raw['approved'] ),
            'eligible'           => ! empty( $raw['eligible'] ),
            'suspended'          => ! empty( $raw['suspended'] ),
            'risk_hold'          => ! empty( $raw['risk_hold'] ) || ! empty( $raw['hard_blocked'] ),
            'is_minor'           => $minor,
            'guardian_verified'  => $guardian_ok,
            'guardian_consent_verified' => $guardian_ok,
            'founder'            => $founder,
            'verified_doctor'    => in_array( 'doctor', $approved_types, true ) && ! empty( $raw['professional_verified'] ),
            'ai_processing_consent' => array_key_exists( 'ai_processing_consent', $raw ) ? ! empty( $raw['ai_processing_consent'] ) : true,
            'claims_version'     => sanitize_text_field( (string) $raw['contract_version'] ),
            'canonical_owner'    => 'file00',
        );
    }

    public static function require_approved( ?int $user_id = null ): true|WP_Error {
        $context = self::current( $user_id );
        if ( ! $context['approved'] ) {
            return new WP_Error(
                'scha_account_not_eligible',
                __( 'Your current account, guardian, consent, or security status does not permit this AI action.', SCHA_TEXT_DOMAIN ),
                array( 'status' => 403, 'reason' => $context['reason'] )
            );
        }
        return true;
    }

    private static function reason( bool $blocked, bool $risk_hold, bool $guardian_ok, bool $consent_ok, string $status ): string {
        if ( $blocked ) return 'account-blocked';
        if ( $risk_hold ) return 'risk-hold';
        if ( ! $guardian_ok ) return 'guardian-verification-required';
        if ( ! $consent_ok ) return 'ai-consent-required';
        if ( ! in_array( $status, array( 'approved', 'active', 'verified' ), true ) ) return 'account-not-approved';
        return '';
    }
}
