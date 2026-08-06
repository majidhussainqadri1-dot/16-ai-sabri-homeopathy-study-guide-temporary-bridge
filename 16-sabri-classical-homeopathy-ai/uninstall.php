<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! defined( 'SCHA_PURGE_ON_UNINSTALL' ) || true !== SCHA_PURGE_ON_UNINSTALL ) {
    return;
}

global $wpdb;
foreach ( array( 'sessions', 'messages', 'corpus_items', 'chunks', 'usage', 'feedback', 'policy_versions', 'evaluation_runs', 'audit_log', 'outbox' ) as $name ) {
    $table = $wpdb->prefix . 'scha_' . $name;
    $wpdb->query( "DROP TABLE IF EXISTS `$table`" );
}
foreach ( array( 'scha_settings', 'scha_schema_version', 'scha_active_policy_version' ) as $option ) {
    delete_option( $option );
}
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ('scha_ai_entitlement_status','scha_ai_plan')" );
