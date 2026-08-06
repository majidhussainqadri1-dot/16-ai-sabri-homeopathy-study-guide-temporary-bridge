<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'SCHA_TEXT_DOMAIN', 'sabri-classical-homeopathy-ai' );

final class WP_Error {
    public function __construct( public string $code = '', public string $message = '', public array $data = array() ) {}
    public function get_error_message(): string { return $this->message; }
}

function is_wp_error( mixed $value ): bool { return $value instanceof WP_Error; }
function __( string $text, string $domain = '' ): string { return $text; }
function wp_strip_all_tags( string $text ): string { return trim( strip_tags( $text ) ); }
function absint( mixed $value ): int { return abs( (int) $value ); }
function get_option( string $key, mixed $default = false ): mixed { global $scha_test_options; return $scha_test_options[ $key ] ?? $default; }
function wp_parse_args( mixed $args, array $defaults = array() ): array { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function sanitize_text_field( mixed $value ): string { return trim( preg_replace( '/[\r\n\t]+/', ' ', strip_tags( (string) $value ) ) ); }
function sanitize_hex_color( mixed $value ): string|false { return preg_match( '/^#[0-9a-fA-F]{6}$/', (string) $value ) ? strtolower( (string) $value ) : false; }
function esc_url_raw( string $url, array $protocols = array() ): string {
    $url = filter_var( trim( $url ), FILTER_SANITIZE_URL );
    $parts = parse_url( $url );
    if ( ! $parts || empty( $parts['scheme'] ) ) return '';
    if ( $protocols && ! in_array( strtolower( $parts['scheme'] ), $protocols, true ) ) return '';
    return $url;
}
function wp_parse_url( string $url ): array|false { return parse_url( $url ); }
function home_url( string $path = '' ): string { return 'https://sabrihomeopathy.com' . $path; }

final class SCHA_Policy_Repository {
    public static function active(): array { return array( 'version' => '1.0.0', 'rules' => array() ); }
}

$GLOBALS['scha_test_options'] = array( 'scha_settings' => array() );

require_once dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-settings.php';
require_once dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-privacy.php';
require_once dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-prompt-policy.php';
require_once dirname( __DIR__ ) . '/16-sabri-classical-homeopathy-ai/includes/class-scha-citation-validator.php';
