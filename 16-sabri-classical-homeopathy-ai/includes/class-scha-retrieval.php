<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Retrieval {
    public static function search( string $query, array $entitlement, int $limit = 6 ): array {
        global $wpdb;
        $tokens = self::tokens( $query );
        if ( empty( $tokens ) ) {
            return array();
        }

        $chunks = SCHA_Database::table( 'chunks' );
        $items  = SCHA_Database::table( 'corpus_items' );
        $access = array_values( array_intersect( $entitlement['access_class'] ?? array( 'public' ), array( 'public', 'subscriber', 'doctor', 'founder', 'internal' ) ) );
        if ( empty( $access ) ) {
            $access = array( 'public' );
        }

        $access_placeholders = implode( ',', array_fill( 0, count( $access ), '%s' ) );
        $like_clauses = array();
        $args = array_merge( $access, $access );
        foreach ( array_slice( $tokens, 0, 8 ) as $token ) {
            $like_clauses[] = 'c.content LIKE %s';
            $args[] = '%' . $wpdb->esc_like( $token ) . '%';
        }
        $args[] = 200;
        $sql = "SELECT c.id,c.chunk_index,c.content,c.access_class,i.public_id,i.title,i.source_url,i.item_version,i.owner_file,i.license_name,i.approved_use,i.rights_evidence_id,i.rights_reviewed_at,i.language,i.access_class item_access_class
                FROM $chunks c INNER JOIN $items i ON i.id=c.item_id
                WHERE i.status='approved' AND i.chunk_status='indexed'
                  AND i.access_class IN ($access_placeholders)
                  AND c.access_class IN ($access_placeholders)
                  AND (" . implode( ' OR ', $like_clauses ) . ")
                ORDER BY i.updated_at DESC LIMIT %d";
        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ) ?: array();

        foreach ( $rows as &$row ) {
            $haystack = function_exists( 'mb_strtolower' ) ? mb_strtolower( $row['content'] ) : strtolower( $row['content'] );
            $title = function_exists( 'mb_strtolower' ) ? mb_strtolower( $row['title'] ) : strtolower( $row['title'] );
            $score = 0.0;
            foreach ( $tokens as $token ) {
                $count = substr_count( $haystack, $token );
                if ( $count > 0 ) {
                    $score += 1 + min( 5, $count ) * 0.25;
                }
                if ( false !== strpos( $title, $token ) ) {
                    $score += 2.0;
                }
            }
            $row['score'] = $score;
        }
        unset( $row );

        usort( $rows, static fn( array $a, array $b ): int => $b['score'] <=> $a['score'] );
        $limit = max( 1, min( 10, $limit ) );
        $selected = array();
        $selected_keys = array();
        $per_source = array();

        // First pass maximizes source diversity; second pass may add one more
        // high-scoring chunk per source when the result budget still has room.
        foreach ( array( 1, 2 ) as $per_source_limit ) {
            foreach ( $rows as $row ) {
                if ( count( $selected ) >= $limit ) break 2;
                $source_id = (string) $row['public_id'];
                $key = $source_id . ':' . $row['chunk_index'];
                if ( isset( $selected_keys[ $key ] ) || ( $per_source[ $source_id ] ?? 0 ) >= $per_source_limit ) continue;
                $selected_keys[ $key ] = true;
                $per_source[ $source_id ] = ( $per_source[ $source_id ] ?? 0 ) + 1;
                $selected[] = self::public_source( $row, count( $selected ) + 1 );
            }
        }
        return $selected;
    }

    private static function public_source( array $row, int $position ): array {
        return array(
            'label'        => 'S' . $position,
            'source_id'    => $row['public_id'],
            'title'        => $row['title'],
            'location'     => 'chunk-' . $row['chunk_index'],
            'version'      => $row['item_version'],
            'owner_file'   => $row['owner_file'],
            'license'      => $row['license_name'],
            'approved_use' => $row['approved_use'],
            'rights_evidence_id' => $row['rights_evidence_id'],
            'rights_reviewed_at' => $row['rights_reviewed_at'],
            'language'     => $row['language'],
            'url'          => $row['source_url'] ?: home_url( '/ai/sources/#source-' . $row['public_id'] ),
            'content'      => $row['content'],
            'score'        => $row['score'],
        );
    }

    private static function tokens( string $query ): array {
        $query = trim( wp_strip_all_tags( $query ) );
        if ( function_exists( 'mb_strtolower' ) ) $query = mb_strtolower( $query ); else $query = strtolower( $query );
        $parts = preg_split( '/[^\p{L}\p{N}]+/u', $query ) ?: array();
        $parts = array_values( array_filter( array_unique( $parts ), static function ( string $token ): bool {
            $length = function_exists( 'mb_strlen' ) ? mb_strlen( $token ) : strlen( $token );
            return $length >= 2;
        } ) );
        return array_slice( $parts, 0, 20 );
    }
}
