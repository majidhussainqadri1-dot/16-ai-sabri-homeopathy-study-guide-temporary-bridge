<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Provider_Registry {
    public static function selected(): SCHA_Provider_Interface {
        $configured = self::configured();
        if ( $configured->is_available() ) {
            return $configured;
        }
        return self::by_key( 'local' );
    }

    public static function configured(): SCHA_Provider_Interface {
        $providers = self::providers();
        $key = sanitize_key( (string) SCHA_Settings::get( 'provider', 'local' ) );
        $provider = $providers[ $key ] ?? $providers['local'];
        return $provider instanceof SCHA_Provider_Interface ? $provider : $providers['local'];
    }

    public static function by_key( string $key ): SCHA_Provider_Interface {
        $providers = self::providers();
        $provider  = $providers[ sanitize_key( $key ) ] ?? $providers['local'];
        if ( ! $provider instanceof SCHA_Provider_Interface || ! $provider->is_available() ) {
            return $providers['local'];
        }
        return $provider;
    }

    public static function providers(): array {
        $providers = array(
            'local'     => new SCHA_Provider_Local(),
            'bridge'    => new SCHA_Provider_Bridge(),
            'http_json' => new SCHA_Provider_Http_Json(),
            'claude'    => new SCHA_Provider_Claude(),
        );
        return apply_filters( 'scha_ai_providers', $providers );
    }
}
