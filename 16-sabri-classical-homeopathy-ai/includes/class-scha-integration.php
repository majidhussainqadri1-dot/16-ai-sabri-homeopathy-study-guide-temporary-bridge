<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Integration {
    public static function register_routes( array $routes ): array {
        $routes['file-16-ai'] = array(
            'owner' => 'File 16', 'version' => SCHA_VERSION,
            'routes' => array( '/ai', '/ai/session/{id}', '/ai/history', '/ai/sources', '/ai/accessibility', '/ai/governance' ),
            'privacy' => 'mixed-public-and-owner-private',
            'clinical_authority' => false,
            'single_free_tier' => true,
            'donor_advantage' => false,
        );
        return $routes;
    }

    public static function register_navigation( array $items ): array {
        $items['sabri-classical-homeopathy-ai'] = array( 'label' => __( 'Sabri Classical Homeopathy AI', SCHA_TEXT_DOMAIN ), 'url' => home_url( '/ai/' ), 'icon' => 'dashicons-superhero-alt', 'priority' => 160, 'owner_file' => 16 );
        return $items;
    }

    public static function register_search_documents( array $documents ): array {
        foreach ( SCHA_Corpus::public_catalog( 100 ) as $item ) {
            $documents[] = array(
                'canonical_id' => 'scha-source:' . $item['public_id'],
                'owner_file' => 16,
                'type' => 'ai_source',
                'title' => $item['title'],
                'url' => $item['source_url'] ?: home_url( '/ai/sources/#source-' . $item['public_id'] ),
                'language' => $item['language'],
                'updated_at' => $item['updated_at'],
                'freshness_at' => $item['updated_at'],
                'visibility' => 'public',
                'why_this_result' => __( 'This is an approved public source in the File 16 AI corpus.', SCHA_TEXT_DOMAIN ),
                'ranking_policy' => array( 'paid_bias' => false, 'donor_bias' => false, 'clinical_rank' => false ),
            );
        }
        return $documents;
    }
}
