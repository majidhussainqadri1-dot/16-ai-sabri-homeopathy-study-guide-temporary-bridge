<?php
defined( 'ABSPATH' ) || exit;
get_header();
$entitlement = SCHA_Entitlements::current();
$provider = SCHA_Provider_Registry::configured();
?>
<main id="primary" class="scha-page" dir="auto"><div class="scha-shell">
<?php SCHA_Public::back_home_controls(); SCHA_Public::page_header( __( 'Sabri Classical Homeopathy AI', SCHA_TEXT_DOMAIN ), __( 'Source-linked educational assistance with citations, privacy controls, and strict clinical boundaries.', SCHA_TEXT_DOMAIN ) ); SCHA_Public::nav(); ?>
<section class="scha-grid">
<article class="scha-card"><h2><?php esc_html_e( 'Approved knowledge only', SCHA_TEXT_DOMAIN ); ?></h2><p><?php esc_html_e( 'Answers use licensed, versioned sources accessible to your account. Unsupported answers are withheld.', SCHA_TEXT_DOMAIN ); ?></p></article>
<article class="scha-card"><h2><?php esc_html_e( 'Not clinical authority', SCHA_TEXT_DOMAIN ); ?></h2><p><?php esc_html_e( 'No diagnosis, prescription, potency, dosage, or emergency replacement.', SCHA_TEXT_DOMAIN ); ?></p></article>
<article class="scha-card"><h2><?php esc_html_e( 'Private by design', SCHA_TEXT_DOMAIN ); ?></h2><p><?php esc_html_e( 'Sessions are private, noindex, exportable, deletable, and retained for a declared period.', SCHA_TEXT_DOMAIN ); ?></p></article>
</section>
<section class="scha-card scha-start"><h2><?php esc_html_e( 'Start a governed session', SCHA_TEXT_DOMAIN ); ?></h2><p><?php echo esc_html( $entitlement['active'] ? __( 'Your AI entitlement is active.', SCHA_TEXT_DOMAIN ) : __( 'A separate AI entitlement is required; the basic education membership alone is not sufficient.', SCHA_TEXT_DOMAIN ) ); ?></p><button class="scha-button scha-button-primary" data-scha-create-session <?php disabled( ! $entitlement['active'] ); ?>>✦ <?php esc_html_e( 'Start AI Session', SCHA_TEXT_DOMAIN ); ?></button><p class="scha-status" data-scha-status aria-live="polite"></p></section>
<?php if ( 'bridge' === $provider->key() ) : ?><section class="scha-card scha-disclosure"><h2><?php esc_html_e( 'Temporary external bridge', SCHA_TEXT_DOMAIN ); ?></h2><p><?php esc_html_e( 'The configured Custom GPT opens externally and has no native access to your private platform data.', SCHA_TEXT_DOMAIN ); ?></p></section><?php endif; ?>
</div></main><?php get_footer(); ?>
