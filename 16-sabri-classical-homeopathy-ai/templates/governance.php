<?php

defined( 'ABSPATH' ) || exit;
get_header();
$health = SCHA_Health::report();
$runs = SCHA_Evaluation::recent( 10 );
?>
<main id="primary" class="scha-page" <?php echo SCHA_Public::html_language_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <div class="scha-shell">
        <?php SCHA_Public::back_home_controls(); ?>
        <?php SCHA_Public::page_header( __( 'AI Governance', SCHA_TEXT_DOMAIN ), __( 'Restricted operational evidence for corpus, policy, provider, evaluations, privacy, cost, and incidents.', SCHA_TEXT_DOMAIN ) ); ?>
        <?php SCHA_Public::nav(); ?>
        <section class="scha-card">
            <h2><?php esc_html_e( 'System status', SCHA_TEXT_DOMAIN ); ?></h2>
            <p class="<?php echo $health['ok'] ? 'scha-success' : 'scha-warning'; ?>"><strong><?php echo esc_html( $health['status'] ); ?></strong></p>
            <div class="scha-table-wrap"><table><thead><tr><th><?php esc_html_e( 'Check', SCHA_TEXT_DOMAIN ); ?></th><th><?php esc_html_e( 'Pass', SCHA_TEXT_DOMAIN ); ?></th><th><?php esc_html_e( 'Value', SCHA_TEXT_DOMAIN ); ?></th></tr></thead><tbody>
            <?php foreach ( $health['checks'] as $name => $check ) : ?><tr><td><?php echo esc_html( $name ); ?></td><td><?php echo $check['ok'] ? '✅' : '⚠️'; ?></td><td><code><?php echo esc_html( is_scalar( $check['value'] ) ? (string) $check['value'] : wp_json_encode( $check['value'] ) ); ?></code></td></tr><?php endforeach; ?>
            </tbody></table></div>
        </section>
        <section class="scha-card">
            <h2><?php esc_html_e( 'Recent evaluations', SCHA_TEXT_DOMAIN ); ?></h2>
            <div class="scha-table-wrap"><table><thead><tr><th><?php esc_html_e( 'Run', SCHA_TEXT_DOMAIN ); ?></th><th><?php esc_html_e( 'Status', SCHA_TEXT_DOMAIN ); ?></th><th><?php esc_html_e( 'Pass rate', SCHA_TEXT_DOMAIN ); ?></th></tr></thead><tbody>
            <?php foreach ( $runs as $run ) : ?><tr><td><code><?php echo esc_html( $run['public_id'] ); ?></code></td><td><?php echo esc_html( $run['status'] ); ?></td><td><?php echo esc_html( isset( $run['metrics']['pass_rate'] ) ? (string) $run['metrics']['pass_rate'] : '—' ); ?></td></tr><?php endforeach; ?>
            <?php if ( empty( $runs ) ) : ?><tr><td colspan="3"><?php esc_html_e( 'No evaluation evidence yet.', SCHA_TEXT_DOMAIN ); ?></td></tr><?php endif; ?>
            </tbody></table></div>
        </section>
    </div>
</main>
<?php get_footer(); ?>
