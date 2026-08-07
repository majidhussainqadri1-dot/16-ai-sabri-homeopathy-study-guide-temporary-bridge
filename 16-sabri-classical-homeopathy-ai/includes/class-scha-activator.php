<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Activator {
    public static function activate(): void {
        if ( version_compare( PHP_VERSION, '8.3', '<' ) ) {
            deactivate_plugins( plugin_basename( SCHA_PLUGIN_FILE ) );
            wp_die( esc_html__( 'Sabri Classical Homeopathy AI requires PHP 8.3 or later.', SCHA_TEXT_DOMAIN ) );
        }
        if ( isset( $GLOBALS['wp_version'] ) && version_compare( (string) $GLOBALS['wp_version'], '7.0', '<' ) ) {
            deactivate_plugins( plugin_basename( SCHA_PLUGIN_FILE ) );
            wp_die( esc_html__( 'Sabri Classical Homeopathy AI requires WordPress 7.0 or later.', SCHA_TEXT_DOMAIN ) );
        }

        $schema = SCHA_Database::install();
        if ( is_wp_error( $schema ) ) {
            deactivate_plugins( plugin_basename( SCHA_PLUGIN_FILE ) );
            wp_die( esc_html( $schema->get_error_message() ) );
        }
        SCHA_Capabilities::install();

        if ( false === get_option( 'scha_settings', false ) ) {
            $defaults = SCHA_Settings::defaults();
            $defaults['teacher_launch_date'] = wp_date( 'Y-m-d' );
            add_option( 'scha_settings', $defaults, '', false );
        }
        if ( false === get_option( 'scha_active_policy_version', false ) ) add_option( 'scha_active_policy_version', SCHA_Policy_Repository::CURRENT_VERSION, '', false );

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
