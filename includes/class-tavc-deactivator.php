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
 * Removes caches, counters and scheduled events. Settings and logs are kept
 * (they are removed by uninstall.php when the site owner opted in).
 *
 * @since      1.0.0
 * @package    Tavc
 * @subpackage Tavc/includes
 * @author     Techbysh
 */
class TAVC_Deactivator {

	/**
	 * Clean up on deactivation, on every site when network-deactivated.
	 *
	 * @since    1.0.0
	 * @since    1.4.0 Added $network_wide.
	 * @param    bool    $network_wide    Whether the plugin is deactivated for the whole network.
	 */
	public static function deactivate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			foreach ( get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			) as $site_id ) {
				switch_to_blog( $site_id );
				self::deactivate_site();
				restore_current_blog();
			}
			return;
		}

		self::deactivate_site();
	}

	/**
	 * Clean up the current site.
	 *
	 * @since    1.4.0
	 */
	private static function deactivate_site() {
		global $wpdb;

		// Flushing now would save the llms.txt rules again (they are still registered
		// during this request). Deleted rules are rebuilt by WordPress on the next
		// request, without this plugin's rules.
		delete_option( 'rewrite_rules' );

		// Caches and the transients used before 1.4.0.
		foreach ( array( 'tavc_llms_txt_cache', 'tavc_llms_full_txt_cache', 'tavc_recent_referrals', 'tavc_beacon_rate', 'tavc_beacon_hourly', 'tavc_health_llms_txt', 'tavc_health_robots' ) as $transient ) {
			delete_transient( $transient );
		}

		// Stored llms.txt files and their locks (rebuilt on the next request after reactivation).
		foreach ( array( 'tavc_llms_store_index', 'tavc_llms_store_full', 'tavc_llms_lock_index', 'tavc_llms_lock_full', 'tavc_upgrade_lock' ) as $option ) {
			delete_option( $option );
		}

		// WooCommerce reports (keys include a date) and the pre-1.4.0 rate limit counters.
		foreach ( array( 'tavc_wc_report_', 'tavc_rate_limit_' ) as $prefix ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_' . $prefix ) . '%', $wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%' ) );
		}
		if ( class_exists( 'TAVC_WooCommerce' ) ) {
			// Also clears them from a persistent object cache.
			TAVC_WooCommerce::clear_report_cache();
		}

		// Rate limit and duplicate-visit counters.
		$counter_table = $wpdb->prefix . 'tavc_counters';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $counter_table ) ) ) === $counter_table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $counter_table ) );
		}

		// Stop scheduled log clean-up and summary emails; they are rescheduled on activation.
		wp_clear_scheduled_hook( 'tavc_daily_maintenance' );
		wp_clear_scheduled_hook( 'tavc_weekly_digest' );
	}
}
