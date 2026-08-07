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
            $provider_delete = SCHA_Provider_Data_Lifecycle::delete_session( (string) $session['public_id'], (string) $session['provider'], 'configured-retention' );
            if ( in_array( $provider_delete['status'], array( 'pending', 'failed' ), true ) ) {
                SCHA_Outbox::publish( 'AIProviderDeletionPending', 'ai_session', (string) $session['public_id'], array( 'session_id' => $session['public_id'], 'provider' => $session['provider'], 'status' => $provider_delete['status'] ), 'provider-delete-' . $session['public_id'] );
            }

            if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
                SCHA_Observability::safe_error( 'Retention transaction could not start.', SCHA_Observability::trace_id() );
                continue;
            }
            try {
                $message_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $messages WHERE session_id=%d", $session['id'] ) ) ?: array();
                if ( $message_ids ) {
                    $placeholders = implode( ',', array_fill( 0, count( $message_ids ), '%d' ) );
                    self::must_query( $wpdb->query( $wpdb->prepare( "DELETE FROM $feedback WHERE message_id IN ($placeholders)", $message_ids ) ), 'feedback retention purge' );
                }
                self::must_query( $wpdb->delete( $usage, array( 'session_id' => $session['id'] ), array( '%d' ) ), 'usage retention purge' );
                self::must_query( $wpdb->delete( $messages, array( 'session_id' => $session['id'] ), array( '%d' ) ), 'message retention purge' );
                self::must_query( $wpdb->update( $sessions, array(
                    'owner_id' => 0, 'guest_token_hash' => '', 'plan_slug' => 'expired', 'role_snapshot' => '', 'claims_version' => '',
                    'assistant_mode' => 'study', 'locale' => '', 'provider' => '', 'model' => '', 'status' => 'expired',
                    'retention_until' => $now, 'updated_at' => $now,
                ), array( 'id' => $session['id'] ) ), 'session retention pseudonymization' );
                self::must_query( $wpdb->query( 'COMMIT' ), 'retention commit' );
                SCHA_Observability::audit( 'session_retention_expired', 'ai_session', $session['public_id'], array( 'provider_deletion_status' => $provider_delete['status'] ), 'configured-retention' );
            } catch ( Throwable $e ) {
                $wpdb->query( 'ROLLBACK' );
                SCHA_Observability::safe_error( $e, SCHA_Observability::trace_id() );
            }
        }

        self::prune_metrics();
    }

    private static function must_query( int|bool|null $result, string $operation ): void {
        if ( false === $result || null === $result ) throw new RuntimeException( 'Database failure during ' . $operation . '.' );
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
