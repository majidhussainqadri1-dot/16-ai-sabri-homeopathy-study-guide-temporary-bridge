<?php

defined( 'ABSPATH' ) || exit;

/** Cross-disciplinary publication gates required before AI Teacher publication. */
final class SCHA_Governance_Gates {
    public static function teacher_draft( string $content, array $sources, array $context = array() ): array {
        $rights_ok = true;
        foreach ( $sources as $source ) {
            if ( '' === trim( (string) ( $source['license'] ?? '' ) )
                || '' === trim( (string) ( $source['approved_use'] ?? '' ) )
                || '' === trim( (string) ( $source['rights_evidence_id'] ?? '' ) )
                || empty( $source['rights_reviewed_at'] ) ) {
                $rights_ok = false;
                break;
            }
        }
        $medical = SCHA_Output_Policy::validate( $content );
        $sharia = apply_filters( 'scha_sharia_content_review_v1', null, $content, $sources, $context );
        $sharia_state = true === $sharia ? 'approved' : ( false === $sharia ? 'rejected' : 'human-review-required' );

        return array(
            'rights_ok'      => $rights_ok,
            'medical_ok'     => (bool) $medical['valid'],
            'medical_reason' => (string) $medical['category'],
            'sharia_state'   => $sharia_state,
            'auto_publish_ok'=> $rights_ok && $medical['valid'] && true === $sharia,
        );
    }
}
