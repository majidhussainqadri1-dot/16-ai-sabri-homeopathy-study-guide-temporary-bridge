<?php

defined( 'ABSPATH' ) || exit;

/**
 * Canonical provider-side lifecycle contract.
 *
 * Built-in adapters send stateless request/response calls and do not create a
 * provider conversation/session object. Integrators that persist a provider
 * session can return a confirmed/pending/failed deletion result through the
 * versioned filter below; no deletion is silently claimed as confirmed.
 */
final class SCHA_Provider_Data_Lifecycle {
    public static function delete_session( string $session_public_id, string $provider, string $reason ): array {
        $provider = sanitize_key( $provider );
        $reason = sanitize_key( $reason );
        $result = array(
            'status'   => 'not_applicable',
            'provider' => $provider,
            'reason'   => 'sessionless-request-transport',
        );

        // Legacy integrations may still listen to the action. New integrations
        // should additionally return an explicit acknowledgement via the filter.
        do_action( 'scha_provider_delete_session', $session_public_id, $provider, $reason );
        $filtered = apply_filters( 'scha_provider_delete_session_result_v1', $result, $session_public_id, $provider, $reason );
        if ( is_array( $filtered ) ) $result = array_merge( $result, $filtered );

        $status = sanitize_key( (string) ( $result['status'] ?? 'failed' ) );
        if ( ! in_array( $status, array( 'confirmed', 'not_applicable', 'pending', 'failed' ), true ) ) $status = 'failed';
        $result['status'] = $status;
        $result['provider'] = $provider;
        unset( $result['raw'], $result['request'], $result['response'], $result['secret'], $result['token'] );

        SCHA_Observability::audit(
            'provider_session_deletion_status',
            'ai_session',
            $session_public_id,
            array( 'provider' => $provider, 'status' => $status, 'reason' => sanitize_key( (string) ( $result['reason'] ?? $reason ) ) ),
            'provider-data-lifecycle'
        );
        return $result;
    }
}
