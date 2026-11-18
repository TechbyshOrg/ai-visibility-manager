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

		// Stop scheduled log clean-up and summary emails; they are rescheduled on activation.
		wp_clear_scheduled_hook( 'tavc_daily_maintenance' );
		wp_clear_scheduled_hook( 'tavc_weekly_digest' );
	}
}
