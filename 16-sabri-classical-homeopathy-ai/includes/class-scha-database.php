<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Database {
    public const SCHEMA_VERSION = '2.1.0';

    public static function table( string $name ): string {
        global $wpdb;
        return $wpdb->prefix . 'scha_' . $name;
    }

    public static function install(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $sql = array();

        $sql[] = 'CREATE TABLE ' . self::table( 'sessions' ) . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            public_id char(36) NOT NULL,
            owner_id bigint(20) unsigned NOT NULL DEFAULT 0,
            guest_token_hash char(64) NOT NULL DEFAULT '',
            plan_slug varchar(100) NOT NULL DEFAULT 'single-free-tier',
            role_snapshot varchar(191) NOT NULL DEFAULT '',
            claims_version varchar(100) NOT NULL DEFAULT '',
            assistant_mode varchar(40) NOT NULL DEFAULT 'study',
            locale varchar(20) NOT NULL DEFAULT 'en_US',
            provider varchar(100) NOT NULL DEFAULT 'local',
            model varchar(191) NOT NULL DEFAULT '',
            status varchar(30) NOT NULL DEFAULT 'active',
            legal_hold tinyint(1) unsigned NOT NULL DEFAULT 0,
            retention_until datetime NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            version bigint(20) unsigned NOT NULL DEFAULT 1,
            PRIMARY KEY  (id),
            UNIQUE KEY public_id (public_id),
            KEY owner_status (owner_id,status),
            KEY retention_until (retention_until),
            KEY legal_hold (legal_hold)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . self::table( 'messages' ) . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            public_id char(36) NOT NULL,
            session_id bigint(20) unsigned NOT NULL,
            role varchar(20) NOT NULL,
            content longtext NOT NULL,
            redacted_content longtext NULL,
            encryption_version varchar(20) NOT NULL DEFAULT 'scha2',
            citations longtext NULL,
            safety_category varchar(60) NOT NULL DEFAULT '',
            provider_response_id varchar(191) NOT NULL DEFAULT '',
            idempotency_key varchar(191) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY public_id (public_id),
            UNIQUE KEY session_idempotency (session_id,idempotency_key),
            KEY session_created (session_id,created_at)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . self::table( 'corpus_items' ) . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            public_id char(36) NOT NULL,
            owner_file varchar(50) NOT NULL,
            owner_item_id varchar(191) NOT NULL,
            item_version varchar(100) NOT NULL,
            title text NOT NULL,
            source_url text NULL,
            license_name varchar(191) NOT NULL DEFAULT '',
            approved_use varchar(191) NOT NULL DEFAULT '',
            rights_evidence_id varchar(191) NOT NULL DEFAULT '',
            rights_reviewed_at datetime NULL,
            language varchar(20) NOT NULL DEFAULT 'en',
            access_class varchar(40) NOT NULL DEFAULT 'public',
            status varchar(30) NOT NULL DEFAULT 'draft',
            checksum char(64) NOT NULL,
            source_content longtext NULL,
            chunk_status varchar(30) NOT NULL DEFAULT 'pending',
            approved_by bigint(20) unsigned NOT NULL DEFAULT 0,
            approved_at datetime NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY public_id (public_id),
            UNIQUE KEY owner_version (owner_file,owner_item_id,item_version),
            KEY status_access (status,access_class),
            KEY checksum (checksum)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . self::table( 'chunks' ) . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            item_id bigint(20) unsigned NOT NULL,
            chunk_index int(10) unsigned NOT NULL,
            content longtext NOT NULL,
            token_count int(10) unsigned NOT NULL DEFAULT 0,
            access_class varchar(40) NOT NULL DEFAULT 'public',
            checksum char(64) NOT NULL,
            embedding_version varchar(100) NOT NULL DEFAULT 'lexical-v1',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY item_chunk (item_id,chunk_index),
            KEY item_id (item_id),
            KEY access_class (access_class)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . self::table( 'usage' ) . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            public_id char(36) NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            session_id bigint(20) unsigned NOT NULL DEFAULT 0,
            response_message_id bigint(20) unsigned NOT NULL DEFAULT 0,
            provider varchar(100) NOT NULL DEFAULT '',
            model varchar(191) NOT NULL DEFAULT '',
            request_hash char(64) NOT NULL,
            idempotency_key varchar(191) NOT NULL,
            input_tokens int(10) unsigned NOT NULL DEFAULT 0,
            output_tokens int(10) unsigned NOT NULL DEFAULT 0,
            cost_micros bigint(20) unsigned NOT NULL DEFAULT 0,
            period_key varchar(20) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY public_id (public_id),
            UNIQUE KEY session_idempotency (session_id,idempotency_key),
            KEY user_period (user_id,period_key)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . self::table( 'feedback' ) . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            public_id char(36) NOT NULL,
            message_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            category varchar(60) NOT NULL,
            comment text NULL,
            status varchar(30) NOT NULL DEFAULT 'submitted',
            action_json longtext NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY public_id (public_id),
            KEY message_id (message_id),
            KEY status (status)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . self::table( 'policy_versions' ) . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            policy_key varchar(100) NOT NULL,
            policy_version varchar(50) NOT NULL,
            rules_json longtext NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'draft',
            effective_at datetime NULL,
            approved_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY policy_version (policy_key,policy_version),
            KEY policy_status (policy_key,status)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . self::table( 'evaluation_runs' ) . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            public_id char(36) NOT NULL,
            dataset_version varchar(100) NOT NULL,
            provider varchar(100) NOT NULL,
            model varchar(191) NOT NULL DEFAULT '',
            policy_version varchar(50) NOT NULL,
            metrics_json longtext NULL,
            status varchar(30) NOT NULL DEFAULT 'running',
            created_at datetime NOT NULL,
            completed_at datetime NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY public_id (public_id),
            KEY status (status)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . self::table( 'audit_log' ) . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
            action varchar(100) NOT NULL,
            object_type varchar(60) NOT NULL,
            object_id varchar(191) NOT NULL DEFAULT '',
            purpose varchar(191) NOT NULL DEFAULT '',
            trace_id varchar(64) NOT NULL,
            context_json longtext NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY object_lookup (object_type,object_id),
            KEY actor_created (actor_id,created_at),
            KEY trace_id (trace_id)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . self::table( 'outbox' ) . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_name varchar(120) NOT NULL,
            event_version varchar(20) NOT NULL DEFAULT 'v1',
            aggregate_type varchar(60) NOT NULL,
            aggregate_id varchar(191) NOT NULL,
            payload_json longtext NOT NULL,
            dedupe_key varchar(191) NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'pending',
            attempts int(10) unsigned NOT NULL DEFAULT 0,
            available_at datetime NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY dedupe_key (dedupe_key),
            KEY status_available (status,available_at)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . self::table( 'teacher_posts' ) . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            public_id char(36) NOT NULL,
            schedule_key varchar(191) NOT NULL,
            slot_date date NOT NULL,
            slot_time time NOT NULL,
            category varchar(60) NOT NULL DEFAULT 'foundations',
            title text NOT NULL,
            content longtext NOT NULL,
            citations longtext NULL,
            provider varchar(100) NOT NULL DEFAULT '',
            model varchar(191) NOT NULL DEFAULT '',
            risk varchar(20) NOT NULL DEFAULT 'standard',
            status varchar(30) NOT NULL DEFAULT 'queued',
            review_required tinyint(1) unsigned NOT NULL DEFAULT 1,
            reviewed_by bigint(20) unsigned NOT NULL DEFAULT 0,
            reviewed_at datetime NULL,
            published_object_id varchar(191) NOT NULL DEFAULT '',
            published_url text NULL,
            attempts int(10) unsigned NOT NULL DEFAULT 0,
            available_at datetime NOT NULL,
            error_code varchar(100) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            version bigint(20) unsigned NOT NULL DEFAULT 1,
            PRIMARY KEY  (id),
            UNIQUE KEY public_id (public_id),
            UNIQUE KEY schedule_key (schedule_key),
            KEY status_available (status,available_at),
            KEY slot_date (slot_date)
        ) $charset;";

        foreach ( $sql as $statement ) {
            dbDelta( $statement );
        }

        update_option( 'scha_schema_version', self::SCHEMA_VERSION, false );
    }

    public static function tables_exist(): bool {
        global $wpdb;
        foreach ( array( 'sessions', 'messages', 'corpus_items', 'chunks', 'usage', 'feedback', 'policy_versions', 'evaluation_runs', 'audit_log', 'outbox', 'teacher_posts' ) as $name ) {
            $table = self::table( $name );
            if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
                return false;
            }
        }
        return true;
    }
}
