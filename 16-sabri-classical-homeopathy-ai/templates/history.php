<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="primary" class="scha-page" <?php echo SCHA_Public::html_language_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <div class="scha-shell">
        <?php SCHA_Public::back_home_controls(); ?>
        <?php SCHA_Public::page_header( __( 'Private AI History', SCHA_TEXT_DOMAIN ), __( 'Your sessions are private, noindex, exportable, and deletable.', SCHA_TEXT_DOMAIN ) ); ?>
        <?php SCHA_Public::nav(); ?>
        <section class="scha-card">
            <div data-scha-history aria-live="polite" aria-busy="true"><p class="scha-loading"><?php esc_html_e( 'Loading your sessions…', SCHA_TEXT_DOMAIN ); ?></p></div>
            <p class="scha-status" role="status" aria-live="polite" data-scha-status></p>
        </section>
    </div>
</main>
<?php get_footer(); ?>
