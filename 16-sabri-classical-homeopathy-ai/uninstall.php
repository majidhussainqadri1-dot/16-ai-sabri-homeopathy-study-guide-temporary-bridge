<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

wp_clear_scheduled_hook( 'scha_retention_cron' );
wp_clear_scheduled_hook( 'scha_outbox_cron' );
wp_clear_scheduled_hook( 'scha_corpus_sync_cron' );
wp_clear_scheduled_hook( 'scha_ai_teacher_cron' );

foreach ( array( 'administrator', 'founder', 'sabri_founder' ) as $role_name ) {
    $role = get_role( $role_name );
    if ( $role ) {
        foreach ( array( 'scha_use_ai', 'scha_manage_ai', 'scha_manage_corpus', 'scha_review_ai_safety', 'scha_view_ai_metrics' ) as $capability ) {
            $role->remove_cap( $capability );
        }
    }
}

$purge_allowed = defined( 'SCHA_ALLOW_PURGE' ) && true === SCHA_ALLOW_PURGE && true === (bool) get_option( 'scha_purge_on_uninstall', false );
if ( ! $purge_allowed ) {
    return;
}

global $wpdb;
foreach ( array( 'teacher_posts', 'outbox', 'audit_log', 'evaluation_runs', 'policy_versions', 'feedback', 'usage', 'chunks', 'corpus_items', 'messages', 'sessions' ) as $name ) {
    $table = $wpdb->prefix . 'scha_' . $name;
    $wpdb->query( "DROP TABLE IF EXISTS `$table`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- fixed internal allowlist.
}

$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ('scha_ai_entitlement_status','scha_ai_plan')" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'scha_metric_%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
delete_option( 'scha_settings' );
delete_option( 'scha_schema_version' );
delete_option( 'scha_active_policy_version' );
delete_option( 'scha_purge_on_uninstall' );
