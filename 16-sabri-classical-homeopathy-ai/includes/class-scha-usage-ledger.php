<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Usage_Ledger {
    public static function record( array $data ): int|WP_Error {
        global $wpdb;
        $table = SCHA_Database::table( 'usage' );
        $idempotency = sanitize_text_field( $data['idempotency_key'] ?? '' );
        $session_id  = absint( $data['session_id'] ?? 0 );
        if ( '' === $idempotency || 0 === $session_id ) {
            return new WP_Error( 'scha_usage_invalid', 'Usage record requires session and idempotency key.' );
        }

        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE session_id=%d AND idempotency_key=%s", $session_id, $idempotency ) );
        if ( $existing ) {
            return absint( $existing );
        }

        $wpdb->insert(
            $table,
            array(
                'public_id'          => wp_generate_uuid4(),
                'user_id'            => get_current_user_id(),
                'session_id'         => $session_id,
                'response_message_id'=> absint( $data['response_message_id'] ?? 0 ),
                'provider'           => sanitize_key( $data['provider'] ?? 'local' ),
                'model'              => sanitize_text_field( $data['model'] ?? '' ),
                'request_hash'       => hash( 'sha256', (string) ( $data['request_hash'] ?? '' ) ),
                'idempotency_key'    => $idempotency,
                'input_tokens'       => absint( $data['input_tokens'] ?? 0 ),
                'output_tokens'      => absint( $data['output_tokens'] ?? 0 ),
                'cost_micros'        => absint( $data['cost_micros'] ?? 0 ),
                'period_key'         => gmdate( 'Y-m' ),
                'created_at'         => current_time( 'mysql', true ),
            ),
            array( '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' )
        );

        return $wpdb->insert_id ?: new WP_Error( 'scha_usage_write_failed', 'Unable to record usage.' );
    }

    public static function budget_check( int $user_id ): true|WP_Error {
        $budget = absint( SCHA_Settings::get( 'monthly_cost_budget_micros', 0 ) );
        if ( 0 === $budget ) {
            return true;
        }
        $summary = self::summary( $user_id );
        if ( absint( $summary['cost_micros'] ?? 0 ) >= $budget ) {
            return new WP_Error(
                'scha_cost_budget_reached',
                __( 'The configured monthly AI cost budget has been reached. No additional paid-provider request was sent.', SCHA_TEXT_DOMAIN ),
                array( 'status' => 429, 'budget_micros' => $budget )
            );
        }
        return true;
    }

    public static function summary( int $user_id ): array {
        global $wpdb;
        $table = SCHA_Database::table( 'usage' );
        $period = gmdate( 'Y-m' );
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT COUNT(*) requests, COALESCE(SUM(input_tokens),0) input_tokens, COALESCE(SUM(output_tokens),0) output_tokens, COALESCE(SUM(cost_micros),0) cost_micros FROM $table WHERE user_id=%d AND period_key=%s", $user_id, $period ),
            ARRAY_A
        ) ?: array();
        return array(
            'period'        => $period,
            'requests'      => absint( $row['requests'] ?? 0 ),
            'input_tokens'  => absint( $row['input_tokens'] ?? 0 ),
            'output_tokens' => absint( $row['output_tokens'] ?? 0 ),
            'cost_micros'   => absint( $row['cost_micros'] ?? 0 ),
            'currency'      => 'USD-micro-estimate',
            'notice'        => __( 'Usage is shown transparently. This record does not itself create a charge.', SCHA_TEXT_DOMAIN ),
        );
    }

    public static function estimate_tokens( string $text ): int {
        $length = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
        return max( 1, (int) ceil( $length / 4 ) );
    }
}
