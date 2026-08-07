<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Guest_Auth {
    private const COOKIE = 'scha_guest_session';

    public static function binding_hash( bool $create = false ): string {
        $token = isset( $_COOKIE[ self::COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) ) : '';
        if ( '' === $token && $create && ! headers_sent() ) {
            $token = wp_generate_password( 64, false, false );
            setcookie( self::COOKIE, $token, array(
                'expires'  => time() + DAY_IN_SECONDS,
                'path'     => COOKIEPATH ?: '/',
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ) );
            $_COOKIE[ self::COOKIE ] = $token;
        }
        return '' === $token ? '' : hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
    }

    public static function issue_request_token(): string {
        $binding = self::binding_hash( true );
        if ( '' === $binding ) {
            return '';
        }
        return hash_hmac( 'sha256', $binding . '|' . gmdate( 'Y-m-d-H' ), wp_salt( 'nonce' ) );
    }

    public static function verify_request_token( string $token ): bool {
        $binding = self::binding_hash( false );
        if ( '' === $binding || '' === $token ) {
            return false;
        }
        foreach ( array( 0, -1 ) as $offset ) {
            $hour = gmdate( 'Y-m-d-H', time() + ( $offset * HOUR_IN_SECONDS ) );
            $expected = hash_hmac( 'sha256', $binding . '|' . $hour, wp_salt( 'nonce' ) );
            if ( hash_equals( $expected, $token ) ) {
                return true;
            }
        }
        return false;
    }
}
