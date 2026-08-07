<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Policy_Repository {
    public const CURRENT_VERSION = '2.2.0';

    public static function seed_default_policy(): void {
        global $wpdb;
        $table = SCHA_Database::table( 'policy_versions' );
        $existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE policy_key=%s AND policy_version=%s LIMIT 1", 'answer-safety', self::CURRENT_VERSION ), ARRAY_A );

        $rules = array(
            'clinical_authority' => false,
            'citation_required'  => true,
            'citation_coverage_required' => true,
            'web_retrieval'      => false,
            'provider_training'  => false,
            'external_redaction' => true,
            'corpus_sensitive_personal_data' => false,
            'one_complete_free_tier' => true,
            'donor_advantage'    => false,
            'emergency'          => 'refuse_and_escalate',
            'diagnosis'          => 'refuse_and_educate',
            'prescription'       => 'refuse_and_educate',
            'potency_dosage'     => 'refuse_and_educate',
            'private_data'       => 'redact_or_refuse',
            'prompt_injection'   => 'isolate_and_refuse',
            'output_gate'        => 'withhold_on_failure',
            'teacher_first_review_days_minimum' => 30,
        );

        if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
            SCHA_Observability::safe_error( 'Policy transaction could not start.', SCHA_Observability::trace_id() );
            return;
        }
        try {
            if ( false === $wpdb->query( $wpdb->prepare( "UPDATE $table SET status='superseded' WHERE policy_key=%s AND status='active' AND policy_version<>%s", 'answer-safety', self::CURRENT_VERSION ) ) ) {
                throw new RuntimeException( 'Previous policy versions could not be superseded.' );
            }

            if ( $existing ) {
                $updated = $wpdb->update(
                    $table,
                    array( 'rules_json' => wp_json_encode( $rules ), 'status' => 'active', 'effective_at' => current_time( 'mysql', true ) ),
                    array( 'id' => $existing['id'] )
                );
                if ( false === $updated ) throw new RuntimeException( 'Current policy version could not be activated.' );
            } else {
                $inserted = $wpdb->insert(
                    $table,
                    array(
                        'policy_key'     => 'answer-safety',
                        'policy_version' => self::CURRENT_VERSION,
                        'rules_json'     => wp_json_encode( $rules ),
                        'status'         => 'active',
                        'effective_at'   => current_time( 'mysql', true ),
                        'approved_by'    => get_current_user_id(),
                        'created_at'     => current_time( 'mysql', true ),
                    )
                );
                if ( ! $inserted || ! $wpdb->insert_id ) throw new RuntimeException( 'Policy insert failed.' );
            }

            if ( false === $wpdb->query( 'COMMIT' ) ) throw new RuntimeException( 'Policy transaction could not be committed.' );
            update_option( 'scha_active_policy_version', self::CURRENT_VERSION, false );
        } catch ( Throwable $e ) {
            $wpdb->query( 'ROLLBACK' );
            SCHA_Observability::safe_error( $e, SCHA_Observability::trace_id() );
        }
    }

    public static function active(): array {
        global $wpdb;
        $table = SCHA_Database::table( 'policy_versions' );
        $row = $wpdb->get_row( "SELECT * FROM $table WHERE policy_key='answer-safety' AND status='active' ORDER BY id DESC LIMIT 1", ARRAY_A );
        if ( ! $row ) return array( 'version' => self::CURRENT_VERSION, 'rules' => array() );
        return array( 'version' => $row['policy_version'], 'rules' => json_decode( (string) $row['rules_json'], true ) ?: array() );
    }
}
