<?php
/**
 * Refuses requests from blocked AI crawlers.
 *
 * @link       https://techbysh.com
 * @since      2.0.0
 *
 * @package    Tavc
 * @subpackage Tavc/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Refuses requests from blocked AI crawlers and slows down busy ones.
 *
 * robots.txt only asks crawlers to stay away. When enforcement is on, a
 * request whose User-Agent matches a crawler blocked on the AI Crawlers tab
 * gets a short 403 response before the theme loads. When the rate limit is
 * on, other AI crawlers get a 429 response once they exceed the allowed
 * requests per minute. robots.txt and llms.txt are always served, so every
 * crawler can still read the rules.
 *
 * Crawlers are matched by User-Agent only. Requests answered by a page cache
 * or CDN never reach WordPress and cannot be refused here.
 *
 * @since      2.0.0
 * @package    Tavc
 * @subpackage Tavc/includes
 * @author     Techbysh
 */
class TAVC_Enforcer {

	/**
	 * Outcome stored for a request refused because the crawler is blocked (403).
	 */
	const OUTCOME_BLOCKED = 'blocked';

	/**
	 * Outcome stored for a request refused by the rate limit (429).
	 */
	const OUTCOME_LIMITED = 'limited';

	/**
	 * Object cache group and transient prefix of the per-minute counters.
	 */
	const RATE_CACHE_GROUP = 'tavc_rate_limit';

	/**
	 * Check the current request. Hooked to init at priority 0: after every
	 * plugin has loaded (so the crawler list filter is applied and translations
	 * can load), before most plugins and the theme do their init work.
	 *
	 * @since    2.0.0
	 */
	public function maybe_refuse_request() {
		if ( is_admin() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}

		// Cheap exit for the common case: nothing to enforce.
		$settings = TAVC_Settings::get_enforcement_settings();
		if ( empty( $settings['enforce_blocks'] ) && empty( $settings['rate_limit'] ) ) {
			return;
		}

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$bot        = TAVC_Bots::match_user_agent( $user_agent );
		if ( ! $bot ) {
			return;
		}

		$raw_path = self::get_raw_request_path();
		$path     = self::to_home_relative_path( $raw_path );
		if ( self::is_exempt_request( $path ) ) {
			return;
		}

		$blocked = TAVC_Bots::get_blocked_settings();

		if ( ! empty( $settings['enforce_blocks'] ) && ! empty( $blocked[ $bot ] ) ) {
			/**
			 * Filters whether a request from a blocked AI crawler is refused.
			 *
			 * Return false to let the request through, for example for a path the
			 * crawler should still reach.
			 *
			 * @since 2.0.0
			 *
			 * @param bool   $refuse Whether to refuse the request.
			 * @param string $bot    Crawler slug.
			 * @param string $path   Requested path, relative to the site's home URL.
			 */
			if ( apply_filters( 'tavc_refuse_blocked_crawler', true, $bot, $path ) ) {
				self::refuse( $bot, $raw_path, $path, self::OUTCOME_BLOCKED );
			}
			return;
		}

		if ( ! empty( $settings['rate_limit'] ) ) {
			$limit = self::get_rate_limit( $bot, (int) $settings['rate_limit_per_minute'] );
			if ( $limit > 0 && self::count_request( $bot ) > $limit ) {
				self::refuse( $bot, $raw_path, $path, self::OUTCOME_LIMITED );
			}
		}
	}

	/**
	 * Requests per minute allowed for a crawler. 0 means no limit.
	 *
	 * User-requested fetchers are not limited by default: each of their
	 * requests is made for a person who asked an AI assistant about the page.
	 *
	 * @since    2.0.0
	 * @param    string    $bot      Crawler slug.
	 * @param    int       $limit    Limit from the settings.
	 * @return   int
	 */
	public static function get_rate_limit( $bot, $limit ) {
		$definitions = TAVC_Bots::get_definitions();
		if ( isset( $definitions[ $bot ] ) && TAVC_Bots::CATEGORY_USER === $definitions[ $bot ]['category'] ) {
			$limit = 0;
		}

		/**
		 * Filters the requests per minute allowed for an AI crawler.
		 *
		 * @since 2.0.0
		 *
		 * @param int    $limit Requests per minute. 0 means no limit.
		 * @param string $bot   Crawler slug.
		 */
		return max( 0, (int) apply_filters( 'tavc_rate_limit_per_minute', $limit, $bot ) );
	}

