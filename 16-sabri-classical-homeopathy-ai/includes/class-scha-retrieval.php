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
        $args = $access;
        foreach ( array_slice( $tokens, 0, 8 ) as $token ) {
            $like_clauses[] = 'c.content LIKE %s';
            $args[] = '%' . $wpdb->esc_like( $token ) . '%';
        }
        $args[] = 200;
        $sql = "SELECT c.id,c.chunk_index,c.content,c.access_class,i.public_id,i.title,i.source_url,i.item_version,i.owner_file,i.license_name,i.approved_use,i.rights_evidence_id,i.rights_reviewed_at,i.language
                FROM $chunks c INNER JOIN $items i ON i.id=c.item_id
                WHERE i.status='approved' AND i.chunk_status='indexed' AND c.access_class IN ($access_placeholders) AND (" . implode( ' OR ', $like_clauses ) . ")
                ORDER BY i.updated_at DESC LIMIT %d";
        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ) ?: array();

        foreach ( $rows as &$row ) {
            $haystack = function_exists( 'mb_strtolower' ) ? mb_strtolower( $row['content'] ) : strtolower( $row['content'] );
            $score = 0.0;
            foreach ( $tokens as $token ) {
                $count = substr_count( $haystack, $token );
                if ( $count > 0 ) {
                    $score += 1 + min( 5, $count ) * 0.25;
                }
                if ( false !== strpos( function_exists( 'mb_strtolower' ) ? mb_strtolower( $row['title'] ) : strtolower( $row['title'] ), $token ) ) {
                    $score += 2.0;
                }
            }
            $row['score'] = $score;
        }
        unset( $row );

        usort( $rows, static fn( array $a, array $b ): int => $b['score'] <=> $a['score'] );
        $selected = array();
        $seen_items = array();
        foreach ( $rows as $row ) {
            if ( count( $selected ) >= max( 1, min( 10, $limit ) ) ) {
                break;
            }
            $key = $row['public_id'] . ':' . $row['chunk_index'];
            if ( isset( $seen_items[ $key ] ) ) {
                continue;
            }
            $seen_items[ $key ] = true;
            $selected[] = array(
                'label'        => 'S' . ( count( $selected ) + 1 ),
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
                'access_class' => $row['access_class'],
                'score'        => $row['score'],
            );
        }
        return $selected;
    }

    private static function tokens( string $query ): array {
        $query = function_exists( 'mb_strtolower' ) ? mb_strtolower( wp_strip_all_tags( $query ) ) : strtolower( wp_strip_all_tags( $query ) );
        $parts = preg_split( '/[^\p{L}\p{N}]+/u', $query ) ?: array();
        $stop = array( 'the','and','or','is','are','of','to','in','a','an','what','how','کہ','ہے','ہیں','اور','کا','کی','کے','میں','کو','سے','پر','یہ','وہ','ما','هو','هي','في','من','إلى','عن','و' );
        $tokens = array();
        foreach ( $parts as $part ) {
            $part = trim( $part );
            $len  = function_exists( 'mb_strlen' ) ? mb_strlen( $part ) : strlen( $part );
            if ( $len < 2 || in_array( $part, $stop, true ) ) {
                continue;
            }
            $tokens[] = $part;
        }
        return array_values( array_unique( array_slice( $tokens, 0, 20 ) ) );
    }
}
