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
            if ( self::substantive_block_requires_citation( (string) $block ) && ! preg_match( '/\[S\d+\]/', $block ) ) {
                return array( 'valid' => false, 'reason' => 'citation_coverage', 'answer' => $answer, 'citations' => array() );
            }
        }

        return array( 'valid' => true, 'reason' => '', 'answer' => $answer, 'citations' => $citations );
    }

    private static function substantive_block_requires_citation( string $block ): bool {
        $plain = trim( wp_strip_all_tags( preg_replace( '/^[#>*\-\d.\s]+/u', '', $block ) ?? $block ) );
        if ( '' === $plain ) return false;
        if ( preg_match( '/^(the approved educational sources contain|this is educational source retrieval|ai[- ]generated and human[- ]governed|safe next steps|citations?|sources?)\b/iu', $plain ) ) return false;
        $length = function_exists( 'mb_strlen' ) ? mb_strlen( $plain ) : strlen( $plain );
        return $length >= 90;
    }
}
