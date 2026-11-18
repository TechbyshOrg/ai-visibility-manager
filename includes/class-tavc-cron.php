<?php
/**
 * Scheduled tasks.
 *
 * Daily log retention clean-up and the optional weekly summary email.
 *
 * @link       https://techbysh.com
 * @since      1.3.0
 *
 * @package    Tavc
 * @subpackage Tavc/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scheduled tasks.
 *
 * @since      1.3.0
 * @package    Tavc
 * @subpackage Tavc/includes
 * @author     Techbysh
 */
class TAVC_Cron {

	/**
	 * Daily maintenance event hook.
	 */
	const DAILY_HOOK = 'tavc_daily_maintenance';

	/**
	 * Weekly summary email event hook.
	 */
	const DIGEST_HOOK = 'tavc_weekly_digest';

	/**
	 * Schedule every event the current settings need.
	 *
	 * @since    1.3.0
	 */
	public static function schedule_events() {
		if ( ! wp_next_scheduled( self::DAILY_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::DAILY_HOOK );
		}

		self::sync_digest_schedule();
	}

	/**
	 * Remove every scheduled event of this plugin.
	 *
	 * @since    1.3.0
	 */
	public static function unschedule_events() {
		wp_clear_scheduled_hook( self::DAILY_HOOK );
		wp_clear_scheduled_hook( self::DIGEST_HOOK );
	}

	/**
	 * Schedule or remove the weekly summary to match the saved setting.
	 *
	 * Hooked to updates of the tavc_tracking_settings option.
	 *
	 * @since    1.3.0
	 */
	public static function sync_digest_schedule() {
		$settings = TAVC_Settings::get_tracking_settings();

		if ( empty( $settings['digest_enabled'] ) ) {
			wp_clear_scheduled_hook( self::DIGEST_HOOK );
			return;
		}

		if ( ! wp_next_scheduled( self::DIGEST_HOOK ) ) {
			wp_schedule_event( self::get_next_digest_time(), 'weekly', self::DIGEST_HOOK );
		}
	}

	/**
	 * Next Monday at 08:00 in the site's timezone, as a Unix timestamp.
	 *
	 * @since    1.3.0
	 * @return   int
	 */
	private static function get_next_digest_time() {
		try {
			$next = new DateTimeImmutable( 'next monday 08:00', wp_timezone() );
			return $next->getTimestamp();
		} catch ( Exception $e ) {
			return time() + WEEK_IN_SECONDS;
		}
	}

	/**
	 * Delete log rows older than the configured retention periods.
	 *
	 * @since    1.3.0
	 */
	public static function run_daily_maintenance() {
		$settings = TAVC_Settings::get_tracking_settings();

		if ( $settings['referral_retention_days'] > 0 ) {
			$cutoff = wp_date( 'Y-m-d H:i:s', time() - ( $settings['referral_retention_days'] * DAY_IN_SECONDS ) );
			TAVC_DB::prune_referrals( $cutoff );
		}

		if ( $settings['crawler_retention_days'] > 0 ) {
			$cutoff = wp_date( 'Y-m-d', time() - ( $settings['crawler_retention_days'] * DAY_IN_SECONDS ) );
			TAVC_DB::prune_crawler_hits( $cutoff );
		}
	}

	/**
	 * Send the weekly summary email when it is enabled.
	 *
	 * @since    1.3.0
	 */
	public static function run_weekly_digest() {
		$settings = TAVC_Settings::get_tracking_settings();

		if ( empty( $settings['digest_enabled'] ) ) {
			return;
		}

		TAVC_Digest::send();
	}
}
