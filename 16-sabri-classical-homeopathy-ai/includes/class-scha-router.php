<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Router {
    public static function init(): void {
        self::register_rewrite_rules();
        add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
        add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ) );
        add_filter( 'wp_robots', array( __CLASS__, 'robots' ) );
        add_action( 'send_headers', array( __CLASS__, 'security_headers' ) );
    }

    public static function register_rewrite_rules(): void {
        add_rewrite_rule( '^ai/?$', 'index.php?scha_route=home', 'top' );
        add_rewrite_rule( '^ai/session/([A-Za-z0-9-]{36})/?$', 'index.php?scha_route=session&scha_session_id=$matches[1]', 'top' );
        add_rewrite_rule( '^ai/history/?$', 'index.php?scha_route=history', 'top' );
        add_rewrite_rule( '^ai/sources/?$', 'index.php?scha_route=sources', 'top' );
        add_rewrite_rule( '^ai/accessibility/?$', 'index.php?scha_route=accessibility', 'top' );
        add_rewrite_rule( '^ai/governance/?$', 'index.php?scha_route=governance', 'top' );
    }

    public static function query_vars( array $vars ): array {
        $vars[] = 'scha_route'; $vars[] = 'scha_session_id'; return $vars;
    }

    public static function template_redirect(): void {
        $route = sanitize_key( (string) get_query_var( 'scha_route' ) );
        if ( ! $route || ! in_array( $route, array( 'home', 'session', 'history', 'sources', 'accessibility', 'governance' ), true ) ) return;

        if ( in_array( $route, array( 'session', 'history', 'governance' ), true ) ) SCHA_Privacy::no_cache_headers();
        if ( 'session' === $route ) {
            $session = SCHA_Session_Service::owned( sanitize_text_field( (string) get_query_var( 'scha_session_id' ) ) );
            if ( is_wp_error( $session ) ) {
                status_header( 410 === absint( $session->get_error_data()['status'] ?? 0 ) ? 410 : 404 );
                $route = 'not-found';
            }
        }
        if ( 'history' === $route && ! is_user_logged_in() ) { auth_redirect(); exit; }
        if ( 'governance' === $route && ! SCHA_Capabilities::current_user_can_manage() ) { status_header( 404 ); $route = 'not-found'; }

        SCHA_Public::enqueue_for_route( $route );
        $template = SCHA_PLUGIN_DIR . 'templates/' . $route . '.php';
        if ( is_readable( $template ) ) { include $template; exit; }
    }

    public static function robots( array $robots ): array {
        $route = sanitize_key( (string) get_query_var( 'scha_route' ) );
        if ( in_array( $route, array( 'session', 'history', 'governance', 'not-found' ), true ) ) {
            $robots['noindex'] = true; $robots['nofollow'] = true; $robots['noarchive'] = true;
        }
        return $robots;
    }

    public static function security_headers(): void {
        $route = sanitize_key( (string) get_query_var( 'scha_route' ) );
        if ( ! $route ) return;
        header( 'Referrer-Policy: strict-origin-when-cross-origin' );
        header( 'X-Content-Type-Options: nosniff' );
        header( 'Permissions-Policy: microphone=(), camera=(), geolocation=()' );
        if ( in_array( $route, array( 'session', 'history', 'governance', 'not-found' ), true ) ) {
            header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
            header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true );
        }
    }
}
