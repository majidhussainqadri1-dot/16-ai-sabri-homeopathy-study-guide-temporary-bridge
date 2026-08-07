<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Privacy_Tools {
    private const PAGE_SIZE = 20;

    public static function register_exporters( array $exporters ): array {
        $exporters['scha-ai-sessions'] = array(
            'exporter_friendly_name' => __( 'Sabri Classical Homeopathy AI sessions', SCHA_TEXT_DOMAIN ),
            'callback'               => array( __CLASS__, 'exporter' ),
        );
        return $exporters;
    }

    public static function register_erasers( array $erasers ): array {
        $erasers['scha-ai-sessions'] = array(
            'eraser_friendly_name' => __( 'Sabri Classical Homeopathy AI sessions', SCHA_TEXT_DOMAIN ),
            'callback'             => array( __CLASS__, 'eraser' ),
        );
        return $erasers;
    }

    public static function exporter( string $email_address, int $page = 1 ): array {
        global $wpdb;

        $user = get_user_by( 'email', $email_address );
        if ( ! $user ) {
            return array( 'data' => array(), 'done' => true );
        }

        $page     = max( 1, $page );
        $offset   = ( $page - 1 ) * self::PAGE_SIZE;
        $sessions = SCHA_Database::table( 'sessions' );
        $rows     = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $sessions WHERE owner_id=%d ORDER BY id ASC LIMIT %d OFFSET %d",
                $user->ID,
                self::PAGE_SIZE,
                $offset
            ),
            ARRAY_A
        ) ?: array();

        $data = array();
        foreach ( $rows as $session ) {
            $public = SCHA_Session_Service::public_session( $session );
            $messages = SCHA_Session_Service::messages( absint( $session['id'] ), 200 );
            $data[] = array(
                'group_id'    => 'scha-ai-sessions',
                'group_label' => __( 'Sabri Classical Homeopathy AI sessions', SCHA_TEXT_DOMAIN ),
                'item_id'     => 'scha-ai-session-' . $session['public_id'],
                'data'        => array(
                    array( 'name' => __( 'Session metadata', SCHA_TEXT_DOMAIN ), 'value' => wp_json_encode( $public, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ),
                    array( 'name' => __( 'Messages', SCHA_TEXT_DOMAIN ), 'value' => wp_json_encode( $messages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ),
                ),
            );
        }

        return array(
            'data' => $data,
            'done' => count( $rows ) < self::PAGE_SIZE,
        );
    }

    public static function eraser( string $email_address, int $page = 1 ): array {
        global $wpdb;

        $user = get_user_by( 'email', $email_address );
        if ( ! $user ) {
            return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
        }

        $sessions = SCHA_Database::table( 'sessions' );
        $messages = SCHA_Database::table( 'messages' );
        $usage    = SCHA_Database::table( 'usage' );
        $feedback = SCHA_Database::table( 'feedback' );
        $rows = $wpdb->get_results(
            $wpdb->prepare( "SELECT id,public_id,provider FROM $sessions WHERE owner_id=%d AND legal_hold=0 ORDER BY id ASC LIMIT %d", $user->ID, self::PAGE_SIZE ),
            ARRAY_A
        ) ?: array();

        $removed = false;
        $provider_pending = 0;
        foreach ( $rows as $session ) {
            $provider_delete = SCHA_Provider_Data_Lifecycle::delete_session( (string) $session['public_id'], (string) $session['provider'], 'wordpress-privacy-erasure' );
            if ( in_array( $provider_delete['status'], array( 'pending', 'failed' ), true ) ) {
                ++$provider_pending;
                SCHA_Outbox::publish( 'AIProviderDeletionPending', 'ai_session', (string) $session['public_id'], array( 'session_id' => $session['public_id'], 'provider' => $session['provider'], 'status' => $provider_delete['status'] ), 'provider-delete-' . $session['public_id'] );
            }

            if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
                return array( 'items_removed' => $removed, 'items_retained' => true, 'messages' => array( __( 'The AI erasure transaction could not be started safely.', SCHA_TEXT_DOMAIN ) ), 'done' => false );
            }
            try {
                $message_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $messages WHERE session_id=%d", $session['id'] ) ) ?: array();
                if ( $message_ids ) {
                    $placeholders = implode( ',', array_fill( 0, count( $message_ids ), '%d' ) );
                    self::must_query( $wpdb->query( $wpdb->prepare( "DELETE FROM $feedback WHERE message_id IN ($placeholders)", $message_ids ) ), 'feedback erasure' );
                }
                self::must_query( $wpdb->delete( $usage, array( 'session_id' => $session['id'] ), array( '%d' ) ), 'usage erasure' );
                self::must_query( $wpdb->delete( $messages, array( 'session_id' => $session['id'] ), array( '%d' ) ), 'message erasure' );
                self::must_query( $wpdb->delete( $sessions, array( 'id' => $session['id'] ), array( '%d' ) ), 'session erasure' );
                self::must_query( $wpdb->query( 'COMMIT' ), 'erasure commit' );
                $removed = true;
                SCHA_Observability::audit( 'privacy_erasure_completed', 'ai_session', $session['public_id'], array( 'provider_deletion_status' => $provider_delete['status'] ), 'wordpress-privacy-erasure' );
            } catch ( Throwable $e ) {
                $wpdb->query( 'ROLLBACK' );
                SCHA_Observability::safe_error( $e, SCHA_Observability::trace_id() );
                return array( 'items_removed' => $removed, 'items_retained' => true, 'messages' => array( __( 'One or more AI sessions could not be erased safely. Please retry or review the system log.', SCHA_TEXT_DOMAIN ) ), 'done' => false );
            }
        }

        $remaining_deletable = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $sessions WHERE owner_id=%d AND legal_hold=0", $user->ID ) ) );
        $held = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $sessions WHERE owner_id=%d AND legal_hold=1", $user->ID ) ) );
        $messages_out = $held > 0 ? array( sprintf( _n( '%d AI session is retained under an authorized legal or security hold.', '%d AI sessions are retained under authorized legal or security holds.', $held, SCHA_TEXT_DOMAIN ), $held ) ) : array();
        if ( $provider_pending > 0 ) {
            $messages_out[] = sprintf( _n( 'Provider-side deletion confirmation is pending for %d erased AI session; the local copy was removed.', 'Provider-side deletion confirmation is pending for %d erased AI sessions; the local copies were removed.', $provider_pending, SCHA_TEXT_DOMAIN ), $provider_pending );
        }

        return array(
            'items_removed'  => $removed,
            'items_retained' => $held > 0 || $provider_pending > 0,
            'messages'       => $messages_out,
            'done'           => 0 === $remaining_deletable,
        );
    }

    private static function must_query( int|bool|null $result, string $operation ): void {
        if ( false === $result || null === $result ) throw new RuntimeException( 'Database failure during ' . $operation . '.' );
    }

    public static function add_policy_content(): void {
        if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
            return;
        }
        $content = '<p>' . esc_html__( 'Sabri Classical Homeopathy AI stores account-owned session metadata, questions, answers, citations, feedback, usage counts, and retention dates to provide the requested educational AI service. External providers receive only the minimum approved and redacted content required for a request when an administrator has enabled such a provider. The plugin does not enable provider training by default and excludes private clinical records, private messages, identity evidence, and private saved studies from the AI corpus.', SCHA_TEXT_DOMAIN ) . '</p>';
        $content .= '<p>' . esc_html__( 'Users may export or erase their AI session data through WordPress privacy tools. Message content is encrypted at rest. An authorized legal or security hold can temporarily retain a session, and security audit records may be retained separately when necessary for integrity, abuse prevention, or legal obligations, without retaining raw prompts in audit context.', SCHA_TEXT_DOMAIN ) . '</p>';
        wp_add_privacy_policy_content( __( 'Sabri Classical Homeopathy AI', SCHA_TEXT_DOMAIN ), wp_kses_post( $content ) );
    }
}
