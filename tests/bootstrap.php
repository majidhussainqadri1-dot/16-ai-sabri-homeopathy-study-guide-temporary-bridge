<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'SCHA_TEXT_DOMAIN', 'sabri-classical-homeopathy-ai' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'MINUTE_IN_SECONDS', 60 );

function __( string $text, ?string $domain = null ): string { return $text; }
function wp_strip_all_tags( string $text ): string { return strip_tags( $text ); }
function wp_salt( string $scheme = 'auth' ): string { return hash( 'sha256', 'test-salt-' . $scheme ); }
function absint( mixed $value ): int { return abs( (int) $value ); }
function sanitize_key( string $key ): string { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ) ?? ''; }
function sanitize_text_field( string $text ): string { return trim( strip_tags( $text ) ); }
function esc_url_raw( string $url ): string { return filter_var( $url, FILTER_SANITIZE_URL ) ?: ''; }
function home_url( string $path = '' ): string { return 'https://example.test' . $path; }
function wp_json_encode( mixed $value, int $flags = 0 ): string|false { return json_encode( $value, $flags ); }
function wp_generate_password( int $length = 12, bool $special_chars = true, bool $extra_special_chars = false ): string { return str_repeat( 'x', $length ); }

final class SCHA_Settings {
    public static function get( string $key, mixed $default = null ): mixed { return $default; }
}
final class SCHA_Policy_Repository {
    public static function active(): array { return array( 'version' => '2.2.0' ); }
}

require_once dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-prompt-policy.php';
require_once dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-output-policy.php';
require_once dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-crypto.php';
require_once dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-privacy.php';
require_once dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-observability.php';
require_once dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-citation-validator.php';
