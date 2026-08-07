<?php

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="primary" class="scha-page" <?php echo SCHA_Public::html_language_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
<div class="scha-shell"><?php SCHA_Public::back_home_controls(); ?><?php SCHA_Public::page_header( __( 'AI Accessibility Statement', SCHA_TEXT_DOMAIN ), __( 'The File 16 interface is designed for keyboard, screen-reader, RTL, reduced-motion and low-bandwidth use.', SCHA_TEXT_DOMAIN ) ); ?><?php SCHA_Public::nav(); ?>
<section class="scha-card"><h2><?php esc_html_e( 'Implemented accessibility controls', SCHA_TEXT_DOMAIN ); ?></h2><ul><li><?php esc_html_e( 'Semantic landmarks, labels, live status regions and predictable focus order.', SCHA_TEXT_DOMAIN ); ?></li><li><?php esc_html_e( 'Keyboard-operable controls with visible focus and minimum 44px targets.', SCHA_TEXT_DOMAIN ); ?></li><li><?php esc_html_e( 'Logical CSS properties and mirrored direction-sensitive controls for RTL.', SCHA_TEXT_DOMAIN ); ?></li><li><?php esc_html_e( 'Reduced-motion and forced-colors support.', SCHA_TEXT_DOMAIN ); ?></li><li><?php esc_html_e( 'A low-bandwidth mode that suppresses decorative effects without removing content.', SCHA_TEXT_DOMAIN ); ?></li></ul></section>
<section class="scha-card"><h2><?php esc_html_e( 'Report a barrier', SCHA_TEXT_DOMAIN ); ?></h2><p><?php esc_html_e( 'Use the platform Support owner to report the page, device, browser, assistive technology and the exact task that failed. Accessibility reports never require clinical information.', SCHA_TEXT_DOMAIN ); ?></p></section>
</div></main><?php get_footer(); ?>
