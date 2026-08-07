<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Admin {
    public static function register_menu(): void {
        add_menu_page( __( 'Sabri Classical Homeopathy AI', SCHA_TEXT_DOMAIN ), __( 'Sabri AI', SCHA_TEXT_DOMAIN ), SCHA_Capabilities::MANAGE_AI, 'scha-ai', array( __CLASS__, 'render_settings_page' ), 'dashicons-superhero-alt', 58 );
        add_submenu_page( 'scha-ai', __( 'AI Teacher', SCHA_TEXT_DOMAIN ), __( 'AI Teacher', SCHA_TEXT_DOMAIN ), SCHA_Capabilities::MANAGE_AI, 'scha-ai-teacher', array( __CLASS__, 'render_teacher_page' ) );
        add_submenu_page( 'scha-ai', __( 'Approved Corpus', SCHA_TEXT_DOMAIN ), __( 'Corpus', SCHA_TEXT_DOMAIN ), SCHA_Capabilities::MANAGE_CORPUS, 'scha-ai-corpus', array( __CLASS__, 'render_corpus_page' ) );
        add_submenu_page( 'scha-ai', __( 'AI Evaluations', SCHA_TEXT_DOMAIN ), __( 'Evaluations', SCHA_TEXT_DOMAIN ), SCHA_Capabilities::REVIEW_SAFETY, 'scha-ai-evaluations', array( __CLASS__, 'render_evaluations_page' ) );
        add_submenu_page( 'scha-ai', __( 'System Check', SCHA_TEXT_DOMAIN ), __( 'System Check', SCHA_TEXT_DOMAIN ), SCHA_Capabilities::VIEW_METRICS, 'scha-ai-health', array( __CLASS__, 'render_health_page' ) );
    }

    public static function register_settings(): void {
        register_setting( 'scha_settings_group', 'scha_settings', array( 'sanitize_callback' => array( 'SCHA_Settings', 'sanitize' ), 'default' => SCHA_Settings::defaults() ) );
    }

    public static function enqueue_assets( string $hook ): void {
        if ( str_contains( $hook, 'scha-ai' ) ) {
            wp_enqueue_style( 'scha-admin', SCHA_PLUGIN_URL . 'assets/css/admin.css', array(), SCHA_VERSION );
            wp_enqueue_script( 'scha-admin', SCHA_PLUGIN_URL . 'assets/js/admin.js', array(), SCHA_VERSION, true );
        }
    }

    public static function render_settings_page(): void {
        self::guard( SCHA_Capabilities::MANAGE_AI );
        $s = SCHA_Settings::all();
        echo '<div class="wrap scha-admin"><h1>' . esc_html__( 'Sabri Classical Homeopathy AI', SCHA_TEXT_DOMAIN ) . '</h1>';
        self::notice();
        echo '<p><strong>' . esc_html__( 'Current business law:', SCHA_TEXT_DOMAIN ) . '</strong> ' . esc_html__( 'One complete free tier. Donations do not change AI access, quota, speed, ranking, source access, or support.', SCHA_TEXT_DOMAIN ) . '</p>';
        echo '<p>' . esc_html__( 'Educational, source-linked assistance only. This module is not a clinical authority and cannot diagnose, prescribe, select a remedy, potency, dose, or frequency.', SCHA_TEXT_DOMAIN ) . '</p>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'scha_settings_group' );
        echo '<table class="form-table" role="presentation">';
        self::check( 'enabled', __( 'Enable AI routes and API', SCHA_TEXT_DOMAIN ), (bool) $s['enabled'] );
        self::check( 'guest_demo', __( 'Allow tightly limited public demo', SCHA_TEXT_DOMAIN ), (bool) $s['guest_demo'] );
        echo '<tr><th>' . esc_html__( 'Provider', SCHA_TEXT_DOMAIN ) . '</th><td><select name="scha_settings[provider]">';
        foreach ( array( 'local' => 'Local evidence fallback', 'claude' => 'Claude AI', 'bridge' => 'Temporary Custom GPT bridge', 'http_json' => 'Allowlisted HTTPS JSON provider' ) as $v => $label ) {
            echo '<option value="' . esc_attr( $v ) . '" ' . selected( $s['provider'], $v, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select></td></tr>';
        self::text( 'bridge_url', __( 'Custom GPT URL', SCHA_TEXT_DOMAIN ), (string) $s['bridge_url'] );
        self::text( 'provider_endpoint', __( 'Provider HTTPS endpoint', SCHA_TEXT_DOMAIN ), (string) $s['provider_endpoint'] );
        self::text( 'provider_model', __( 'Provider model', SCHA_TEXT_DOMAIN ), (string) $s['provider_model'] );
        self::area( 'allowed_provider_hosts', __( 'Allowed provider hosts', SCHA_TEXT_DOMAIN ), implode( "\n", (array) $s['allowed_provider_hosts'] ) );
        self::number( 'retention_days', __( 'Retention days', SCHA_TEXT_DOMAIN ), (int) $s['retention_days'], 1, 365 );
        self::number( 'max_prompt_chars', __( 'Maximum prompt characters', SCHA_TEXT_DOMAIN ), (int) $s['max_prompt_chars'], 500, 20000 );
        self::number( 'requests_per_minute', __( 'Requests per minute', SCHA_TEXT_DOMAIN ), (int) $s['requests_per_minute'], 1, 120 );
        self::number( 'daily_request_quota', __( 'Fair-use daily request quota', SCHA_TEXT_DOMAIN ), (int) $s['daily_request_quota'], 1, 10000 );
        self::number( 'monthly_cost_budget_micros', __( 'Monthly provider cost safety budget (micro-units)', SCHA_TEXT_DOMAIN ), (int) $s['monthly_cost_budget_micros'], 0, 999999999 );
        echo '<tr><th>' . esc_html__( 'External-provider privacy redaction', SCHA_TEXT_DOMAIN ) . '</th><td><strong>' . esc_html__( 'Always enabled', SCHA_TEXT_DOMAIN ) . '</strong><p class="description">' . esc_html__( 'Detected identifiers and credentials are redacted before any external-provider transfer; this invariant cannot be disabled.', SCHA_TEXT_DOMAIN ) . '</p></td></tr>';
        self::check( 'public_sources_page', __( 'Publish approved public source catalogue', SCHA_TEXT_DOMAIN ), (bool) $s['public_sources_page'] );
        self::check( 'low_bandwidth_default', __( 'Prefer low-bandwidth interface by default', SCHA_TEXT_DOMAIN ), (bool) $s['low_bandwidth_default'] );
        self::text( 'green_primary', __( 'Primary green', SCHA_TEXT_DOMAIN ), (string) $s['green_primary'] );
        echo '<tr><th colspan="2"><h2>' . esc_html__( 'Institutional AI Teacher', SCHA_TEXT_DOMAIN ) . '</h2></th></tr>';
        self::check( 'teacher_enabled', __( 'Enable four governed daily AI Teacher slots', SCHA_TEXT_DOMAIN ), (bool) $s['teacher_enabled'] );
        echo '<tr><th>' . esc_html__( 'Teacher provider', SCHA_TEXT_DOMAIN ) . '</th><td><select name="scha_settings[teacher_provider]">';
        foreach ( array( 'claude' => 'Claude AI', 'local' => 'Local evidence fallback', 'http_json' => 'Allowlisted HTTPS JSON provider' ) as $v => $label ) {
            echo '<option value="' . esc_attr( $v ) . '" ' . selected( $s['teacher_provider'], $v, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select></td></tr>';
        self::text( 'teacher_model', __( 'Teacher model', SCHA_TEXT_DOMAIN ), (string) $s['teacher_model'] );
        self::area( 'teacher_slots', __( 'Exactly four daily slots (HH:MM)', SCHA_TEXT_DOMAIN ), implode( "\n", (array) $s['teacher_slots'] ) );
        self::number( 'teacher_human_review_days', __( 'Mandatory human-review launch period (minimum 30 days)', SCHA_TEXT_DOMAIN ), (int) $s['teacher_human_review_days'], 30, 365 );
        self::check( 'teacher_auto_publish', __( 'Allow low-risk auto-publication after launch review period and explicit Founder policy hook', SCHA_TEXT_DOMAIN ), (bool) $s['teacher_auto_publish'] );
        self::area( 'teacher_low_risk_categories', __( 'Low-risk categories', SCHA_TEXT_DOMAIN ), implode( "\n", (array) $s['teacher_low_risk_categories'] ) );
        self::text( 'teacher_launch_date', __( 'Teacher launch date (YYYY-MM-DD)', SCHA_TEXT_DOMAIN ), (string) $s['teacher_launch_date'] );
        self::number( 'teacher_daily_budget_micros', __( 'Teacher daily provider budget (micro-units)', SCHA_TEXT_DOMAIN ), (int) $s['teacher_daily_budget_micros'], 0, 999999999 );
        echo '</table>';
        submit_button();
        echo '</form><h2>' . esc_html__( 'Secret configuration', SCHA_TEXT_DOMAIN ) . '</h2><pre>define(\'SCHA_ANTHROPIC_API_KEY\', \'…\');\ndefine(\'SCHA_PROVIDER_API_KEY\', \'…\');</pre></div>';
    }

    public static function render_teacher_page(): void {
        self::guard( SCHA_Capabilities::MANAGE_AI );
        self::notice();
        $rows = SCHA_AI_Teacher::recent();
        echo '<div class="wrap scha-admin"><h1>' . esc_html__( 'AI Homeopathy Teacher', SCHA_TEXT_DOMAIN ) . '</h1>';
        echo '<p>' . esc_html__( 'Institutional AI account; not a human and not a verified doctor. File 16 prepares source-linked drafts; File 21/22 remain the canonical publishing owners.', SCHA_TEXT_DOMAIN ) . '</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'scha_teacher_action' );
        echo '<input type="hidden" name="action" value="scha_teacher_action"><input type="hidden" name="teacher_action" value="reconcile">';
        submit_button( __( 'Queue and Process Due Slots', SCHA_TEXT_DOMAIN ), 'secondary', 'submit', false );
        echo '</form>';
        echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Slot', SCHA_TEXT_DOMAIN ) . '</th><th>' . esc_html__( 'Category / title', SCHA_TEXT_DOMAIN ) . '</th><th>' . esc_html__( 'Provider', SCHA_TEXT_DOMAIN ) . '</th><th>' . esc_html__( 'Status', SCHA_TEXT_DOMAIN ) . '</th><th>' . esc_html__( 'Actions', SCHA_TEXT_DOMAIN ) . '</th></tr></thead><tbody>';
        foreach ( $rows as $row ) {
            $public = SCHA_AI_Teacher::public_post( $row );
            echo '<tr><td><code>' . esc_html( $row['schedule_key'] ) . '</code></td><td><strong>' . esc_html( $row['category'] ) . '</strong><br>' . esc_html( $row['title'] ?: '—' ) . '</td><td>' . esc_html( $row['provider'] ?: '—' ) . '</td><td>' . esc_html( $row['status'] ) . ( ! empty( $row['review_required'] ) ? '<br><small>human review required</small>' : '' ) . '</td><td>';
            foreach ( array( 'approve' => __( 'Approve', SCHA_TEXT_DOMAIN ), 'publish' => __( 'Publish through owner', SCHA_TEXT_DOMAIN ), 'reject' => __( 'Reject', SCHA_TEXT_DOMAIN ), 'retry' => __( 'Retry', SCHA_TEXT_DOMAIN ) ) as $action => $label ) {
                echo '<form class="scha-inline-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
                wp_nonce_field( 'scha_teacher_action' );
                echo '<input type="hidden" name="action" value="scha_teacher_action"><input type="hidden" name="teacher_action" value="' . esc_attr( $action ) . '"><input type="hidden" name="public_id" value="' . esc_attr( $public['id'] ) . '"><button class="button button-small">' . esc_html( $label ) . '</button></form>';
            }
            echo '</td></tr>';
        }
        if ( ! $rows ) {
            echo '<tr><td colspan="5">' . esc_html__( 'No scheduled AI Teacher records yet.', SCHA_TEXT_DOMAIN ) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public static function handle_teacher_action(): void {
        self::guard( SCHA_Capabilities::MANAGE_AI );
        check_admin_referer( 'scha_teacher_action' );
        $action = sanitize_key( wp_unslash( $_POST['teacher_action'] ?? '' ) );
        $id = sanitize_text_field( wp_unslash( $_POST['public_id'] ?? '' ) );
        $result = match ( $action ) {
            'reconcile' => ( function (): true { SCHA_AI_Teacher::reconcile(); return true; } )(),
            'approve' => SCHA_AI_Teacher::approve( $id ),
            'publish' => SCHA_AI_Teacher::publish( $id ),
            'reject' => SCHA_AI_Teacher::reject( $id, 'human_rejection' ),
            'retry' => self::retry_teacher( $id ),
            default => new WP_Error( 'scha_invalid_action', __( 'Invalid action.', SCHA_TEXT_DOMAIN ) ),
        };
        self::redirect( 'scha-ai-teacher', $result );
    }

    private static function retry_teacher( string $id ): true|WP_Error {
        global $wpdb;
        $post = SCHA_AI_Teacher::get( $id );
        if ( ! $post ) {
            return new WP_Error( 'scha_teacher_not_found', __( 'AI Teacher draft not found.', SCHA_TEXT_DOMAIN ) );
        }
        if ( 'failed' !== (string) $post['status'] ) {
            return new WP_Error( 'scha_teacher_retry_invalid_state', __( 'Only a failed AI Teacher generation may be manually re-queued.', SCHA_TEXT_DOMAIN ), array( 'status' => 409 ) );
        }
        $updated = $wpdb->update( SCHA_Database::table( 'teacher_posts' ), array( 'status' => 'queued', 'attempts' => 0, 'available_at' => current_time( 'mysql', true ), 'error_code' => '', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $post['id'], 'status' => 'failed' ) );
        if ( 1 !== $updated ) {
            return new WP_Error( 'scha_teacher_retry_conflict', __( 'The AI Teacher record changed before it could be re-queued.', SCHA_TEXT_DOMAIN ), array( 'status' => 409 ) );
        }
        SCHA_AI_Teacher::process_queue( 1 );
        return true;
    }

    public static function render_corpus_page(): void {
        self::guard( SCHA_Capabilities::MANAGE_CORPUS );
        global $wpdb;
        $items = $wpdb->get_results( 'SELECT * FROM ' . SCHA_Database::table( 'corpus_items' ) . ' ORDER BY id DESC LIMIT 200', ARRAY_A ) ?: array();
        echo '<div class="wrap scha-admin"><h1>' . esc_html__( 'Approved AI Corpus', SCHA_TEXT_DOMAIN ) . '</h1>';
        self::notice();
        echo '<p>' . esc_html__( 'Only licensed, versioned, access-labelled canonical owner content is eligible. Clinical records, messages, identity evidence and private studies are excluded.', SCHA_TEXT_DOMAIN ) . '</p>';
        echo '<form class="scha-card" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'scha_corpus_action' );
        echo '<input type="hidden" name="action" value="scha_corpus_action"><input type="hidden" name="corpus_action" value="register">';
        foreach ( array( 'title' => 'Title', 'owner_file' => 'Canonical owner file', 'owner_item_id' => 'Canonical owner item ID', 'version' => 'Version/edition', 'source_url' => 'Canonical source URL', 'license' => 'License/permission', 'approved_use' => 'Approved AI use', 'rights_evidence_id' => 'Rights evidence ID', 'rights_reviewed_at' => 'Rights review date', 'language' => 'Language' ) as $name => $label ) {
            echo '<p><label><strong>' . esc_html( $label ) . '</strong><br><input class="regular-text" name="' . esc_attr( $name ) . '" ' . ( in_array( $name, array( 'title', 'owner_file', 'owner_item_id', 'version', 'license', 'approved_use', 'rights_evidence_id', 'rights_reviewed_at', 'language' ), true ) ? 'required' : '' ) . '></label></p>';
        }
        echo '<p><label><strong>Access class</strong><br><select name="access_class"><option>public</option><option>subscriber</option><option>doctor</option><option>founder</option><option>internal</option></select></label></p><p><label><strong>Source text</strong><br><textarea class="large-text" rows="12" name="content" required></textarea></label></p><p><label><input type="checkbox" name="approve" value="1"> Approve and index now after rights review</label></p>';
        submit_button( __( 'Register Source', SCHA_TEXT_DOMAIN ) );
        echo '</form><h2>Registry</h2><table class="widefat striped"><thead><tr><th>Title</th><th>Owner/version</th><th>Access</th><th>Status</th><th>Action</th></tr></thead><tbody>';
        foreach ( $items as $item ) {
            echo '<tr><td>' . esc_html( $item['title'] ) . '</td><td><code>' . esc_html( $item['owner_file'] . ':' . $item['owner_item_id'] . '@' . $item['item_version'] ) . '</code></td><td>' . esc_html( $item['access_class'] ) . '</td><td>' . esc_html( $item['status'] ) . '</td><td>';
            foreach ( array( 'approve' => 'Approve/index', 'retract' => 'Retract', 'reindex' => 'Reindex' ) as $action => $label ) {
                echo '<form class="scha-inline-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
                wp_nonce_field( 'scha_corpus_action' );
                echo '<input type="hidden" name="action" value="scha_corpus_action"><input type="hidden" name="corpus_action" value="' . esc_attr( $action ) . '"><input type="hidden" name="item_id" value="' . esc_attr( (string) $item['id'] ) . '"><button class="button button-small">' . esc_html( $label ) . '</button></form>';
            }
            echo '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public static function render_evaluations_page(): void {
        self::guard( SCHA_Capabilities::REVIEW_SAFETY );
        echo '<div class="wrap scha-admin"><h1>' . esc_html__( 'AI Safety Evaluations', SCHA_TEXT_DOMAIN ) . '</h1>';
        self::notice();
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'scha_run_evaluation' );
        echo '<input type="hidden" name="action" value="scha_run_evaluation">';
        submit_button( __( 'Run Built-in Evaluation Suite', SCHA_TEXT_DOMAIN ) );
        echo '</form><pre>' . esc_html( wp_json_encode( SCHA_Evaluation::recent(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ) . '</pre></div>';
    }

    public static function render_health_page(): void {
        self::guard( SCHA_Capabilities::VIEW_METRICS );
        echo '<div class="wrap scha-admin"><h1>' . esc_html__( 'File 16 System Check', SCHA_TEXT_DOMAIN ) . '</h1><pre>' . esc_html( wp_json_encode( SCHA_Health::report(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ) . '</pre></div>';
    }

    public static function handle_corpus_action(): void {
        self::guard( SCHA_Capabilities::MANAGE_CORPUS );
        check_admin_referer( 'scha_corpus_action' );
        $action = sanitize_key( wp_unslash( $_POST['corpus_action'] ?? '' ) );
        if ( 'register' === $action ) {
            $result = SCHA_Corpus::register_item( array(
                'title' => wp_unslash( $_POST['title'] ?? '' ), 'owner_file' => wp_unslash( $_POST['owner_file'] ?? '' ), 'owner_item_id' => wp_unslash( $_POST['owner_item_id'] ?? '' ), 'version' => wp_unslash( $_POST['version'] ?? '' ), 'source_url' => wp_unslash( $_POST['source_url'] ?? '' ), 'license' => wp_unslash( $_POST['license'] ?? '' ), 'approved_use' => wp_unslash( $_POST['approved_use'] ?? '' ), 'rights_evidence_id' => wp_unslash( $_POST['rights_evidence_id'] ?? '' ), 'rights_reviewed_at' => wp_unslash( $_POST['rights_reviewed_at'] ?? '' ), 'language' => wp_unslash( $_POST['language'] ?? '' ), 'access_class' => wp_unslash( $_POST['access_class'] ?? '' ), 'content' => wp_unslash( $_POST['content'] ?? '' ), 'approve' => ! empty( $_POST['approve'] ),
            ), ! empty( $_POST['approve'] ) );
        } else {
            $id = absint( $_POST['item_id'] ?? 0 );
            $result = match ( $action ) { 'approve' => SCHA_Corpus::approve_and_index( $id ), 'retract' => SCHA_Corpus::suspend( $id, 'manual-governance' ), 'reindex' => SCHA_Corpus::approve_and_index( $id ), default => new WP_Error( 'scha_invalid_action', 'Invalid action.' ) };
        }
        self::redirect( 'scha-ai-corpus', $result );
    }

    public static function handle_run_evaluation(): void {
        self::guard( SCHA_Capabilities::REVIEW_SAFETY );
        check_admin_referer( 'scha_run_evaluation' );
        self::redirect( 'scha-ai-evaluations', SCHA_Evaluation::run() );
    }

    private static function guard( string $cap ): void { if ( ! current_user_can( $cap ) ) wp_die( esc_html__( 'You are not authorized for this operation.', SCHA_TEXT_DOMAIN ), '', array( 'response' => 403 ) ); }
    private static function redirect( string $page, mixed $result ): never { $ok = ! is_wp_error( $result ); wp_safe_redirect( add_query_arg( array( 'page' => $page, 'scha_status' => $ok ? 'success' : 'error', 'scha_message' => rawurlencode( $ok ? 'Operation completed.' : $result->get_error_message() ) ), admin_url( 'admin.php' ) ) ); exit; }
    private static function notice(): void { $status = sanitize_key( wp_unslash( $_GET['scha_status'] ?? '' ) ); $message = sanitize_text_field( rawurldecode( wp_unslash( $_GET['scha_message'] ?? '' ) ) ); if ( $status && $message ) echo '<div class="notice ' . ( 'success' === $status ? 'notice-success' : 'notice-error' ) . '"><p>' . esc_html( $message ) . '</p></div>'; }
    private static function check( string $key, string $label, bool $value ): void { echo '<tr><th>' . esc_html( $label ) . '</th><td><input type="checkbox" name="scha_settings[' . esc_attr( $key ) . ']" value="1" ' . checked( $value, true, false ) . '></td></tr>'; }
    private static function text( string $key, string $label, string $value ): void { echo '<tr><th>' . esc_html( $label ) . '</th><td><input class="regular-text" name="scha_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '"></td></tr>'; }
    private static function area( string $key, string $label, string $value ): void { echo '<tr><th>' . esc_html( $label ) . '</th><td><textarea class="large-text" rows="4" name="scha_settings[' . esc_attr( $key ) . ']">' . esc_textarea( $value ) . '</textarea></td></tr>'; }
    private static function number( string $key, string $label, int $value, int $min, int $max ): void { echo '<tr><th>' . esc_html( $label ) . '</th><td><input type="number" min="' . esc_attr( (string) $min ) . '" max="' . esc_attr( (string) $max ) . '" name="scha_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( (string) $value ) . '"></td></tr>'; }
}
