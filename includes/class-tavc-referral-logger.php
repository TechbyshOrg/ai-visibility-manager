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
 * Checks the HTTP referrer header (and, since 2.0.0, the utm_source tag),
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

		$this->record_referral( $matched_source, self::get_current_url(), $method );
	}

	/**
	 * Store a referral unless the same visit was logged in the last 5 minutes.
	 *
	 * @since    2.0.0
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

		// The same visitor, source and page are counted once per 5-minute window.
		// Only a hash is used as the counter name; the IP address is never stored.
		if ( TAVC_DB::bump_counter( 'ref|' . TAVC_Enforcer::get_client_ip() . '|' . $matched_source . '|' . $target_url, self::DEBOUNCE_SECONDS ) > 1 ) {
			return false;
		}

		// Write to DB
		return false !== TAVC_DB::insert_referral( $matched_source, $target_url, $method );
	}

	/**
	 * Clean a visited URL before it is stored.
	 *
	 * Removes the fragment and utm_* tracking parameters. URLs without tracking
	 * parameters are stored exactly as before.
	 *
	 * @since    2.0.0
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

		$query_position = strpos( $url, '?' );
		if ( false === $query_position ) {
			return $url;
		}

		// Query strings can carry personal data (search terms, e-mail addresses,
		// order keys), so only arguments that identify the content are kept.
		$query = substr( $url, $query_position + 1 );
		$url   = substr( $url, 0, $query_position );
		$args  = array();
		wp_parse_str( $query, $args );

		/**
		 * Filters the query arguments kept in logged AI visit URLs.
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $allowed Argument names. Default: the plain-permalink content arguments.
		 */
		$allowed = (array) apply_filters( 'tavc_referral_allowed_query_args', array( 'p', 'page_id', 'post_type', 'cat', 'tag', 'product', 'lang' ) );
		$kept    = array();
		foreach ( $allowed as $name ) {
			if ( isset( $args[ $name ] ) && is_scalar( $args[ $name ] ) && '' !== (string) $args[ $name ] ) {
				$kept[ $name ] = (string) $args[ $name ];
			}
		}

		return empty( $kept ) ? $url : esc_url_raw( add_query_arg( urlencode_deep( $kept ), $url ) );
	}

	/**
	 * Address of the current request on this site's own host.
	 *
	 * Built from home_url() and the raw request path, so a forged Host header
	 * cannot plant another domain in the reports, and percent-encoded (for
	 * example Cyrillic or Arabic) paths are kept intact.
	 *
	 * @since    2.0.0
	 * @return   string
	 */
	public static function get_current_url() {
		$home = wp_parse_url( home_url() );
		if ( empty( $home['scheme'] ) || empty( $home['host'] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by esc_url_raw() in normalize_target_url().
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';

		return $home['scheme'] . '://' . $home['host'] . ( isset( $home['port'] ) ? ':' . $home['port'] : '' ) . '/' . ltrim( $request_uri, '/' );
	}

	/**
	 * Whether the current request comes from a known AI crawler or fetcher.
	 *
	 * @since    2.0.0
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
	 * @since    2.0.0
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
	 * @since    2.0.0
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
	 * @since    2.0.0
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

		$limits = $this->check_beacon_limits();
		if ( 'client' === $limits ) {
			return new WP_Error( 'tavc_rate_limited', __( 'Too many requests. Please try again later.', 'tbsh-ai-visibility-control' ), array( 'status' => 429 ) );
		}
		if ( 'site' === $limits ) {
			// Busy hour: skip quietly so visitors' browsers never see an error.
			return new WP_REST_Response( array( 'logged' => false ), 200 );
		}

		$logged = $this->record_referral( $matched_source, $url, TAVC_AI_Sources::METHOD_BEACON );

		return new WP_REST_Response( array( 'logged' => $logged ), 200 );
	}

	/**
	 * Whether a URL points to this site's front end.
	 *
	 * @since    2.0.0
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
	 * Uses the shared request counters (one small row per counter), so spoofed
	 * traffic cannot grow an option or lose counts under concurrent requests.
	 *
	 * @since    2.0.0
	 * @return   string    'ok', 'client' when this address sent too many requests,
	 *                     or 'site' when the site-wide hourly budget is used up.
	 */
	private function check_beacon_limits() {
		// Checked first so one abusive client cannot use up the site-wide budget.
		$per_client = TAVC_DB::bump_counter( 'beacon_ip|' . TAVC_Enforcer::get_client_ip(), self::BEACON_WINDOW );
		if ( $per_client > self::BEACON_LIMIT_PER_IP ) {
			return 'client';
		}

		/**
		 * Filters the maximum number of beacon visits processed per hour, site-wide.
		 *
		 * @since 2.0.0
		 *
		 * @param int $limit Requests per hour. 0 for no limit.
		 */
		$hourly_limit = absint( apply_filters( 'tavc_beacon_hourly_limit', 2000 ) );
		if ( $hourly_limit > 0 && TAVC_DB::bump_counter( 'beacon_hour', HOUR_IN_SECONDS ) > $hourly_limit ) {
			return 'site';
		}

		return 'ok';
	}
}
