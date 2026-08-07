<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Usage_Ledger {
    public static function record( array $data ): int|WP_Error {
        global $wpdb;
        $table = SCHA_Database::table( 'usage' );
        $idempotency = sanitize_text_field( (string) ( $data['idempotency_key'] ?? '' ) );
        $session_id = absint( $data['session_id'] ?? 0 );
        $teacher_record = str_starts_with( $idempotency, 'teacher:' );
        if ( '' === $idempotency || ( 0 === $session_id && ! $teacher_record ) ) {
            return new WP_Error( 'scha_usage_invalid', 'Usage record requires a session or a governed teacher idempotency key.' );
        }

        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE session_id=%d AND idempotency_key=%s", $session_id, $idempotency ) );
        if ( $existing ) return absint( $existing );

        $request_hash = (string) ( $data['request_hash'] ?? '' );
        if ( ! preg_match( '/^[a-f0-9]{64}$/i', $request_hash ) ) $request_hash = hash_hmac( 'sha256', $request_hash, wp_salt( 'auth' ) );
        $user_id = array_key_exists( 'user_id', $data ) ? absint( $data['user_id'] ) : get_current_user_id();
        $wpdb->insert( $table, array(
            'public_id' => wp_generate_uuid4(),
            'user_id' => $user_id,
            'session_id' => $session_id,
            'response_message_id' => absint( $data['response_message_id'] ?? 0 ),
            'provider' => sanitize_key( (string) ( $data['provider'] ?? 'local' ) ),
            'model' => sanitize_text_field( (string) ( $data['model'] ?? '' ) ),
            'request_hash' => strtolower( $request_hash ),
            'idempotency_key' => $idempotency,
            'input_tokens' => absint( $data['input_tokens'] ?? 0 ),
            'output_tokens' => absint( $data['output_tokens'] ?? 0 ),
            'cost_micros' => absint( $data['cost_micros'] ?? 0 ),
            'period_key' => gmdate( 'Y-m' ),
            'created_at' => current_time( 'mysql', true ),
        ) );
        return $wpdb->insert_id ?: new WP_Error( 'scha_usage_write_failed', 'Unable to record usage.' );
    }

    public static function budget_check( int $user_id ): true|WP_Error {
        $budget = absint( SCHA_Settings::get( 'monthly_cost_budget_micros', 0 ) );
        if ( 0 === $budget ) return true;
        $summary = self::summary( $user_id );
        if ( absint( $summary['cost_micros'] ?? 0 ) >= $budget ) {
            return new WP_Error( 'scha_cost_budget_reached', __( 'The configured monthly AI cost safety budget has been reached. No additional external-provider request was sent.', SCHA_TEXT_DOMAIN ), array( 'status' => 429, 'budget_micros' => $budget ) );
        }
        return true;
    }

    public static function summary( int $user_id ): array {
        global $wpdb;
        $table = SCHA_Database::table( 'usage' );
        $period = gmdate( 'Y-m' );
        $scope = 0 === $user_id ? ' AND session_id > 0' : '';
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) requests, COALESCE(SUM(input_tokens),0) input_tokens, COALESCE(SUM(output_tokens),0) output_tokens, COALESCE(SUM(cost_micros),0) cost_micros FROM $table WHERE user_id=%d AND period_key=%s$scope", $user_id, $period ), ARRAY_A ) ?: array();
        return array(
            'period' => $period,
            'requests' => absint( $row['requests'] ?? 0 ),
            'input_tokens' => absint( $row['input_tokens'] ?? 0 ),
            'output_tokens' => absint( $row['output_tokens'] ?? 0 ),
            'cost_micros' => absint( $row['cost_micros'] ?? 0 ),
            'currency' => 'USD-micro-estimate',
            'notice' => __( 'Usage is shown transparently. This telemetry does not create a user charge or donor advantage.', SCHA_TEXT_DOMAIN ),
        );
    }

    public static function estimate_tokens( string $text ): int {
        $length = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
        return max( 1, (int) ceil( $length / 4 ) );
    }
}
