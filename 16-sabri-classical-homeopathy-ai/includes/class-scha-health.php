<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Health {
    public static function report(): array {
        global $wpdb;
        $configured_provider = SCHA_Provider_Registry::configured();
        $effective_provider  = SCHA_Provider_Registry::selected();
        $checks = array(
            'plugin_version' => array( 'ok' => true, 'value' => SCHA_VERSION ),
            'plan_version'   => array( 'ok' => true, 'value' => SCHA_PLAN_VERSION ),
            'php'            => array( 'ok' => version_compare( PHP_VERSION, '8.3', '>=' ), 'value' => PHP_VERSION ),
            'wordpress'      => array( 'ok' => version_compare( get_bloginfo( 'version' ), '7.0', '>=' ), 'value' => get_bloginfo( 'version' ) ),
            'database'       => array( 'ok' => SCHA_Database::tables_exist() && SCHA_Database::schema_invariants_hold() && SCHA_Database::SCHEMA_VERSION === (string) get_option( 'scha_schema_version', 'missing' ), 'value' => get_option( 'scha_schema_version', 'missing' ) ),
            'configured_provider' => array( 'ok' => $configured_provider->is_available(), 'value' => $configured_provider->key() ),
            'effective_provider'  => array( 'ok' => $effective_provider->is_available(), 'value' => $effective_provider->key() ),
            'retention_cron' => array( 'ok' => (bool) wp_next_scheduled( 'scha_retention_cron' ), 'value' => wp_next_scheduled( 'scha_retention_cron' ) ?: 0 ),
            'outbox_cron'    => array( 'ok' => (bool) wp_next_scheduled( 'scha_outbox_cron' ), 'value' => wp_next_scheduled( 'scha_outbox_cron' ) ?: 0 ),
            'teacher_cron'   => array( 'ok' => (bool) wp_next_scheduled( 'scha_ai_teacher_cron' ), 'value' => wp_next_scheduled( 'scha_ai_teacher_cron' ) ?: 0 ),
            'four_plan_manifest' => array( 'ok' => true, 'value' => SCHA_Four_Plan_Compliance::GOVERNING_PLANS ),
        );
        $corpus = SCHA_Database::table( 'corpus_items' );
        $outbox = SCHA_Database::table( 'outbox' );
        if ( SCHA_Database::tables_exist() ) {
            $checks['approved_sources'] = array( 'ok' => absint( $wpdb->get_var( "SELECT COUNT(*) FROM $corpus WHERE status='approved'" ) ) > 0, 'value' => absint( $wpdb->get_var( "SELECT COUNT(*) FROM $corpus WHERE status='approved'" ) ) );
            $checks['dead_letters'] = array( 'ok' => 0 === absint( $wpdb->get_var( "SELECT COUNT(*) FROM $outbox WHERE status='dead_letter'" ) ), 'value' => absint( $wpdb->get_var( "SELECT COUNT(*) FROM $outbox WHERE status='dead_letter'" ) ) );
        }
        $ok = ! in_array( false, array_column( $checks, 'ok' ), true );
        return array(
            'ok'        => $ok,
            'status'    => $ok ? 'healthy' : 'attention_required',
            'checks'    => $checks,
            'generated' => gmdate( 'c' ),
            'secrets_exposed' => false,
        );
    }
}
