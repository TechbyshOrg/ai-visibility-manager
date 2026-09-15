<?php
/**
 * Detects and logs AI referrals
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
 * Detects and logs AI referrals.
 *
 * Checks HTTP referrer header, matches it against a list of AI search bots,
 * debounces duplicate entries from the same IP/destination, and stores them.
 *
 * @since      1.0.0
 * @package    Tavc
 * @subpackage Tavc/includes
 * @author     Techbysh
 */
class TAVC_Referral_Logger {

	/**
	 * Run checks on template_redirect to inspect the referrer.
	 *
	 * @since    1.0.0
	 */
	public function log_incoming_referral() {
		// Do not run on admin pages, REST requests, or cron
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return;
		}

		if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
			return;
		}

		$raw_referrer = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
		$matched_source = $this->detect_ai_referrer( $raw_referrer );

		if ( ! $matched_source ) {
			return;
		}

		// Build target URL
		$target_url = ( is_ssl() ? 'https://' : 'http://' ) . ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' ) . ( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );
		$target_url = esc_url_raw( $target_url );

		// Get visitor IP for debouncing (hashed to respect privacy / GDPR)
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		
		// Generate unique visit hash for the combination of IP + Source + Target
		$visit_hash = md5( $ip . '|' . $matched_source . '|' . $target_url );

		// Retrieve recent referrals from a single fixed transient key to prevent database/cache bloat from unauthenticated spoofed requests
		$recent_referrals = get_transient( 'tavc_recent_referrals' );
		if ( ! is_array( $recent_referrals ) ) {
			$recent_referrals = array();
		}

		$now = time();

		// Clean up entries older than 5 minutes (300 seconds)
		foreach ( $recent_referrals as $hash => $timestamp ) {
			if ( ( $now - $timestamp ) > 300 ) {
				unset( $recent_referrals[ $hash ] );
			}
		}

		// Check if this specific visit was already logged recently
		if ( ! isset( $recent_referrals[ $visit_hash ] ) ) {
			// Bound the array size to prevent memory/option bloat under heavy traffic (max 100 entries)
			if ( count( $recent_referrals ) >= 100 ) {
				asort( $recent_referrals );
				array_shift( $recent_referrals );
			}

			// Save the visit timestamp
			$recent_referrals[ $visit_hash ] = $now;
			set_transient( 'tavc_recent_referrals', $recent_referrals, 300 );

			// Write to DB
			require_once plugin_dir_path( __FILE__ ) . 'class-tavc-db.php';
			TAVC_DB::insert_referral( $matched_source, $target_url );
		}
	}

	/**
	 * Detect if a referrer URL matches one of the target AI engines.
	 *
	 * @since    1.0.0
	 * @param    string    $referrer_url    The HTTP referrer URL.
	 * @return   string|false               The matched AI source name or false.
	 */
	private function detect_ai_referrer( $referrer_url ) {
		$parsed = wp_parse_url( $referrer_url );
		if ( empty( $parsed['host'] ) ) {
			return false;
		}

		$host = strtolower( $parsed['host'] );

		// Supported AI engines domains to track
		$ai_domains = array(
			'chatgpt.com'            => 'ChatGPT',
			'chat.openai.com'        => 'ChatGPT',
			'perplexity.ai'          => 'Perplexity',
			'claude.ai'              => 'Claude',
			'copilot.microsoft.com'  => 'Copilot',
			'gemini.google.com'      => 'Gemini',
			'bard.google.com'        => 'Bard',
			'grok.com'               => 'Grok',
			'grok.x.ai'              => 'Grok',
			'meta.ai'                => 'Meta AI',
		);

		foreach ( $ai_domains as $domain => $name ) {
			if ( $host === $domain || substr( $host, -strlen( '.' . $domain ) ) === '.' . $domain ) {
				return $name;
			}
		}

		return false;
	}
}
