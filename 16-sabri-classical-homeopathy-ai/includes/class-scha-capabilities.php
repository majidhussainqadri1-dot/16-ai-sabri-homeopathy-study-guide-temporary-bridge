<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Capabilities {
    public const USE_AI         = 'scha_use_ai';
    public const MANAGE_AI      = 'scha_manage_ai';
    public const MANAGE_CORPUS  = 'scha_manage_corpus';
    public const REVIEW_SAFETY  = 'scha_review_ai_safety';
    public const VIEW_METRICS   = 'scha_view_ai_metrics';

    public static function install(): void {
        $roles = array_filter(
            array(
                get_role( 'administrator' ),
                get_role( 'founder' ),
                get_role( 'sabri_founder' ),
            )
        );
        foreach ( $roles as $role ) {
            foreach ( self::all() as $cap ) {
                $role->add_cap( $cap );
            }
        }
    }

    public static function all(): array {
        return array( self::USE_AI, self::MANAGE_AI, self::MANAGE_CORPUS, self::REVIEW_SAFETY, self::VIEW_METRICS );
    }

    public static function current_user_can_manage(): bool {
        return current_user_can( self::MANAGE_AI ) || current_user_can( 'manage_options' );
    }
}
