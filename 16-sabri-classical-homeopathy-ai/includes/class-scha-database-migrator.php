<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Database_Migrator {
    public static function maybe_upgrade(): void {
        $current = (string) get_option( 'scha_schema_version', '0' );
        if ( version_compare( $current, SCHA_Database::SCHEMA_VERSION, '<' ) ) {
            $lock = get_transient( 'scha_schema_upgrade_lock' );
            if ( $lock ) {
                return;
            }
            set_transient( 'scha_schema_upgrade_lock', wp_generate_uuid4(), 5 * MINUTE_IN_SECONDS );
            try {
                SCHA_Database::install();
            } finally {
                delete_transient( 'scha_schema_upgrade_lock' );
            }
        }
    }
}
