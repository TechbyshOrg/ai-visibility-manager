<?php
/**
 * Database Helper Class
 *
 * Handles custom database queries and schema checks.
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
 * Database Helper Class.
 *
 * Handles database operations for the custom tables.
 *
 * @since      1.0.0
 * @package    Tavc
 * @subpackage Tavc/includes
 * @author     Techbysh
 */
class TAVC_DB {

	/**
	 * Rows deleted per query when pruning old logs.
	 */
	const PRUNE_BATCH_SIZE = 5000;

	/**
	 * Per-request cache of table existence checks.
	 *
	 * @since    2.0.0
	 * @var      array
	 */
	private static $table_exists = array();

	/**
	 * Version of the table layout created by TAVC_Activator. Stored in the
	 * tavc_db_version option once dbDelta() has run; bump it whenever a table changes.
	 *
	 * @since    2.0.0
	 */
	const SCHEMA_VERSION = '2.0.0';

	/**
	 * Object cache group for the request counters.
	 *
	 * @since    2.0.0
	 */
	const COUNTER_CACHE_GROUP = 'tavc_counters';

	/**
	 * Whether the tables match SCHEMA_VERSION. Saves the SHOW TABLES / SHOW COLUMNS
	 * checks on every logged request; tavc_db_version is autoloaded.
	 *
	 * @since    2.0.0
	 * @return   bool
	 */
	public static function schema_is_current() {
		return self::SCHEMA_VERSION === get_option( 'tavc_db_version', '' );
	}

