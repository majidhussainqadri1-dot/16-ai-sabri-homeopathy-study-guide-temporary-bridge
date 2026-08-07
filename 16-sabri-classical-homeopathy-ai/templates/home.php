<?php

defined( 'ABSPATH' ) || exit;
get_header();
$entitlement = SCHA_Entitlements::current();
$provider = SCHA_Provider_Registry::selected();
$modes = SCHA_Assistant_Modes::all();
?>
<main id="primary" class="scha-page" <?php echo SCHA_Public::html_language_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <div class="scha-shell">
        <?php SCHA_Public::back_home_controls(); ?>
        <?php SCHA_Public::page_header( __( 'Sabri Classical Homeopathy AI', SCHA_TEXT_DOMAIN ), __( 'Source-linked educational assistance, learning tools and an institutional AI Teacher under human governance.', SCHA_TEXT_DOMAIN ) ); ?>
        <?php SCHA_Public::nav(); ?>

        <section class="scha-grid" aria-label="<?php esc_attr_e( 'AI overview', SCHA_TEXT_DOMAIN ); ?>">
            <article class="scha-card"><h2>📚 <?php esc_html_e( 'Approved sources only', SCHA_TEXT_DOMAIN ); ?></h2><p><?php esc_html_e( 'Answers use approved, versioned, access-labelled sources from canonical content owners. Missing evidence is stated instead of invented.', SCHA_TEXT_DOMAIN ); ?></p></article>
            <article class="scha-card"><h2>🛡️ <?php esc_html_e( 'Educational—not clinical authority', SCHA_TEXT_DOMAIN ); ?></h2><p><?php esc_html_e( 'The AI does not diagnose, prescribe, choose remedies, potency, dosage or frequency, replace emergency care, or expose private records.', SCHA_TEXT_DOMAIN ); ?></p></article>
            <article class="scha-card"><h2>🔐 <?php esc_html_e( 'Private and portable', SCHA_TEXT_DOMAIN ); ?></h2><p><?php printf( esc_html__( 'Session content is encrypted at rest, private, noindex, no-cache, exportable and erasable, with an approximate %d-day retention unless an authorized legal hold applies.', SCHA_TEXT_DOMAIN ), absint( SCHA_Settings::get( 'retention_days', 30 ) ) ); ?></p></article>
        </section>

        <section class="scha-card scha-teacher-card" aria-labelledby="scha-teacher-title">
            <h2 id="scha-teacher-title">🤖 <?php esc_html_e( 'AI Homeopathy Teacher', SCHA_TEXT_DOMAIN ); ?></h2>
            <p><strong><?php esc_html_e( 'Institutional AI account—not a human and not a verified doctor.', SCHA_TEXT_DOMAIN ); ?></strong></p>
            <p><?php esc_html_e( 'It can prepare up to four source-linked educational drafts daily. For at least the first 30 days every draft requires human review; later low-risk publication still requires explicit Founder policy and the canonical publishing owner.', SCHA_TEXT_DOMAIN ); ?></p>
            <p class="scha-disclosure"><?php esc_html_e( 'Powered by Claude AI when configured, with governed provider fallback, citations, cost controls, retry, audit and a kill switch.', SCHA_TEXT_DOMAIN ); ?></p>
        </section>

        <section class="scha-card scha-start" aria-labelledby="scha-start-title">
            <h2 id="scha-start-title"><?php esc_html_e( 'Start a governed AI session', SCHA_TEXT_DOMAIN ); ?></h2>
            <p class="scha-success"><strong><?php esc_html_e( 'One complete free tier:', SCHA_TEXT_DOMAIN ); ?></strong> <?php esc_html_e( 'donations do not change access, source privileges, fair-use quota, speed, ranking or support.', SCHA_TEXT_DOMAIN ); ?></p>
            <dl class="scha-facts">
                <div><dt><?php esc_html_e( 'Access', SCHA_TEXT_DOMAIN ); ?></dt><dd><?php echo $entitlement['active'] ? esc_html__( 'Available', SCHA_TEXT_DOMAIN ) : esc_html__( 'Unavailable for current account state', SCHA_TEXT_DOMAIN ); ?></dd></div>
                <div><dt><?php esc_html_e( 'Tier', SCHA_TEXT_DOMAIN ); ?></dt><dd><?php echo esc_html( $entitlement['plan'] ); ?></dd></div>
                <div><dt><?php esc_html_e( 'Provider mode', SCHA_TEXT_DOMAIN ); ?></dt><dd><?php echo esc_html( $provider->key() ); ?></dd></div>
                <div><dt><?php esc_html_e( 'Fair-use daily limit', SCHA_TEXT_DOMAIN ); ?></dt><dd><?php echo esc_html( (string) $entitlement['quota'] ); ?></dd></div>
            </dl>
            <?php if ( $entitlement['active'] ) : ?>
                <label for="scha-mode"><strong><?php esc_html_e( 'Assistant mode', SCHA_TEXT_DOMAIN ); ?></strong></label>
                <select id="scha-mode" data-scha-mode>
                    <?php foreach ( $modes as $value => $label ) : ?>
                        <?php if ( 'doctor_admin' === $value && 'verified_doctor' !== $entitlement['role'] && 'founder' !== $entitlement['role'] ) continue; ?>
                        <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="scha-button scha-button-primary" data-scha-create-session>✦ <?php esc_html_e( 'Create New Session', SCHA_TEXT_DOMAIN ); ?></button>
                <p class="scha-status" role="status" aria-live="polite" data-scha-status></p>
            <?php elseif ( ! is_user_logged_in() ) : ?>
                <a class="scha-button scha-button-primary" href="<?php echo esc_url( wp_login_url( home_url( '/ai/' ) ) ); ?>"><?php esc_html_e( 'Log in to continue', SCHA_TEXT_DOMAIN ); ?></a>
            <?php else : ?>
                <p class="scha-warning"><?php esc_html_e( 'Your current account, guardian, consent or security state does not permit this protected action.', SCHA_TEXT_DOMAIN ); ?></p>
            <?php endif; ?>
        </section>

        <?php if ( 'bridge' === $provider->key() ) : ?>
            <section class="scha-card scha-disclosure"><h2><?php esc_html_e( 'Temporary Custom GPT bridge disclosure', SCHA_TEXT_DOMAIN ); ?></h2><p><?php esc_html_e( 'The bridge opens an external service and does not receive native access to private sessions, clinical records, messages, identity evidence or private studies.', SCHA_TEXT_DOMAIN ); ?></p></section>
        <?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>
