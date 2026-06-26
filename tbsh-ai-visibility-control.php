<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://techbysh.com
 * @since             1.0.0
 * @package           Tavc
 *
 * @wordpress-plugin
 * Plugin Name:       AI Visibility Control – Dynamic llms.txt & robots.txt Crawler Control
 * Description:       Manage search crawler visibility, block AI bots, generate llms.txt, and log AI search referral traffic.
 * Version:           1.0.2
 * Author:            Techbysh
 * Author URI:        https://techbysh.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Text Domain:       tbsh-ai-visibility-control
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-tavc-activator.php
 */
function tavc_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-tavc-activator.php';
	TAVC_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-tavc-deactivator.php
 */
function tavc_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-tavc-deactivator.php';
	TAVC_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'tavc_activate' );
register_deactivation_hook( __FILE__, 'tavc_deactivate' );

/**
 * Core class to start the plugin.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-tavc.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks, catching the
 * hook registry and executing the source path is handled by TAVC.
 *
 * @since    1.0.0
 */
function tavc_run() {
	$plugin = TAVC::get_instance();
	$plugin->run();
}
tavc_run();
