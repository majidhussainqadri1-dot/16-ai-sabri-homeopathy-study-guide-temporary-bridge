<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Assistant_Modes {
    public static function all(): array {
        return array(
            'study'            => __( 'Source-grounded study', SCHA_TEXT_DOMAIN ),
            'learning_tutor'   => __( 'Learning tutor', SCHA_TEXT_DOMAIN ),
            'search_assistant' => __( 'Knowledge search assistant', SCHA_TEXT_DOMAIN ),
            'creator_assistant'=> __( 'Creator assistant', SCHA_TEXT_DOMAIN ),
            'doctor_admin'     => __( 'Doctor administrative assistant', SCHA_TEXT_DOMAIN ),
        );
    }

    public static function validate( string $mode, array $context ): string|WP_Error {
        $mode = sanitize_key( $mode );
        if ( ! isset( self::all()[ $mode ] ) ) {
            return new WP_Error( 'scha_invalid_assistant_mode', __( 'The requested assistant mode is not available.', SCHA_TEXT_DOMAIN ), array( 'status' => 400 ) );
        }
        if ( 'doctor_admin' === $mode && empty( $context['verified_doctor'] ) && empty( $context['founder'] ) ) {
            return new WP_Error( 'scha_doctor_mode_forbidden', __( 'Doctor administrative assistance is limited to an eligible verified doctor or the Founder.', SCHA_TEXT_DOMAIN ), array( 'status' => 403 ) );
        }
        return $mode;
    }

    public static function instruction( string $mode ): string {
        return match ( $mode ) {
            'learning_tutor' => 'Teach step by step, ask a brief comprehension question, and keep every substantive claim tied to supplied sources.',
            'search_assistant' => 'Return a concise evidence map explaining why each supplied source is relevant; do not invent results.',
            'creator_assistant' => 'Offer clearly labelled drafts for human review only; never auto-publish, impersonate, or fabricate references.',
            'doctor_admin' => 'Assist only with non-clinical administration, education and documentation; never diagnose, prescribe, select a remedy, potency, dose or frequency.',
            default => 'Answer as a source-grounded educational study guide using only supplied sources.',
        };
    }
}
