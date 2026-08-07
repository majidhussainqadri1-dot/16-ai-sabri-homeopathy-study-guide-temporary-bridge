<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Rate_Limiter {
    public static function check( array $entitlement, string $fingerprint = '' ): true|WP_Error {
        $identity = self::identity();
        $minute_key = 'scha_rl_m_' . gmdate( 'YmdHi' ) . '_' . $identity;
        $day_key    = 'scha_rl_d_' . gmdate( 'Ymd' ) . '_' . $identity;
        $minute_max = absint( SCHA_Settings::get( 'requests_per_minute', 10 ) );
        $daily_max  = max( 1, absint( $entitlement['quota'] ?? SCHA_Settings::get( 'daily_request_quota', 50 ) ) );

        return self::with_lock( $identity, static function () use ( $minute_key, $day_key, $minute_max, $daily_max ): true|WP_Error {
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
        } );
    }

    private static function identity(): string {
        $user_id = get_current_user_id();
        if ( $user_id > 0 ) return 'u' . $user_id;

        $binding = SCHA_Guest_Auth::binding_hash( false );
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $material = ( '' !== $binding ? $binding : 'no-binding' ) . '|' . $ip;
        return 'g' . substr( hash_hmac( 'sha256', $material, wp_salt( 'nonce' ) ), 0, 24 );
    }

    private static function with_lock( string $identity, callable $callback ): true|WP_Error {
        global $wpdb;
        $name = 'scha_rl_' . substr( hash( 'sha256', $identity ), 0, 32 );
        $acquired = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $name, 2 ) );
        if ( 1 !== $acquired ) {
            SCHA_Observability::metric( 'rate_limiter_lock_busy' );
            return new WP_Error( 'scha_rate_limiter_busy', __( 'The request limiter is temporarily busy. Please retry shortly.', SCHA_TEXT_DOMAIN ), array( 'status' => 503, 'retry_after' => 2 ) );
        }
        try {
            return $callback();
        } finally {
            $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
        }
    }
}
