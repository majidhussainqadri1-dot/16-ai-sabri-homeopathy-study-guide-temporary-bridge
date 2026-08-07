<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Outbox {
    public static function publish( string $event_name, string $aggregate_type, string $aggregate_id, array $payload, string $dedupe_key, string $version = 'v1' ): bool {
        global $wpdb;

        $event_name = preg_replace( '/[^A-Za-z0-9_.:-]/', '', $event_name ) ?: '';
        $version    = preg_replace( '/[^A-Za-z0-9_.:-]/', '', $version ) ?: 'v1';
        if ( '' === $event_name || '' === trim( $dedupe_key ) ) {
            return false;
        }

        $table = SCHA_Database::table( 'outbox' );
        $sql   = $wpdb->prepare(
            "INSERT IGNORE INTO $table (event_name,event_version,aggregate_type,aggregate_id,payload_json,dedupe_key,status,attempts,available_at,created_at) VALUES (%s,%s,%s,%s,%s,%s,'pending',0,%s,%s)",
            $event_name,
            $version,
            sanitize_key( $aggregate_type ),
            sanitize_text_field( $aggregate_id ),
            wp_json_encode( SCHA_Observability::sanitize_payload( $payload ) ),
            sanitize_text_field( $dedupe_key ),
            current_time( 'mysql', true ),
            current_time( 'mysql', true )
        );
        $result = $wpdb->query( $sql );
        if ( false === $result ) {
            SCHA_Observability::safe_error( 'Outbox write failed.', SCHA_Observability::trace_id() );
            return false;
        }
        return true;
    }

    public static function process(): void {
        global $wpdb;
        $table = SCHA_Database::table( 'outbox' );
        $now   = current_time( 'mysql', true );
        $rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE status IN ('pending','retry') AND available_at <= %s ORDER BY id ASC LIMIT 50", $now ), ARRAY_A ) ?: array();

        foreach ( $rows as $row ) {
            $locked = $wpdb->query( $wpdb->prepare( "UPDATE $table SET status='processing', attempts=attempts+1 WHERE id=%d AND status IN ('pending','retry')", $row['id'] ) );
            if ( 1 !== $locked ) {
                continue;
            }
            try {
                $payload = json_decode( (string) $row['payload_json'], true ) ?: array();
                $hook    = preg_replace( '/[^A-Za-z0-9_.:-]/', '', (string) $row['event_name'] ) ?: 'UnknownEvent';
                do_action( 'scha_event_' . $hook, $payload, $row );
                do_action( 'scha_event', $hook . '.' . $row['event_version'], $payload, $row );
                $wpdb->update( $table, array( 'status' => 'delivered' ), array( 'id' => $row['id'] ), array( '%s' ), array( '%d' ) );
            } catch ( Throwable $e ) {
                $attempts = absint( $row['attempts'] ) + 1;
                $status   = $attempts >= 8 ? 'dead_letter' : 'retry';
                $delay    = min( DAY_IN_SECONDS, ( 2 ** min( $attempts, 10 ) ) * MINUTE_IN_SECONDS );
                $wpdb->update(
                    $table,
                    array(
                        'status'       => $status,
                        'available_at' => gmdate( 'Y-m-d H:i:s', time() + $delay ),
                    ),
                    array( 'id' => $row['id'] ),
                    array( '%s', '%s' ),
                    array( '%d' )
                );
                SCHA_Observability::safe_error( $e, SCHA_Observability::trace_id() );
            }
        }
    }
}
