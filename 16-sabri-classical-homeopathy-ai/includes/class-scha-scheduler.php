<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Scheduler {
    public static function cron_schedules( array $schedules ): array {
        $schedules['scha_five_minutes'] = array(
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display'  => __( 'Every five minutes (Sabri AI)', SCHA_TEXT_DOMAIN ),
        );
        $schedules['scha_hourly'] = array(
            'interval' => HOUR_IN_SECONDS,
            'display'  => __( 'Hourly (Sabri AI)', SCHA_TEXT_DOMAIN ),
        );
        return $schedules;
    }

    public static function ensure_events(): void {
        if ( ! wp_next_scheduled( 'scha_retention_cron' ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'scha_retention_cron' );
        }
        if ( ! wp_next_scheduled( 'scha_outbox_cron' ) ) {
            wp_schedule_event( time() + 2 * MINUTE_IN_SECONDS, 'scha_five_minutes', 'scha_outbox_cron' );
        }
        if ( ! wp_next_scheduled( 'scha_corpus_sync_cron' ) ) {
            wp_schedule_event( time() + 15 * MINUTE_IN_SECONDS, 'scha_hourly', 'scha_corpus_sync_cron' );
        }
    }

    public static function clear_events(): void {
        foreach ( array( 'scha_retention_cron', 'scha_outbox_cron', 'scha_corpus_sync_cron' ) as $hook ) {
            $timestamp = wp_next_scheduled( $hook );
            while ( $timestamp ) {
                wp_unschedule_event( $timestamp, $hook );
                $timestamp = wp_next_scheduled( $hook );
            }
        }
    }
}
