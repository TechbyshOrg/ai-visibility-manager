<?php
/**
 * Detects and logs AI referrals
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Aivm
 * @subpackage Aivm/includes
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
 * @package    Aivm
 * @subpackage Aivm/includes
 * @author     Techbysh
 */
class AIVM_Referral_Logger {

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
		
		// Generate unique transient key for the combination of IP + Source + Target
		$debounce_key = 'aivm_ref_' . md5( $ip . '|' . $matched_source . '|' . $target_url );

		// Check if we logged this specific visit in the last 5 minutes
		if ( false === get_transient( $debounce_key ) ) {
			// Set transient block for 5 minutes (300 seconds)
			set_transient( $debounce_key, 1, 300 );

			// Write to DB
			require_once plugin_dir_path( __FILE__ ) . 'class-aivm-db.php';
			AIVM_DB::insert_referral( $matched_source, $target_url );
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
			'perplexity.ai'          => 'Perplexity',
			'claude.ai'              => 'Claude',
			'copilot.microsoft.com'  => 'Copilot',
			'gemini.google.com'      => 'Gemini',
			'bard.google.com'        => 'Bard',
		);

		foreach ( $ai_domains as $domain => $name ) {
			if ( $host === $domain || substr( $host, -strlen( '.' . $domain ) ) === '.' . $domain ) {
				return $name;
			}
		}

		return false;
	}
}
