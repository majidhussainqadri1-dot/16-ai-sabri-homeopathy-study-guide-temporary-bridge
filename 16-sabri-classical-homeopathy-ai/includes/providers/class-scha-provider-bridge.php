<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Provider_Bridge implements SCHA_Provider_Interface {
    public function key(): string {
        return 'bridge';
    }

    public function is_available(): bool {
        return '' !== SCHA_Settings::sanitize_bridge_url( (string) SCHA_Settings::get( 'bridge_url', '' ) );
    }

    public function generate( array $request ): array|WP_Error {
        $url = SCHA_Settings::sanitize_bridge_url( (string) SCHA_Settings::get( 'bridge_url', '' ) );
        if ( '' === $url ) {
            return new WP_Error( 'scha_bridge_unavailable', __( 'The temporary Custom GPT bridge is not configured.', SCHA_TEXT_DOMAIN ) );
        }
        return array(
            'bridge'       => true,
            'bridge_url'   => $url,
            'answer'       => __( 'The temporary bridge opens an external Custom GPT in a new tab. It does not have native access to your platform account, private records, paid corpus, or session history unless a future approved integration explicitly provides that access.', SCHA_TEXT_DOMAIN ),
            'provider'     => 'bridge',
            'model'        => 'external-custom-gpt',
            'response_id'  => wp_generate_uuid4(),
            'input_tokens' => 0,
            'output_tokens'=> 0,
            'cost_micros'  => 0,
            'disclosure'   => __( 'External provider bridge; model identity and policies are controlled by the external service.', SCHA_TEXT_DOMAIN ),
        );
    }
}
