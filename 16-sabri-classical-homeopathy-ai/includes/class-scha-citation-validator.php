<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Citation_Validator {
    public static function validate( string $answer, array $sources ): array {
        $answer = trim( $answer );
        if ( '' === $answer ) {
            return array( 'valid' => false, 'reason' => 'empty_answer', 'answer' => '', 'citations' => array() );
        }
        if ( empty( $sources ) ) {
            return array( 'valid' => false, 'reason' => 'no_sources', 'answer' => $answer, 'citations' => array() );
        }

        preg_match_all( '/\[S(\d+)\]/', $answer, $matches );
        $used = array_values( array_unique( array_map( 'absint', $matches[1] ?? array() ) ) );
        if ( empty( $used ) ) {
            return array( 'valid' => false, 'reason' => 'citation_missing', 'answer' => $answer, 'citations' => array() );
        }

        $citations = array();
        foreach ( $used as $number ) {
            $index = $number - 1;
            if ( ! isset( $sources[ $index ] ) ) {
                return array( 'valid' => false, 'reason' => 'citation_out_of_range', 'answer' => $answer, 'citations' => array() );
            }
            $source = $sources[ $index ];
            if ( empty( $source['source_id'] ) || empty( $source['version'] ) || empty( $source['location'] ) ) {
                return array( 'valid' => false, 'reason' => 'citation_incomplete', 'answer' => $answer, 'citations' => array() );
            }
            $citations[] = array(
                'marker'    => 'S' . $number,
                'source_id' => $source['source_id'],
                'title'     => $source['title'],
                'version'   => $source['version'],
                'location'  => $source['location'],
                'url'       => $source['url'],
                'owner_file'=> $source['owner_file'],
                'license'   => (string) ( $source['license'] ?? '' ),
                'approved_use' => (string) ( $source['approved_use'] ?? '' ),
                'rights_reviewed_at' => (string) ( $source['rights_reviewed_at'] ?? '' ),
            );
        }

        foreach ( preg_split( '/\R+/u', $answer ) ?: array() as $block ) {
            if ( ! self::substantive_block_requires_citation( (string) $block ) ) {
                continue;
            }
            if ( ! preg_match_all( '/\[S(\d+)\]/', (string) $block, $block_matches ) ) {
                return array( 'valid' => false, 'reason' => 'citation_coverage', 'answer' => $answer, 'citations' => array() );
            }
            if ( ! self::has_basic_source_overlap( (string) $block, $sources, $block_matches[1] ) ) {
                return array( 'valid' => false, 'reason' => 'citation_content_mismatch', 'answer' => $answer, 'citations' => array() );
            }
        }

        return array( 'valid' => true, 'reason' => '', 'answer' => $answer, 'citations' => $citations );
    }

    /**
     * Cheap lexical mismatch prefilter; not a factual entailment or translation verifier.
     * An unrelated claim cannot pass merely by appending [S1].
     */
    private static function has_basic_source_overlap( string $block, array $sources, array $markers ): bool {
        $excerpts = array();
        foreach ( array_unique( array_map( 'absint', $markers ) ) as $number ) {
            $content = trim( wp_strip_all_tags( (string) ( $sources[ $number - 1 ]['content'] ?? '' ) ) );
            if ( '' !== $content ) $excerpts[] = $content;
        }
        if ( empty( $excerpts ) ) return true; // Metadata-only legacy callers.
        $source_text = implode( ' ', $excerpts );
        $claim = preg_replace( '/\[S\d+\]/', '', wp_strip_all_tags( $block ) ) ?? $block;

        // Different scripts need a separately reviewed translation/entailment evaluator.
        if ( (bool) preg_match( '/\p{Arabic}/u', $claim ) !== (bool) preg_match( '/\p{Arabic}/u', $source_text ) ) {
            return true;
        }
        $claim_words = self::significant_words( $claim );
        $source_words = array_fill_keys( self::significant_words( $source_text ), true );
        if ( count( $claim_words ) < 5 || empty( $source_words ) ) return true;

        $overlap = 0;
        foreach ( $claim_words as $word ) {
            if ( isset( $source_words[ $word ] ) ) ++$overlap;
        }
        return $overlap >= 2 && ( $overlap / count( $claim_words ) ) >= 0.10;
    }

    private static function significant_words( string $text ): array {
        $text = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text ) : strtolower( $text );
        preg_match_all( '/[\p{L}\p{N}]{4,}/u', $text, $matches );
        $stop_words = array_flip( array( 'this', 'that', 'with', 'from', 'your', 'these', 'those', 'their', 'there', 'which', 'have', 'does', 'into', 'where', 'were', 'will', 'only', 'used', 'using', 'some', 'what', 'about', 'would', 'could', 'should' ) );
        return array_values( array_unique( array_filter( $matches[0] ?? array(), static fn( string $word ): bool => ! isset( $stop_words[ $word ] ) ) ) );
    }

    private static function substantive_block_requires_citation( string $block ): bool {
        $plain = trim( wp_strip_all_tags( preg_replace( '/^[#>*\-\d.\s]+/u', '', $block ) ?? $block ) );
        if ( '' === $plain ) return false;
        if ( preg_match( '/^(the approved educational sources contain|this is educational source retrieval|ai[- ]generated and human[- ]governed|safe next steps|citations?|sources?)\b/iu', $plain ) ) return false;
        $length = function_exists( 'mb_strlen' ) ? mb_strlen( $plain ) : strlen( $plain );
        return $length >= 90;
    }
}
