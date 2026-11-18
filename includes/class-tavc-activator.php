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
	 * Create tables, default options, rewrite rules and scheduled events.
	 *
	 * Also runs on plugin updates through TAVC::maybe_upgrade().
	 *
	 * @since    1.0.0
	 * @param    bool    $flush_rewrite    Flush rewrite rules immediately. Pass false when
	 *                                     running before other plugins have registered their
	 *                                     rules (the caller must then flush later).
	 */
	public static function activate( $flush_rewrite = true ) {
		$includes_dir = plugin_dir_path( dirname( __FILE__ ) ) . 'includes/';
		require_once $includes_dir . 'class-tavc-bots.php';
		require_once $includes_dir . 'class-tavc-settings.php';
		require_once $includes_dir . 'class-tavc-db.php';
		require_once $includes_dir . 'class-tavc-digest.php';
		require_once $includes_dir . 'class-tavc-cron.php';
		require_once $includes_dir . 'class-tavc-llms-txt.php';

		self::create_database_tables();

		// Set default settings if not already present
		self::set_default_options();

		// Register the rewrite rules for llms.txt and llms-full.txt.
		$llms_txt = new TAVC_LLMS_Txt();
		$llms_txt->register_rewrite_rule();

		if ( $flush_rewrite ) {
			flush_rewrite_rules();
		}

		TAVC_Cron::schedule_events();

		$plugin_version = defined( 'TAVC_VERSION' ) ? TAVC_VERSION : '1.3.0';
		update_option( 'tavc_plugin_version', $plugin_version );
	}

	/**
	 * Create or update the custom database tables.
	 *
	 * dbDelta() adds missing columns and indexes to existing tables without
	 * touching stored rows.
	 *
	 * @since    1.3.0
	 */
	private static function create_database_tables() {
		global $wpdb;

		$referrals_table = $wpdb->prefix . 'tavc_referrals';
		$crawler_table   = $wpdb->prefix . 'tavc_crawler_hits';
		$charset_collate = $wpdb->get_charset_collate();

		$referrals_sql = "CREATE TABLE $referrals_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			referrer varchar(255) NOT NULL,
			target_url text NOT NULL,
			method varchar(20) DEFAULT 'referrer' NOT NULL,
			PRIMARY KEY  (id),
			KEY referrer (referrer(191)),
			KEY timestamp (timestamp)
		) $charset_collate;";

		$crawler_sql = "CREATE TABLE $crawler_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			hit_date date NOT NULL,
			bot varchar(64) NOT NULL,
			url_hash char(32) NOT NULL,
			url_path varchar(255) DEFAULT '' NOT NULL,
			hits int(10) unsigned DEFAULT 0 NOT NULL,
			last_seen datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY bot_date_url (bot,hit_date,url_hash),
			KEY hit_date (hit_date)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( array( $referrals_sql, $crawler_sql ) );
	}

	/**
	 * Set default options on activation.
	 *
	 * @since    1.0.0
	 */
	private static function set_default_options() {
		if ( false === get_option( 'tavc_blocked_bots' ) ) {
			update_option( 'tavc_blocked_bots', TAVC_Bots::get_default_settings() );
		}
	}
}
