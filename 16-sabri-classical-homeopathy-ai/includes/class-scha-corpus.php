<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Corpus {
    public static function register_item( array $item, bool $approve = false ): int|WP_Error {
        global $wpdb;
        $required = array( 'owner_file', 'owner_item_id', 'version', 'title', 'content' );
        foreach ( $required as $field ) {
            if ( empty( $item[ $field ] ) ) {
                return new WP_Error( 'scha_corpus_missing_field', sprintf( 'Missing corpus field: %s', $field ) );
            }
        }

        $owner_file  = sanitize_key( $item['owner_file'] );
        if ( ! self::owner_allowed( $owner_file, $item ) ) {
            return new WP_Error( 'scha_corpus_owner_not_allowed', __( 'This canonical owner is not approved for the File 16 corpus.', SCHA_TEXT_DOMAIN ) );
        }
        $owner_id    = sanitize_text_field( $item['owner_item_id'] );
        $version     = sanitize_text_field( $item['version'] );
        $title       = sanitize_text_field( $item['title'] );
        $content     = wp_kses_post( (string) $item['content'] );
        $plain       = trim( wp_strip_all_tags( $content ) );
        $privacy     = SCHA_Privacy::inspect_and_redact( $plain );
        if ( ! empty( $privacy['had_sensitive_data'] ) && true !== apply_filters( 'scha_corpus_sensitive_content_authorized_v1', false, $item, $privacy['findings'] ) ) {
            return new WP_Error( 'scha_corpus_sensitive_content', __( 'Sensitive personal data is not eligible for the AI corpus without an explicit lawful authorization contract.', SCHA_TEXT_DOMAIN ) );
        }
        $checksum    = hash( 'sha256', $plain );
        $access      = in_array( $item['access_class'] ?? 'public', array( 'public', 'subscriber', 'doctor', 'founder', 'internal' ), true ) ? $item['access_class'] : 'public';
        $source_url  = esc_url_raw( $item['source_url'] ?? '' );
        $license     = sanitize_text_field( $item['license'] ?? '' );
        $approved_use = sanitize_text_field( $item['approved_use'] ?? '' );
        $rights_evidence_id = sanitize_text_field( $item['rights_evidence_id'] ?? '' );
        $rights_reviewed_at = self::normalize_datetime( $item['rights_reviewed_at'] ?? '' );
        $language    = sanitize_text_field( $item['language'] ?? 'en' );
        $table       = SCHA_Database::table( 'corpus_items' );
        $existing    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE owner_file=%s AND owner_item_id=%s AND item_version=%s", $owner_file, $owner_id, $version ), ARRAY_A );
        $now         = current_time( 'mysql', true );

        $data = array(
            'title'          => $title,
            'source_url'     => $source_url,
            'license_name'   => $license,
            'approved_use'  => $approved_use,
            'rights_evidence_id' => $rights_evidence_id,
            'rights_reviewed_at' => $rights_reviewed_at,
            'language'       => $language,
            'access_class'   => $access,
            'checksum'       => $checksum,
            'source_content' => $plain,
            'updated_at'     => $now,
        );

        if ( $existing ) {
            if ( hash_equals( (string) $existing['checksum'], $checksum ) ) {
                $item_id = absint( $existing['id'] );
                $rights_changed = (string) $existing['license_name'] !== $license
                    || (string) ( $existing['approved_use'] ?? '' ) !== $approved_use
                    || (string) ( $existing['rights_evidence_id'] ?? '' ) !== $rights_evidence_id
                    || (string) ( $existing['rights_reviewed_at'] ?? '' ) !== (string) $rights_reviewed_at;
                if ( $rights_changed ) {
                    $data['status'] = 'reviewed';
                    $data['chunk_status'] = 'pending';
                }
                $wpdb->update( $table, $data, array( 'id' => $item_id ) );
                if ( $approve ) {
                    $indexed = self::approve_and_index( $item_id );
                    if ( is_wp_error( $indexed ) ) return $indexed;
                }
                return $item_id;
            }
            $data['status']       = 'reviewed';
            $data['chunk_status'] = 'pending';
            $wpdb->update( $table, $data, array( 'id' => $existing['id'] ) );
            $item_id = absint( $existing['id'] );
        } else {
            $data += array(
                'public_id'     => wp_generate_uuid4(),
                'owner_file'    => $owner_file,
                'owner_item_id' => $owner_id,
                'item_version'  => $version,
                'status'        => $approve ? 'reviewed' : 'draft',
                'chunk_status'  => 'pending',
                'approved_by'   => 0,
                'approved_at'   => null,
                'created_at'    => $now,
            );
            $wpdb->insert( $table, $data );
            $item_id = absint( $wpdb->insert_id );
        }

        if ( ! $item_id ) {
            return new WP_Error( 'scha_corpus_write_failed', 'Unable to save corpus item.' );
        }

        if ( $approve ) {
            $indexed = self::approve_and_index( $item_id );
            if ( is_wp_error( $indexed ) ) return $indexed;
        }
        SCHA_Observability::audit( 'corpus_registered', 'corpus_item', (string) $item_id, array( 'owner_file' => $owner_file, 'version' => $version, 'access' => $access ), 'approved-corpus-management' );
        return $item_id;
    }

    public static function approve_and_index( int $item_id ): true|WP_Error {
        global $wpdb;
        $items = SCHA_Database::table( 'corpus_items' );
        $chunks = SCHA_Database::table( 'chunks' );
        $item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $items WHERE id=%d", $item_id ), ARRAY_A );
        if ( ! $item ) {
            return new WP_Error( 'scha_source_not_found', __( 'Corpus item not found.', SCHA_TEXT_DOMAIN ) );
        }
        if ( ! self::owner_allowed( sanitize_key( (string) $item['owner_file'] ), $item ) ) {
            return new WP_Error( 'scha_corpus_owner_not_allowed', __( 'This canonical owner is not approved for the File 16 corpus.', SCHA_TEXT_DOMAIN ) );
        }
        $privacy = SCHA_Privacy::inspect_and_redact( (string) $item['source_content'] );
        if ( ! empty( $privacy['had_sensitive_data'] ) && true !== apply_filters( 'scha_corpus_sensitive_content_authorized_v1', false, $item, $privacy['findings'] ) ) {
            return new WP_Error( 'scha_corpus_sensitive_content', __( 'Sensitive personal data is not eligible for the AI corpus without an explicit lawful authorization contract.', SCHA_TEXT_DOMAIN ) );
        }
        if ( '' === trim( (string) $item['license_name'] ) || '' === trim( (string) ( $item['approved_use'] ?? '' ) ) || '' === trim( (string) ( $item['rights_evidence_id'] ?? '' ) ) || empty( $item['rights_reviewed_at'] ) ) {
            return new WP_Error( 'scha_rights_review_required', __( 'License, approved AI use, rights evidence and a rights-review date are required before approval.', SCHA_TEXT_DOMAIN ) );
        }
        if ( '' === trim( (string) $item['source_content'] ) ) {
            return new WP_Error( 'scha_source_empty', 'The source has no indexable content.' );
        }

        $wpdb->query( 'START TRANSACTION' );
        try {
            $wpdb->delete( $chunks, array( 'item_id' => $item_id ), array( '%d' ) );
            $parts = self::chunk_text( (string) $item['source_content'] );
            foreach ( $parts as $index => $part ) {
                $wpdb->insert(
                    $chunks,
                    array(
                        'item_id'           => $item_id,
                        'chunk_index'       => $index,
                        'content'           => $part,
                        'token_count'       => SCHA_Usage_Ledger::estimate_tokens( $part ),
                        'access_class'      => $item['access_class'],
                        'checksum'          => hash( 'sha256', $part ),
                        'embedding_version' => 'lexical-v1',
                        'created_at'        => current_time( 'mysql', true ),
                    ),
                    array( '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
                );
            }
            $wpdb->update(
                $items,
                array(
                    'status'       => 'approved',
                    'chunk_status' => 'indexed',
                    'approved_by'  => get_current_user_id(),
                    'approved_at'  => current_time( 'mysql', true ),
                    'updated_at'   => current_time( 'mysql', true ),
                ),
                array( 'id' => $item_id )
            );
            $wpdb->query( 'COMMIT' );
        } catch ( Throwable $e ) {
            $wpdb->query( 'ROLLBACK' );
            return new WP_Error( 'scha_index_failed', 'Indexing failed.' );
        }

        SCHA_Outbox::publish( 'KnowledgeSourceUpdated', 'corpus_item', (string) $item['public_id'], array( 'source_id' => $item['public_id'], 'version' => $item['item_version'], 'status' => 'approved' ), 'source-updated-' . $item['public_id'] . '-' . $item['item_version'] );
        return true;
    }

    public static function suspend( int $item_id, string $reason = '' ): bool {
        global $wpdb;
        $table = SCHA_Database::table( 'corpus_items' );
        $item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id=%d", $item_id ), ARRAY_A );
        if ( ! $item ) {
            return false;
        }
        $updated = $wpdb->update( $table, array( 'status' => 'suspended', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $item_id ) );
        if ( false !== $updated ) {
            SCHA_Outbox::publish( 'KnowledgeSourceRetracted', 'corpus_item', (string) $item['public_id'], array( 'source_id' => $item['public_id'], 'version' => $item['item_version'], 'reason_category' => sanitize_key( $reason ?: 'governance' ) ), 'source-retracted-' . $item['public_id'] . '-' . time() );
            return true;
        }
        return false;
    }

    public static function handle_source_retracted( string $owner_file, string $owner_item_id, string $version ): void {
        global $wpdb;
        $table = SCHA_Database::table( 'corpus_items' );
        $wpdb->update(
            $table,
            array( 'status' => 'suspended', 'updated_at' => current_time( 'mysql', true ) ),
            array( 'owner_file' => $owner_file, 'owner_item_id' => $owner_item_id, 'item_version' => $version )
        );
    }

    public static function sync_registered_sources(): void {
        $sources = apply_filters( 'scha_registered_corpus_sources', array() );
        if ( ! is_array( $sources ) ) {
            return;
        }
        foreach ( $sources as $source ) {
            if ( is_array( $source ) ) {
                self::register_item( $source, ! empty( $source['approved'] ) );
            }
        }
    }

    public static function public_catalog( int $limit = 100 ): array {
        global $wpdb;
        $table = SCHA_Database::table( 'corpus_items' );
        $limit = min( 500, max( 1, $limit ) );
        return $wpdb->get_results( "SELECT public_id,title,source_url,license_name,approved_use,rights_reviewed_at,language,item_version,owner_file,updated_at FROM $table WHERE status='approved' AND access_class='public' ORDER BY title ASC LIMIT $limit", ARRAY_A ) ?: array();
    }

    public static function accessible_catalog( array $access_classes, int $limit = 100 ): array {
        global $wpdb;
        $table = SCHA_Database::table( 'corpus_items' );
        $allowed = array_values( array_intersect( $access_classes, array( 'public', 'subscriber', 'doctor', 'founder', 'internal' ) ) );
        if ( empty( $allowed ) ) {
            $allowed = array( 'public' );
        }
        $placeholders = implode( ',', array_fill( 0, count( $allowed ), '%s' ) );
        $sql = $wpdb->prepare( "SELECT public_id,title,source_url,license_name,approved_use,rights_reviewed_at,language,item_version,owner_file,access_class,updated_at FROM $table WHERE status='approved' AND access_class IN ($placeholders) ORDER BY title ASC LIMIT %d", array_merge( $allowed, array( min( 500, max( 1, $limit ) ) ) ) );
        return $wpdb->get_results( $sql, ARRAY_A ) ?: array();
    }

    private static function owner_allowed( string $owner_file, array $item ): bool {
        $allowed = (array) apply_filters( 'scha_allowed_corpus_owner_files_v1', array(
            '05', 'file-05', 'learn-sabri-classical-homeopathy',
            '06', 'file-06', 'homeopathy-encyclopedia',
            '12', 'file-12', 'pdf-library',
            '15', 'file-15', 'radar-trends',
            'research', 'research-center',
        ) );
        $allowed = array_values( array_unique( array_filter( array_map( 'sanitize_key', $allowed ) ) ) );
        if ( in_array( $owner_file, $allowed, true ) ) return true;
        return true === apply_filters( 'scha_corpus_source_authorized_v1', false, $owner_file, $item );
    }

    private static function normalize_datetime( mixed $value ): ?string {
        $value = trim( (string) $value );
        if ( '' === $value ) return null;
        $timestamp = strtotime( $value );
        return false === $timestamp ? null : gmdate( 'Y-m-d H:i:s', $timestamp );
    }

    private static function chunk_text( string $text ): array {
        $text = preg_replace( '/\r\n?|\x{2028}|\x{2029}/u', "\n", trim( $text ) );
        $paragraphs = preg_split( '/\n{2,}/u', $text ) ?: array( $text );
        $chunks = array();
        $buffer = '';
        foreach ( $paragraphs as $paragraph ) {
            $paragraph = trim( preg_replace( '/\s+/u', ' ', $paragraph ) );
            if ( '' === $paragraph ) {
                continue;
            }
            $candidate = '' === $buffer ? $paragraph : $buffer . "\n\n" . $paragraph;
            $length = function_exists( 'mb_strlen' ) ? mb_strlen( $candidate ) : strlen( $candidate );
            if ( $length > 1400 && '' !== $buffer ) {
                $chunks[] = $buffer;
                $buffer = $paragraph;
            } else {
                $buffer = $candidate;
            }
        }
        if ( '' !== $buffer ) {
            $chunks[] = $buffer;
        }
        return array_slice( $chunks, 0, 10000 );
    }
}