	/**
	 * Count a request for the current minute and return the new total.
	 *
	 * One counter per crawler, not per IP address: crawlers spread their
	 * requests over many addresses. Uses the persistent object cache when the
	 * site has one; otherwise a short-lived transient. Without an object cache
	 * the count is approximate when requests arrive at the same moment.
	 *
	 * @since    2.0.0
	 * @param    string    $bot    Crawler slug.
	 * @return   int               Requests counted this minute, including this one.
	 */
	public static function count_request( $bot ) {
		// Counted per crawler and client network (IPv4 /24, IPv6 /48). With one counter
		// per crawler, anyone sending a fake user agent could use up the real crawler's
		// budget and get it refused.
		return TAVC_DB::bump_counter( 'rate|' . $bot . '|' . self::get_network_key( self::get_client_ip() ), MINUTE_IN_SECONDS );
	}

	/**
	 * The visitor's IP address.
	 *
	 * REMOTE_ADDR by default. Sites behind a proxy or CDN that does not restore
	 * the visitor address can return the right one with the tavc_client_ip filter.
	 *
	 * @since    2.0.0
	 * @return   string    Valid IP address, or an empty string.
	 */
	public static function get_client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		/**
		 * Filters the visitor IP address used for rate limits and duplicate-visit checks.
		 *
		 * The address is never stored; only a one-way hash is kept, for at most a day.
		 *
		 * @since 2.0.0
		 *
		 * @param string $ip REMOTE_ADDR.
		 */
		$ip = (string) apply_filters( 'tavc_client_ip', $ip );

		return false !== filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	/**
	 * The network an IP address belongs to: the first three IPv4 octets or the
	 * first 48 bits of an IPv6 address.
	 *
	 * @since    2.0.0
	 * @param    string    $ip    IP address.
	 * @return   string
	 */
	public static function get_network_key( $ip ) {
		$packed = '' !== $ip ? inet_pton( $ip ) : false;
		if ( false === $packed ) {
			return 'unknown';
		}

		return bin2hex( substr( $packed, 0, 4 === strlen( $packed ) ? 3 : 6 ) );
	}

	/**
	 * Log, announce and send the refusal.
	 *
	 * @since    2.0.0
	 * @param    string    $bot         Crawler slug.
	 * @param    string    $raw_path    Requested path as sent.
	 * @param    string    $path        Path relative to the home URL.
	 * @param    string    $outcome     OUTCOME_BLOCKED or OUTCOME_LIMITED.
	 */
	private static function refuse( $bot, $raw_path, $path, $outcome ) {
		// Logged with the full path, like TAVC_Crawler_Logger, so both land in the same row.
		self::log_refusal( $bot, $raw_path, $outcome );

		/**
		 * Fires before an AI crawler request is refused.
		 *
		 * @since 2.0.0
		 *
		 * @param string $bot     Crawler slug.
		 * @param string $path    Requested path, relative to the site's home URL.
		 * @param string $outcome "blocked" (403) or "limited" (429).
		 */
		do_action( 'tavc_crawler_request_refused', $bot, $path, $outcome );

		if ( self::OUTCOME_LIMITED === $outcome ) {
			self::send_rate_limited();
		}

		self::send_refusal( $bot );
	}

	/**
	 * Requested URL path as sent by the client, e.g. "/blog/llms.txt".
	 *
	 * @since    2.0.0
	 * @return   string
	 */
	public static function get_raw_request_path() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path        = wp_parse_url( $request_uri, PHP_URL_PATH );

