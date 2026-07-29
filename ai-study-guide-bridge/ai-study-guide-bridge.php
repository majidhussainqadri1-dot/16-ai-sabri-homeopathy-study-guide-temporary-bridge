<?php
/**
 * Plugin Name: AI Sabri Homeopathy Study Guide Temporary Bridge
 * Plugin URI: https://www.sabrihomeopathy.com/
 * Description: A temporary, accessible bridge from Sabri Homeopathy to the AI Sabri Homeopathy Study Guide on ChatGPT.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Dr. Allama Majid Hussain Sabri
 * License: GPL-2.0-or-later
 * Text Domain: ai-study-guide-bridge
 */

defined( 'ABSPATH' ) || exit;

define( 'SAI_VERSION', '0.1.0' );
define( 'SAI_FILE', __FILE__ );
define( 'SAI_DIR', plugin_dir_path( __FILE__ ) );
define( 'SAI_URL', plugin_dir_url( __FILE__ ) );

require_once SAI_DIR . 'includes/class-sai-provider.php';
require_once SAI_DIR . 'includes/class-sai-helpers.php';
require_once SAI_DIR . 'includes/class-sai-activator.php';
require_once SAI_DIR . 'includes/class-sai-frontend.php';
require_once SAI_DIR . 'includes/class-sai-admin.php';
require_once SAI_DIR . 'includes/class-sai-privacy.php';
require_once SAI_DIR . 'includes/class-sai-plugin.php';

register_activation_hook( SAI_FILE, array( 'SAI_Activator', 'activate' ) );
register_deactivation_hook( SAI_FILE, array( 'SAI_Activator', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		( new SAI_Plugin() )->run();
	},
	95
);
