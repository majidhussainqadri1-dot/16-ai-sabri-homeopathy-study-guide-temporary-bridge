<?php

defined( 'ABSPATH' ) || exit;

/**
 * Governed institutional AI Teacher publisher.
 *
 * File 16 owns generation, source grounding, provider/cost policy and review state.
 * File 21/22 own publication and content workflow. This class never creates a
 * duplicate WordPress post or grants itself doctor/human verification.
 */
final class SCHA_AI_Teacher {
    private const ACCOUNT_ID = 'scha-ai-homeopathy-teacher';
    private const CATEGORIES = array( 'materia-medica', 'philosophy', 'history', 'study-method' );

    public static function institutional_profile( array $profiles ): array {
        $profiles[ self::ACCOUNT_ID ] = array(
            'id'                   => self::ACCOUNT_ID,
            'display_name'         => __( 'AI Homeopathy Teacher', SCHA_TEXT_DOMAIN ),
            'alternate_name'       => __( 'Sabri AI Teacher', SCHA_TEXT_DOMAIN ),
            'account_class'        => 'institutional-ai-teacher',
            'is_human'             => false,
            'is_verified_doctor'   => false,
            'clinical_authority'   => false,
            'labels'               => array( __( 'AI-generated', SCHA_TEXT_DOMAIN ), __( 'Human-governed', SCHA_TEXT_DOMAIN ), __( 'Source-linked', SCHA_TEXT_DOMAIN ) ),
            'provider_disclosure'  => __( 'Powered by Claude AI when configured, with a governed local fallback.', SCHA_TEXT_DOMAIN ),
            'canonical_url'        => home_url( '/ai/' ),
            'publication_owner'    => 'File 21 / File 22',
            'generation_owner'     => 'File 16',
        );
        return $profiles;
    }

    public static function reconcile(): void {
        if ( ! SCHA_Settings::get( 'teacher_enabled', false ) ) {
            return;
        }
        self::enqueue_daily_slots();
        self::process_queue();
        self::process_publication_queue();
    }

