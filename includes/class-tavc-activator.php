<?php
/**
 * Fired during plugin activation
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Tavc
 * @subpackage Tavc/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Tavc
 * @subpackage Tavc/includes
 * @author     Techbysh
 */
class TAVC_Activator {

	/**
	 * Short description.
	 *
	 * Long description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		self::create_database_table();

		// Set default settings if not already present
		self::set_default_options();

		// Register the rewrite rule for llms.txt dynamically and flush
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-tavc-llms-txt.php';
		$llms_txt = new TAVC_LLMS_Txt();
		$llms_txt->register_rewrite_rule();

		flush_rewrite_rules();

		$plugin_version = defined( 'TAVC_VERSION' ) ? TAVC_VERSION : '1.2.0';
		update_option( 'tavc_plugin_version', $plugin_version );
	}

	/**
	 * Create custom database table for referral logging.
	 *
	 * @since    1.0.0
	 */
	private static function create_database_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'tavc_referrals';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			referrer varchar(255) NOT NULL,
			target_url text NOT NULL,
			PRIMARY KEY  (id),
			KEY referrer (referrer(191)),
			KEY timestamp (timestamp)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Set default options on activation.
	 *
	 * @since    1.0.0
	 */
	private static function set_default_options() {
		$default_bots = array(
			'gptbot'             => 0,
			'claudebot'          => 0,
			'perplexitybot'      => 0,
			'google-extended'    => 0,
			'ccbot'              => 0,
			'amazonbot'          => 0,
			'applebot-extended'  => 0,
		);

		if ( false === get_option( 'tavc_blocked_bots' ) ) {
			update_option( 'tavc_blocked_bots', $default_bots );
		}
	}
}
