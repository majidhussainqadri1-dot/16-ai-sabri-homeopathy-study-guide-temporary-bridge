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

        // available_at acts as a processing lease while status=processing. A worker
        // crash therefore cannot strand an event permanently.
        $recovered = $wpdb->query( $wpdb->prepare( "UPDATE $table SET status='retry',available_at=%s WHERE status='processing' AND available_at <= %s", $now, $now ) );
        if ( $recovered > 0 ) {
            SCHA_Observability::audit( 'outbox_stale_claims_recovered', 'outbox', 'batch', array( 'count' => absint( $recovered ) ), 'event-delivery-recovery' );
        }

        $rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE status IN ('pending','retry') AND available_at <= %s ORDER BY id ASC LIMIT 50", $now ), ARRAY_A ) ?: array();
        foreach ( $rows as $row ) {
            $lease_until = gmdate( 'Y-m-d H:i:s', time() + 30 * MINUTE_IN_SECONDS );
            $locked = $wpdb->query( $wpdb->prepare( "UPDATE $table SET status='processing',attempts=attempts+1,available_at=%s WHERE id=%d AND status IN ('pending','retry') AND available_at <= %s", $lease_until, $row['id'], $now ) );
            if ( 1 !== $locked ) continue;

            try {
                $payload = json_decode( (string) $row['payload_json'], true ) ?: array();
                $hook    = preg_replace( '/[^A-Za-z0-9_.:-]/', '', (string) $row['event_name'] ) ?: 'UnknownEvent';
                do_action( 'scha_event_' . $hook, $payload, $row );
                do_action( 'scha_event', $hook . '.' . $row['event_version'], $payload, $row );
                $updated = $wpdb->update( $table, array( 'status' => 'delivered' ), array( 'id' => $row['id'], 'status' => 'processing' ), array( '%s' ), array( '%d', '%s' ) );
                if ( 1 !== $updated ) throw new RuntimeException( 'Outbox delivery state could not be finalized.' );
            } catch ( Throwable $e ) {
                $attempts = absint( $row['attempts'] ) + 1;
                $status   = $attempts >= 8 ? 'dead_letter' : 'retry';
                $delay    = min( DAY_IN_SECONDS, ( 2 ** min( $attempts, 10 ) ) * MINUTE_IN_SECONDS );
                $wpdb->update(
                    $table,
                    array( 'status' => $status, 'available_at' => gmdate( 'Y-m-d H:i:s', time() + $delay ) ),
                    array( 'id' => $row['id'], 'status' => 'processing' ),
                    array( '%s', '%s' ),
                    array( '%d', '%s' )
                );
                SCHA_Observability::safe_error( $e, SCHA_Observability::trace_id() );
            }
        }
    }
}
