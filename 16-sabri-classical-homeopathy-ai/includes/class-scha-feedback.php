<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Feedback {
    public static function submit( string $message_public_id, string $category, string $comment = '' ): array|WP_Error {
        global $wpdb;
        $messages = SCHA_Database::table( 'messages' );
        $sessions = SCHA_Database::table( 'sessions' );
        $message = $wpdb->get_row(
            $wpdb->prepare( "SELECT m.*,s.owner_id,s.public_id session_public_id FROM $messages m INNER JOIN $sessions s ON s.id=m.session_id WHERE m.public_id=%s LIMIT 1", $message_public_id ),
            ARRAY_A
        );
        if ( ! $message || absint( $message['owner_id'] ) !== get_current_user_id() ) {
            return new WP_Error( 'scha_message_not_found', __( 'Message not found.', SCHA_TEXT_DOMAIN ), array( 'status' => 404 ) );
        }
        $allowed = array( 'helpful', 'unhelpful', 'citation_issue', 'unsafe_answer', 'correction' );
        if ( ! in_array( $category, $allowed, true ) ) {
            return new WP_Error( 'scha_feedback_invalid', __( 'Invalid feedback category.', SCHA_TEXT_DOMAIN ), array( 'status' => 400 ) );
        }
        $comment = trim( sanitize_textarea_field( $comment ) );
        if ( function_exists( 'mb_strlen' ) && mb_strlen( $comment ) > 2000 ) {
            $comment = mb_substr( $comment, 0, 2000 );
        } elseif ( strlen( $comment ) > 2000 ) {
            $comment = substr( $comment, 0, 2000 );
        }
        $public_id = wp_generate_uuid4();
        $now = current_time( 'mysql', true );
        $wpdb->insert(
            SCHA_Database::table( 'feedback' ),
            array(
                'public_id'  => $public_id,
                'message_id' => $message['id'],
                'user_id'    => get_current_user_id(),
                'category'   => $category,
                'comment'    => $comment,
                'status'     => 'submitted',
                'action_json'=> wp_json_encode( array( 'policy_version' => SCHA_Policy_Repository::active()['version'], 'citations' => json_decode( (string) $message['citations'], true ) ?: array() ) ),
                'created_at' => $now,
                'updated_at' => $now,
            )
        );
        if ( ! $wpdb->insert_id ) {
            return new WP_Error( 'scha_feedback_failed', __( 'Feedback could not be saved.', SCHA_TEXT_DOMAIN ), array( 'status' => 500 ) );
        }
        SCHA_Observability::metric( 'feedback' );
        if ( in_array( $category, array( 'citation_issue', 'unsafe_answer', 'correction' ), true ) ) {
            SCHA_Outbox::publish( 'AIFeedbackEscalated', 'ai_feedback', $public_id, array( 'feedback_id' => $public_id, 'category' => $category, 'message_id' => $message_public_id, 'session_id' => $message['session_public_id'] ), 'feedback-' . $public_id );
        }
        return array( 'id' => $public_id, 'status' => 'submitted', 'category' => $category );
    }
}
