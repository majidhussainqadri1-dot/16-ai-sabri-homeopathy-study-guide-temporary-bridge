<?php
defined( 'ABSPATH' ) || exit;
get_header();
$id = sanitize_text_field( (string) get_query_var( 'scha_session_id' ) );
?>
<main id="primary" class="scha-page" dir="auto"><div class="scha-shell scha-chat-shell">
<?php SCHA_Public::back_home_controls(); SCHA_Public::page_header( __( 'AI Study Session', SCHA_TEXT_DOMAIN ), __( 'Each answer passes entitlement, safety, retrieval, and citation gates.', SCHA_TEXT_DOMAIN ) ); SCHA_Public::nav(); ?>
<section class="scha-card scha-warning"><strong><?php esc_html_e( 'Educational use only.', SCHA_TEXT_DOMAIN ); ?></strong> <?php esc_html_e( 'Do not use this AI for diagnosis, prescription, potency, dosage, or emergencies.', SCHA_TEXT_DOMAIN ); ?></section>
<section class="scha-chat" data-scha-chat data-session-id="<?php echo esc_attr( $id ); ?>"><div class="scha-messages" data-scha-messages aria-live="polite" aria-busy="true"><p><?php esc_html_e( 'Loading private session…', SCHA_TEXT_DOMAIN ); ?></p></div><form class="scha-prompt-form" data-scha-prompt-form><label for="scha-prompt"><strong><?php esc_html_e( 'Educational question', SCHA_TEXT_DOMAIN ); ?></strong></label><textarea id="scha-prompt" name="prompt" maxlength="<?php echo esc_attr( (string) absint( SCHA_Settings::get( 'max_prompt_chars', 4000 ) ) ); ?>" required></textarea><p><?php esc_html_e( 'Do not enter personal identifiers or private patient records.', SCHA_TEXT_DOMAIN ); ?></p><div class="scha-form-actions"><button class="scha-button scha-button-primary" type="submit"><?php esc_html_e( 'Ask from Approved Sources', SCHA_TEXT_DOMAIN ); ?></button><button class="scha-button scha-button-danger" type="button" data-scha-delete-session><?php esc_html_e( 'Delete Session', SCHA_TEXT_DOMAIN ); ?></button><a class="scha-button scha-button-quiet" href="#" data-scha-export><?php esc_html_e( 'Export JSON', SCHA_TEXT_DOMAIN ); ?></a></div><p class="scha-status" data-scha-status aria-live="polite"></p></form></section>
</div></main><?php get_footer(); ?>
