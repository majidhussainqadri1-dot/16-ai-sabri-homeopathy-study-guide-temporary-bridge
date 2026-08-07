<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Observability {
    public static function trace_id(): string {
        return 'scha-' . wp_generate_password( 20, false, false );
    }

    public static function metric( string $name, int $amount = 1 ): void {
        $allowed = array( 'requests', 'answers', 'refusals', 'errors', 'citation_failures', 'rate_limited', 'provider_failures', 'output_policy_failures', 'feedback' );
        if ( ! in_array( $name, $allowed, true ) ) {
            return;
        }
        $day = gmdate( 'Y-m-d' );
        $key = 'scha_metric_' . $day;
        $all = get_option( $key, array() );
        $all = is_array( $all ) ? $all : array();
        $all[ $name ] = absint( $all[ $name ] ?? 0 ) + max( 0, $amount );
        update_option( $key, $all, false );
    }

    public static function audit( string $action, string $object_type, string $object_id, array $context = array(), string $purpose = '', ?string $trace_id = null ): string {
        global $wpdb;
        $trace_id = $trace_id ?: self::trace_id();
        $safe_context = self::sanitize_context( $context );
        $wpdb->insert(
            SCHA_Database::table( 'audit_log' ),
            array(
                'actor_id'    => get_current_user_id(),
                'action'      => sanitize_key( $action ),
                'object_type' => sanitize_key( $object_type ),
                'object_id'   => sanitize_text_field( $object_id ),
                'purpose'     => sanitize_text_field( $purpose ),
                'trace_id'    => $trace_id,
                'context_json'=> wp_json_encode( $safe_context ),
                'created_at'  => current_time( 'mysql', true ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );
        return $trace_id;
    }

    public static function safe_error( Throwable|string $error, string $trace_id ): void {
        self::metric( 'errors' );
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $message = $error instanceof Throwable ? $error->getMessage() : (string) $error;
            error_log( wp_json_encode( array( 'component' => 'scha', 'trace_id' => $trace_id, 'error' => substr( SCHA_Privacy::inspect_and_redact( wp_strip_all_tags( $message ) )['redacted'], 0, 500 ) ) ) );
        }
    }

    public static function sanitize_payload( mixed $value, int $depth = 0 ): mixed {
        if ( $depth > 5 ) return '[truncated]';
        if ( is_array( $value ) ) {
            $safe = array();
            foreach ( array_slice( $value, 0, 100, true ) as $key => $child ) {
                $clean_key = is_int( $key ) ? $key : sanitize_key( (string) $key );
                if ( ! is_int( $clean_key ) && preg_match( '/(?:prompt|content|answer|api[_-]?key|authorization|secret|token|password|cookie|private|patient)/i', (string) $clean_key ) ) {
                    $safe[ $clean_key ] = '[redacted]';
                } else {
                    $safe[ $clean_key ] = self::sanitize_payload( $child, $depth + 1 );
                }
            }
            return $safe;
        }
        if ( is_string( $value ) ) {
            $redacted = SCHA_Privacy::inspect_and_redact( wp_strip_all_tags( $value ) )['redacted'];
            return substr( sanitize_text_field( $redacted ), 0, 500 );
        }
        return is_scalar( $value ) || null === $value ? $value : '[unsupported]';
    }

    private static function sanitize_context( array $context ): array {
        $safe = self::sanitize_payload( $context );
        return is_array( $safe ) ? $safe : array();
    }
}
