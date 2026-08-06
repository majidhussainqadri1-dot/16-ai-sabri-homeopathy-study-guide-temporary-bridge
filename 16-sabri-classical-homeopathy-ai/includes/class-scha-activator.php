<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Activator {
    public static function activate(): void {
        if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
            deactivate_plugins( plugin_basename( SCHA_PLUGIN_FILE ) );
            wp_die( esc_html__( 'Sabri Classical Homeopathy AI requires PHP 8.1 or later.', SCHA_TEXT_DOMAIN ) );
        }

        SCHA_Database::install();
        SCHA_Capabilities::install();

        if ( false === get_option( 'scha_settings', false ) ) {
            add_option( 'scha_settings', SCHA_Settings::defaults(), '', false );
        }

        if ( false === get_option( 'scha_active_policy_version', false ) ) {
            add_option( 'scha_active_policy_version', '1.0.0', '', false );
        }

        SCHA_Policy_Repository::seed_default_policy();
        SCHA_Router::register_rewrite_rules();
        flush_rewrite_rules( false );
        SCHA_Scheduler::ensure_events();
    }

    public static function deactivate(): void {
        SCHA_Scheduler::clear_events();
        flush_rewrite_rules( false );
    }
}
