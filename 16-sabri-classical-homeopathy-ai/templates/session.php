<?php

defined( 'ABSPATH' ) || exit;
get_header();
$session_id = sanitize_text_field( (string) get_query_var( 'scha_session_id' ) );
?>
<main id="primary" class="scha-page" <?php echo SCHA_Public::html_language_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <div class="scha-shell scha-chat-shell">
        <?php SCHA_Public::back_home_controls(); ?>
        <?php SCHA_Public::page_header( __( 'AI Study Session', SCHA_TEXT_DOMAIN ), __( 'Every request is rechecked against current account, privacy, clinical-safety, output-safety and citation rules.', SCHA_TEXT_DOMAIN ) ); ?>
        <?php SCHA_Public::nav(); ?>
        <section class="scha-card scha-safety-banner" aria-label="<?php esc_attr_e( 'Medical safety notice', SCHA_TEXT_DOMAIN ); ?>"><strong>⚕ <?php esc_html_e( 'Educational use only.', SCHA_TEXT_DOMAIN ); ?></strong> <?php esc_html_e( 'Do not use this AI for diagnosis, prescription, remedy selection, potency, dosage, frequency or emergencies.', SCHA_TEXT_DOMAIN ); ?></section>
        <section class="scha-chat" data-scha-chat data-session-id="<?php echo esc_attr( $session_id ); ?>" aria-label="<?php esc_attr_e( 'AI conversation', SCHA_TEXT_DOMAIN ); ?>">
            <div class="scha-messages" data-scha-messages aria-live="polite" aria-busy="true"><p class="scha-loading"><?php esc_html_e( 'Loading the private session…', SCHA_TEXT_DOMAIN ); ?></p></div>
            <form class="scha-prompt-form" data-scha-prompt-form>
                <label for="scha-prompt"><strong><?php esc_html_e( 'Educational question', SCHA_TEXT_DOMAIN ); ?></strong></label>
                <textarea id="scha-prompt" name="prompt" rows="4" maxlength="<?php echo esc_attr( (string) absint( SCHA_Settings::get( 'max_prompt_chars', 4000 ) ) ); ?>" required aria-describedby="scha-prompt-help"></textarea>
                <p id="scha-prompt-help" class="scha-help"><?php esc_html_e( 'Avoid personal identifiers and private health records. Sensitive data is redacted before external-provider transfer; stored messages are encrypted at rest.', SCHA_TEXT_DOMAIN ); ?></p>
                <div class="scha-form-actions"><button type="submit" class="scha-button scha-button-primary">✦ <?php esc_html_e( 'Ask from Approved Sources', SCHA_TEXT_DOMAIN ); ?></button><button type="button" class="scha-button scha-button-danger" data-scha-delete-session><?php esc_html_e( 'Delete Session', SCHA_TEXT_DOMAIN ); ?></button><a class="scha-button scha-button-quiet" href="<?php echo esc_url( rest_url( 'scha/v1/sessions/' . rawurlencode( $session_id ) . '/export' ) ); ?>" data-scha-export><?php esc_html_e( 'Export JSON', SCHA_TEXT_DOMAIN ); ?></a></div>
                <p class="scha-status" role="status" aria-live="polite" data-scha-status></p>
            </form>
        </section>
    </div>
</main>
<?php get_footer(); ?>
