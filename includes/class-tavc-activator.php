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

		$plugin_version = defined( 'TAVC_VERSION' ) ? TAVC_VERSION : '1.4.0';
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
		$counter_table   = $wpdb->prefix . 'tavc_counters';
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
			blocked int(10) unsigned DEFAULT 0 NOT NULL,
			limited int(10) unsigned DEFAULT 0 NOT NULL,
			last_seen datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY bot_date_url (bot,hit_date,url_hash),
			KEY hit_date (hit_date)
		) $charset_collate;";

		// Short-lived request counters (rate limit, beacon limits, referral debounce).
		// One row per counter, overwritten in place; expired rows are pruned daily.
		$counter_sql = "CREATE TABLE $counter_table (
			bucket char(32) NOT NULL,
			window_start int(10) unsigned DEFAULT 0 NOT NULL,
			expires int(10) unsigned DEFAULT 0 NOT NULL,
			hits int(10) unsigned DEFAULT 0 NOT NULL,
			PRIMARY KEY  (bucket),
			KEY expires (expires)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( array( $referrals_sql, $crawler_sql, $counter_sql ) );

		// Record the layout only when every table really exists, so a failed
		// dbDelta() keeps the per-request schema checks in TAVC_DB.
		foreach ( array( $referrals_table, $crawler_table, $counter_table ) as $table_name ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) ) !== $table_name ) {
				delete_option( 'tavc_db_version' );
				return;
			}
		}

		update_option( 'tavc_db_version', TAVC_DB::SCHEMA_VERSION, true );
	}

	/**
	 * Set default options on activation.
	 *
	 * Every option the front end reads on each request is created (autoloaded),
	 * so a missing option never costs an extra query per page view.
	 *
	 * @since    1.0.0
	 * @since    1.4.0 Also creates the llms.txt, tracking and enforcement options.
	 */
	private static function set_default_options() {
		// tavc_blocked_bots has existed since 1.0.0, so its absence means a new install.
		$is_new_install = false === get_option( 'tavc_blocked_bots' );

		if ( $is_new_install ) {
			update_option( 'tavc_blocked_bots', TAVC_Bots::get_default_settings() );
		}

		$llms     = TAVC_Settings::get_llms_defaults();
		$tracking = TAVC_Settings::get_tracking_defaults();

		if ( $is_new_install ) {
			// New sites: keep referral logs for a year.
			$tracking['referral_retention_days'] = 365;
		} else {
			// Upgrades keep the earlier behaviour: every post listed, logs kept forever.
			$llms['max_per_type'] = 0;
		}

		$defaults = array(
			'tavc_llms_txt_settings'    => $llms,
			'tavc_tracking_settings'    => $tracking,
			'tavc_enforcement_settings' => TAVC_Settings::get_enforcement_defaults(),
		);

		// add_option() never overwrites a saved value.
		foreach ( $defaults as $option => $value ) {
			add_option( $option, $value, '', true );
		}
	}
}
