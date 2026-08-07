<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Public {
    public static function register_assets(): void {
        wp_register_style( 'scha-public', SCHA_PLUGIN_URL . 'assets/css/public.css', array(), SCHA_VERSION );
        wp_register_script( 'scha-public', SCHA_PLUGIN_URL . 'assets/js/public.js', array(), SCHA_VERSION, true );
    }

    public static function enqueue_for_route( string $route ): void {
        self::register_assets();
        wp_enqueue_style( 'scha-public' );
        wp_enqueue_script( 'scha-public' );
        $session_id = 'session' === $route ? sanitize_text_field( (string) get_query_var( 'scha_session_id' ) ) : '';
        wp_localize_script( 'scha-public', 'SCHA_APP', array(
            'route'       => $route,
            'sessionId'   => $session_id,
            'restRoot'    => esc_url_raw( rest_url( 'scha/v1/' ) ),
            'nonce'       => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
            'guestToken'  => is_user_logged_in() ? '' : SCHA_Guest_Auth::issue_request_token(),
            'homeUrl'     => esc_url_raw( home_url( '/ai/' ) ),
            'historyUrl'  => esc_url_raw( home_url( '/ai/history/' ) ),
            'loginUrl'    => esc_url_raw( wp_login_url( home_url( '/ai/' ) ) ),
            'isLoggedIn'  => is_user_logged_in(),
            'maxPrompt'   => absint( SCHA_Settings::get( 'max_prompt_chars', 4000 ) ),
            'lowBandwidth'=> (bool) SCHA_Settings::get( 'low_bandwidth_default', false ),
            'strings'     => array(
                'working' => __( 'Working…', SCHA_TEXT_DOMAIN ),
                'error'   => __( 'The request could not be completed.', SCHA_TEXT_DOMAIN ),
                'deleted' => __( 'Session deleted.', SCHA_TEXT_DOMAIN ),
                'empty'   => __( 'Please enter a question.', SCHA_TEXT_DOMAIN ),
                'you'     => __( 'You', SCHA_TEXT_DOMAIN ),
                'assistant'=> __( 'Sabri AI', SCHA_TEXT_DOMAIN ),
                'citations'=> __( 'Citations', SCHA_TEXT_DOMAIN ),
                'feedback'=> __( 'Feedback received.', SCHA_TEXT_DOMAIN ),
                'noMessages'=> __( 'No question has been asked in this session.', SCHA_TEXT_DOMAIN ),
                'delivered'=> __( 'Answer delivered.', SCHA_TEXT_DOMAIN ),
                'deleteConfirm'=> __( 'Delete this session and schedule its dependent data for erasure?', SCHA_TEXT_DOMAIN ),
                'noSessions'=> __( 'No AI sessions yet. Create a governed session from the AI home page.', SCHA_TEXT_DOMAIN ),
                'open' => __( 'Open', SCHA_TEXT_DOMAIN ),
            ),
        ) );
    }

    public static function page_header( string $title, string $description = '' ): void {
        echo '<header class="scha-hero"><div class="scha-icon" aria-hidden="true">✦</div><div><h1>' . esc_html( $title ) . '</h1>';
        if ( $description ) echo '<p>' . esc_html( $description ) . '</p>';
        echo '</div></header>';
    }

    public static function nav(): void {
        echo '<nav class="scha-nav" aria-label="' . esc_attr__( 'AI navigation', SCHA_TEXT_DOMAIN ) . '">';
        echo '<a href="' . esc_url( home_url( '/ai/' ) ) . '">' . esc_html__( 'AI Home', SCHA_TEXT_DOMAIN ) . '</a>';
        echo '<a href="' . esc_url( home_url( '/ai/history/' ) ) . '">' . esc_html__( 'History', SCHA_TEXT_DOMAIN ) . '</a>';
        echo '<a href="' . esc_url( home_url( '/ai/sources/' ) ) . '">' . esc_html__( 'Approved Sources', SCHA_TEXT_DOMAIN ) . '</a>';
        echo '<a href="' . esc_url( home_url( '/ai/accessibility/' ) ) . '">' . esc_html__( 'Accessibility', SCHA_TEXT_DOMAIN ) . '</a>';
        if ( SCHA_Capabilities::current_user_can_manage() ) echo '<a href="' . esc_url( home_url( '/ai/governance/' ) ) . '">' . esc_html__( 'Governance', SCHA_TEXT_DOMAIN ) . '</a>';
        echo '</nav>';
    }

    public static function back_home_controls(): void {
        $context = array( 'owner_file' => 16, 'route' => sanitize_key( (string) get_query_var( 'scha_route' ) ), 'fallback_home' => home_url( '/ai/' ) );
        $owner_markup = apply_filters( 'sabri_file20_context_controls_markup_v1', '', $context );
        if ( is_string( $owner_markup ) && '' !== trim( $owner_markup ) ) {
            echo wp_kses_post( $owner_markup );
            return;
        }
        if ( has_action( 'sabri_render_back_home_controls' ) ) {
            do_action( 'sabri_render_back_home_controls', $context );
            return;
        }
        $fallback = home_url( '/ai/' );
        $referer = wp_get_referer();
        $back_url = $fallback;
        if ( $referer ) {
            $home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
            $ref_host = strtolower( (string) wp_parse_url( $referer, PHP_URL_HOST ) );
            if ( '' !== $home_host && hash_equals( $home_host, $ref_host ) ) $back_url = wp_validate_redirect( $referer, $fallback );
        }
        echo '<div class="scha-context-nav"><a class="scha-button scha-button-quiet" href="' . esc_url( $back_url ) . '"><span aria-hidden="true" class="scha-dir-icon">←</span> ' . esc_html__( 'Back', SCHA_TEXT_DOMAIN ) . '</a><a class="scha-button scha-button-quiet" href="' . esc_url( home_url( '/' ) ) . '">⌂ ' . esc_html__( 'Home', SCHA_TEXT_DOMAIN ) . '</a></div>';
    }

    public static function html_language_attributes(): string {
        $locale = determine_locale();
        $lang = str_replace( '_', '-', $locale );
        $rtl = is_rtl() || str_starts_with( strtolower( $lang ), 'ur' ) || str_starts_with( strtolower( $lang ), 'ar' );
        return 'lang="' . esc_attr( $lang ) . '" dir="' . ( $rtl ? 'rtl' : 'ltr' ) . '"';
    }
}
