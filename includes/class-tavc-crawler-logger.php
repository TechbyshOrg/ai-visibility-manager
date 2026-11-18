<?php
/**
 * Logs visits from known AI crawlers.
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
 * Logs visits from known AI crawlers.
 *
 * Requests are matched by User-Agent and counted per crawler, day and path,
 * so the table grows with the number of distinct pages crawled, not with the
 * number of requests. No IP address is stored.
 *
 * @since      1.3.0
 * @package    Tavc
 * @subpackage Tavc/includes
 * @author     Techbysh
 */
class TAVC_Crawler_Logger {

	/**
	 * Count the current request when it comes from a known AI crawler.
	 *
	 * Hooked to template_redirect at priority 1 so /llms.txt and robots.txt
	 * requests are counted before they are served.
	 *
	 * @since    1.3.0
	 */
	public function log_crawler_visit() {
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return;
		}

		$settings = TAVC_Settings::get_tracking_settings();
		if ( empty( $settings['crawler_logging'] ) ) {
			return;
		}

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$bot        = TAVC_Bots::match_user_agent( $user_agent );
		if ( ! $bot ) {
			return;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path        = wp_parse_url( $request_uri, PHP_URL_PATH );
		$path        = ( is_string( $path ) && '' !== $path ) ? $path : '/';

		// Requests for pages that do not exist share one row per crawler and day,
		// so random addresses cannot add a row each. llms.txt served through the
		// path fallback is still a 404 query at this point and keeps its path.
		if ( is_404() && ! in_array( strtolower( TAVC_Enforcer::to_home_relative_path( $path ) ), array( '/llms.txt', '/llms-full.txt' ), true ) ) {
			$path = TAVC_DB::NOT_FOUND_PATH;
		}

		/**
		 * Filters whether an AI crawler request is logged.
		 *
		 * @since 1.3.0
		 *
		 * @param bool   $log  Whether to log the request.
		 * @param string $bot  Crawler slug.
		 * @param string $path Requested path.
		 */
		if ( ! apply_filters( 'tavc_log_crawler_visit', true, $bot, $path ) ) {
			return;
		}

		TAVC_DB::record_crawler_hit( $bot, $path );
	}
}
