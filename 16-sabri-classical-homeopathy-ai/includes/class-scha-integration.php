<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Integration {
    /** @var callable|null */
    private static $grounded_profile_context_provider = null;

    /** Accept the canonical File 03 public-work context provider. */
    public static function register_grounded_profile_context_provider( string $owner, $callback ): void {
        if ( 'file03' !== sanitize_key( $owner ) || ! is_callable( $callback ) ) {
            return;
        }
        self::$grounded_profile_context_provider = $callback;
    }

    /**
     * File 03 FUT-08 provider: answer only from current public professional evidence.
     * No private profile fields, diagnosis, prescribing, dosage or emergency substitution.
     */
    public static function grounded_profile_ask( $claim, int $user_id, int $viewer_id, string $consumer_contract, string $public_id, string $question ) {
        if ( $user_id <= 0 || $viewer_id <= 0 || $viewer_id !== get_current_user_id() || ! is_user_logged_in() ) {
            return $claim;
        }
        if ( '' !== $consumer_contract && ! preg_match( '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $consumer_contract ) ) {
            return $claim;
        }
        if ( ! SCHA_Settings::get( 'enabled', true ) ) {
            return $claim;
        }
        $access = SCHA_Entitlements::require_active();
        if ( is_wp_error( $access ) ) {
            return $claim;
        }
        $entitlement = SCHA_Entitlements::current();
        if ( absint( $entitlement['user_id'] ?? 0 ) !== $viewer_id ) {
            return $claim;
        }

        $question = trim( wp_strip_all_tags( $question ) );
        if ( '' === $question || ( function_exists( 'mb_strlen' ) ? mb_strlen( $question ) : strlen( $question ) ) > 500 ) {
            return $claim;
        }
        $policy = SCHA_Prompt_Policy::classify( $question );
        if ( empty( $policy['allowed'] ) ) {
            return $claim;
        }

        $rate = SCHA_Rate_Limiter::check( $entitlement, 'profile-work' );
        if ( is_wp_error( $rate ) ) {
            return $claim;
        }
        if ( ! is_callable( self::$grounded_profile_context_provider ) ) {
            return $claim;
        }

        try {
            $context = call_user_func( self::$grounded_profile_context_provider, $user_id, 0 );
        } catch ( Throwable $error ) {
            SCHA_Observability::metric( 'provider_failures' );
            SCHA_Observability::safe_error( $error->getMessage(), SCHA_Observability::trace_id() );
            return $claim;
        }
        if ( is_wp_error( $context ) || ! self::current_profile_context( $context, $user_id, $public_id ) ) {
            return $claim;
        }

        $sources = self::profile_sources( (array) ( $context['sources'] ?? array() ) );
        if ( empty( $sources ) ) {
            return $claim;
        }

        $provider = SCHA_Provider_Registry::selected();
        if ( in_array( $provider->key(), array( 'http_json', 'claude' ), true ) ) {
            $budget = SCHA_Usage_Ledger::budget_check( $viewer_id );
            if ( is_wp_error( $budget ) ) {
                return $claim;
            }
        }

        $trace_id = SCHA_Observability::trace_id();
        $privacy = SCHA_Privacy::provider_text( $question );
        $safe_prompt = trim( (string) ( $privacy['redacted'] ?? '' ) );
        if ( '' === $safe_prompt ) {
            return $claim;
        }
        $result = $provider->generate( array(
            'prompt'             => $safe_prompt,
            'sources'            => $sources,
            'trace_id'           => $trace_id,
            'policy_version'     => sanitize_text_field( (string) ( $policy['policy_version'] ?? '' ) ),
            'locale'             => sanitize_text_field( (string) ( $entitlement['locale'] ?? get_locale() ) ),
            'role'               => sanitize_key( (string) ( $entitlement['role'] ?? 'member' ) ),
            'assistant_mode'     => 'study',
            'mode_instruction'   => 'Answer only about the supplied professional public work. Do not infer biography, competence, treatment outcomes or private facts.',
            'clinical_authority' => false,
        ) );
        if ( is_wp_error( $result ) ) {
            SCHA_Observability::metric( 'provider_failures' );
            return $claim;
        }

        $answer = (string) ( $result['answer'] ?? '' );
        $output = SCHA_Output_Policy::validate( $answer );
        if ( empty( $output['valid'] ) ) {
            SCHA_Observability::metric( 'output_policy_failures' );
            return $claim;
        }
        $validated = SCHA_Citation_Validator::validate( $answer, $sources );
        if ( empty( $validated['valid'] ) ) {
            SCHA_Observability::metric( 'citation_failures' );
            return $claim;
        }

        $citations = array();
        foreach ( array_slice( (array) $validated['citations'], 0, 12 ) as $citation ) {
            if ( ! is_array( $citation ) || empty( $citation['url'] ) || ! self::same_origin_url( (string) $citation['url'] ) ) {
                continue;
            }
            $citations[] = array(
                'title' => sanitize_text_field( (string) ( $citation['title'] ?? '' ) ),
                'url'   => esc_url_raw( (string) $citation['url'] ),
            );
        }
        if ( empty( $citations ) ) {
            return $claim;
        }

        $usage_key = 'profile-work:' . wp_generate_uuid4();
        $usage = SCHA_Usage_Ledger::record( array(
            'user_id'         => $viewer_id,
            'session_id'      => 0,
            'provider'        => sanitize_key( (string) ( $result['provider'] ?? $provider->key() ) ),
            'model'           => sanitize_text_field( (string) ( $result['model'] ?? '' ) ),
            'request_hash'    => hash_hmac( 'sha256', $safe_prompt . '|' . $public_id, wp_salt( 'auth' ) ),
            'idempotency_key' => $usage_key,
            'input_tokens'    => absint( $result['input_tokens'] ?? SCHA_Usage_Ledger::estimate_tokens( $safe_prompt ) ),
            'output_tokens'   => absint( $result['output_tokens'] ?? SCHA_Usage_Ledger::estimate_tokens( (string) $validated['answer'] ) ),
            'cost_micros'     => absint( $result['cost_micros'] ?? 0 ),
        ) );
        if ( is_wp_error( $usage ) ) {
            SCHA_Observability::safe_error( $usage->get_error_message(), $trace_id );
        }

        $now = time();
        return array(
            'contract_version' => '1.0.0',
            'generated_at'     => gmdate( 'c', $now ),
            'valid_until'      => gmdate( 'c', $now + 120 ),
            'user_id'          => $user_id,
            'viewer_user_id'   => $viewer_id,
            'public_id'        => sanitize_text_field( $public_id ),
            'scope'            => 'public_professional_work',
            'grounded'         => true,
            'answer'           => wp_kses_post( (string) $validated['answer'] ),
            'citations'        => $citations,
            'medical_advice'   => false,
            'provider'         => sanitize_key( (string) ( $result['provider'] ?? $provider->key() ) ),
            'trace_id'         => $trace_id,
        );
    }

    private static function current_profile_context( array $context, int $user_id, string $public_id ): bool {
        $contract = sanitize_text_field( (string) ( $context['contract_version'] ?? '' ) );
        $generated = strtotime( (string) ( $context['generated_at'] ?? '' ) );
        $valid_until = strtotime( (string) ( $context['valid_until'] ?? '' ) );
        $now = time();
        return '' !== $contract
            && version_compare( $contract, '1.0.0', '>=' )
            && false !== $generated
            && false !== $valid_until
            && $generated <= $now + 300
            && $generated >= $now - 300
            && $valid_until > $now
            && absint( $context['user_id'] ?? 0 ) === $user_id
            && hash_equals( sanitize_text_field( $public_id ), sanitize_text_field( (string) ( $context['public_id'] ?? '' ) ) )
            && 'public_professional_work' === sanitize_key( (string) ( $context['scope'] ?? '' ) );
    }

    /** @return array<int,array<string,string>> */
    private static function profile_sources( array $raw_sources ): array {
        $sources = array();
        foreach ( array_slice( $raw_sources, 0, 12 ) as $source ) {
            if ( ! is_array( $source ) ) {
                continue;
            }
            $url = esc_url_raw( (string) ( $source['url'] ?? '' ) );
            if ( ! self::same_origin_url( $url ) ) {
                continue;
            }
            $content = trim( wp_strip_all_tags( (string) ( $source['content'] ?? '' ) ) );
            if ( '' === $content ) {
                continue;
            }
            $content = function_exists( 'mb_substr' ) ? mb_substr( $content, 0, 6000 ) : substr( $content, 0, 6000 );
            $sources[] = array(
                'source_id'          => sanitize_text_field( (string) ( $source['source_id'] ?? '' ) ),
                'owner_file'         => sanitize_text_field( (string) ( $source['owner_file'] ?? 'File 03' ) ),
                'label'              => sanitize_text_field( (string) ( $source['owner_file'] ?? 'File 03' ) ),
                'title'              => sanitize_text_field( (string) ( $source['title'] ?? 'Professional public work' ) ),
                'version'            => sanitize_text_field( (string) ( $source['version'] ?? '1' ) ),
                'location'           => sanitize_text_field( (string) ( $source['location'] ?? 'public profile' ) ),
                'url'                => $url,
                'content'            => $content,
                'license'            => sanitize_text_field( (string) ( $source['license'] ?? '' ) ),
                'approved_use'       => 'public_professional_work',
                'rights_reviewed_at' => sanitize_text_field( (string) ( $source['rights_reviewed_at'] ?? '' ) ),
            );
        }
        return array_values( array_filter( $sources, static function ( array $source ): bool {
            return '' !== $source['source_id'] && '' !== $source['title'] && '' !== $source['version'] && '' !== $source['location'];
        } ) );
    }

    private static function same_origin_url( string $url ): bool {
        $url = trim( $url );
        if ( '' === $url ) return false;
        $parts = wp_parse_url( $url );
        $home = wp_parse_url( home_url( '/' ) );
        if ( ! is_array( $parts ) || ! is_array( $home ) || empty( $parts['host'] ) || empty( $home['host'] ) ) return false;
        $scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
        return in_array( $scheme, array( 'http', 'https' ), true )
            && 0 === strcasecmp( (string) $parts['host'], (string) $home['host'] )
            && ! isset( $parts['user'], $parts['pass'] );
    }

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
