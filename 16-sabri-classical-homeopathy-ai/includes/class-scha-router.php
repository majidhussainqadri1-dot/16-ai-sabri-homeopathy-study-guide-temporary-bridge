<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Router {
    public static function init(): void {
        self::register_rewrite_rules();
        add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
        add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ) );
        add_action( 'wp_head', array( __CLASS__, 'robots_meta' ), 1 );
    }

    public static function register_rewrite_rules(): void {
        add_rewrite_rule( '^ai/?$', 'index.php?scha_route=home', 'top' );
        add_rewrite_rule( '^ai/session/([A-Za-z0-9-]{36})/?$', 'index.php?scha_route=session&scha_session_id=$matches[1]', 'top' );
        add_rewrite_rule( '^ai/history/?$', 'index.php?scha_route=history', 'top' );
        add_rewrite_rule( '^ai/sources/?$', 'index.php?scha_route=sources', 'top' );
        add_rewrite_rule( '^ai/governance/?$', 'index.php?scha_route=governance', 'top' );
    }

    public static function query_vars( array $vars ): array {
        $vars[] = 'scha_route';
        $vars[] = 'scha_session_id';
        return $vars;
    }

    public static function template_redirect(): void {
        $route = get_query_var( 'scha_route' );
        if ( ! $route ) {
            return;
        }
        $allowed = array( 'home', 'session', 'history', 'sources', 'governance' );
        if ( ! in_array( $route, $allowed, true ) ) {
            return;
        }

        if ( in_array( $route, array( 'session', 'history', 'governance' ), true ) ) {
            SCHA_Privacy::no_cache_headers();
        }
        if ( 'session' === $route ) {
            $session_id = sanitize_text_field( (string) get_query_var( 'scha_session_id' ) );
            $session = SCHA_Session_Service::owned( $session_id );
            if ( is_wp_error( $session ) ) {
                status_header( 404 );
                $route = 'not-found';
            }
        }
        if ( 'history' === $route && ! is_user_logged_in() ) {
            auth_redirect();
            exit;
        }
        if ( 'governance' === $route && ! SCHA_Capabilities::current_user_can_manage() ) {
            status_header( 404 );
            $route = 'not-found';
        }

        SCHA_Public::enqueue_for_route( $route );
        $template = SCHA_PLUGIN_DIR . 'templates/' . $route . '.php';
        if ( is_readable( $template ) ) {
            include $template;
            exit;
        }
    }

    public static function robots_meta(): void {
        $route = get_query_var( 'scha_route' );
        if ( in_array( $route, array( 'session', 'history', 'governance' ), true ) ) {
            echo '<meta name="robots" content="noindex,nofollow,noarchive" />' . "\n";
        }
    }
}
