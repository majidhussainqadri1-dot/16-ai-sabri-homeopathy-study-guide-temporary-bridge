<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Privacy {
    public static function inspect_and_redact( string $text ): array {
        $findings = array();
        $redacted = $text;
        $patterns = array(
            'email' => '/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/iu',
            'phone' => '/(?<!\d)(?:\+?\d[\d\s().-]{7,}\d)(?!\d)/u',
            'pakistan_cnic' => '/(?<!\d)\d{5}-?\d{7}-?\d(?!\d)/u',
            'credit_card' => '/(?<!\d)(?:\d[ -]*?){13,19}(?!\d)/u',
            'api_key' => '/\b(?:sk|pk|api|token)[-_][A-Za-z0-9_-]{16,}\b/u',
            'password_assignment' => '/\b(password|passwd|pwd|secret)\s*[:=]\s*\S+/iu',
        );

        foreach ( $patterns as $type => $pattern ) {
            if ( preg_match_all( $pattern, $redacted, $matches ) && ! empty( $matches[0] ) ) {
                $findings[ $type ] = count( $matches[0] );
                $redacted = preg_replace( $pattern, '[' . strtoupper( $type ) . '_REDACTED]', $redacted );
            }
        }

        return array(
            'original'   => $text,
            'redacted'   => $redacted,
            'findings'   => $findings,
            'had_sensitive_data' => ! empty( $findings ),
        );
    }

    public static function provider_text( string $text ): array {
        $inspection = self::inspect_and_redact( $text );
        if ( ! SCHA_Settings::get( 'privacy_redaction', true ) && ! $inspection['had_sensitive_data'] ) {
            $inspection['redacted'] = $text;
        }
        return $inspection;
    }

    public static function no_cache_headers(): void {
        nocache_headers();
        header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
        header( 'Referrer-Policy: strict-origin-when-cross-origin', true );
        header( 'X-Content-Type-Options: nosniff', true );
        header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()', true );
    }
}
