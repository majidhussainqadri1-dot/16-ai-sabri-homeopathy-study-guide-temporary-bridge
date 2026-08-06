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
        $out['provider']            = in_array( $input['provider'] ?? 'local', array( 'local', 'bridge', 'http_json' ), true ) ? $input['provider'] : 'local';
        $out['provider_endpoint']   = self::sanitize_https_url( $input['provider_endpoint'] ?? '' );
        $out['provider_model']      = sanitize_text_field( $input['provider_model'] ?? '' );
        $out['bridge_url']          = self::sanitize_bridge_url( $input['bridge_url'] ?? '' );
        $out['retention_days']      = min( 365, max( 1, absint( $input['retention_days'] ?? 30 ) ) );
        $out['max_prompt_chars']    = min( 20000, max( 500, absint( $input['max_prompt_chars'] ?? 4000 ) ) );
        $out['requests_per_minute'] = min( 120, max( 1, absint( $input['requests_per_minute'] ?? 10 ) ) );
        $out['daily_request_quota'] = min( 10000, max( 1, absint( $input['daily_request_quota'] ?? 50 ) ) );
        $out['monthly_cost_budget_micros'] = max( 0, absint( $input['monthly_cost_budget_micros'] ?? 0 ) );
        $out['privacy_redaction']   = ! empty( $input['privacy_redaction'] );
        $out['raw_prompt_logging']  = false;
        $out['public_sources_page'] = ! empty( $input['public_sources_page'] );
        $out['green_primary']       = sanitize_hex_color( $input['green_primary'] ?? '#137a3d' ) ?: '#137a3d';

        $hosts = $input['allowed_provider_hosts'] ?? array();
        if ( is_string( $hosts ) ) {
            $hosts = preg_split( '/[\s,]+/', $hosts );
        }
        $out['allowed_provider_hosts'] = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static function ( $host ): string {
                            $host = strtolower( trim( (string) $host ) );
                            return preg_match( '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $host ) ? $host : '';
                        },
                        is_array( $hosts ) ? $hosts : array()
                    )
                )
            )
        );

        return $out;
    }

    private static function sanitize_https_url( string $url ): string {
        $url = esc_url_raw( trim( $url ), array( 'https' ) );
        if ( '' === $url ) {
            return '';
        }
        $parts = wp_parse_url( $url );
        return isset( $parts['scheme'], $parts['host'] ) && 'https' === strtolower( $parts['scheme'] ) ? $url : '';
    }

    public static function sanitize_bridge_url( string $url ): string {
        $url = self::sanitize_https_url( $url );
        if ( '' === $url ) {
            return '';
        }
        $parts = wp_parse_url( $url );
        $host  = strtolower( $parts['host'] ?? '' );
        $path  = $parts['path'] ?? '';
        if ( ! in_array( $host, array( 'chatgpt.com', 'www.chatgpt.com' ), true ) || ! preg_match( '#^/g/[a-zA-Z0-9_-]+#', $path ) ) {
            return '';
        }
        return $url;
    }
}
