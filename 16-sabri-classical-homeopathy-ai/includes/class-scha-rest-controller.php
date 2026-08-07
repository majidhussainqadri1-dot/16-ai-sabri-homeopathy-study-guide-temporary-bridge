<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_REST_Controller {
    private const NS = 'scha/v1';

    public static function register_routes(): void {
        register_rest_route( self::NS, '/sessions', array(
            array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'create_session' ), 'permission_callback' => '__return_true', 'args' => array( 'mode' => array( 'type' => 'string', 'default' => 'study' ) ) ),
            array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'list_sessions' ), 'permission_callback' => 'is_user_logged_in' ),
        ) );
        register_rest_route( self::NS, '/sessions/(?P<id>[A-Za-z0-9-]{36})', array(
            array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'get_session' ), 'permission_callback' => '__return_true' ),
            array( 'methods' => WP_REST_Server::DELETABLE, 'callback' => array( __CLASS__, 'delete_session' ), 'permission_callback' => '__return_true' ),
        ) );
        register_rest_route( self::NS, '/sessions/(?P<id>[A-Za-z0-9-]{36})/answer', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'answer' ), 'permission_callback' => '__return_true', 'args' => array( 'prompt' => array( 'required' => true, 'type' => 'string' ), 'idempotency_key' => array( 'required' => true, 'type' => 'string' ) ) ) );
        register_rest_route( self::NS, '/sessions/(?P<id>[A-Za-z0-9-]{36})/export', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'export_session' ), 'permission_callback' => '__return_true' ) );
        register_rest_route( self::NS, '/feedback', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'feedback' ), 'permission_callback' => 'is_user_logged_in' ) );
        register_rest_route( self::NS, '/sources', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'sources' ), 'permission_callback' => '__return_true' ) );
        register_rest_route( self::NS, '/usage', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'usage' ), 'permission_callback' => 'is_user_logged_in' ) );
        register_rest_route( self::NS, '/health', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'health' ), 'permission_callback' => static fn(): bool => current_user_can( SCHA_Capabilities::VIEW_METRICS ) ) );
    }

    public static function create_session( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        if ( ! self::valid_nonce( $request ) ) return self::forbidden();
        $check = SCHA_Entitlements::require_active();
        if ( is_wp_error( $check ) ) return $check;
        $session = SCHA_Session_Service::create( SCHA_Entitlements::current(), sanitize_key( (string) ( $request['mode'] ?? 'study' ) ) );
        return is_wp_error( $session ) ? $session : self::private_response( $session, 201 );
    }

    public static function list_sessions( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        if ( ! self::valid_nonce( $request ) ) return self::forbidden();
        return self::private_response( SCHA_Session_Service::list_owned( min( 100, max( 1, absint( $request['limit'] ?: 50 ) ) ) ) );
    }

    public static function get_session( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        if ( ! self::valid_nonce( $request ) ) return self::forbidden();
        $session = SCHA_Session_Service::owned( sanitize_text_field( (string) $request['id'] ) );
        if ( is_wp_error( $session ) ) return $session;
        return self::private_response( array( 'session' => SCHA_Session_Service::public_session( $session ), 'messages' => SCHA_Session_Service::messages( absint( $session['id'] ) ) ) );
    }

    public static function answer( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        if ( ! self::valid_nonce( $request ) ) return self::forbidden();
        $result = SCHA_AI_Service::answer( sanitize_text_field( (string) $request['id'] ), (string) $request['prompt'], sanitize_text_field( (string) $request['idempotency_key'] ) );
        return is_wp_error( $result ) ? $result : self::private_response( $result );
    }

    public static function delete_session( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        if ( ! self::valid_nonce( $request ) ) return self::forbidden();
        $session = SCHA_Session_Service::owned( sanitize_text_field( (string) $request['id'] ) );
        if ( is_wp_error( $session ) ) return $session;
        $deleted = SCHA_Session_Service::delete( $session );
        return is_wp_error( $deleted ) ? $deleted : self::private_response( array( 'deleted' => true ) );
    }

    public static function export_session( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        if ( ! self::valid_nonce( $request ) ) return self::forbidden();
        $session = SCHA_Session_Service::owned( sanitize_text_field( (string) $request['id'] ) );
        return is_wp_error( $session ) ? $session : self::private_response( SCHA_Session_Service::export( $session ) );
    }

    public static function feedback( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        if ( ! self::valid_nonce( $request ) ) return self::forbidden();
        $result = SCHA_Feedback::submit( sanitize_text_field( (string) $request['message_id'] ), sanitize_key( (string) $request['category'] ), sanitize_textarea_field( (string) ( $request['comment'] ?? '' ) ) );
        return is_wp_error( $result ) ? $result : self::private_response( $result, 201 );
    }

    public static function sources( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        if ( is_user_logged_in() ) {
            if ( ! self::valid_nonce( $request ) ) return self::forbidden();
            $approved = SCHA_Account_Context::require_approved();
            if ( is_wp_error( $approved ) ) return $approved;
            $entitlement = SCHA_Entitlements::current();
            return self::private_response( SCHA_Corpus::accessible_catalog( (array) ( $entitlement['access_class'] ?? array( 'public' ) ), 100 ) );
        }
        if ( ! SCHA_Settings::get( 'public_sources_page', false ) ) {
            return new WP_Error( 'scha_sources_not_public', __( 'The public AI source catalogue is not enabled.', SCHA_TEXT_DOMAIN ), array( 'status' => 404 ) );
        }
        return new WP_REST_Response( SCHA_Corpus::public_catalog(), 200 );
    }

    public static function usage( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        if ( ! self::valid_nonce( $request ) ) return self::forbidden();
        $approved = SCHA_Account_Context::require_approved();
        return is_wp_error( $approved ) ? $approved : self::private_response( SCHA_Usage_Ledger::summary( get_current_user_id() ) );
    }

    public static function health( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        if ( ! self::valid_nonce( $request ) ) return self::forbidden();
        return self::private_response( SCHA_Health::report() );
    }

    public static function secure_response_headers( WP_HTTP_Response $response, WP_REST_Server $server, WP_REST_Request $request ): WP_HTTP_Response {
        if ( str_starts_with( $request->get_route(), '/' . self::NS . '/' ) || $request->get_route() === '/' . self::NS ) {
            $response->header( 'Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0' );
            $response->header( 'Pragma', 'no-cache' );
            $response->header( 'X-Robots-Tag', 'noindex, nofollow, noarchive' );
        }
        return $response;
    }

    private static function valid_nonce( WP_REST_Request $request ): bool {
        if ( is_user_logged_in() ) {
            return (bool) wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' );
        }
        return SCHA_Settings::get( 'guest_demo', false ) && SCHA_Guest_Auth::verify_request_token( $request->get_header( 'X-SCHA-Guest-Token' ) );
    }

    private static function private_response( mixed $data, int $status = 200 ): WP_REST_Response {
        $response = new WP_REST_Response( $data, $status );
        $response->header( 'Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0' );
        $response->header( 'Pragma', 'no-cache' );
        $response->header( 'X-Robots-Tag', 'noindex, nofollow, noarchive' );
        return $response;
    }

    private static function forbidden(): WP_Error {
        return new WP_Error( 'scha_rest_forbidden', __( 'The request could not be authorized.', SCHA_TEXT_DOMAIN ), array( 'status' => 403 ) );
    }
}
