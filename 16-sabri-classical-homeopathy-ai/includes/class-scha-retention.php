<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Retention {
    public static function run(): void {
        global $wpdb;
        $sessions = SCHA_Database::table( 'sessions' );
        $messages = SCHA_Database::table( 'messages' );
        $usage    = SCHA_Database::table( 'usage' );
        $feedback = SCHA_Database::table( 'feedback' );
        $now      = current_time( 'mysql', true );

        $expired = $wpdb->get_results( $wpdb->prepare( "SELECT id,public_id,provider FROM $sessions WHERE retention_until IS NOT NULL AND retention_until <= %s AND legal_hold=0 AND status NOT IN ('expired') LIMIT 200", $now ), ARRAY_A ) ?: array();
        foreach ( $expired as $session ) {
            do_action( 'scha_provider_delete_session', $session['public_id'], $session['provider'] );
            $wpdb->query( 'START TRANSACTION' );
            try {
                $message_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $messages WHERE session_id=%d", $session['id'] ) );
                if ( $message_ids ) {
                    $placeholders = implode( ',', array_fill( 0, count( $message_ids ), '%d' ) );
                    $wpdb->query( $wpdb->prepare( "DELETE FROM $feedback WHERE message_id IN ($placeholders)", $message_ids ) );
                }
                $wpdb->delete( $usage, array( 'session_id' => $session['id'] ), array( '%d' ) );
                $wpdb->delete( $messages, array( 'session_id' => $session['id'] ), array( '%d' ) );
                $wpdb->update( $sessions, array(
                    'owner_id' => 0, 'guest_token_hash' => '', 'plan_slug' => 'expired', 'role_snapshot' => '', 'claims_version' => '',
                    'assistant_mode' => 'study', 'locale' => '', 'provider' => '', 'model' => '', 'status' => 'expired',
                    'retention_until' => $now, 'updated_at' => $now,
                ), array( 'id' => $session['id'] ) );
                $wpdb->query( 'COMMIT' );
                SCHA_Observability::audit( 'session_retention_expired', 'ai_session', $session['public_id'], array(), 'configured-retention' );
            } catch ( Throwable $e ) {
                $wpdb->query( 'ROLLBACK' );
                SCHA_Observability::safe_error( $e, SCHA_Observability::trace_id() );
            }
        }

        self::prune_metrics();
    }

    private static function prune_metrics(): void {
        global $wpdb;
        $cutoff = gmdate( 'Y-m-d', time() - 90 * DAY_IN_SECONDS );
        $options = $wpdb->options;
        $names = $wpdb->get_col( "SELECT option_name FROM $options WHERE option_name LIKE 'scha_metric_%'" );
        foreach ( $names as $name ) {
            $date = substr( $name, -10 );
            if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) && $date < $cutoff ) {
                delete_option( $name );
            }
        }
    }
}
