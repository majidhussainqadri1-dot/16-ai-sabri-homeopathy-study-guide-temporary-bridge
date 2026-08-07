<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Settings {
    public static function defaults(): array {
        return array(
            'enabled'                    => true,
            'guest_demo'                 => false,
            'provider'                   => 'local',
            'provider_endpoint'          => '',
            'provider_model'             => '',
            'allowed_provider_hosts'     => array(),
            'bridge_url'                 => '',
            'retention_days'             => 30,
            'max_prompt_chars'           => 4000,
            'requests_per_minute'        => 10,
            'daily_request_quota'        => 50,
            'monthly_cost_budget_micros' => 0,
            'privacy_redaction'          => true,
            'raw_prompt_logging'         => false,
            'public_sources_page'        => true,
            'green_primary'              => '#137a3d',
            'teacher_enabled'            => false,
            'teacher_provider'           => 'claude',
            'teacher_model'              => '',
            'teacher_slots'              => array( '08:00', '12:00', '16:00', '20:00' ),
            'teacher_human_review_days'  => 30,
            'teacher_auto_publish'       => false,
            'teacher_low_risk_categories'=> array( 'materia-medica', 'philosophy', 'history', 'study-method' ),
            'teacher_launch_date'        => '',
            'teacher_daily_budget_micros'=> 0,
            'low_bandwidth_default'      => false,
        );
    }

    public static function all(): array {
        $saved = get_option( 'scha_settings', array() );
        return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
    }

    public static function get( string $key, mixed $default = null ): mixed {
        $all = self::all();
        return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
    }

    public static function sanitize( mixed $input ): array {
        $input = is_array( $input ) ? $input : array();
        $out   = self::defaults();

        $out['enabled']             = ! empty( $input['enabled'] );
        $out['guest_demo']          = ! empty( $input['guest_demo'] );
        $out['provider']            = in_array( $input['provider'] ?? 'local', array( 'local', 'bridge', 'http_json', 'claude' ), true ) ? $input['provider'] : 'local';
        $out['provider_endpoint']   = self::sanitize_https_url( $input['provider_endpoint'] ?? '' );
        $out['provider_model']      = sanitize_text_field( $input['provider_model'] ?? '' );
        $out['bridge_url']          = self::sanitize_bridge_url( $input['bridge_url'] ?? '' );
        $out['retention_days']      = min( 365, max( 1, absint( $input['retention_days'] ?? 30 ) ) );
        $out['max_prompt_chars']    = min( 20000, max( 500, absint( $input['max_prompt_chars'] ?? 4000 ) ) );
        $out['requests_per_minute'] = min( 120, max( 1, absint( $input['requests_per_minute'] ?? 10 ) ) );
        $out['daily_request_quota'] = min( 10000, max( 1, absint( $input['daily_request_quota'] ?? 50 ) ) );
        $out['monthly_cost_budget_micros'] = max( 0, absint( $input['monthly_cost_budget_micros'] ?? 0 ) );
        $out['privacy_redaction']   = true; // External-provider transport redaction is a non-disableable privacy invariant.
        $out['raw_prompt_logging']  = false;
        $out['public_sources_page'] = ! empty( $input['public_sources_page'] );
        $out['green_primary']       = sanitize_hex_color( $input['green_primary'] ?? '#137a3d' ) ?: '#137a3d';
        $out['teacher_enabled']     = ! empty( $input['teacher_enabled'] );
        $out['teacher_provider']    = in_array( $input['teacher_provider'] ?? 'claude', array( 'claude', 'http_json', 'local' ), true ) ? $input['teacher_provider'] : 'claude';
        $out['teacher_model']       = sanitize_text_field( $input['teacher_model'] ?? '' );
        $out['teacher_slots']       = self::sanitize_slots( $input['teacher_slots'] ?? self::defaults()['teacher_slots'] );
        $out['teacher_human_review_days'] = min( 365, max( 30, absint( $input['teacher_human_review_days'] ?? 30 ) ) );
        $out['teacher_auto_publish'] = ! empty( $input['teacher_auto_publish'] );
        $out['teacher_low_risk_categories'] = self::sanitize_keys( $input['teacher_low_risk_categories'] ?? self::defaults()['teacher_low_risk_categories'] );
        $out['teacher_launch_date'] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $input['teacher_launch_date'] ?? '' ) ) ? (string) $input['teacher_launch_date'] : '';
        $out['teacher_daily_budget_micros'] = max( 0, absint( $input['teacher_daily_budget_micros'] ?? 0 ) );
        $out['low_bandwidth_default'] = ! empty( $input['low_bandwidth_default'] );

        $hosts = $input['allowed_provider_hosts'] ?? array();
        if ( is_string( $hosts ) ) $hosts = preg_split( '/[\s,]+/', $hosts );
        $out['allowed_provider_hosts'] = array_values( array_unique( array_filter( array_map( array( __CLASS__, 'sanitize_host' ), is_array( $hosts ) ? $hosts : array() ) ) ) );
        return $out;
    }

    private static function sanitize_slots( mixed $slots ): array {
        if ( is_string( $slots ) ) $slots = preg_split( '/[\s,]+/', $slots );
        $valid = array();
        foreach ( is_array( $slots ) ? $slots : array() as $slot ) {
            $slot = trim( (string) $slot );
            if ( preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $slot ) ) $valid[] = $slot;
        }
        $valid = array_values( array_unique( $valid ) );
        return count( $valid ) === 4 ? $valid : self::defaults()['teacher_slots'];
    }

    private static function sanitize_keys( mixed $values ): array {
        if ( is_string( $values ) ) $values = preg_split( '/[\s,]+/', $values );
        return array_values( array_unique( array_filter( array_map( 'sanitize_key', is_array( $values ) ? $values : array() ) ) ) );
    }

    private static function sanitize_host( mixed $host ): string {
        $host = strtolower( trim( (string) $host ) );
        return preg_match( '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $host ) ? $host : '';
    }

    private static function sanitize_https_url( string $url ): string {
        $url = esc_url_raw( trim( $url ), array( 'https' ) );
        if ( '' === $url ) return '';
        $parts = wp_parse_url( $url );
        return isset( $parts['scheme'], $parts['host'] ) && 'https' === strtolower( $parts['scheme'] ) ? $url : '';
    }

    public static function sanitize_bridge_url( string $url ): string {
        $url = self::sanitize_https_url( $url );
        if ( '' === $url ) return '';
        $parts = wp_parse_url( $url );
        $host  = strtolower( $parts['host'] ?? '' );
        $path  = $parts['path'] ?? '';
        if ( ! in_array( $host, array( 'chatgpt.com', 'www.chatgpt.com' ), true ) || ! preg_match( '#^/g/[a-zA-Z0-9_-]+#', $path ) ) return '';
        return $url;
    }
}
