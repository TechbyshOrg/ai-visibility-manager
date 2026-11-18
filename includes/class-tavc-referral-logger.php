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
 * Checks the HTTP referrer header (and, since 1.3.0, the utm_source tag),
 * matches it against a list of AI platforms, debounces duplicate entries from
 * the same IP/destination, and stores them. An optional browser beacon records
 * visits on pages served from a full-page cache.
 *
 * @since      1.0.0
 * @package    Tavc
 * @subpackage Tavc/includes
 * @author     Techbysh
 */
class TAVC_Referral_Logger {

	/**
	 * REST namespace of the beacon endpoint.
	 */
	const REST_NAMESPACE = 'tavc/v1';

	/**
	 * REST route of the beacon endpoint.
	 */
	const REST_ROUTE = '/referral';

	/**
	 * Seconds during which a repeat visit (same IP, source and page) is ignored.
	 */
	const DEBOUNCE_SECONDS = 300;

	/**
	 * Beacon rate limit window in seconds.
	 */
	const BEACON_WINDOW = 600;

	/**
	 * Beacon requests allowed per IP address and window.
	 */
	const BEACON_LIMIT_PER_IP = 30;

	/**
	 * Maximum number of IP addresses tracked by the beacon rate limiter.
	 */
	const BEACON_MAX_TRACKED_IPS = 500;

