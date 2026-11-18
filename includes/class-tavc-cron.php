<?php
/**
 * Scheduled tasks.
 *
 * Daily log retention clean-up and the optional weekly or monthly summary email.
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
	 * Summary email event hook. The name dates from 1.3.0, when the summary
	 * was weekly only; it is kept so existing schedules keep working.
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
	 * Schedule or remove the summary email to match the saved settings.
	 *
	 * Weekly summaries use a recurring "weekly" event. Months differ in
	 * length, so monthly summaries use a single event on the 1st that is
	 * scheduled again after each run.
	 *
	 * Hooked to updates of the tavc_tracking_settings option.
	 *
	 * @since    1.3.0
	 * @since    1.4.0 Supports monthly summaries.
	 */
	public static function sync_digest_schedule() {
		$settings = TAVC_Settings::get_tracking_settings();

		if ( empty( $settings['digest_enabled'] ) ) {
			wp_clear_scheduled_hook( self::DIGEST_HOOK );
			return;
		}

		$monthly = 'monthly' === $settings['digest_frequency'];
		$event   = wp_get_scheduled_event( self::DIGEST_HOOK );

		// Replace an event of the other kind (switching weekly <-> monthly).
		if ( $event && ( $monthly ? false !== $event->schedule : 'weekly' !== $event->schedule ) ) {
			wp_clear_scheduled_hook( self::DIGEST_HOOK );
			$event = false;
		}

		if ( $event ) {
			return;
		}

		if ( $monthly ) {
			wp_schedule_single_event( self::get_next_digest_time( 'monthly' ), self::DIGEST_HOOK );
		} else {
			wp_schedule_event( self::get_next_digest_time( 'weekly' ), 'weekly', self::DIGEST_HOOK );
		}
	}

	/**
	 * Next send time in the site's timezone, as a Unix timestamp: next Monday
	 * at 08:00 (weekly) or the 1st of next month at 08:00 (monthly).
	 *
	 * @since    1.3.0
	 * @since    1.4.0 Added the $frequency parameter.
	 * @param    string    $frequency    'weekly' or 'monthly'.
	 * @return   int
	 */
	public static function get_next_digest_time( $frequency = 'weekly' ) {
		try {
			$next = new DateTimeImmutable( 'monthly' === $frequency ? 'first day of next month 08:00' : 'next monday 08:00', wp_timezone() );
			return $next->getTimestamp();
		} catch ( Exception $e ) {
			return time() + ( 'monthly' === $frequency ? MONTH_IN_SECONDS : WEEK_IN_SECONDS );
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

		// Expired rate limit and duplicate-visit counters.
		TAVC_DB::prune_counters();
	}

	/**
	 * Send the summary email when it is enabled.
	 *
	 * @since    1.3.0
	 * @since    1.4.0 Schedules the next monthly summary.
	 */
	public static function run_weekly_digest() {
		$settings = TAVC_Settings::get_tracking_settings();

		if ( empty( $settings['digest_enabled'] ) ) {
			return;
		}

		TAVC_Digest::send();

		// A monthly summary is a single event: schedule the next one.
		self::sync_digest_schedule();
	}
}
