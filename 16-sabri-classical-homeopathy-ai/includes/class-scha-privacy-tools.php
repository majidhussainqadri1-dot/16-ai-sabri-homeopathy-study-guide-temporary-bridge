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
        if ( ! $user ) return array( 'data' => array(), 'done' => true );
        $page = max( 1, $page );
        $offset = ( $page - 1 ) * self::PAGE_SIZE;
        $sessions = SCHA_Database::table( 'sessions' );
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $sessions WHERE owner_id=%d ORDER BY id ASC LIMIT %d OFFSET %d", $user->ID, self::PAGE_SIZE, $offset ), ARRAY_A ) ?: array();
        $data = array();
        foreach ( $rows as $session ) {
            $data[] = array(
                'group_id' => 'scha-ai-sessions',
                'group_label' => __( 'Sabri Classical Homeopathy AI sessions', SCHA_TEXT_DOMAIN ),
                'item_id' => 'scha-ai-session-' . $session['public_id'],
                'data' => array(
                    array( 'name' => __( 'Session metadata', SCHA_TEXT_DOMAIN ), 'value' => wp_json_encode( SCHA_Session_Service::public_session( $session ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ),
                    array( 'name' => __( 'Messages', SCHA_TEXT_DOMAIN ), 'value' => wp_json_encode( SCHA_Session_Service::messages( absint( $session['id'] ), 200 ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ),
                ),
            );
        }
        return array( 'data' => $data, 'done' => count( $rows ) < self::PAGE_SIZE );
    }

    public static function eraser( string $email_address, int $page = 1 ): array {
        global $wpdb;
        $user = get_user_by( 'email', $email_address );
        if ( ! $user ) return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
        $sessions = SCHA_Database::table( 'sessions' );
        $messages = SCHA_Database::table( 'messages' );
        $usage = SCHA_Database::table( 'usage' );
        $feedback = SCHA_Database::table( 'feedback' );
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT id,public_id,provider FROM $sessions WHERE owner_id=%d ORDER BY id ASC LIMIT %d", $user->ID, self::PAGE_SIZE ), ARRAY_A ) ?: array();
        $removed = false;
        foreach ( $rows as $session ) {
            do_action( 'scha_provider_delete_session', $session['public_id'], $session['provider'] );
            $wpdb->query( 'START TRANSACTION' );
            try {
                $message_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $messages WHERE session_id=%d", $session['id'] ) ) ?: array();
                if ( $message_ids ) {
                    $placeholders = implode( ',', array_fill( 0, count( $message_ids ), '%d' ) );
                    $wpdb->query( $wpdb->prepare( "DELETE FROM $feedback WHERE message_id IN ($placeholders)", $message_ids ) );
                }
                $wpdb->delete( $usage, array( 'session_id' => $session['id'] ), array( '%d' ) );
                $wpdb->delete( $messages, array( 'session_id' => $session['id'] ), array( '%d' ) );
                $wpdb->delete( $sessions, array( 'id' => $session['id'] ), array( '%d' ) );
                $wpdb->query( 'COMMIT' );
                $removed = true;
                SCHA_Observability::audit( 'privacy_erasure_completed', 'ai_session', $session['public_id'], array(), 'wordpress-privacy-erasure' );
            } catch ( Throwable $e ) {
                $wpdb->query( 'ROLLBACK' );
                SCHA_Observability::safe_error( $e, SCHA_Observability::trace_id() );
                return array( 'items_removed' => $removed, 'items_retained' => true, 'messages' => array( __( 'One or more AI sessions could not be erased safely. Please retry or review the system log.', SCHA_TEXT_DOMAIN ) ), 'done' => false );
            }
        }
        return array( 'items_removed' => $removed, 'items_retained' => false, 'messages' => array(), 'done' => count( $rows ) < self::PAGE_SIZE );
    }

    public static function add_policy_content(): void {
        if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) return;
        $content = '<p>' . esc_html__( 'Sabri Classical Homeopathy AI stores account-owned session metadata, questions, answers, citations, feedback, usage counts, and retention dates to provide the requested educational AI service. External providers receive only the minimum approved and redacted content required for a request when an administrator has enabled such a provider. The plugin does not enable provider training by default and excludes private clinical records, private messages, identity evidence, and private saved studies from the AI corpus.', SCHA_TEXT_DOMAIN ) . '</p>';
        $content .= '<p>' . esc_html__( 'Users may export or erase their AI session data through WordPress privacy tools. Security audit records may be retained separately when necessary for integrity, abuse prevention, or legal obligations, without retaining raw prompts in audit context.', SCHA_TEXT_DOMAIN ) . '</p>';
        wp_add_privacy_policy_content( __( 'Sabri Classical Homeopathy AI', SCHA_TEXT_DOMAIN ), wp_kses_post( $content ) );
    }
}