		return ( is_string( $path ) && '' !== $path ) ? $path : '/';
	}

	/**
	 * Path relative to the site's home URL, e.g. "/llms.txt" for
	 * "/blog/llms.txt" when the site lives in /blog.
	 *
	 * @since    2.0.0
	 * @param    string    $path    Raw request path.
	 * @return   string
	 */
	public static function to_home_relative_path( $path ) {
		$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$home_path = is_string( $home_path ) ? untrailingslashit( $home_path ) : '';

		if ( '' !== $home_path && 0 === strpos( $path, $home_path . '/' ) ) {
			$path = substr( $path, strlen( $home_path ) );
		} elseif ( '' !== $home_path && $path === $home_path ) {
			$path = '/';
		}

		return $path;
	}

	/**
	 * Whether the request is for a file every crawler must be able to read:
	 * robots.txt (its rules), llms.txt and llms-full.txt.
	 *
	 * @since    2.0.0
	 * @param    string    $path    Path relative to the home URL.
	 * @return   bool
	 */
	public static function is_exempt_request( $path ) {
		// Plain-permalink forms (?robots=1, ?tavc_llms_txt=1) count only on the home URL
		// itself and with the exact values WordPress serves the file for; otherwise any
		// URL (or the REST API) could be read by adding the parameter.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only routing check.
		if ( in_array( $path, array( '/', '/index.php' ), true ) && ! isset( $_GET['rest_route'] ) ) {
			if ( ( isset( $_GET['robots'] ) && '1' === $_GET['robots'] ) || ( isset( $_GET['tavc_llms_txt'] ) && '1' === $_GET['tavc_llms_txt'] ) ) {
				return true;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// "PATHINFO" permalinks serve the files at /index.php/llms.txt.
		if ( 0 === strpos( $path, '/index.php/' ) ) {
			$path = substr( $path, strlen( '/index.php' ) );
		}

		// llms-full.txt holds the full text of the site, so blocked crawlers do not get it.
		$exempt = array( '/robots.txt', '/llms.txt' );

		/**
		 * Filters the paths that blocked crawlers can always read.
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $exempt Paths relative to the home URL.
		 */
		$exempt = (array) apply_filters( 'tavc_enforcement_exempt_paths', $exempt );

		return in_array( untrailingslashit( $path ), $exempt, true ) || in_array( $path, $exempt, true );
	}

	/**
	 * Count the refused request in the crawler log, when logging is on.
	 *
	 * @since    2.0.0
	 * @param    string    $bot        Crawler slug.
	 * @param    string    $path       Requested path.
	 * @param    string    $outcome    Outcome constant.
	 */
	private static function log_refusal( $bot, $path, $outcome ) {
		$tracking = TAVC_Settings::get_tracking_settings();
		if ( empty( $tracking['crawler_logging'] ) ) {
			return;
		}

		/** This filter is documented in includes/class-tavc-crawler-logger.php */
		if ( ! apply_filters( 'tavc_log_crawler_visit', true, $bot, $path ) ) {
			return;
		}

		TAVC_DB::record_crawler_hit( $bot, $path, $outcome );
	}

	/**
	 * Send a short plain-text 429 response and stop.
	 *
	 * @since    2.0.0
	 */
	private static function send_rate_limited() {
		$retry_after = max( 1, MINUTE_IN_SECONDS - ( time() % MINUTE_IN_SECONDS ) );

		if ( ! headers_sent() ) {
			status_header( 429 );
			nocache_headers();
			header( 'Content-Type: text/plain; charset=utf-8' );
			header( 'X-Robots-Tag: noindex, nofollow' );
			header( 'Retry-After: ' . $retry_after );
		}

		self::print_plain_text( "429 Too Many Requests\n\n" . __( 'This crawler is sending requests too quickly. Please slow down and try again later.', 'tbsh-ai-visibility-control' ) . "\n" );
		exit;
	}

	/**
	 * Send a short plain-text 403 response and stop.
	 *
	 * @since    2.0.0
	 * @param    string    $bot    Crawler slug.
	 */
	private static function send_refusal( $bot ) {
		$definitions = TAVC_Bots::get_definitions();
		$name        = isset( $definitions[ $bot ] ) ? $definitions[ $bot ]['name'] : $bot;

		if ( ! headers_sent() ) {
			status_header( 403 );
			nocache_headers();
			header( 'Content-Type: text/plain; charset=utf-8' );
			header( 'X-Robots-Tag: noindex, nofollow' );
		}

		$message = sprintf(
			"403 Forbidden\n\n%s\n",
			sprintf(
				/* translators: 1: crawler name such as GPTBot, 2: robots.txt URL. */
				__( 'This site does not allow %1$s to access its content. The crawler rules are published at %2$s', 'tbsh-ai-visibility-control' ),
				$name,
				home_url( '/robots.txt' )
			)
		);

		self::print_plain_text( $message );
		exit;
	}

	/**
	 * Print a text/plain body. HTML escaping would show entities such as &#039;
	 * in translations, so only control characters (other than new lines) are removed.
	 *
	 * @since    2.0.0
	 * @param    string    $text    Message.
	 */
	private static function print_plain_text( $text ) {
		if ( ! headers_sent() ) {
			header( 'X-Content-Type-Options: nosniff' );
		}

		echo preg_replace( '/[^\P{Cc}\n]/u', '', (string) $text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text/plain with nosniff; control characters removed.
	}
}
