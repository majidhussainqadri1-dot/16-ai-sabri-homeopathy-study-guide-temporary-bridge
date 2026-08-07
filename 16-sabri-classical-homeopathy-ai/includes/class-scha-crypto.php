<?php

defined( 'ABSPATH' ) || exit;

/** Authenticated encryption for private prompts, answers and restricted corpus text. */
final class SCHA_Crypto {
    private const PREFIX = 'scha2:';

    public static function encrypt( ?string $plaintext, string $purpose = 'message' ): ?string {
        if ( null === $plaintext || '' === $plaintext ) return $plaintext;
        if ( str_starts_with( $plaintext, self::PREFIX ) ) return $plaintext;
        $key = self::key( $purpose );

        if ( function_exists( 'sodium_crypto_aead_xchacha20poly1305_ietf_encrypt' ) ) {
            $nonce = random_bytes( SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES );
            $cipher = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt( $plaintext, $purpose, $nonce, $key );
            return self::PREFIX . 'sodium:' . self::b64( $nonce . $cipher );
        }

        if ( function_exists( 'openssl_encrypt' ) ) {
            $iv = random_bytes( 12 );
            $tag = '';
            $cipher = openssl_encrypt( $plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $purpose, 16 );
            if ( false !== $cipher ) return self::PREFIX . 'aesgcm:' . self::b64( $iv . $tag . $cipher );
        }

        throw new RuntimeException( 'Authenticated encryption is unavailable.' );
    }

    public static function decrypt( ?string $stored, string $purpose = 'message' ): string {
        if ( null === $stored || '' === $stored ) return '';
        if ( ! str_starts_with( $stored, self::PREFIX ) ) return $stored; // legacy plaintext; migrated lazily.
        $key = self::key( $purpose );
        $payload = substr( $stored, strlen( self::PREFIX ) );
        if ( str_starts_with( $payload, 'sodium:' ) ) {
            $raw = self::unb64( substr( $payload, 7 ) );
            $n = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
            if ( strlen( $raw ) <= $n ) return '';
            $plain = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt( substr( $raw, $n ), $purpose, substr( $raw, 0, $n ), $key );
            return false === $plain ? '' : $plain;
        }
        if ( str_starts_with( $payload, 'aesgcm:' ) ) {
            $raw = self::unb64( substr( $payload, 7 ) );
            if ( strlen( $raw ) <= 28 ) return '';
            $plain = openssl_decrypt( substr( $raw, 28 ), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr( $raw, 0, 12 ), substr( $raw, 12, 16 ), $purpose );
            return false === $plain ? '' : $plain;
        }
        return '';
    }

    public static function is_encrypted( ?string $value ): bool {
        return is_string( $value ) && str_starts_with( $value, self::PREFIX );
    }

    private static function key( string $purpose ): string {
        $material = wp_salt( 'secure_auth' ) . '|' . wp_salt( 'logged_in' ) . '|SCHA|' . $purpose;
        return hash( 'sha256', $material, true );
    }
    private static function b64( string $value ): string { return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' ); }
    private static function unb64( string $value ): string {
        $value = strtr( $value, '-_', '+/' );
        $value .= str_repeat( '=', ( 4 - strlen( $value ) % 4 ) % 4 );
        return (string) base64_decode( $value, true );
    }
}
