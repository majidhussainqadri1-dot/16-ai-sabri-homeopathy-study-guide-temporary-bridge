<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Admin {
    public static function register_menu(): void {
        add_menu_page( __( 'Sabri Classical Homeopathy AI', SCHA_TEXT_DOMAIN ), __( 'Sabri AI', SCHA_TEXT_DOMAIN ), SCHA_Capabilities::MANAGE_AI, 'scha-ai', array( __CLASS__, 'render_settings_page' ), 'dashicons-superhero-alt', 58 );
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
        echo '<p>' . esc_html__( 'Educational, source-linked AI only; it is neither a clinical authority nor part of the basic education membership automatically.', SCHA_TEXT_DOMAIN ) . '</p><form method="post" action="options.php">';
        settings_fields( 'scha_settings_group' );
        echo '<table class="form-table" role="presentation">';
        self::check( 'enabled', 'Enable AI routes and API', $s['enabled'] );
        self::check( 'guest_demo', 'Allow tightly limited public demo', $s['guest_demo'] );
        echo '<tr><th>Provider</th><td><select name="scha_settings[provider]">';
        foreach ( array( 'local' => 'Local evidence fallback', 'bridge' => 'Temporary Custom GPT bridge', 'http_json' => 'Allowlisted HTTPS JSON provider' ) as $v => $label ) {
            echo '<option value="' . esc_attr( $v ) . '" ' . selected( $s['provider'], $v, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select></td></tr>';
        self::text( 'bridge_url', 'Custom GPT URL', $s['bridge_url'] );
        self::text( 'provider_endpoint', 'Provider HTTPS endpoint', $s['provider_endpoint'] );
        self::text( 'provider_model', 'Provider model', $s['provider_model'] );
        self::area( 'allowed_provider_hosts', 'Allowed provider hosts', implode( "\n", (array) $s['allowed_provider_hosts'] ) );
        self::number( 'retention_days', 'Retention days', $s['retention_days'], 1, 365 );
        self::number( 'max_prompt_chars', 'Maximum prompt characters', $s['max_prompt_chars'], 500, 20000 );
        self::number( 'requests_per_minute', 'Requests per minute', $s['requests_per_minute'], 1, 120 );
        self::number( 'daily_request_quota', 'Daily request quota', $s['daily_request_quota'], 1, 10000 );
        self::number( 'monthly_cost_budget_micros', 'Monthly provider cost budget (micro-units)', $s['monthly_cost_budget_micros'], 0, 999999999 );
        self::check( 'privacy_redaction', 'Redact detected sensitive data', $s['privacy_redaction'] );
        self::check( 'public_sources_page', 'Publish approved public source catalogue', $s['public_sources_page'] );
        self::text( 'green_primary', 'Primary green', $s['green_primary'] );
        echo '</table>';
        submit_button();
        echo '</form><h2>Secret configuration</h2><pre>define(\'SCHA_PROVIDER_API_KEY\', \'…\');</pre></div>';
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
        foreach ( array( 'title' => 'Title', 'owner_file' => 'Canonical owner file', 'owner_item_id' => 'Canonical owner item ID', 'version' => 'Version/edition', 'source_url' => 'Canonical source URL', 'license' => 'License/permission', 'language' => 'Language' ) as $name => $label ) {
            echo '<p><label><strong>' . esc_html( $label ) . '</strong><br><input class="regular-text" name="' . esc_attr( $name ) . '" ' . ( in_array( $name, array( 'title', 'owner_file', 'owner_item_id', 'version', 'license', 'language' ), true ) ? 'required' : '' ) . '></label></p>';
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
                'title' => wp_unslash( $_POST['title'] ?? '' ), 'owner_file' => wp_unslash( $_POST['owner_file'] ?? '' ), 'owner_item_id' => wp_unslash( $_POST['owner_item_id'] ?? '' ), 'version' => wp_unslash( $_POST['version'] ?? '' ), 'source_url' => wp_unslash( $_POST['source_url'] ?? '' ), 'license' => wp_unslash( $_POST['license'] ?? '' ), 'language' => wp_unslash( $_POST['language'] ?? '' ), 'access_class' => wp_unslash( $_POST['access_class'] ?? '' ), 'content' => wp_unslash( $_POST['content'] ?? '' ), 'approve' => ! empty( $_POST['approve'] ),
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

    public static function render_user_entitlement_fields( WP_User $user ): void {
        if ( ! current_user_can( 'edit_users' ) || ! current_user_can( 'edit_user', $user->ID ) ) return;
        echo '<h2>' . esc_html__( 'Sabri Classical Homeopathy AI entitlement', SCHA_TEXT_DOMAIN ) . '</h2><table class="form-table"><tr><th>Status</th><td><select name="scha_ai_entitlement_status"><option value="inactive">inactive</option><option value="active" ' . selected( get_user_meta( $user->ID, 'scha_ai_entitlement_status', true ), 'active', false ) . '>active</option><option value="suspended" ' . selected( get_user_meta( $user->ID, 'scha_ai_entitlement_status', true ), 'suspended', false ) . '>suspended</option></select></td></tr><tr><th>Plan</th><td><input name="scha_ai_plan" value="' . esc_attr( (string) get_user_meta( $user->ID, 'scha_ai_plan', true ) ) . '"></td></tr></table>';
    }

    public static function save_user_entitlement_fields( int $user_id ): void {
        if ( ! current_user_can( 'edit_users' ) || ! current_user_can( 'edit_user', $user_id ) ) return;
        update_user_meta( $user_id, 'scha_ai_entitlement_status', in_array( $_POST['scha_ai_entitlement_status'] ?? '', array( 'inactive', 'active', 'suspended' ), true ) ? sanitize_key( $_POST['scha_ai_entitlement_status'] ) : 'inactive' );
        update_user_meta( $user_id, 'scha_ai_plan', sanitize_text_field( wp_unslash( $_POST['scha_ai_plan'] ?? '' ) ) );
        SCHA_Observability::audit( 'entitlement_updated', 'user', (string) $user_id, array( 'status' => get_user_meta( $user_id, 'scha_ai_entitlement_status', true ) ), 'entitlement-administration' );
    }

    private static function guard( string $cap ): void { if ( ! current_user_can( $cap ) ) wp_die( esc_html__( 'You are not authorized for this operation.', SCHA_TEXT_DOMAIN ), '', array( 'response' => 403 ) ); }
    private static function redirect( string $page, mixed $result ): never { $ok = ! is_wp_error( $result ); wp_safe_redirect( add_query_arg( array( 'page' => $page, 'scha_status' => $ok ? 'success' : 'error', 'scha_message' => rawurlencode( $ok ? 'Operation completed.' : $result->get_error_message() ) ), admin_url( 'admin.php' ) ) ); exit; }
    private static function notice(): void { $status = sanitize_key( wp_unslash( $_GET['scha_status'] ?? '' ) ); $message = sanitize_text_field( rawurldecode( wp_unslash( $_GET['scha_message'] ?? '' ) ) ); if ( $status && $message ) echo '<div class="notice ' . ( 'success' === $status ? 'notice-success' : 'notice-error' ) . '"><p>' . esc_html( $message ) . '</p></div>'; }
    private static function check( string $key, string $label, bool $value ): void { echo '<tr><th>' . esc_html( $label ) . '</th><td><input type="checkbox" name="scha_settings[' . esc_attr( $key ) . ']" value="1" ' . checked( $value, true, false ) . '></td></tr>'; }
    private static function text( string $key, string $label, string $value ): void { echo '<tr><th>' . esc_html( $label ) . '</th><td><input class="regular-text" name="scha_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '"></td></tr>'; }
    private static function area( string $key, string $label, string $value ): void { echo '<tr><th>' . esc_html( $label ) . '</th><td><textarea class="large-text" rows="4" name="scha_settings[' . esc_attr( $key ) . ']">' . esc_textarea( $value ) . '</textarea></td></tr>'; }
    private static function number( string $key, string $label, int $value, int $min, int $max ): void { echo '<tr><th>' . esc_html( $label ) . '</th><td><input type="number" min="' . esc_attr( (string) $min ) . '" max="' . esc_attr( (string) $max ) . '" name="scha_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( (string) $value ) . '"></td></tr>'; }
}
