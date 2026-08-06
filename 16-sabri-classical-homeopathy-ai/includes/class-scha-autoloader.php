<?php

defined( 'ABSPATH' ) || exit;

final class SCHA_Autoloader {
    public static function register(): void {
        spl_autoload_register( array( __CLASS__, 'autoload' ) );
    }

    public static function autoload( string $class ): void {
        if ( 0 !== strpos( $class, 'SCHA_' ) ) {
            return;
        }

        $relative = strtolower( str_replace( '_', '-', substr( $class, 5 ) ) );
        $candidates = array(
            SCHA_PLUGIN_DIR . 'includes/class-scha-' . $relative . '.php',
            SCHA_PLUGIN_DIR . 'includes/providers/class-scha-' . $relative . '.php',
        );

        foreach ( $candidates as $file ) {
            if ( is_readable( $file ) ) {
                require_once $file;
                return;
            }
        }
    }
}
