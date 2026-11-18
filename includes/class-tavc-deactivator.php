<?php
/**
 * Fired during plugin deactivation
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
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Tavc
 * @subpackage Tavc/includes
 * @author     Techbysh
 */
class TAVC_Deactivator {

	/**
	 * Short description.
	 *
	 * Long description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		flush_rewrite_rules();

		// Clear transients
		delete_transient( 'tavc_llms_txt_cache' );
		delete_transient( 'tavc_llms_full_txt_cache' );
		delete_transient( 'tavc_recent_referrals' );
		delete_transient( 'tavc_beacon_rate' );
		delete_transient( 'tavc_beacon_hourly' );
		delete_transient( 'tavc_health_llms_txt' );
		delete_transient( 'tavc_health_robots' );

		// Cached WooCommerce reports for the current ranges (older keys expire after 15 minutes).
		if ( class_exists( 'TAVC_WooCommerce' ) ) {
			TAVC_WooCommerce::clear_report_cache();
		}

		// Crawl rate limit counters (one per crawler, expire after two minutes anyway).
		if ( class_exists( 'TAVC_Bots' ) && class_exists( 'TAVC_Enforcer' ) ) {
			foreach ( array_keys( TAVC_Bots::get_definitions() ) as $bot ) {
				delete_transient( TAVC_Enforcer::RATE_CACHE_GROUP . '_' . $bot );
			}
		}

		// Stop scheduled log clean-up and summary emails; they are rescheduled on activation.
		wp_clear_scheduled_hook( 'tavc_daily_maintenance' );
		wp_clear_scheduled_hook( 'tavc_weekly_digest' );
	}
}
