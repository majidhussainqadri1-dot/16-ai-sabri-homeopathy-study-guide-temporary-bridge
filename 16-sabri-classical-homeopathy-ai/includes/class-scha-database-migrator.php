<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Database_Migrator {
    public static function maybe_upgrade(): true|WP_Error {
        global $wpdb;
        $current = (string) get_option( 'scha_schema_version', '0' );
        if ( ! version_compare( $current, SCHA_Database::SCHEMA_VERSION, '<' ) ) return true;

        $lock_name = substr( 'scha-schema-' . md5( (string) home_url( '/' ) ), 0, 64 );
        $acquired = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $lock_name, 5 ) );
        if ( 1 !== $acquired ) {
            return new WP_Error( 'scha_schema_upgrade_busy', __( 'Another File 16 schema migration is currently running.', SCHA_TEXT_DOMAIN ) );
        }

        try {
            $current = (string) get_option( 'scha_schema_version', '0' );
            if ( ! version_compare( $current, SCHA_Database::SCHEMA_VERSION, '<' ) ) return true;
            $result = SCHA_Database::install();
            if ( is_wp_error( $result ) ) {
                SCHA_Observability::safe_error( $result->get_error_message(), SCHA_Observability::trace_id() );
                return $result;
            }
            return true;
        } finally {
            $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
        }
    }
}
