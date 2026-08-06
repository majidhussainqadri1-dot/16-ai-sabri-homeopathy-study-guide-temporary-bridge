<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Policy_Repository {
    public static function seed_default_policy(): void {
        global $wpdb;
        $table = SCHA_Database::table( 'policy_versions' );
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE policy_key=%s AND policy_version=%s", 'answer-safety', '1.0.0' ) );
        if ( $exists ) {
            return;
        }

        $rules = array(
            'clinical_authority' => false,
            'citation_required'  => true,
            'web_retrieval'      => false,
            'provider_training'  => false,
            'emergency'          => 'refuse_and_escalate',
            'diagnosis'          => 'refuse_and_educate',
            'prescription'       => 'refuse_and_educate',
            'potency_dosage'     => 'refuse_and_educate',
            'private_data'       => 'redact_or_refuse',
            'prompt_injection'   => 'isolate_and_refuse',
        );

        $wpdb->insert(
            $table,
            array(
                'policy_key'     => 'answer-safety',
                'policy_version' => '1.0.0',
                'rules_json'     => wp_json_encode( $rules ),
                'status'         => 'active',
                'effective_at'   => current_time( 'mysql', true ),
                'approved_by'    => get_current_user_id(),
                'created_at'     => current_time( 'mysql', true ),
            )
        );
    }

    public static function active(): array {
        global $wpdb;
        $table = SCHA_Database::table( 'policy_versions' );
        $row = $wpdb->get_row( "SELECT * FROM $table WHERE policy_key='answer-safety' AND status='active' ORDER BY id DESC LIMIT 1", ARRAY_A );
        if ( ! $row ) {
            return array(
                'version' => '1.0.0',
                'rules'   => array(),
            );
        }
        return array(
            'version' => $row['policy_version'],
            'rules'   => json_decode( (string) $row['rules_json'], true ) ?: array(),
        );
    }
}
