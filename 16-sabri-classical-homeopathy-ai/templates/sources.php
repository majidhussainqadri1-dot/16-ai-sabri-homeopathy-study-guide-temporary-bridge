<?php

defined( 'ABSPATH' ) || exit;
get_header();
$entitlement = SCHA_Entitlements::current();
$sources = is_user_logged_in() ? SCHA_Corpus::accessible_catalog( $entitlement['access_class'], 200 ) : SCHA_Corpus::public_catalog( 200 );
?>
<main id="primary" class="scha-page" <?php echo SCHA_Public::html_language_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <div class="scha-shell">
        <?php SCHA_Public::back_home_controls(); ?>
        <?php SCHA_Public::page_header( __( 'Approved AI Sources', SCHA_TEXT_DOMAIN ), __( 'The AI corpus is versioned, access-labelled, rights-reviewed, and controlled by canonical source owners.', SCHA_TEXT_DOMAIN ) ); ?>
        <?php SCHA_Public::nav(); ?>
        <section class="scha-card">
            <h2><?php esc_html_e( 'Method and limitations', SCHA_TEXT_DOMAIN ); ?></h2>
            <p><?php esc_html_e( 'Retrieval is limited to approved sources accessible to the current user. Private clinical records, messages, identity evidence, and unapproved web material are not included. Citations identify source, version, and location.', SCHA_TEXT_DOMAIN ); ?></p>
        </section>
        <section class="scha-source-list" aria-label="<?php esc_attr_e( 'Source catalogue', SCHA_TEXT_DOMAIN ); ?>">
            <?php foreach ( $sources as $source ) : ?>
                <article class="scha-card" id="source-<?php echo esc_attr( $source['public_id'] ); ?>">
                    <h2><?php echo esc_html( $source['title'] ); ?></h2>
                    <dl class="scha-facts">
                        <div><dt><?php esc_html_e( 'Owner', SCHA_TEXT_DOMAIN ); ?></dt><dd><?php echo esc_html( $source['owner_file'] ); ?></dd></div>
                        <div><dt><?php esc_html_e( 'Version', SCHA_TEXT_DOMAIN ); ?></dt><dd><?php echo esc_html( $source['item_version'] ); ?></dd></div>
                        <div><dt><?php esc_html_e( 'Language', SCHA_TEXT_DOMAIN ); ?></dt><dd><?php echo esc_html( $source['language'] ); ?></dd></div>
                        <div><dt><?php esc_html_e( 'License', SCHA_TEXT_DOMAIN ); ?></dt><dd><?php echo esc_html( $source['license_name'] ); ?></dd></div>
                        <div><dt><?php esc_html_e( 'Approved AI use', SCHA_TEXT_DOMAIN ); ?></dt><dd><?php echo esc_html( $source['approved_use'] ); ?></dd></div>
                        <div><dt><?php esc_html_e( 'Rights review', SCHA_TEXT_DOMAIN ); ?></dt><dd><?php echo esc_html( $source['rights_reviewed_at'] ); ?></dd></div>
                        <?php if ( isset( $source['access_class'] ) ) : ?><div><dt><?php esc_html_e( 'Access', SCHA_TEXT_DOMAIN ); ?></dt><dd><?php echo esc_html( $source['access_class'] ); ?></dd></div><?php endif; ?>
                    </dl>
                    <?php if ( ! empty( $source['source_url'] ) ) : ?><a class="scha-button scha-button-quiet" href="<?php echo esc_url( $source['source_url'] ); ?>" rel="noopener"><?php esc_html_e( 'Open Canonical Source', SCHA_TEXT_DOMAIN ); ?></a><?php endif; ?>
                </article>
            <?php endforeach; ?>
            <?php if ( empty( $sources ) ) : ?>
                <div class="scha-card scha-empty"><h2><?php esc_html_e( 'No approved source is available', SCHA_TEXT_DOMAIN ); ?></h2><p><?php esc_html_e( 'An answer cannot be grounded until a licensed, versioned source is approved and indexed.', SCHA_TEXT_DOMAIN ); ?></p></div>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php get_footer(); ?>
