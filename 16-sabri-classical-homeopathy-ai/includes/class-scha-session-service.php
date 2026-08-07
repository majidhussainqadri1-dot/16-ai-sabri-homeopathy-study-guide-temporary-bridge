<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Session_Service {
    public static function create( array $entitlement, string $mode = 'study' ): array|WP_Error {
        global $wpdb;

        $context = SCHA_Account_Context::current();
        $allowed = SCHA_Account_Context::require_approved();
        if ( is_wp_error( $allowed ) && is_user_logged_in() ) {
            return $allowed;
        }

        $mode = SCHA_Assistant_Modes::validate( $mode, $context );
        if ( is_wp_error( $mode ) ) {
            return $mode;
        }

        $provider = SCHA_Provider_Registry::selected();
        $public_id = wp_generate_uuid4();
        $owner_id = get_current_user_id();
        $guest_token_hash = 0 === $owner_id ? SCHA_Guest_Auth::binding_hash( true ) : '';
        if ( 0 === $owner_id && '' === $guest_token_hash ) {
            return new WP_Error( 'scha_guest_session_unavailable', __( 'A secure guest session could not be created. Please sign in and try again.', SCHA_TEXT_DOMAIN ), array( 'status' => 503 ) );
        }

        $retention_days = absint( SCHA_Settings::get( 'retention_days', 30 ) );
        $now = current_time( 'mysql', true );
        $retention_until = gmdate( 'Y-m-d H:i:s', time() + ( $retention_days * DAY_IN_SECONDS ) );
        $claims_version = sanitize_text_field( (string) ( $context['claims_version'] ?? $context['version'] ?? '' ) );

        $inserted = $wpdb->insert(
            SCHA_Database::table( 'sessions' ),
            array(
                'public_id'        => $public_id,
                'owner_id'         => $owner_id,
                'guest_token_hash' => $guest_token_hash,
                'plan_slug'        => sanitize_key( $entitlement['plan'] ?? 'single-free-tier' ),
                'role_snapshot'    => sanitize_key( $entitlement['role'] ?? 'member' ),
                'claims_version'   => $claims_version,
                'assistant_mode'   => $mode,
                'locale'           => sanitize_text_field( $entitlement['locale'] ?? get_locale() ),
                'provider'         => $provider->key(),
                'model'            => sanitize_text_field( (string) SCHA_Settings::get( 'provider_model', '' ) ),
                'status'           => 'active',
                'legal_hold'       => 0,
                'retention_until'  => $retention_until,
                'created_at'       => $now,
                'updated_at'       => $now,
                'version'          => 1,
            )
        );
        if ( ! $inserted ) {
            return new WP_Error( 'scha_session_create_failed', __( 'The AI session could not be created.', SCHA_TEXT_DOMAIN ), array( 'status' => 500 ) );
        }

        SCHA_Observability::audit(
            'session_created',
            'ai_session',
            $public_id,
            array( 'provider' => $provider->key(), 'retention_days' => $retention_days, 'guest' => 0 === $owner_id, 'mode' => $mode, 'claims_version' => $claims_version ),
            'user-requested-ai-session'
        );
        SCHA_Outbox::publish( 'AISessionCreated', 'ai_session', $public_id, array( 'session_id' => $public_id, 'owner_id' => $owner_id, 'provider' => $provider->key(), 'mode' => $mode ), 'session-created-' . $public_id );

        return self::public_session( self::get_by_id( absint( $wpdb->insert_id ) ) );
    }

    public static function get_by_public_id( string $public_id ): ?array {
        global $wpdb;
        $table = SCHA_Database::table( 'sessions' );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE public_id=%s LIMIT 1", $public_id ), ARRAY_A );
        return $row ?: null;
    }

    public static function get_by_id( int $id ): ?array {
        global $wpdb;
        $table = SCHA_Database::table( 'sessions' );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id=%d LIMIT 1", $id ), ARRAY_A );
        return $row ?: null;
    }

    public static function owned( string $public_id, bool $allow_manager = false ): array|WP_Error {
        $session = self::get_by_public_id( $public_id );
        if ( ! $session ) {
            return new WP_Error( 'scha_session_not_found', __( 'Session not found.', SCHA_TEXT_DOMAIN ), array( 'status' => 404 ) );
        }

        $owner_id = absint( $session['owner_id'] );
        $current = get_current_user_id();
        $guest_ok = false;
        if ( 0 === $owner_id && 0 === $current && SCHA_Settings::get( 'guest_demo', false ) ) {
            $stored_hash = (string) ( $session['guest_token_hash'] ?? '' );
            $request_hash = SCHA_Guest_Auth::binding_hash( false );
            $guest_ok = '' !== $stored_hash && '' !== $request_hash && hash_equals( $stored_hash, $request_hash );
        }

        if ( ! $guest_ok && $owner_id !== $current && ! ( $allow_manager && SCHA_Capabilities::current_user_can_manage() ) ) {
            return new WP_Error( 'scha_session_not_found', __( 'Session not found.', SCHA_TEXT_DOMAIN ), array( 'status' => 404 ) );
        }
        if ( in_array( (string) $session['status'], array( 'deleted', 'expired' ), true ) ) {
            return new WP_Error( 'scha_session_unavailable', __( 'This session is no longer available.', SCHA_TEXT_DOMAIN ), array( 'status' => 410 ) );
        }

        if ( $owner_id > 0 && $owner_id === $current ) {
            $approved = SCHA_Account_Context::require_approved( $owner_id );
            if ( is_wp_error( $approved ) ) {
                return $approved;
            }
        }
        return $session;
    }

    public static function list_owned( int $limit = 50 ): array {
        global $wpdb;
        $table = SCHA_Database::table( 'sessions' );
        $user_id = get_current_user_id();
        $limit = min( 100, max( 1, $limit ) );
        if ( 0 === $user_id || is_wp_error( SCHA_Account_Context::require_approved( $user_id ) ) ) {
            return array();
        }
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE owner_id=%d AND status NOT IN ('deleted','expired') ORDER BY updated_at DESC,id DESC LIMIT %d", $user_id, $limit ), ARRAY_A ) ?: array();
        return array_map( array( __CLASS__, 'public_session' ), $rows );
    }

    public static function messages( int $session_id, int $limit = 100 ): array {
        global $wpdb;
        $table = SCHA_Database::table( 'messages' );
        $limit = min( 200, max( 1, $limit ) );
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE session_id=%d ORDER BY id ASC LIMIT %d", $session_id, $limit ), ARRAY_A ) ?: array();
        return array_map( array( __CLASS__, 'public_message' ), $rows );
    }

    public static function insert_message( int $session_id, string $role, string $content, array $extra = array() ): int|WP_Error {
        global $wpdb;
        $idempotency = sanitize_text_field( (string) ( $extra['idempotency_key'] ?? wp_generate_uuid4() ) );
        $table = SCHA_Database::table( 'messages' );
        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE session_id=%d AND idempotency_key=%s", $session_id, $idempotency ) );
        if ( $existing ) {
            return absint( $existing );
        }

        $encrypted = SCHA_Crypto::encrypt( $content, 'session-message' );
        $redacted = array_key_exists( 'redacted_content', $extra ) ? SCHA_Crypto::encrypt( (string) $extra['redacted_content'], 'session-message-redacted' ) : null;
        if ( null === $encrypted ) {
            return new WP_Error( 'scha_message_encryption_failed', __( 'The private message could not be encrypted.', SCHA_TEXT_DOMAIN ), array( 'status' => 500 ) );
        }

        $inserted = $wpdb->insert(
            $table,
            array(
                'public_id'            => wp_generate_uuid4(),
                'session_id'           => $session_id,
                'role'                 => in_array( $role, array( 'user', 'assistant', 'system' ), true ) ? $role : 'assistant',
                'content'              => $encrypted,
                'redacted_content'     => $redacted,
                'encryption_version'   => 'scha2',
                'citations'            => wp_json_encode( $extra['citations'] ?? array() ),
                'safety_category'      => sanitize_key( (string) ( $extra['safety_category'] ?? '' ) ),
                'provider_response_id' => sanitize_text_field( (string) ( $extra['provider_response_id'] ?? '' ) ),
                'idempotency_key'      => $idempotency,
                'created_at'           => current_time( 'mysql', true ),
            )
        );
        if ( ! $inserted ) {
            return new WP_Error( 'scha_message_write_failed', __( 'The message could not be saved.', SCHA_TEXT_DOMAIN ) );
        }

        $sessions = SCHA_Database::table( 'sessions' );
        $wpdb->query( $wpdb->prepare( "UPDATE $sessions SET updated_at=%s, version=version+1 WHERE id=%d", current_time( 'mysql', true ), $session_id ) );
        return absint( $wpdb->insert_id );
    }

    public static function get_message_by_id( int $id ): array|WP_Error {
        global $wpdb;
        $table = SCHA_Database::table( 'messages' );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id=%d LIMIT 1", $id ), ARRAY_A );
        return $row ? self::public_message( $row ) : new WP_Error( 'scha_message_not_found', __( 'Message not found.', SCHA_TEXT_DOMAIN ) );
    }

    public static function find_response_by_idempotency( int $session_id, string $idempotency_key ): ?array {
        global $wpdb;
        $usage = SCHA_Database::table( 'usage' );
        $messages = SCHA_Database::table( 'messages' );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT m.* FROM $usage u INNER JOIN $messages m ON m.id=u.response_message_id WHERE u.session_id=%d AND u.idempotency_key=%s LIMIT 1", $session_id, $idempotency_key ), ARRAY_A );
        return $row ? self::public_message( $row ) : null;
    }

    public static function delete( array $session ): true|WP_Error {
        global $wpdb;
        if ( ! empty( $session['legal_hold'] ) ) {
            return new WP_Error( 'scha_session_legal_hold', __( 'This session is preserved under an authorized legal or security hold and cannot be erased yet.', SCHA_TEXT_DOMAIN ), array( 'status' => 409 ) );
        }
        $updated = $wpdb->update(
            SCHA_Database::table( 'sessions' ),
            array( 'status' => 'deleted', 'updated_at' => current_time( 'mysql', true ), 'retention_until' => current_time( 'mysql', true ) ),
            array( 'id' => $session['id'] )
        );
        if ( false === $updated ) {
            return new WP_Error( 'scha_session_delete_failed', __( 'The session could not be deleted.', SCHA_TEXT_DOMAIN ) );
        }
        $provider_delete = SCHA_Provider_Data_Lifecycle::delete_session( (string) $session['public_id'], (string) $session['provider'], 'user-erasure-request' );
        SCHA_Observability::audit( 'session_delete_requested', 'ai_session', $session['public_id'], array( 'legal_hold' => false, 'provider_deletion_status' => $provider_delete['status'] ), 'user-erasure-request' );
        SCHA_Outbox::publish( 'AISessionDeletionRequested', 'ai_session', $session['public_id'], array( 'session_id' => $session['public_id'] ), 'session-delete-' . $session['public_id'] );
        return true;
    }

    public static function export( array $session ): array {
        return array(
            'schema'      => 'SCHA-session-export-v2',
            'exported_at' => gmdate( 'c' ),
            'session'     => self::public_session( $session ),
            'messages'    => self::messages( absint( $session['id'] ), 200 ),
            'notice'      => 'This export excludes internal audit context, provider secrets, guest binding hashes and security telemetry.',
        );
    }

    public static function public_session( ?array $session ): array {
        if ( ! $session ) {
            return array();
        }
        return array(
            'id'              => (string) $session['public_id'],
            'plan'            => (string) $session['plan_slug'],
            'role'            => (string) $session['role_snapshot'],
            'claimsVersion'   => (string) ( $session['claims_version'] ?? '' ),
            'mode'            => (string) ( $session['assistant_mode'] ?? 'study' ),
            'modeLabel'       => SCHA_Assistant_Modes::all()[ $session['assistant_mode'] ?? 'study' ] ?? SCHA_Assistant_Modes::all()['study'],
            'locale'          => (string) $session['locale'],
            'provider'        => (string) $session['provider'],
            'model'           => (string) $session['model'],
            'status'          => (string) $session['status'],
            'legalHold'       => ! empty( $session['legal_hold'] ),
            'retentionUntil'  => self::rfc3339( $session['retention_until'] ),
            'createdAt'       => self::rfc3339( $session['created_at'] ),
            'updatedAt'       => self::rfc3339( $session['updated_at'] ),
            'version'         => absint( $session['version'] ),
            'url'             => home_url( '/ai/session/' . rawurlencode( (string) $session['public_id'] ) . '/' ),
        );
    }

    public static function public_message( array $message ): array {
        $citations = json_decode( (string) ( $message['citations'] ?? '[]' ), true );
        return array(
            'id'             => (string) $message['public_id'],
            'role'           => (string) $message['role'],
            'content'        => SCHA_Crypto::decrypt( (string) $message['content'], 'session-message' ),
            'redacted'       => SCHA_Crypto::decrypt( (string) ( $message['redacted_content'] ?? '' ), 'session-message-redacted' ),
            'citations'      => is_array( $citations ) ? $citations : array(),
            'safetyCategory' => (string) ( $message['safety_category'] ?? '' ),
            'createdAt'      => self::rfc3339( $message['created_at'] ?? null ),
        );
    }

    private static function rfc3339( ?string $mysql_datetime ): string {
        if ( ! $mysql_datetime ) {
            return '';
        }
        $timestamp = strtotime( $mysql_datetime . ' UTC' );
        return $timestamp ? gmdate( 'c', $timestamp ) : '';
    }
}
