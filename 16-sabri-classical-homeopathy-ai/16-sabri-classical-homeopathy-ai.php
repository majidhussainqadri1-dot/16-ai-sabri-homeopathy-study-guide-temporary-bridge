<?php
/**
 * Plugin Name: Sabri Classical Homeopathy AI
 * Plugin URI: https://sabrihomeopathy.com/ai
 * Description: Governed, source-linked educational AI and institutional AI Teacher for the Sabri Social Homeopathy Platform.
 * Version: 2.2.0
 * Requires at least: 7.0
 * Requires PHP: 8.3
 * Author: Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed
 * Text Domain: sabri-classical-homeopathy-ai
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'SCHA_VERSION', '2.2.0' );
define( 'SCHA_PLAN_VERSION', '1.0' );
define( 'SCHA_PLUGIN_FILE', __FILE__ );
define( 'SCHA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SCHA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SCHA_TEXT_DOMAIN', 'sabri-classical-homeopathy-ai' );

require_once SCHA_PLUGIN_DIR . 'includes/class-scha-autoloader.php';
SCHA_Autoloader::register();

register_activation_hook( __FILE__, array( 'SCHA_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SCHA_Activator', 'deactivate' ) );

add_action( 'plugins_loaded', static function (): void {
    SCHA_Plugin::instance()->boot();
} );