    public static function enqueue_daily_slots( ?DateTimeImmutable $now = null ): int {
        global $wpdb;

        if ( ! SCHA_Settings::get( 'teacher_enabled', false ) ) {
            return 0;
        }
        $timezone = wp_timezone();
        $now = $now ?: new DateTimeImmutable( 'now', $timezone );
        $date = $now->format( 'Y-m-d' );
        $slots = SCHA_Settings::get( 'teacher_slots', array() );
        $categories = self::CATEGORIES;
        $table = SCHA_Database::table( 'teacher_posts' );
        $created = 0;

        foreach ( array_values( $slots ) as $index => $slot ) {
            $slot = (string) $slot;
            if ( ! preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $slot ) ) {
                continue;
            }
            $schedule_key = $date . '|' . $slot;
            $local_due = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $date . ' ' . $slot, $timezone );
            if ( ! $local_due ) {
                continue;
            }
            $utc_due = $local_due->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
            $inserted = $wpdb->query(
                $wpdb->prepare(
                    "INSERT IGNORE INTO $table (public_id,schedule_key,slot_date,slot_time,category,title,content,citations,provider,model,risk,status,review_required,attempts,available_at,error_code,created_at,updated_at,version) VALUES (%s,%s,%s,%s,%s,'','',%s,'','','standard','queued',1,0,%s,'',%s,%s,1)",
                    wp_generate_uuid4(),
                    $schedule_key,
                    $date,
                    $slot . ':00',
                    $categories[ $index % count( $categories ) ],
                    '[]',
                    $utc_due,
                    current_time( 'mysql', true ),
                    current_time( 'mysql', true )
                )
            );
            if ( $inserted ) {
                ++$created;
                SCHA_Outbox::publish( 'AITeacherSlotQueued', 'ai_teacher_post', $schedule_key, array( 'schedule_key' => $schedule_key, 'category' => $categories[ $index % count( $categories ) ] ), 'teacher-slot-' . $schedule_key );
            }
        }
        return $created;
    }

    public static function process_queue( int $limit = 4 ): int {
        global $wpdb;
        if ( ! SCHA_Settings::get( 'teacher_enabled', false ) ) {
            return 0;
        }
        $table = SCHA_Database::table( 'teacher_posts' );
        self::recover_stale_generation_claims( $table );
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE status IN ('queued','failed') AND attempts < 5 AND available_at <= %s ORDER BY available_at ASC,id ASC LIMIT %d",
                current_time( 'mysql', true ),
                min( 8, max( 1, $limit ) )
            ),
            ARRAY_A
        ) ?: array();
        $processed = 0;
        foreach ( $rows as $row ) {
            $claimed = $wpdb->query( $wpdb->prepare( "UPDATE $table SET status='generating',attempts=attempts+1,updated_at=%s,version=version+1 WHERE id=%d AND status IN ('queued','failed')", current_time( 'mysql', true ), $row['id'] ) );
            if ( ! $claimed ) {
                continue;
            }
            self::generate( array_merge( $row, array( 'attempts' => absint( $row['attempts'] ) + 1 ) ) );
            ++$processed;
        }
        return $processed;
    }

    public static function generate( array $row ): true|WP_Error {
        global $wpdb;
        $table = SCHA_Database::table( 'teacher_posts' );
        $trace_id = SCHA_Observability::trace_id();
        $category = sanitize_key( (string) $row['category'] );
        $query = self::topic_query( $category, (string) $row['slot_date'] );
        $entitlement = array( 'role' => 'founder', 'access_class' => array( 'public', 'subscriber', 'doctor', 'founder' ) );
        $sources = SCHA_Retrieval::search( $query, $entitlement, 8 );
        if ( empty( $sources ) ) {
            return self::fail( $row, 'insufficient_evidence', __( 'No approved source set was available for this scheduled educational post.', SCHA_TEXT_DOMAIN ) );
        }

        $provider_key = sanitize_key( (string) SCHA_Settings::get( 'teacher_provider', 'claude' ) );
        $provider = SCHA_Provider_Registry::by_key( $provider_key );
        $budget = self::teacher_budget_check();
        if ( is_wp_error( $budget ) ) {
            return self::fail( $row, 'daily_budget', $budget->get_error_message() );
        }

        $prompt = self::teacher_prompt( $category );
        $result = $provider->generate( array(
            'prompt'            => $prompt,
            'sources'           => $sources,
            'trace_id'          => $trace_id,
            'policy_version'    => SCHA_Policy_Repository::active()['version'],
            'locale'            => get_locale(),
            'role'              => 'institutional-ai-teacher',
            'assistant_mode'    => 'creator_assistant',
            'mode_instruction'  => SCHA_Assistant_Modes::instruction( 'creator_assistant' ),
            'model'             => (string) SCHA_Settings::get( 'teacher_model', '' ),
            'clinical_authority'=> false,
        ) );
        if ( is_wp_error( $result ) ) {
            return self::fail( $row, 'provider_failure', $result->get_error_message() );
        }

        $output = SCHA_Output_Policy::validate( (string) ( $result['answer'] ?? '' ) );
        if ( ! $output['valid'] ) {
            return self::fail( $row, 'unsafe_output', (string) $output['category'] );
        }
        $governance = SCHA_Governance_Gates::teacher_draft( (string) $result['answer'], $sources, array( 'category' => $category, 'schedule_key' => $row['schedule_key'] ) );
        if ( ! $governance['rights_ok'] ) {
            return self::fail( $row, 'rights_gate', __( 'One or more sources lacks an approved rights or licence record.', SCHA_TEXT_DOMAIN ) );
        }
        if ( 'rejected' === $governance['sharia_state'] ) {
            return self::fail( $row, 'sharia_gate', __( 'The draft did not pass the configured Sharīʿah governance gate.', SCHA_TEXT_DOMAIN ) );
        }
        $citation = SCHA_Citation_Validator::validate( (string) $result['answer'], $sources );
        if ( ! $citation['valid'] ) {
            return self::fail( $row, 'citation_failure', __( 'The scheduled post failed citation validation.', SCHA_TEXT_DOMAIN ) );
        }

        $launch = (string) SCHA_Settings::get( 'teacher_launch_date', '' );
        $review_days = max( 30, absint( SCHA_Settings::get( 'teacher_human_review_days', 30 ) ) );
        $mandatory_review = true;
        if ( $launch && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $launch ) ) {
            $mandatory_review = time() < ( strtotime( $launch . ' 00:00:00 UTC' ) + ( $review_days * DAY_IN_SECONDS ) );
        }
        $low_risk = in_array( $category, (array) SCHA_Settings::get( 'teacher_low_risk_categories', array() ), true );
        $founder_policy = (bool) apply_filters( 'scha_teacher_auto_publish_policy_approved', false, $category, $row );
        $auto_allowed = ! $mandatory_review && $low_risk && $governance['auto_publish_ok'] && SCHA_Settings::get( 'teacher_auto_publish', false ) && $founder_policy;
        $status = $auto_allowed ? 'publish_pending' : 'review_required';
        $title = self::extract_title( (string) $citation['answer'], $category );

        $updated = $wpdb->update(
            $table,
            array(
                'title'           => $title,
                'content'         => SCHA_Crypto::encrypt( (string) $citation['answer'], 'teacher-draft' ),
                'citations'       => wp_json_encode( $citation['citations'] ),
                'provider'        => sanitize_key( (string) ( $result['provider'] ?? $provider->key() ) ),
                'model'           => sanitize_text_field( (string) ( $result['model'] ?? '' ) ),
                'risk'            => $low_risk ? 'low' : 'standard',
                'status'          => $status,
                'review_required' => $auto_allowed ? 0 : 1,
                'error_code'      => '',
                'updated_at'      => current_time( 'mysql', true ),
            ),
            array( 'id' => $row['id'] )
        );
        if ( false === $updated ) {
            return self::fail( $row, 'storage_failure', __( 'The generated draft could not be stored.', SCHA_TEXT_DOMAIN ) );
        }

        SCHA_Usage_Ledger::record( array(
            'session_id' => 0,
            'response_message_id' => 0,
            'provider' => $result['provider'] ?? $provider->key(),
            'model' => $result['model'] ?? '',
            'request_hash' => hash_hmac( 'sha256', $prompt . '|' . $row['schedule_key'], wp_salt( 'auth' ) ),
            'idempotency_key' => 'teacher:' . $row['schedule_key'],
            'input_tokens' => absint( $result['input_tokens'] ?? 0 ),
            'output_tokens' => absint( $result['output_tokens'] ?? 0 ),
            'cost_micros' => absint( $result['cost_micros'] ?? 0 ),
            'user_id' => 0,
        ) );
        SCHA_Observability::audit( 'ai_teacher_draft_created', 'ai_teacher_post', (string) $row['public_id'], array( 'category' => $category, 'provider' => $result['provider'] ?? $provider->key(), 'review_required' => ! $auto_allowed, 'sharia_state' => $governance['sharia_state'], 'rights_ok' => $governance['rights_ok'] ), 'institutional-educational-publishing', $trace_id );
        SCHA_Outbox::publish( $auto_allowed ? 'AITeacherDraftApproved' : 'AITeacherReviewRequired', 'ai_teacher_post', (string) $row['public_id'], array( 'post_id' => $row['public_id'], 'category' => $category, 'human_review_required' => ! $auto_allowed, 'provider_disclosure' => 'Powered by Claude AI when configured', 'sharia_state' => $governance['sharia_state'] ), 'teacher-generated-' . $row['schedule_key'] );

        if ( $auto_allowed ) {
            return self::publish_automatic( (string) $row['public_id'] );
        }
        do_action( 'sabri_file22_ai_teacher_draft_ready_v1', self::public_post( self::get( (string) $row['public_id'] ) ) );
        return true;
    }

    public static function approve( string $public_id ): true|WP_Error {
        global $wpdb;
        if ( ! SCHA_Capabilities::current_user_can_manage() ) {
            return new WP_Error( 'scha_teacher_forbidden', __( 'You are not authorized to review AI Teacher drafts.', SCHA_TEXT_DOMAIN ), array( 'status' => 403 ) );
        }
        $post = self::get( $public_id );
        if ( ! $post ) return new WP_Error( 'scha_teacher_not_found', __( 'AI Teacher draft not found.', SCHA_TEXT_DOMAIN ), array( 'status' => 404 ) );
        if ( 'approved' === $post['status'] ) return true;
        if ( 'review_required' !== $post['status'] ) {
            return new WP_Error( 'scha_teacher_invalid_state', __( 'This AI Teacher draft is not awaiting approval.', SCHA_TEXT_DOMAIN ), array( 'status' => 409 ) );
        }
        $updated = $wpdb->update( SCHA_Database::table( 'teacher_posts' ), array( 'status' => 'approved', 'review_required' => 0, 'reviewed_by' => get_current_user_id(), 'reviewed_at' => current_time( 'mysql', true ), 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $post['id'], 'status' => 'review_required' ) );
        if ( 1 !== $updated ) return new WP_Error( 'scha_teacher_review_conflict', __( 'The draft changed while it was being reviewed. Refresh and retry.', SCHA_TEXT_DOMAIN ), array( 'status' => 409 ) );
        SCHA_Observability::audit( 'ai_teacher_draft_approved', 'ai_teacher_post', $public_id, array(), 'founder-or-authorized-human-review' );
        return true;
    }

    public static function reject( string $public_id, string $reason = '' ): true|WP_Error {
        global $wpdb;
        if ( ! SCHA_Capabilities::current_user_can_manage() ) {
            return new WP_Error( 'scha_teacher_forbidden', __( 'You are not authorized to review AI Teacher drafts.', SCHA_TEXT_DOMAIN ), array( 'status' => 403 ) );
        }
        $post = self::get( $public_id );
        if ( ! $post ) return new WP_Error( 'scha_teacher_not_found', __( 'AI Teacher draft not found.', SCHA_TEXT_DOMAIN ), array( 'status' => 404 ) );
        $allowed = array( 'review_required', 'approved', 'publish_pending' );
        if ( ! in_array( $post['status'], $allowed, true ) ) {
            return new WP_Error( 'scha_teacher_invalid_state', __( 'This record can no longer be rejected as a draft. A published item must use the canonical correction or retraction workflow.', SCHA_TEXT_DOMAIN ), array( 'status' => 409 ) );
        }
        $updated = $wpdb->query( $wpdb->prepare( "UPDATE " . SCHA_Database::table( 'teacher_posts' ) . " SET status='rejected',reviewed_by=%d,reviewed_at=%s,error_code=%s,updated_at=%s,version=version+1 WHERE id=%d AND status=%s", get_current_user_id(), current_time( 'mysql', true ), sanitize_key( $reason ?: 'human_rejection' ), current_time( 'mysql', true ), $post['id'], $post['status'] ) );
        if ( 1 !== $updated ) return new WP_Error( 'scha_teacher_review_conflict', __( 'The draft changed while it was being reviewed. Refresh and retry.', SCHA_TEXT_DOMAIN ), array( 'status' => 409 ) );
        SCHA_Observability::audit( 'ai_teacher_draft_rejected', 'ai_teacher_post', $public_id, array( 'reason' => sanitize_key( $reason ) ), 'founder-or-authorized-human-review' );
        return true;
    }

    public static function process_publication_queue( int $limit = 4 ): int {
        global $wpdb;
        $table = SCHA_Database::table( 'teacher_posts' );
        self::recover_stale_publication_claims( $table );
        $ids = $wpdb->get_col( $wpdb->prepare( "SELECT public_id FROM $table WHERE status='publish_pending' AND available_at <= %s ORDER BY available_at ASC,id ASC LIMIT %d", current_time( 'mysql', true ), min( 8, max( 1, $limit ) ) ) ) ?: array();
        $processed = 0;
        foreach ( $ids as $id ) {
            self::publish_automatic( (string) $id );
            ++$processed;
        }
        return $processed;
    }

    public static function publish( string $public_id ): true|WP_Error {
        if ( ! SCHA_Capabilities::current_user_can_manage() ) return new WP_Error( 'scha_teacher_forbidden', __( 'You are not authorized to publish AI Teacher drafts.', SCHA_TEXT_DOMAIN ), array( 'status' => 403 ) );
        return self::publish_internal( $public_id, false );
    }

    private static function publish_automatic( string $public_id ): true|WP_Error {
        return self::publish_internal( $public_id, true );
    }

    private static function publish_internal( string $public_id, bool $automatic ): true|WP_Error {
        global $wpdb;
        $post = self::get( $public_id );
        if ( ! $post ) return new WP_Error( 'scha_teacher_not_found', __( 'AI Teacher draft not found.', SCHA_TEXT_DOMAIN ), array( 'status' => 404 ) );
        $required_state = $automatic ? 'publish_pending' : 'approved';
        if ( $required_state !== $post['status'] ) return new WP_Error( 'scha_teacher_invalid_state', __( 'The AI Teacher draft is not in a publishable state.', SCHA_TEXT_DOMAIN ), array( 'status' => 409 ) );

        $table = SCHA_Database::table( 'teacher_posts' );
        $now = current_time( 'mysql', true );
        $claimed = $wpdb->query( $wpdb->prepare( "UPDATE $table SET status='publishing',attempts=attempts+1,updated_at=%s,version=version+1 WHERE id=%d AND status=%s", $now, $post['id'], $required_state ) );
        if ( 1 !== $claimed ) return new WP_Error( 'scha_teacher_publish_in_progress', __( 'This AI Teacher draft is already being published or its state changed.', SCHA_TEXT_DOMAIN ), array( 'status' => 409 ) );
        $publication_attempt = absint( $post['attempts'] ?? 0 ) + 1;

        $payload = self::public_post( array_merge( $post, array( 'status' => 'publishing', 'attempts' => $publication_attempt ) ) );
        $payload['author'] = self::ACCOUNT_ID;
        $payload['labels'] = array( 'ai-generated', 'human-governed', 'source-linked' );
        $payload['clinical_authority'] = false;
        $payload['provider_disclosure'] = __( 'AI-generated and human-governed; Powered by Claude AI when configured.', SCHA_TEXT_DOMAIN );
        $payload['idempotency_key'] = 'scha-ai-teacher:' . $public_id;

        try {
            $result = apply_filters( 'sabri_file21_publish_ai_teacher_post_v1', null, $payload );
        } catch ( Throwable $e ) {
            $result = null;
            SCHA_Observability::safe_error( $e, SCHA_Observability::trace_id() );
        }
        if ( ! is_array( $result ) || empty( $result['object_id'] ) ) {
            $retry_state = $automatic ? 'publish_pending' : 'approved';
            $delay = min( DAY_IN_SECONDS, max( 5 * MINUTE_IN_SECONDS, ( 2 ** min( 8, $publication_attempt ) ) * MINUTE_IN_SECONDS ) );
            $restored = $wpdb->update( $table, array( 'status' => $retry_state, 'available_at' => gmdate( 'Y-m-d H:i:s', time() + $delay ), 'error_code' => 'publishing-owner-unavailable', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $post['id'], 'status' => 'publishing' ) );
            if ( false === $restored ) SCHA_Observability::safe_error( 'AI Teacher publication retry state could not be restored.', SCHA_Observability::trace_id() );
            do_action( 'sabri_file22_ai_teacher_draft_ready_v1', $payload );
            return new WP_Error( 'scha_teacher_owner_unavailable', __( 'The canonical publishing owner is unavailable. The approved draft remains queued without creating a duplicate post.', SCHA_TEXT_DOMAIN ), array( 'status' => 503 ) );
        }

        $updated = $wpdb->update( $table, array( 'status' => 'published', 'published_object_id' => sanitize_text_field( (string) $result['object_id'] ), 'published_url' => esc_url_raw( (string) ( $result['url'] ?? '' ) ), 'error_code' => '', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $post['id'], 'status' => 'publishing' ) );
        if ( 1 !== $updated ) return new WP_Error( 'scha_teacher_publish_state_failed', __( 'The canonical post was created but local publication state could not be finalized. Use the idempotency key for reconciliation.', SCHA_TEXT_DOMAIN ), array( 'status' => 500 ) );
        SCHA_Outbox::publish( 'AITeacherPostPublished', 'ai_teacher_post', $public_id, array( 'post_id' => $public_id, 'owner_object_id' => $result['object_id'], 'url' => $result['url'] ?? '' ), 'teacher-published-' . $public_id );
        SCHA_Observability::audit( 'ai_teacher_post_published', 'ai_teacher_post', $public_id, array( 'owner_object_id' => $result['object_id'], 'policy_auto' => $automatic, 'attempt' => $publication_attempt ), 'canonical-content-owner-publication' );
        return true;
    }

    public static function get( string $public_id ): ?array {
        global $wpdb;
        $table = SCHA_Database::table( 'teacher_posts' );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE public_id=%s LIMIT 1", $public_id ), ARRAY_A );
        return $row ?: null;
    }

    public static function recent( int $limit = 50 ): array {
        global $wpdb;
        $table = SCHA_Database::table( 'teacher_posts' );
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table ORDER BY slot_date DESC,slot_time DESC,id DESC LIMIT %d", min( 100, max( 1, $limit ) ) ), ARRAY_A ) ?: array();
    }

    public static function public_post( ?array $post ): array {
        if ( ! $post ) {
            return array();
        }
        $citations = json_decode( (string) ( $post['citations'] ?? '[]' ), true );
        return array(
            'id'                 => (string) $post['public_id'],
            'scheduleKey'        => (string) $post['schedule_key'],
            'category'           => (string) $post['category'],
            'title'              => (string) $post['title'],
            'content'            => SCHA_Crypto::decrypt( (string) $post['content'], 'teacher-draft' ),
            'citations'          => is_array( $citations ) ? $citations : array(),
            'provider'           => (string) $post['provider'],
            'model'              => (string) $post['model'],
            'risk'               => (string) $post['risk'],
            'status'             => (string) $post['status'],
            'reviewRequired'     => ! empty( $post['review_required'] ),
            'publishedObjectId'  => (string) $post['published_object_id'],
            'publishedUrl'       => (string) $post['published_url'],
            'isHuman'            => false,
            'isVerifiedDoctor'   => false,
            'clinicalAuthority'  => false,
        );
    }

    private static function recover_stale_generation_claims( string $table ): void {
        global $wpdb;
        $now = current_time( 'mysql', true );
        $stale = gmdate( 'Y-m-d H:i:s', time() - 20 * MINUTE_IN_SECONDS );
        $recovered = $wpdb->query( $wpdb->prepare( "UPDATE $table SET status='failed',error_code='generation-worker-timeout',available_at=%s,updated_at=%s,version=version+1 WHERE status='generating' AND updated_at < %s", $now, $now, $stale ) );
        if ( $recovered > 0 ) SCHA_Observability::audit( 'ai_teacher_stale_generation_recovered', 'ai_teacher_queue', 'generation', array( 'count' => absint( $recovered ) ), 'queue-recovery' );
    }

    private static function recover_stale_publication_claims( string $table ): void {
        global $wpdb;
        $now = current_time( 'mysql', true );
        $stale = gmdate( 'Y-m-d H:i:s', time() - 20 * MINUTE_IN_SECONDS );
        $recovered = $wpdb->query( $wpdb->prepare( "UPDATE $table SET status='publish_pending',error_code='publishing-worker-timeout',available_at=%s,updated_at=%s,version=version+1 WHERE status='publishing' AND updated_at < %s", $now, $now, $stale ) );
        if ( $recovered > 0 ) SCHA_Observability::audit( 'ai_teacher_stale_publication_recovered', 'ai_teacher_queue', 'publication', array( 'count' => absint( $recovered ) ), 'queue-recovery' );
    }

    private static function fail( array $row, string $code, string $message ): WP_Error {
        global $wpdb;
        $attempts = absint( $row['attempts'] ?? 1 );
        $delay = min( DAY_IN_SECONDS, ( 2 ** min( 8, $attempts ) ) * 5 * MINUTE_IN_SECONDS );
        $wpdb->update( SCHA_Database::table( 'teacher_posts' ), array(
            'status' => 'failed',
            'error_code' => sanitize_key( $code ),
            'available_at' => gmdate( 'Y-m-d H:i:s', time() + $delay ),
            'updated_at' => current_time( 'mysql', true ),
        ), array( 'id' => $row['id'] ) );
        SCHA_Outbox::publish( 'AITeacherGenerationFailed', 'ai_teacher_post', (string) $row['public_id'], array( 'post_id' => $row['public_id'], 'error_code' => sanitize_key( $code ), 'retry_after_seconds' => $delay ), 'teacher-failed-' . $row['schedule_key'] . '-' . $attempts );
        SCHA_Observability::audit( 'ai_teacher_generation_failed', 'ai_teacher_post', (string) $row['public_id'], array( 'code' => sanitize_key( $code ), 'attempts' => $attempts ), 'institutional-educational-publishing' );
        return new WP_Error( 'scha_teacher_' . sanitize_key( $code ), $message );
    }

    private static function teacher_budget_check(): true|WP_Error {
        global $wpdb;
        $budget = absint( SCHA_Settings::get( 'teacher_daily_budget_micros', 0 ) );
        if ( 0 === $budget ) {
            return true;
        }
        $usage = SCHA_Database::table( 'usage' );
        $sum = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(cost_micros),0) FROM $usage WHERE user_id=0 AND DATE(created_at)=%s AND idempotency_key LIKE 'teacher:%%'", gmdate( 'Y-m-d' ) ) ) );
        return $sum >= $budget ? new WP_Error( 'scha_teacher_budget_exceeded', __( 'The AI Teacher daily provider budget has been reached.', SCHA_TEXT_DOMAIN ) ) : true;
    }

    private static function topic_query( string $category, string $date ): string {
        return match ( $category ) {
            'materia-medica' => 'classical homeopathy materia medica educational principle approved source',
            'philosophy' => 'classical homeopathy philosophy educational principle approved source',
            'history' => 'history of classical homeopathy approved source',
            default => 'classical homeopathy study method learning approved source',
        } . ' ' . $date;
    }

    private static function teacher_prompt( string $category ): string {
        return 'Draft one concise educational post for the institutional AI Homeopathy Teacher in the category "' . $category . '". Use only the approved sources supplied. Begin with a clear title on the first line. Cite each substantive claim with [S#]. Clearly label the content AI-generated and human-governed. Do not diagnose, prescribe, select remedies, potency, dosage or frequency; do not promise cures; do not impersonate a human or verified doctor.';
    }

    private static function extract_title( string $answer, string $category ): string {
        $first = trim( (string) strtok( wp_strip_all_tags( $answer ), "\r\n" ) );
        $first = preg_replace( '/^#+\s*/', '', $first );
        $first = function_exists( 'mb_substr' ) ? mb_substr( $first, 0, 180 ) : substr( $first, 0, 180 );
        return $first ?: ucwords( str_replace( '-', ' ', $category ) );
    }
}