	/**
	 * Tracking query parameters removed from logged URLs so the same page is
	 * not split into several report rows.
	 *
	 * @since    1.3.0
	 * @var      array
	 */
	private static $tracking_params = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'utm_id' );

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

		$settings = TAVC_Settings::get_tracking_settings();

		// With cache-compatible tracking on, the browser script records every
		// visit (cached or not), so the server must not count it a second time.
		if ( ! empty( $settings['beacon_tracking'] ) ) {
			return;
		}

		// AI crawlers and fetchers are bots, not referred visitors.
		if ( self::is_ai_crawler_request() ) {
			return;
		}

		$matched_source = false;
		$method         = TAVC_AI_Sources::METHOD_REFERRER;

		if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
			$raw_referrer   = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
			$matched_source = TAVC_AI_Sources::detect_from_referrer( $raw_referrer );
		}

		// Many AI assistants strip the referrer but tag their links with utm_source.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $matched_source && ! empty( $settings['utm_detection'] ) && isset( $_GET['utm_source'] ) && is_string( $_GET['utm_source'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$matched_source = TAVC_AI_Sources::detect_from_utm( sanitize_text_field( wp_unslash( $_GET['utm_source'] ) ) );
			$method         = TAVC_AI_Sources::METHOD_UTM;
		}

		if ( ! $matched_source ) {
			return;
		}

		// Build target URL
		$target_url = ( is_ssl() ? 'https://' : 'http://' ) . ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' ) . ( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );

		$this->record_referral( $matched_source, $target_url, $method );
	}

	/**
	 * Store a referral unless the same visit was logged in the last 5 minutes.
	 *
	 * @since    1.3.0
	 * @param    string    $matched_source    AI platform display name.
	 * @param    string    $target_url        Visited URL.
	 * @param    string    $method            Detection method.
	 * @return   bool                         Whether a row was written.
	 */
	public function record_referral( $matched_source, $target_url, $method ) {
		$target_url = self::normalize_target_url( $target_url );
		if ( '' === $target_url ) {
			return false;
		}

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
			if ( ( $now - $timestamp ) > self::DEBOUNCE_SECONDS ) {
				unset( $recent_referrals[ $hash ] );
			}
		}

		// Check if this specific visit was already logged recently
		if ( isset( $recent_referrals[ $visit_hash ] ) ) {
			return false;
		}

		// Bound the array size to prevent memory/option bloat under heavy traffic (max 100 entries)
		if ( count( $recent_referrals ) >= 100 ) {
			asort( $recent_referrals );
			reset( $recent_referrals );
			unset( $recent_referrals[ key( $recent_referrals ) ] );
		}

		// Save the visit timestamp
		$recent_referrals[ $visit_hash ] = $now;
		set_transient( 'tavc_recent_referrals', $recent_referrals, self::DEBOUNCE_SECONDS );

		// Write to DB
		return false !== TAVC_DB::insert_referral( $matched_source, $target_url, $method );
	}

	/**
	 * Clean a visited URL before it is stored.
	 *
	 * Removes the fragment and utm_* tracking parameters. URLs without tracking
	 * parameters are stored exactly as before.
	 *
	 * @since    1.3.0
	 * @param    string    $url    Visited URL.
	 * @return   string            Clean URL, or an empty string when invalid.
	 */
	public static function normalize_target_url( $url ) {
		$url = esc_url_raw( (string) $url );
		if ( '' === $url ) {
			return '';
		}

		$hash_position = strpos( $url, '#' );
		if ( false !== $hash_position ) {
			$url = substr( $url, 0, $hash_position );
		}

		if ( false !== stripos( $url, 'utm_' ) ) {
			$url = remove_query_arg( self::$tracking_params, $url );
		}

		return $url;
	}

	/**
	 * Whether the current request comes from a known AI crawler or fetcher.
	 *
	 * @since    1.3.0
	 * @return   bool
	 */
	public static function is_ai_crawler_request() {
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		return false !== TAVC_Bots::match_user_agent( $user_agent );
	}

	/*
	|--------------------------------------------------------------------------
	| Cache-compatible tracking (browser beacon)
	|--------------------------------------------------------------------------
	*/

	/**
	 * Load the tracking script on public pages when cache-compatible tracking is on.
	 *
	 * @since    1.3.0
	 */
	public function enqueue_tracker() {
		$settings = TAVC_Settings::get_tracking_settings();
		if ( empty( $settings['beacon_tracking'] ) ) {
			return;
		}

		wp_enqueue_script(
			'tavc-tracker',
			plugin_dir_url( dirname( __FILE__ ) ) . 'public/js/tavc-tracker.js',
			array(),
			TAVC_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		$config = array(
			'endpoint'   => esc_url_raw( rest_url( self::REST_NAMESPACE . self::REST_ROUTE ) ),
			'domains'    => array_keys( TAVC_AI_Sources::get_referrer_domains() ),
			'utmSources' => ! empty( $settings['utm_detection'] ) ? array_keys( TAVC_AI_Sources::get_utm_sources() ) : array(),
		);

		wp_add_inline_script( 'tavc-tracker', 'window.tavcTracker = ' . wp_json_encode( $config ) . ';', 'before' );
	}

	/**
	 * Register the beacon endpoint when cache-compatible tracking is on.
	 *
	 * @since    1.3.0
	 */
	public function register_rest_routes() {
		$settings = TAVC_Settings::get_tracking_settings();
		if ( empty( $settings['beacon_tracking'] ) ) {
			return;
		}

		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_beacon' ),
				// Public by design: the script runs for anonymous visitors on cached pages.
				// Input is validated, matched against known AI sources, debounced and rate limited.
				'permission_callback' => '__return_true',
				'args'                => array(
					'url' => array(
						'type'     => 'string',
						'required' => true,
					),
					'ref' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);
	}

	/**
	 * Record a visit reported by the tracking script.
	 *
	 * @since    1.3.0
	 * @param    WP_REST_Request    $request    Request.
	 * @return   WP_REST_Response|WP_Error
	 */
	public function handle_beacon( $request ) {
		$url      = substr( (string) $request->get_param( 'url' ), 0, 2000 );
		$referrer = substr( (string) $request->get_param( 'ref' ), 0, 2000 );

		if ( ! $this->is_own_url( $url ) ) {
			return new WP_Error( 'tavc_invalid_url', __( 'The page URL does not belong to this site.', 'tbsh-ai-visibility-control' ), array( 'status' => 400 ) );
		}

		if ( self::is_ai_crawler_request() ) {
			return new WP_REST_Response( array( 'logged' => false ), 200 );
		}

		$matched_source = TAVC_AI_Sources::detect_from_referrer( esc_url_raw( $referrer ) );

		if ( ! $matched_source ) {
			$settings = TAVC_Settings::get_tracking_settings();
			if ( ! empty( $settings['utm_detection'] ) ) {
				$query = wp_parse_url( $url, PHP_URL_QUERY );
				$args  = array();
				if ( is_string( $query ) ) {
					wp_parse_str( $query, $args );
				}
				if ( isset( $args['utm_source'] ) && is_string( $args['utm_source'] ) ) {
					$matched_source = TAVC_AI_Sources::detect_from_utm( sanitize_text_field( $args['utm_source'] ) );
				}
			}
		}

		if ( ! $matched_source ) {
			return new WP_REST_Response( array( 'logged' => false ), 200 );
		}

		if ( ! $this->allow_beacon_request() ) {
			return new WP_Error( 'tavc_rate_limited', __( 'Too many requests. Please try again later.', 'tbsh-ai-visibility-control' ), array( 'status' => 429 ) );
		}

		$logged = $this->record_referral( $matched_source, $url, TAVC_AI_Sources::METHOD_BEACON );

		return new WP_REST_Response( array( 'logged' => $logged ), 200 );
	}

	/**
	 * Whether a URL points to this site's front end.
	 *
	 * @since    1.3.0
	 * @param    string    $url    URL.
	 * @return   bool
	 */
	private function is_own_url( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! is_string( $host ) || '' === $host ) {
			return false;
		}

		$allowed = array();
		foreach ( array( home_url(), site_url() ) as $own_url ) {
			$own_host = wp_parse_url( $own_url, PHP_URL_HOST );
			if ( is_string( $own_host ) ) {
				$allowed[] = strtolower( $own_host );
			}
		}

		return in_array( strtolower( $host ), $allowed, true );
	}

	/**
	 * Per-IP and site-wide rate limiting for the public beacon endpoint.
	 *
	 * State is kept in two fixed transients, so spoofed traffic cannot create
	 * an unbounded number of rows or options.
	 *
	 * @since    1.3.0
	 * @return   bool    Whether the request may be processed.
	 */
	private function allow_beacon_request() {
		$now = time();

		/**
		 * Filters the maximum number of beacon visits processed per hour, site-wide.
		 *
		 * @since 1.3.0
		 *
		 * @param int $limit Requests per hour. Default 500.
		 */
		$hourly_limit = absint( apply_filters( 'tavc_beacon_hourly_limit', 500 ) );

		$hour = get_transient( 'tavc_beacon_hourly' );
		if ( ! is_array( $hour ) || ! isset( $hour['start'], $hour['count'] ) || ( $now - $hour['start'] ) >= HOUR_IN_SECONDS ) {
			$hour = array(
				'start' => $now,
				'count' => 0,
			);
		}
		if ( $hourly_limit > 0 && $hour['count'] >= $hourly_limit ) {
			return false;
		}

		$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ip_hash = 'h' . md5( $ip );

		$per_ip = get_transient( 'tavc_beacon_rate' );
		if ( ! is_array( $per_ip ) ) {
			$per_ip = array();
		}

		foreach ( $per_ip as $hash => $entry ) {
			if ( ! is_array( $entry ) || ! isset( $entry['start'] ) || ( $now - $entry['start'] ) >= self::BEACON_WINDOW ) {
				unset( $per_ip[ $hash ] );
			}
		}

		if ( isset( $per_ip[ $ip_hash ] ) ) {
			if ( $per_ip[ $ip_hash ]['count'] >= self::BEACON_LIMIT_PER_IP ) {
				return false;
			}
			$per_ip[ $ip_hash ]['count']++;
		} else {
			if ( count( $per_ip ) >= self::BEACON_MAX_TRACKED_IPS ) {
				uasort(
					$per_ip,
					function ( $a, $b ) {
						return $a['start'] - $b['start'];
					}
				);
				reset( $per_ip );
				unset( $per_ip[ key( $per_ip ) ] );
			}
			$per_ip[ $ip_hash ] = array(
				'start' => $now,
				'count' => 1,
			);
		}

		set_transient( 'tavc_beacon_rate', $per_ip, self::BEACON_WINDOW );

		$hour['count']++;
		set_transient( 'tavc_beacon_hourly', $hour, HOUR_IN_SECONDS );

		return true;
	}
}
