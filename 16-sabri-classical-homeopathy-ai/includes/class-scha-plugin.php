<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Plugin {
    private static ?self $instance = null;
    private bool $booted = false;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function boot(): void {
        if ( $this->booted ) {
            return;
        }
        $this->booted = true;

        load_plugin_textdomain( SCHA_TEXT_DOMAIN, false, dirname( plugin_basename( SCHA_PLUGIN_FILE ) ) . '/languages' );

        add_action( 'init', array( 'SCHA_Router', 'init' ) );
        add_action( 'rest_api_init', array( 'SCHA_REST_Controller', 'register_routes' ) );
        add_action( 'admin_menu', array( 'SCHA_Admin', 'register_menu' ) );
        add_action( 'admin_init', array( 'SCHA_Admin', 'register_settings' ) );
        add_action( 'admin_init', array( 'SCHA_Privacy_Tools', 'add_policy_content' ) );
        add_action( 'admin_post_scha_corpus_action', array( 'SCHA_Admin', 'handle_corpus_action' ) );
        add_action( 'admin_post_scha_run_evaluation', array( 'SCHA_Admin', 'handle_run_evaluation' ) );
        add_action( 'show_user_profile', array( 'SCHA_Admin', 'render_user_entitlement_fields' ) );
        add_action( 'edit_user_profile', array( 'SCHA_Admin', 'render_user_entitlement_fields' ) );
        add_action( 'personal_options_update', array( 'SCHA_Admin', 'save_user_entitlement_fields' ) );
        add_action( 'edit_user_profile_update', array( 'SCHA_Admin', 'save_user_entitlement_fields' ) );
        add_action( 'wp_enqueue_scripts', array( 'SCHA_Public', 'register_assets' ) );
        add_action( 'admin_enqueue_scripts', array( 'SCHA_Admin', 'enqueue_assets' ) );
        add_action( 'scha_retention_cron', array( 'SCHA_Retention', 'run' ) );
        add_action( 'scha_outbox_cron', array( 'SCHA_Outbox', 'process' ) );
        add_action( 'scha_corpus_sync_cron', array( 'SCHA_Corpus', 'sync_registered_sources' ) );

        add_filter( 'wp_privacy_personal_data_exporters', array( 'SCHA_Privacy_Tools', 'register_exporters' ) );
        add_filter( 'wp_privacy_personal_data_erasers', array( 'SCHA_Privacy_Tools', 'register_erasers' ) );
        add_filter( 'cron_schedules', array( 'SCHA_Scheduler', 'cron_schedules' ) );
        add_filter( 'sabri_platform_routes', array( 'SCHA_Integration', 'register_routes' ) );
        add_filter( 'sabri_global_navigation_items', array( 'SCHA_Integration', 'register_navigation' ) );
        add_filter( 'sabri_search_documents', array( 'SCHA_Integration', 'register_search_documents' ) );
        add_action( 'scha_source_retracted', array( 'SCHA_Corpus', 'handle_source_retracted' ), 10, 3 );

        SCHA_Scheduler::ensure_events();
        SCHA_Database_Migrator::maybe_upgrade();
    }
}