	/**
	 * Name of the request counter table.
	 *
	 * @since    2.0.0
	 * @return   string
	 */
	public static function get_counter_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'tavc_counters';
	}

	/**
	 * Count one event in a fixed time window and return the count so far.
	 *
	 * Used for the crawler rate limit, the beacon limits and the referral
	 * debounce. Uses the persistent object cache when there is one, otherwise
	 * a single atomic upsert in the counter table, so concurrent requests are
	 * never lost and no option row is rewritten per request.
	 *
	 * @since    2.0.0
	 * @param    string    $key       Counter name (any length; it is hashed).
	 * @param    int       $window    Window length in seconds.
	 * @return   int                  Events in the current window, this one included.
	 *                                0 when the counter is unavailable (callers should then allow).
	 */
	public static function bump_counter( $key, $window ) {
		global $wpdb;

		$window = max( 1, (int) $window );
		$start  = (int) ( floor( time() / $window ) * $window );
		$bucket = md5( $key );

		if ( wp_using_ext_object_cache() ) {
			$cache_key = $bucket . '_' . $start;
			wp_cache_add( $cache_key, 0, self::COUNTER_CACHE_GROUP, $window + 60 );
			$count = wp_cache_incr( $cache_key, 1, self::COUNTER_CACHE_GROUP );
			if ( false !== $count ) {
				return (int) $count;
			}
		}

		$table_name = self::get_counter_table_name();
		if ( ! self::table_exists( $table_name ) ) {
			return 0;
		}

		// "hits" is assigned before "window_start", so the IF() still compares with the stored window.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (bucket, window_start, expires, hits) VALUES (%s, %d, %d, 1) ON DUPLICATE KEY UPDATE hits = IF(window_start = VALUES(window_start), hits + 1, 1), window_start = VALUES(window_start), expires = VALUES(expires)',
				$table_name,
				$bucket,
				$start,
				$start + $window
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT hits FROM %i WHERE bucket = %s', $table_name, $bucket ) );
	}

	/**
	 * Delete expired counter rows.
	 *
	 * @since    2.0.0
	 * @return   int    Rows deleted.
	 */
	public static function prune_counters() {
		global $wpdb;

		$table_name = self::get_counter_table_name();
		if ( ! self::table_exists( $table_name ) ) {
			return 0;
		}

		return self::delete_in_batches( $wpdb->prepare( 'DELETE FROM %i WHERE expires < %d LIMIT %d', $table_name, time(), self::PRUNE_BATCH_SIZE ) );
	}

	/**
	 * Retrieve table name with proper prefix.
	 *
	 * @since    1.0.0
	 * @return   string    Table name.
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'tavc_referrals';
	}

	/**
	 * Retrieve the crawler visits table name with proper prefix.
	 *
	 * @since    2.0.0
	 * @return   string    Table name.
	 */
	public static function get_crawler_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'tavc_crawler_hits';
	}

	/**
	 * Whether a plugin table exists (cached per request).
	 *
	 * @since    2.0.0
	 * @param    string    $table_name    Full table name.
	 * @return   bool
	 */
	private static function table_exists( $table_name ) {
		global $wpdb;

		if ( ! isset( self::$table_exists[ $table_name ] ) && self::schema_is_current() ) {
			self::$table_exists[ $table_name ] = true;
		}

		if ( ! isset( self::$table_exists[ $table_name ] ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			self::$table_exists[ $table_name ] = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) ) === $table_name );
		}

		return self::$table_exists[ $table_name ];
	}

	/**
	 * Whether the referrals table has the "method" column added in 2.0.0.
	 *
	 * Normally true once TAVC::maybe_upgrade() has run; the check keeps logging
	 * and reports working if that migration could not alter the table.
	 *
	 * @since    2.0.0
	 * @return   bool
	 */
	private static function referrals_has_method_column() {
		global $wpdb;

		static $has_column = null;

		if ( null === $has_column && self::schema_is_current() ) {
			$has_column = true;
		}

		if ( null === $has_column ) {
			$table_name = self::get_table_name();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$has_column = self::table_exists( $table_name ) && null !== $wpdb->get_var( $wpdb->prepare( 'SHOW COLUMNS FROM %i LIKE %s', $table_name, 'method' ) );
		}

		return $has_column;
	}

	/**
	 * Log a referral entry.
	 *
	 * @since    1.0.0
	 * @since    2.0.0 Added the $method parameter.
	 * @param    string    $referrer     AI Referral Source.
	 * @param    string    $target_url   Requested Destination URL.
	 * @param    string    $method       How the visit was detected: referrer, utm or beacon.
	 * @return   int|false               Rows affected or false on failure.
	 */
	public static function insert_referral( $referrer, $target_url, $method = 'referrer' ) {
		global $wpdb;

		$table_name = self::get_table_name();
		$method     = in_array( $method, array( 'referrer', 'utm', 'beacon' ), true ) ? $method : 'referrer';

		$data = array(
			'timestamp'  => current_time( 'mysql' ),
			'referrer'   => sanitize_text_field( $referrer ),
			'target_url' => esc_url_raw( $target_url ),
		);

		if ( self::referrals_has_method_column() ) {
			$data['method'] = $method;
		}

		// Insert into the database safely
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->insert( $table_name, $data, array_fill( 0, count( $data ), '%s' ) );
	}

	/**
	 * Get total count of AI Referrals.
	 *
	 * @since    1.0.0
	 * @return   int       Total referrals.
	 */
	public static function get_total_referrals() {
		global $wpdb;

		$table_name = self::get_table_name();

		// Check table existence first to avoid errors before DB table is created
		if ( ! self::table_exists( $table_name ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(id) FROM %i', $table_name ) );

		return intval( $count );
	}

	/**
	 * Count referrals logged in a time window.
	 *
	 * @since    2.0.0
	 * @param    string    $start    Inclusive start, site-local 'Y-m-d H:i:s'.
	 * @param    string    $end      Exclusive end, site-local 'Y-m-d H:i:s'.
	 * @return   int
	 */
	public static function get_referral_count_between( $start, $end ) {
		global $wpdb;

		$table_name = self::get_table_name();
		if ( ! self::table_exists( $table_name ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(id) FROM %i WHERE timestamp >= %s AND timestamp < %s', $table_name, $start, $end ) );

		return intval( $count );
	}

	/**
	 * Referral counts per day.
	 *
	 * @since    2.0.0
	 * @param    string    $since    Inclusive start, site-local 'Y-m-d H:i:s'.
	 * @return   array               'Y-m-d' => count, only for days that have visits.
	 */
	public static function get_daily_referral_counts( $since ) {
		global $wpdb;

		$table_name = self::get_table_name();
		if ( ! self::table_exists( $table_name ) ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT DATE(timestamp) AS day, COUNT(id) AS count FROM %i WHERE timestamp >= %s GROUP BY DATE(timestamp)', $table_name, $since ) );

		$counts = array();
		foreach ( (array) $rows as $row ) {
			$counts[ $row->day ] = intval( $row->count );
		}

		return $counts;
	}

	/**
	 * Get details of the last logged AI referral.
	 *
	 * @since    1.0.0
	 * @return   object|null    Last referral row or null if none.
	 */
	public static function get_last_referral() {
		global $wpdb;

		$table_name = self::get_table_name();

		if ( ! self::table_exists( $table_name ) ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row( $wpdb->prepare( 'SELECT timestamp, referrer FROM %i ORDER BY id DESC LIMIT 1', $table_name ) );
	}

	/**
	 * Get top AI referral sources.
	 *
	 * @since    1.0.0
	 * @since    2.0.0 Added the $since and $until parameters.
	 * @param    int            $limit    Max sources to fetch.
	 * @param    string|null    $since    Optional inclusive start, site-local 'Y-m-d H:i:s'.
	 * @param    string|null    $until    Optional exclusive end, site-local 'Y-m-d H:i:s'. Requires $since.
	 * @return   array                    Array of database objects.
	 */
	public static function get_top_referral_sources( $limit = 5, $since = null, $until = null ) {
		global $wpdb;

		$table_name = self::get_table_name();

		if ( ! self::table_exists( $table_name ) ) {
			return array();
		}

		$limit = intval( $limit );

		if ( null !== $since ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_results(
				$wpdb->prepare(
					'SELECT referrer, COUNT(id) as count FROM %i WHERE timestamp >= %s AND timestamp < %s GROUP BY referrer ORDER BY count DESC LIMIT %d',
					$table_name,
					$since,
					null !== $until ? $until : '9999-12-31 23:59:59',
					$limit
				)
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT referrer, COUNT(id) as count FROM %i GROUP BY referrer ORDER BY count DESC LIMIT %d',
				$table_name,
				$limit
			)
		);
	}

	/**
	 * Get top visited pages from AI sources.
	 *
	 * @since    1.0.0
	 * @since    2.0.0 Added the $since and $until parameters.
	 * @param    int            $limit    Max pages to fetch.
	 * @param    string|null    $since    Optional inclusive start, site-local 'Y-m-d H:i:s'.
	 * @param    string|null    $until    Optional exclusive end, site-local 'Y-m-d H:i:s'. Requires $since.
	 * @return   array                    Array of database objects.
	 */
	public static function get_top_target_pages( $limit = 5, $since = null, $until = null ) {
		global $wpdb;

		$table_name = self::get_table_name();

		if ( ! self::table_exists( $table_name ) ) {
			return array();
		}

		$limit = intval( $limit );

		if ( null !== $since ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_results(
				$wpdb->prepare(
					'SELECT target_url, COUNT(id) as count FROM %i WHERE timestamp >= %s AND timestamp < %s GROUP BY target_url ORDER BY count DESC LIMIT %d',
					$table_name,
					$since,
					null !== $until ? $until : '9999-12-31 23:59:59',
					$limit
				)
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT target_url, COUNT(id) as count FROM %i GROUP BY target_url ORDER BY count DESC LIMIT %d',
				$table_name,
				$limit
			)
		);
	}

	/**
	 * Get recent logged AI referrals.
	 *
	 * @since    1.0.0
	 * @param    int       $limit    Max entries to fetch.
	 * @return   array               Array of database objects.
	 */
	public static function get_recent_referrals( $limit = 20 ) {
		global $wpdb;

		$table_name = self::get_table_name();

		if ( ! self::table_exists( $table_name ) ) {
			return array();
		}

		$limit = intval( $limit );

		if ( ! self::referrals_has_method_column() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_results( $wpdb->prepare( "SELECT timestamp, referrer, target_url, 'referrer' AS method FROM %i ORDER BY id DESC LIMIT %d", $table_name, $limit ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT timestamp, referrer, target_url, method FROM %i ORDER BY id DESC LIMIT %d',
				$table_name,
				$limit
			)
		);
	}

	/**
	 * Delete all recorded referral logs.
	 *
	 * @since    1.0.0
	 * @return   int|false    Number of rows deleted, or false on error.
	 */
	public static function clear_referral_logs() {
		global $wpdb;

		$table_name = self::get_table_name();

		if ( ! self::table_exists( $table_name ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $table_name ) );
	}

	/**
	 * Delete referral rows older than a cutoff, in batches.
	 *
	 * @since    2.0.0
	 * @param    string    $before    Exclusive cutoff, site-local 'Y-m-d H:i:s'.
	 * @return   int                  Rows deleted.
	 */
	public static function prune_referrals( $before ) {
		global $wpdb;

		$table_name = self::get_table_name();
		if ( ! self::table_exists( $table_name ) ) {
			return 0;
		}

		return self::delete_in_batches( $wpdb->prepare( 'DELETE FROM %i WHERE timestamp < %s LIMIT %d', $table_name, $before, self::PRUNE_BATCH_SIZE ) );
	}

	/**
	 * One batch of referral rows for CSV export, newest first.
	 *
	 * Exports read the table in batches (keyset on id) so large logs never
	 * have to fit in memory at once.
	 *
	 * @since    2.0.0
	 * @param    int    $before_id    Only rows with a smaller id; 0 for the first batch.
	 * @param    int    $limit        Rows per batch.
	 * @return   array                Objects with id, timestamp, referrer, target_url, method.
	 */
	public static function get_referrals_csv_batch( $before_id = 0, $limit = 2000 ) {
		global $wpdb;

		$table_name = self::get_table_name();
		if ( ! self::table_exists( $table_name ) ) {
			return array();
		}

		$method    = self::referrals_has_method_column() ? 'method' : "'referrer' AS method";
		$before_id = $before_id > 0 ? (int) $before_id : PHP_INT_MAX;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $method is one of two fixed strings.
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT id, timestamp, referrer, target_url, {$method} FROM %i WHERE id < %d ORDER BY id DESC LIMIT %d", $table_name, $before_id, max( 1, (int) $limit ) ) );
	}

	/*
	|--------------------------------------------------------------------------
	| AI crawler visits
	|--------------------------------------------------------------------------
	*/

	/**
	 * Whether the crawler table has the "blocked" and "limited" columns added in 2.0.0.
	 *
	 * Normally true once TAVC::maybe_upgrade() has run; the check keeps logging
	 * and reports working if that migration could not alter the table.
	 *
	 * @since    2.0.0
	 * @return   bool
	 */
	private static function crawler_has_outcome_columns() {
		global $wpdb;

		static $has_columns = null;

		if ( null === $has_columns && self::schema_is_current() ) {
			$has_columns = true;
		}

		if ( null === $has_columns ) {
			$table_name = self::get_crawler_table_name();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$has_columns = self::table_exists( $table_name ) && 2 === count( (array) $wpdb->get_col( $wpdb->prepare( "SHOW COLUMNS FROM %i WHERE Field IN ('blocked', 'limited')", $table_name ) ) );
		}

		return $has_columns;
	}

	/**
	 * Path stored for crawler requests that ended in a 404 page.
	 *
	 * @since    2.0.0
	 */
	const NOT_FOUND_PATH = '(not found)';

	/**
	 * Path stored for requests beyond the daily number of distinct paths per crawler.
	 *
	 * @since    2.0.0
	 */
	const OTHER_PATHS = '(other paths)';

	/**
	 * Readable label for a stored crawler path (translates the placeholder rows).
	 *
	 * @since    2.0.0
	 * @param    string    $path    Stored path.
	 * @return   string
	 */
	public static function get_path_label( $path ) {
		if ( self::NOT_FOUND_PATH === $path ) {
			return __( '(pages not found)', 'tbsh-ai-visibility-control' );
		}
		if ( self::OTHER_PATHS === $path ) {
			return __( '(other pages)', 'tbsh-ai-visibility-control' );
		}

		return (string) $path;
	}

	/**
	 * Count one crawler request. Rows are aggregated per crawler, day and path.
	 *
	 * Each crawler gets at most a fixed number of distinct path rows per day
	 * (filter tavc_crawler_paths_per_day, default 5000); later new paths are
	 * counted in one "(other paths)" row. Anyone can send a crawler user agent
	 * with random addresses, so this keeps the table from growing without limit.
	 *
	 * @since    2.0.0
	 * @param    string    $bot        Crawler slug.
	 * @param    string    $path       Requested URL path.
	 * @param    string    $outcome    Empty for a served request, 'blocked' when it was refused with
	 *                                 a 403, or 'limited' when it was refused by the rate limit (429).
	 * @return   int|false             Rows affected or false on failure.
	 */
	public static function record_crawler_hit( $bot, $path, $outcome = '' ) {
		global $wpdb;

		$result = self::upsert_crawler_hit( $bot, $path, $outcome );

		// 1 = a new row was inserted (2 = an existing row was updated).
		if ( 1 !== $result || self::OTHER_PATHS === $path ) {
			return $result;
		}

		$bot = substr( sanitize_key( $bot ), 0, 64 );

		/**
		 * Filters how many distinct paths are logged per crawler and day.
		 *
		 * @since 2.0.0
		 *
		 * @param int    $limit Paths per day. 0 for no limit.
		 * @param string $bot   Crawler slug.
		 */
		$limit = absint( apply_filters( 'tavc_crawler_paths_per_day', 5000, $bot ) );
		$today = current_time( 'Y-m-d' );

		if ( $limit > 0 && self::bump_counter( 'crawler_paths|' . $bot . '|' . $today, DAY_IN_SECONDS ) > $limit ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE bot = %s AND hit_date = %s AND url_hash = %s', self::get_crawler_table_name(), $bot, $today, md5( substr( (string) $path, 0, 255 ) ) ) );

			return self::upsert_crawler_hit( $bot, self::OTHER_PATHS, $outcome );
		}

		return $result;
	}

	/**
	 * Insert or increment one crawler row.
	 *
	 * @since    2.0.0
	 * @param    string    $bot        Crawler slug.
	 * @param    string    $path       Requested URL path.
	 * @param    string    $outcome    '', 'blocked' or 'limited'.
	 * @return   int|false             1 when a row was inserted, 2 when one was updated, false on failure.
	 */
	private static function upsert_crawler_hit( $bot, $path, $outcome ) {
		global $wpdb;

		$table_name = self::get_crawler_table_name();
		if ( ! self::table_exists( $table_name ) ) {
			return false;
		}

		$bot  = substr( sanitize_key( $bot ), 0, 64 );
		$path = substr( (string) $path, 0, 255 );
		$now  = current_time( 'mysql' );

		if ( in_array( $outcome, array( 'blocked', 'limited' ), true ) && self::crawler_has_outcome_columns() ) {
			$blocked = 'blocked' === $outcome ? 1 : 0;
			$limited = 'limited' === $outcome ? 1 : 0;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->query(
				$wpdb->prepare(
					'INSERT INTO %i (hit_date, bot, url_hash, url_path, hits, blocked, limited, last_seen) VALUES (%s, %s, %s, %s, 1, %d, %d, %s) ON DUPLICATE KEY UPDATE hits = hits + 1, blocked = blocked + VALUES(blocked), limited = limited + VALUES(limited), last_seen = VALUES(last_seen)',
					$table_name,
					substr( $now, 0, 10 ),
					$bot,
					md5( $path ),
					$path,
					$blocked,
					$limited,
					$now
				)
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (hit_date, bot, url_hash, url_path, hits, last_seen) VALUES (%s, %s, %s, %s, 1, %s) ON DUPLICATE KEY UPDATE hits = hits + 1, last_seen = VALUES(last_seen)',
				$table_name,
				substr( $now, 0, 10 ),
				$bot,
				md5( $path ),
				$path,
				$now
			)
		);
	}

	/**
	 * Requests and last visit per crawler.
	 *
	 * @since    2.0.0
	 * @param    string         $since    Inclusive start date, site-local 'Y-m-d'.
	 * @param    string|null    $until    Optional exclusive end date, site-local 'Y-m-d'.
	 * @return   array                    Crawler slug => object( hits, blocked, limited, last_seen ).
	 *                                    "hits" includes the refused requests counted in
	 *                                    "blocked" (403) and "limited" (429).
	 */
	public static function get_crawler_summary( $since, $until = null ) {
		global $wpdb;

		$table_name = self::get_crawler_table_name();
		if ( ! self::table_exists( $table_name ) ) {
			return array();
		}

		$until = null !== $until ? $until : '9999-12-31';

		if ( self::crawler_has_outcome_columns() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT bot, SUM(hits) AS hits, SUM(blocked) AS blocked, SUM(limited) AS limited, MAX(last_seen) AS last_seen FROM %i WHERE hit_date >= %s AND hit_date < %s GROUP BY bot ORDER BY hits DESC', $table_name, $since, $until ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT bot, SUM(hits) AS hits, 0 AS blocked, 0 AS limited, MAX(last_seen) AS last_seen FROM %i WHERE hit_date >= %s AND hit_date < %s GROUP BY bot ORDER BY hits DESC', $table_name, $since, $until ) );
		}

		$summary = array();
		foreach ( (array) $rows as $row ) {
			$summary[ $row->bot ] = (object) array(
				'hits'      => intval( $row->hits ),
				'blocked'   => intval( $row->blocked ),
				'limited'   => intval( $row->limited ),
				'last_seen' => $row->last_seen,
			);
		}

		return $summary;
	}

	/**
	 * Most requested paths across all AI crawlers.
	 *
	 * @since    2.0.0
	 * @param    string    $since    Inclusive start date, site-local 'Y-m-d'.
	 * @param    int       $limit    Max rows.
	 * @return   array               Objects with url_path and hits.
	 */
	public static function get_crawler_top_paths( $since, $limit = 10 ) {
		global $wpdb;

		$table_name = self::get_crawler_table_name();
		if ( ! self::table_exists( $table_name ) ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT url_path, SUM(hits) AS hits FROM %i WHERE hit_date >= %s GROUP BY url_hash, url_path ORDER BY hits DESC LIMIT %d', $table_name, $since, intval( $limit ) ) );
	}

	/**
	 * Crawler requests for one path, per crawler.
	 *
	 * @since    2.0.0
	 * @param    string    $path     URL path, e.g. /llms.txt.
	 * @param    string    $since    Inclusive start date, site-local 'Y-m-d'.
	 * @return   array               Crawler slug => hits.
	 */
	public static function get_crawler_hits_for_path( $path, $since ) {
		global $wpdb;

		$table_name = self::get_crawler_table_name();
		if ( ! self::table_exists( $table_name ) ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT bot, SUM(hits) AS hits FROM %i WHERE url_hash = %s AND hit_date >= %s GROUP BY bot ORDER BY hits DESC', $table_name, md5( $path ), $since ) );

		$hits = array();
		foreach ( (array) $rows as $row ) {
			$hits[ $row->bot ] = intval( $row->hits );
		}

		return $hits;
	}

	/**
	 * Total crawler requests in a date window.
	 *
	 * @since    2.0.0
	 * @param    string    $start    Inclusive start date, site-local 'Y-m-d'.
	 * @param    string    $end      Exclusive end date, site-local 'Y-m-d'.
	 * @return   int
	 */
	public static function get_crawler_hits_between( $start, $end ) {
		global $wpdb;

		$table_name = self::get_crawler_table_name();
		if ( ! self::table_exists( $table_name ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return intval( $wpdb->get_var( $wpdb->prepare( 'SELECT SUM(hits) FROM %i WHERE hit_date >= %s AND hit_date < %s', $table_name, $start, $end ) ) );
	}

	/**
	 * One batch of crawler rows for CSV export, newest first.
	 *
	 * @since    2.0.0
	 * @param    int    $before_id    Only rows with a smaller id; 0 for the first batch.
	 * @param    int    $limit        Rows per batch.
	 * @return   array                Objects with id, hit_date, bot, url_path, hits, blocked, limited, last_seen.
	 */
	public static function get_crawler_hits_csv_batch( $before_id = 0, $limit = 2000 ) {
		global $wpdb;

		$table_name = self::get_crawler_table_name();
		if ( ! self::table_exists( $table_name ) ) {
			return array();
		}

		$outcome   = self::crawler_has_outcome_columns() ? 'blocked, limited' : '0 AS blocked, 0 AS limited';
		$before_id = $before_id > 0 ? (int) $before_id : PHP_INT_MAX;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $outcome is one of two fixed strings.
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT id, hit_date, bot, url_path, hits, {$outcome}, last_seen FROM %i WHERE id < %d ORDER BY id DESC LIMIT %d", $table_name, $before_id, max( 1, (int) $limit ) ) );
	}

	/**
	 * Delete crawler rows older than a cutoff date, in batches.
	 *
	 * @since    2.0.0
	 * @param    string    $before    Exclusive cutoff date, site-local 'Y-m-d'.
	 * @return   int                  Rows deleted.
	 */
	public static function prune_crawler_hits( $before ) {
		global $wpdb;

		$table_name = self::get_crawler_table_name();
		if ( ! self::table_exists( $table_name ) ) {
			return 0;
		}

		return self::delete_in_batches( $wpdb->prepare( 'DELETE FROM %i WHERE hit_date < %s LIMIT %d', $table_name, $before, self::PRUNE_BATCH_SIZE ) );
	}

	/**
	 * Delete every crawler visit row.
	 *
	 * @since    2.0.0
	 * @return   int|false
	 */
	public static function clear_crawler_logs() {
		global $wpdb;

		$table_name = self::get_crawler_table_name();
		if ( ! self::table_exists( $table_name ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $table_name ) );
	}

	/**
	 * Run a prepared "DELETE ... LIMIT n" query until nothing is left to delete.
	 *
	 * Batching keeps each query short so large tables are not locked for long.
	 *
	 * @since    2.0.0
	 * @param    string    $prepared_sql    Prepared DELETE statement with a LIMIT clause.
	 * @return   int                        Rows deleted.
	 */
	private static function delete_in_batches( $prepared_sql ) {
		global $wpdb;

		$total = 0;

		// At most 200 batches (1,000,000 rows) per run; the rest is removed on the next run.
		for ( $i = 0; $i < 200; $i++ ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$deleted = $wpdb->query( $prepared_sql );
			if ( ! $deleted ) {
				break;
			}
			$total += intval( $deleted );
			if ( $deleted < self::PRUNE_BATCH_SIZE ) {
				break;
			}
		}

		return $total;
	}
}
