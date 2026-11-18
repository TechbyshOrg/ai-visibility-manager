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
 * Plugin Name:       AI Visibility Control – Block AI Crawlers, llms.txt & AI Traffic
 * Description:       Block AI training crawlers, stay visible in AI search, publish llms.txt, and see the visits and sales AI assistants send to your site.
 * Version:           1.4.0
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

if ( ! defined( 'TAVC_VERSION' ) ) {
	define( 'TAVC_VERSION', '1.4.0' );
}

if ( ! defined( 'TAVC_PLUGIN_FILE' ) ) {
	define( 'TAVC_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'TAVC_PLUGIN_URL' ) ) {
	define( 'TAVC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
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
function tavc_deactivate( $network_wide = false ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-tavc-deactivator.php';
	TAVC_Deactivator::deactivate( $network_wide );
}

register_activation_hook( __FILE__, 'tavc_activate' );
register_deactivation_hook( __FILE__, 'tavc_deactivate' );

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage and
 * the block-based cart and checkout.
 *
 * @since    1.3.0
 */
function tavc_declare_woocommerce_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'tavc_declare_woocommerce_compatibility' );

/**
 * Core class to start the plugin.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-tavc.php';

// Optional deactivation feedback survey (Plugins screen and its AJAX handler only).
if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-tavc-deactivation-feedback.php';
	TAVC_Deactivation_Feedback::init();
}

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
