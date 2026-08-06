<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Rate_Limiter {
    public static function check( array $entitlement, string $fingerprint = '' ): true|WP_Error {
        $user_id = get_current_user_id();
        // A caller-supplied browser fingerprint is never authoritative; guests are
        // bounded by a server-derived network/user-agent identity so rotating a
        // header cannot trivially evade the limiter.
        $identity = $user_id > 0 ? 'u' . $user_id : 'g' . substr( hash( 'sha256', self::client_fingerprint() ), 0, 20 );
        $minute_key = 'scha_rl_m_' . gmdate( 'YmdHi' ) . '_' . $identity;
        $day_key    = 'scha_rl_d_' . gmdate( 'Ymd' ) . '_' . $identity;
        $minute_max = absint( SCHA_Settings::get( 'requests_per_minute', 10 ) );
        $daily_max  = max( 1, absint( $entitlement['quota'] ?? SCHA_Settings::get( 'daily_request_quota', 50 ) ) );

        $minute_count = absint( get_transient( $minute_key ) );
        $day_count    = absint( get_transient( $day_key ) );
        if ( $minute_count >= $minute_max ) {
            SCHA_Observability::metric( 'rate_limited' );
            return new WP_Error( 'scha_rate_limited', __( 'Too many requests. Please wait before trying again.', SCHA_TEXT_DOMAIN ), array( 'status' => 429, 'retry_after' => 60 ) );
        }
        if ( $day_count >= $daily_max ) {
            SCHA_Observability::metric( 'rate_limited' );
            return new WP_Error( 'scha_daily_quota', __( 'Your current AI request quota has been reached. No hidden charge will be made.', SCHA_TEXT_DOMAIN ), array( 'status' => 429, 'retry_after' => DAY_IN_SECONDS ) );
        }

        set_transient( $minute_key, $minute_count + 1, 2 * MINUTE_IN_SECONDS );
        set_transient( $day_key, $day_count + 1, 2 * DAY_IN_SECONDS );
        return true;
    }

    private static function client_fingerprint(): string {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        return wp_salt( 'nonce' ) . '|' . $ip . '|' . substr( $ua, 0, 120 );
    }
}
