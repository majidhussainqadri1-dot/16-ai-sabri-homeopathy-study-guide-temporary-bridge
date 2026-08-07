<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="primary" class="scha-page" <?php echo SCHA_Public::html_language_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><div class="scha-shell">
<?php SCHA_Public::back_home_controls(); ?>
<section class="scha-card scha-empty"><h1><?php esc_html_e( 'AI resource not found', SCHA_TEXT_DOMAIN ); ?></h1><p><?php esc_html_e( 'The requested session or governance resource is unavailable, expired, restricted, or does not exist.', SCHA_TEXT_DOMAIN ); ?></p><a class="scha-button scha-button-primary" href="<?php echo esc_url( home_url( '/ai/' ) ); ?>"><?php esc_html_e( 'Return to AI Home', SCHA_TEXT_DOMAIN ); ?></a></section>
</div></main>
<?php get_footer(); ?>
