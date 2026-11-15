<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Database_Migrator {
    public static function maybe_upgrade(): true|WP_Error {
        global $wpdb;
        $current = (string) get_option( 'scha_schema_version', '0' );
        if ( version_compare( $current, SCHA_Database::SCHEMA_VERSION, '>' ) ) {
            return new WP_Error( 'scha_schema_newer_than_code', __( 'The installed database is newer than this File 16 build; do not run older code against newer schema.', SCHA_TEXT_DOMAIN ) );
        }
        if ( $current === SCHA_Database::SCHEMA_VERSION ) return self::verify_current_schema();

        $lock_name = substr( 'scha-schema-' . md5( (string) home_url( '/' ) ), 0, 64 );
        $acquired = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $lock_name, 5 ) );
        if ( 1 !== $acquired ) {
            return new WP_Error( 'scha_schema_upgrade_busy', __( 'Another File 16 schema migration is currently running.', SCHA_TEXT_DOMAIN ) );
        }

        try {
            $current = (string) get_option( 'scha_schema_version', '0' );
            if ( version_compare( $current, SCHA_Database::SCHEMA_VERSION, '>' ) ) {
                return new WP_Error( 'scha_schema_newer_than_code', __( 'The database was upgraded by a newer File 16 build; the current build must not continue.', SCHA_TEXT_DOMAIN ) );
            }
            if ( $current === SCHA_Database::SCHEMA_VERSION ) return self::verify_current_schema();
            $result = SCHA_Database::install();
            if ( is_wp_error( $result ) ) {
                SCHA_Observability::safe_error( $result->get_error_message(), SCHA_Observability::trace_id() );
                return $result;
            }
            set_transient( 'scha_schema_integrity_current', 1, 5 * MINUTE_IN_SECONDS );
            return true;
        } finally {
            $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
        }
    }

    /**
     * Detect same-version partial deployments without paying the cost of a
     * complete SHOW TABLES/SHOW COLUMNS scan on every user request.
     * Integrity is rechecked at least every five minutes per site.
     */
    private static function verify_current_schema(): true|WP_Error {
        if ( 1 === (int) get_transient( 'scha_schema_integrity_current' ) ) return true;
        if ( ! SCHA_Database::tables_exist() || ! SCHA_Database::schema_invariants_hold() ) {
            return new WP_Error(
                'scha_schema_incomplete',
                __( 'The declared File 16 schema version exists, but required tables or columns are missing. AI remains disabled until repaired.', SCHA_TEXT_DOMAIN )
            );
        }
        set_transient( 'scha_schema_integrity_current', 1, 5 * MINUTE_IN_SECONDS );
        return true;
    }
}
