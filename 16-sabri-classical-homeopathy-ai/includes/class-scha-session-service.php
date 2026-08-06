<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Session_Service {
    private const GUEST_COOKIE = 'scha_guest_session';

    public static function create( array $entitlement ): array|WP_Error {
        global $wpdb;

        $provider         = SCHA_Provider_Registry::selected();
        $public_id        = wp_generate_uuid4();
        $owner_id         = get_current_user_id();
        $guest_token_hash = 0 === $owner_id ? self::guest_token_hash( true ) : '';
        if ( 0 === $owner_id && '' === $guest_token_hash ) {
            return new WP_Error( 'scha_guest_session_unavailable', __( 'A secure guest session could not be created. Please sign in and try again.', SCHA_TEXT_DOMAIN ), array( 'status' => 503 ) );
        }

        $retention_days  = absint( SCHA_Settings::get( 'retention_days', 30 ) );
        $now             = current_time( 'mysql', true );
        $retention_until = gmdate( 'Y-m-d H:i:s', time() + $retention_days * DAY_IN_SECONDS );

        $inserted = $wpdb->insert(
            SCHA_Database::table( 'sessions' ),
            array(
                'public_id'        => $public_id,
                'owner_id'         => $owner_id,
                'guest_token_hash' => $guest_token_hash,
                'plan_slug'        => sanitize_key( $entitlement['plan'] ?? 'ai-addon' ),
                'role_snapshot'    => sanitize_key( $entitlement['role'] ?? 'subscriber' ),
                'locale'           => sanitize_text_field( $entitlement['locale'] ?? get_locale() ),
                'provider'         => $provider->key(),
                'model'            => sanitize_text_field( SCHA_Settings::get( 'provider_model', '' ) ),
                'status'           => 'active',
                'retention_until'  => $retention_until,
                'created_at'       => $now,
                'updated_at'       => $now,
                'version'          => 1,
            ),
            array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
        );
        if ( ! $inserted ) {
            return new WP_Error( 'scha_session_create_failed', __( 'The AI session could not be created.', SCHA_TEXT_DOMAIN ), array( 'status' => 500 ) );
        }

        $id = absint( $wpdb->insert_id );
        SCHA_Observability::audit( 'session_created', 'ai_session', $public_id, array( 'provider' => $provider->key(), 'retention_days' => $retention_days, 'guest' => 0 === $owner_id ), 'user-requested-ai-session' );
        SCHA_Outbox::publish( 'AISessionCreated', 'ai_session', $public_id, array( 'session_id' => $public_id, 'owner_id' => $owner_id, 'provider' => $provider->key() ), 'session-created-' . $public_id );

        return self::public_session( self::get_by_id( $id ) );
    }

    public static function get_by_public_id( string $public_id ): ?array {
        global $wpdb;
        $table = SCHA_Database::table( 'sessions' );
        $row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE public_id=%s LIMIT 1", $public_id ), ARRAY_A );
        return $row ?: null;
    }

    public static function get_by_id( int $id ): ?array {
        global $wpdb;
        $table = SCHA_Database::table( 'sessions' );
        $row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id=%d LIMIT 1", $id ), ARRAY_A );
        return $row ?: null;
    }

    public static function owned( string $public_id, bool $allow_manager = false ): array|WP_Error {
        $session = self::get_by_public_id( $public_id );
        if ( ! $session ) {
            return new WP_Error( 'scha_session_not_found', __( 'Session not found.', SCHA_TEXT_DOMAIN ), array( 'status' => 404 ) );
        }

        $owner_id = absint( $session['owner_id'] );
        $current  = get_current_user_id();
        $guest_ok = false;
        if ( 0 === $owner_id && 0 === $current && SCHA_Settings::get( 'guest_demo', false ) ) {
            $stored_hash  = (string) ( $session['guest_token_hash'] ?? '' );
            $request_hash = self::guest_token_hash( false );
            $guest_ok     = '' !== $stored_hash && '' !== $request_hash && hash_equals( $stored_hash, $request_hash );
        }

        if ( ! $guest_ok && $owner_id !== $current && ! ( $allow_manager && SCHA_Capabilities::current_user_can_manage() ) ) {
            return new WP_Error( 'scha_session_not_found', __( 'Session not found.', SCHA_TEXT_DOMAIN ), array( 'status' => 404 ) );
        }
        if ( in_array( $session['status'], array( 'deleted', 'expired' ), true ) ) {
            return new WP_Error( 'scha_session_unavailable', __( 'This session is no longer available.', SCHA_TEXT_DOMAIN ), array( 'status' => 410 ) );
        }

        return $session;
    }

    public static function list_owned( int $limit = 50 ): array {
        global $wpdb;
        $table   = SCHA_Database::table( 'sessions' );
        $user_id = get_current_user_id();
        $limit   = min( 100, max( 1, $limit ) );
        if ( 0 === $user_id ) {
            return array();
        }
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE owner_id=%d AND status NOT IN ('deleted','expired') ORDER BY updated_at DESC LIMIT %d", $user_id, $limit ), ARRAY_A ) ?: array();
        return array_map( array( __CLASS__, 'public_session' ), $rows );
    }

    public static function messages( int $session_id, int $limit = 100 ): array {
        global $wpdb;
        $table = SCHA_Database::table( 'messages' );
        $limit = min( 200, max( 1, $limit ) );
        $rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE session_id=%d ORDER BY id ASC LIMIT %d", $session_id, $limit ), ARRAY_A ) ?: array();
        return array_map( array( __CLASS__, 'public_message' ), $rows );
    }

    public static function insert_message( int $session_id, string $role, string $content, array $extra = array() ): int|WP_Error {
        global $wpdb;
        $idempotency = sanitize_text_field( $extra['idempotency_key'] ?? wp_generate_uuid4() );
        $table       = SCHA_Database::table( 'messages' );
        $existing    = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE session_id=%d AND idempotency_key=%s", $session_id, $idempotency ) );
        if ( $existing ) {
            return absint( $existing );
        }

        $inserted = $wpdb->insert(
            $table,
            array(
                'public_id'            => wp_generate_uuid4(),
                'session_id'           => $session_id,
                'role'                 => in_array( $role, array( 'user', 'assistant', 'system' ), true ) ? $role : 'assistant',
                'content'              => $content,
                'redacted_content'     => $extra['redacted_content'] ?? null,
                'citations'            => wp_json_encode( $extra['citations'] ?? array() ),
                'safety_category'      => sanitize_key( $extra['safety_category'] ?? '' ),
                'provider_response_id' => sanitize_text_field( $extra['provider_response_id'] ?? '' ),
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
        $row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id=%d LIMIT 1", $id ), ARRAY_A );
        return $row ? self::public_message( $row ) : new WP_Error( 'scha_message_not_found', __( 'Message not found.', SCHA_TEXT_DOMAIN ) );
    }

    public static function find_response_by_idempotency( int $session_id, string $idempotency_key ): ?array {
        global $wpdb;
        $usage    = SCHA_Database::table( 'usage' );
        $messages = SCHA_Database::table( 'messages' );
        $row      = $wpdb->get_row(
            $wpdb->prepare( "SELECT m.* FROM $usage u INNER JOIN $messages m ON m.id=u.response_message_id WHERE u.session_id=%d AND u.idempotency_key=%s LIMIT 1", $session_id, $idempotency_key ),
            ARRAY_A
        );
        return $row ? self::public_message( $row ) : null;
    }

    public static function delete( array $session ): true|WP_Error {
        global $wpdb;
        $updated = $wpdb->update(
            SCHA_Database::table( 'sessions' ),
            array( 'status' => 'deleted', 'updated_at' => current_time( 'mysql', true ), 'retention_until' => current_time( 'mysql', true ) ),
            array( 'id' => $session['id'] )
        );
        if ( false === $updated ) {
            return new WP_Error( 'scha_session_delete_failed', __( 'The session could not be deleted.', SCHA_TEXT_DOMAIN ) );
        }
        do_action( 'scha_provider_delete_session', $session['public_id'], $session['provider'] );
        SCHA_Observability::audit( 'session_delete_requested', 'ai_session', $session['public_id'], array(), 'user-erasure-request' );
        SCHA_Outbox::publish( 'AISessionDeletionRequested', 'ai_session', $session['public_id'], array( 'session_id' => $session['public_id'] ), 'session-delete-' . $session['public_id'] );
        return true;
    }

    public static function export( array $session ): array {
        return array(
            'schema'      => 'SCHA-session-export-v1',
            'exported_at' => gmdate( 'c' ),
            'session'     => self::public_session( $session ),
            'messages'    => self::messages( absint( $session['id'] ), 200 ),
            'notice'      => 'This export excludes internal audit, provider secrets, guest ownership tokens, and other users’ data.',
        );
    }

    public static function public_session( ?array $session ): array {
        if ( ! $session ) {
            return array();
        }
        return array(
            'id'              => $session['public_id'],
            'plan'            => $session['plan_slug'],
            'role'            => $session['role_snapshot'],
            'locale'          => $session['locale'],
            'provider'        => $session['provider'],
            'model'           => $session['model'],
            'status'          => $session['status'],
            'retention_until' => self::rfc3339( $session['retention_until'] ),
            'created_at'      => self::rfc3339( $session['created_at'] ),
            'updated_at'      => self::rfc3339( $session['updated_at'] ),
            'version'         => absint( $session['version'] ),
            'url'             => home_url( '/ai/session/' . rawurlencode( $session['public_id'] ) . '/' ),
        );
    }

    public static function public_message( array $message ): array {
        return array(
            'id'              => $message['public_id'],
            'role'            => $message['role'],
            'content'         => $message['content'],
            'citations'       => json_decode( (string) $message['citations'], true ) ?: array(),
            'safety_category' => $message['safety_category'],
            'created_at'      => self::rfc3339( $message['created_at'] ),
        );
    }

    private static function guest_token_hash( bool $create ): string {
        $token = isset( $_COOKIE[ self::GUEST_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::GUEST_COOKIE ] ) ) : '';
        if ( ! preg_match( '/^[A-Za-z0-9]{40,96}$/', $token ) ) {
            if ( ! $create || headers_sent() ) {
                return '';
            }
            $token  = wp_generate_password( 64, false, false );
            $path   = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
            $domain = defined( 'COOKIE_DOMAIN' ) ? (string) COOKIE_DOMAIN : '';
            setcookie(
                self::GUEST_COOKIE,
                $token,
                array(
                    'expires'  => 0,
                    'path'     => $path,
                    'domain'   => $domain,
                    'secure'   => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                )
            );
            $_COOKIE[ self::GUEST_COOKIE ] = $token;
        }
        return hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
    }

    private static function rfc3339( ?string $mysql_datetime ): string {
        if ( ! $mysql_datetime || '0000-00-00 00:00:00' === $mysql_datetime ) {
            return '';
        }
        try {
            $date = new DateTimeImmutable( $mysql_datetime, new DateTimeZone( 'UTC' ) );
            return $date->format( DATE_RFC3339 );
        } catch ( Exception ) {
            return '';
        }
    }
}
