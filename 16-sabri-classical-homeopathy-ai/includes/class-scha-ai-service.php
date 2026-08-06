<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_AI_Service {
    public static function answer( string $session_public_id, string $prompt, string $idempotency_key, string $fingerprint = '' ): array|WP_Error {
        $trace_id = SCHA_Observability::trace_id();
        SCHA_Observability::metric( 'requests' );
        try {
            if ( ! SCHA_Settings::get( 'enabled', true ) ) {
                return new WP_Error( 'scha_disabled', __( 'The AI service is currently disabled.', SCHA_TEXT_DOMAIN ), array( 'status' => 503 ) );
            }
            $entitlement_check = SCHA_Entitlements::require_active();
            if ( is_wp_error( $entitlement_check ) ) {
                return $entitlement_check;
            }
            $entitlement = SCHA_Entitlements::current();
            $session = SCHA_Session_Service::owned( $session_public_id );
            if ( is_wp_error( $session ) ) {
                return $session;
            }
            if ( 'active' !== $session['status'] ) {
                return new WP_Error( 'scha_session_not_active', __( 'This session is not active.', SCHA_TEXT_DOMAIN ), array( 'status' => 409 ) );
            }
            $idempotency_key = self::valid_idempotency_key( $idempotency_key );
            if ( is_wp_error( $idempotency_key ) ) {
                return $idempotency_key;
            }
            $existing = SCHA_Session_Service::find_response_by_idempotency( absint( $session['id'] ), $idempotency_key );
            if ( $existing ) {
                return array( 'message' => $existing, 'idempotent_replay' => true, 'trace_id' => $trace_id );
            }

            $rate = SCHA_Rate_Limiter::check( $entitlement, $fingerprint );
            if ( is_wp_error( $rate ) ) {
                return $rate;
            }
            $policy = SCHA_Prompt_Policy::classify( $prompt );
            $privacy = SCHA_Privacy::provider_text( $prompt );
            $user_message_id = SCHA_Session_Service::insert_message(
                absint( $session['id'] ),
                'user',
                $prompt,
                array(
                    'redacted_content' => $privacy['redacted'],
                    'safety_category'  => $policy['category'],
                    'idempotency_key'  => $idempotency_key . ':user',
                )
            );
            if ( is_wp_error( $user_message_id ) ) {
                return $user_message_id;
            }

            if ( ! $policy['allowed'] ) {
                $answer = self::format_refusal( $policy );
                $assistant_id = SCHA_Session_Service::insert_message(
                    absint( $session['id'] ),
                    'assistant',
                    $answer,
                    array(
                        'safety_category' => $policy['category'],
                        'idempotency_key' => $idempotency_key . ':assistant',
                    )
                );
                if ( is_wp_error( $assistant_id ) ) {
                    return $assistant_id;
                }
                SCHA_Usage_Ledger::record(
                    array(
                        'session_id'         => $session['id'],
                        'response_message_id'=> $assistant_id,
                        'provider'           => 'policy',
                        'model'              => 'answer-safety-' . $policy['policy_version'],
                        'request_hash'       => $privacy['redacted'],
                        'idempotency_key'    => $idempotency_key,
                        'input_tokens'       => SCHA_Usage_Ledger::estimate_tokens( $privacy['redacted'] ),
                        'output_tokens'      => SCHA_Usage_Ledger::estimate_tokens( $answer ),
                        'cost_micros'        => 0,
                    )
                );
                SCHA_Observability::metric( 'refusals' );
                SCHA_Outbox::publish( 'AIAnswerRefused', 'ai_session', $session['public_id'], array( 'session_id' => $session['public_id'], 'category' => $policy['category'], 'trace_id' => $trace_id ), 'refused-' . $session['public_id'] . '-' . $idempotency_key );
                $stored = SCHA_Session_Service::get_message_by_id( $assistant_id );
                return is_wp_error( $stored ) ? $stored : array( 'message' => $stored, 'refused' => true, 'trace_id' => $trace_id );
            }

            $sources = SCHA_Retrieval::search( $privacy['redacted'], $entitlement, 6 );
            $provider = SCHA_Provider_Registry::by_key( (string) $session['provider'] );
            if ( 'bridge' !== $provider->key() && empty( $sources ) ) {
                $answer = __( 'The approved sources available to your account do not provide enough evidence for a grounded answer. No citation has been invented.', SCHA_TEXT_DOMAIN );
                $assistant_id = SCHA_Session_Service::insert_message(
                    absint( $session['id'] ),
                    'assistant',
                    $answer,
                    array( 'safety_category' => 'insufficient_evidence', 'idempotency_key' => $idempotency_key . ':assistant' )
                );
                if ( is_wp_error( $assistant_id ) ) {
                    return $assistant_id;
                }
                SCHA_Usage_Ledger::record(
                    array(
                        'session_id' => $session['id'], 'response_message_id' => $assistant_id, 'provider' => 'retrieval', 'model' => 'no-evidence',
                        'request_hash' => $privacy['redacted'], 'idempotency_key' => $idempotency_key,
                        'input_tokens' => SCHA_Usage_Ledger::estimate_tokens( $privacy['redacted'] ), 'output_tokens' => SCHA_Usage_Ledger::estimate_tokens( $answer ), 'cost_micros' => 0,
                    )
                );
                $stored = SCHA_Session_Service::get_message_by_id( $assistant_id );
                return is_wp_error( $stored ) ? $stored : array( 'message' => $stored, 'insufficient_evidence' => true, 'trace_id' => $trace_id );
            }

            if ( 'http_json' === $provider->key() ) {
                $budget = SCHA_Usage_Ledger::budget_check( get_current_user_id() );
                if ( is_wp_error( $budget ) ) {
                    return $budget;
                }
            }

            $result = $provider->generate(
                array(
                    'prompt'         => $privacy['redacted'],
                    'sources'        => $sources,
                    'trace_id'       => $trace_id,
                    'policy_version' => $policy['policy_version'],
                    'locale'         => $session['locale'],
                    'role'           => $entitlement['role'],
                )
            );
            if ( is_wp_error( $result ) ) {
                SCHA_Observability::metric( 'provider_failures' );
                SCHA_Observability::safe_error( $result->get_error_message(), $trace_id );
                return new WP_Error( 'scha_answer_unavailable', __( 'A grounded answer is temporarily unavailable. No unverified answer was delivered.', SCHA_TEXT_DOMAIN ), array( 'status' => 503, 'trace_id' => $trace_id ) );
            }

            $citations = array();
            if ( empty( $result['bridge'] ) ) {
                $validation = SCHA_Citation_Validator::validate( (string) $result['answer'], $sources );
                if ( ! $validation['valid'] ) {
                    SCHA_Observability::metric( 'citation_failures' );
                    $result['answer'] = __( 'The generated response did not pass citation validation, so it has been withheld. Please refine the question or consult the approved sources directly.', SCHA_TEXT_DOMAIN );
                    $result['provider'] = 'citation-gate';
                    $result['model'] = 'withheld';
                    $result['cost_micros'] = absint( $result['cost_micros'] ?? 0 );
                    $citations = array();
                } else {
                    $result['answer'] = $validation['answer'];
                    $citations = $validation['citations'];
                }
            }

            $assistant_id = SCHA_Session_Service::insert_message(
                absint( $session['id'] ),
                'assistant',
                wp_kses_post( (string) $result['answer'] ),
                array(
                    'citations'            => $citations,
                    'safety_category'      => empty( $citations ) && empty( $result['bridge'] ) ? 'citation_withheld' : 'educational',
                    'provider_response_id' => $result['response_id'] ?? '',
                    'idempotency_key'      => $idempotency_key . ':assistant',
                )
            );
            if ( is_wp_error( $assistant_id ) ) {
                return $assistant_id;
            }
            SCHA_Usage_Ledger::record(
                array(
                    'session_id'          => $session['id'],
                    'response_message_id' => $assistant_id,
                    'provider'            => $result['provider'] ?? $provider->key(),
                    'model'               => $result['model'] ?? '',
                    'request_hash'        => $privacy['redacted'],
                    'idempotency_key'     => $idempotency_key,
                    'input_tokens'        => absint( $result['input_tokens'] ?? 0 ),
                    'output_tokens'       => absint( $result['output_tokens'] ?? 0 ),
                    'cost_micros'         => absint( $result['cost_micros'] ?? 0 ),
                )
            );
            SCHA_Observability::metric( 'answers' );
            SCHA_Outbox::publish( 'AIAnswerDelivered', 'ai_session', $session['public_id'], array( 'session_id' => $session['public_id'], 'provider' => $result['provider'] ?? $provider->key(), 'citations' => count( $citations ), 'trace_id' => $trace_id ), 'delivered-' . $session['public_id'] . '-' . $idempotency_key );
            SCHA_Outbox::publish( 'AIUsageRecorded', 'ai_session', $session['public_id'], array( 'session_id' => $session['public_id'], 'input_tokens' => absint( $result['input_tokens'] ?? 0 ), 'output_tokens' => absint( $result['output_tokens'] ?? 0 ), 'cost_micros' => absint( $result['cost_micros'] ?? 0 ) ), 'usage-' . $session['public_id'] . '-' . $idempotency_key );
            $stored = SCHA_Session_Service::get_message_by_id( $assistant_id );
            if ( is_wp_error( $stored ) ) {
                return $stored;
            }
            return array(
                'message'    => $stored,
                'disclosure' => sanitize_text_field( $result['disclosure'] ?? '' ),
                'bridge_url' => ! empty( $result['bridge_url'] ) ? esc_url_raw( $result['bridge_url'] ) : '',
                'trace_id'   => $trace_id,
            );
        } catch ( Throwable $e ) {
            SCHA_Observability::safe_error( $e, $trace_id );
            return new WP_Error( 'scha_internal_error', __( 'The request could not be completed safely.', SCHA_TEXT_DOMAIN ), array( 'status' => 500, 'trace_id' => $trace_id ) );
        }
    }

    private static function valid_idempotency_key( string $key ): string|WP_Error {
        $key = trim( $key );
        if ( ! preg_match( '/^[A-Za-z0-9._:-]{12,128}$/', $key ) ) {
            return new WP_Error( 'scha_idempotency_required', __( 'A valid idempotency key is required.', SCHA_TEXT_DOMAIN ), array( 'status' => 400 ) );
        }
        return $key;
    }

    private static function format_refusal( array $policy ): string {
        $answer = $policy['message'];
        if ( ! empty( $policy['escalation'] ) ) {
            $answer .= "\n\n" . __( 'Safe next steps:', SCHA_TEXT_DOMAIN );
            foreach ( $policy['escalation'] as $link ) {
                $answer .= "\n- " . $link['label'];
                if ( ! empty( $link['url'] ) ) {
                    $answer .= ': ' . esc_url_raw( $link['url'] );
                }
            }
        }
        return $answer;
    }
}
