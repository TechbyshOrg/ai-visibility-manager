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
 * @package           Aivm
 *
 * @wordpress-plugin
 * Plugin Name:       AI Visibility Manager – Dynamic llms.txt & robots.txt Crawler Control
 * Plugin URI:        https://techbysh.com/plugins/ai-visibility-manager/
 * Description:       Manage search crawler visibility, block AI bots, generate llms.txt, and log AI search referral traffic.
 * Version:           1.0.0
 * Author:            Techbysh
 * Author URI:        https://techbysh.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Text Domain:       ai-visibility-manager
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-aivm-activator.php
 */
function aivm_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aivm-activator.php';
	AIVM_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-aivm-deactivator.php
 */
function aivm_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aivm-deactivator.php';
	AIVM_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'aivm_activate' );
register_deactivation_hook( __FILE__, 'aivm_deactivate' );

/**
 * Core class to start the plugin.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-aivm.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks, catching the
 * hook registry and executing the source path is handled by AIVM.
 *
 * @since    1.0.0
 */
function aivm_run() {
	$plugin = AIVM::get_instance();
	$plugin->run();
}
aivm_run();
