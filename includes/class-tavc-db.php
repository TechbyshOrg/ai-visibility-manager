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
	 * @since    1.3.0
	 * @var      array
	 */
	private static $table_exists = array();

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
	 * @since    1.3.0
	 * @return   string    Table name.
	 */
	public static function get_crawler_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'tavc_crawler_hits';
	}

	/**
	 * Whether a plugin table exists (cached per request).
	 *
	 * @since    1.3.0
	 * @param    string    $table_name    Full table name.
	 * @return   bool
	 */
	private static function table_exists( $table_name ) {
		global $wpdb;

		if ( ! isset( self::$table_exists[ $table_name ] ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			self::$table_exists[ $table_name ] = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) ) === $table_name );
		}

		return self::$table_exists[ $table_name ];
	}

	/**
	 * Whether the referrals table has the "method" column added in 1.3.0.
	 *
	 * Normally true once TAVC::maybe_upgrade() has run; the check keeps logging
	 * and reports working if that migration could not alter the table.
	 *
	 * @since    1.3.0
	 * @return   bool
	 */
	private static function referrals_has_method_column() {
		global $wpdb;

		static $has_column = null;

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
	 * @since    1.3.0 Added the $method parameter.
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
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(id) FROM %i', $table_name ) );

		return intval( $count );
	}

	/**
	 * Count referrals logged in a time window.
	 *
	 * @since    1.3.0
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
	 * @since    1.3.0
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row( $wpdb->prepare( 'SELECT timestamp, referrer FROM %i ORDER BY id DESC LIMIT 1', $table_name ) );
	}

	/**
	 * Get top AI referral sources.
	 *
	 * @since    1.0.0
	 * @since    1.3.0 Added the $since and $until parameters.
	 * @param    int            $limit    Max sources to fetch.
	 * @param    string|null    $since    Optional inclusive start, site-local 'Y-m-d H:i:s'.
	 * @param    string|null    $until    Optional exclusive end, site-local 'Y-m-d H:i:s'. Requires $since.
	 * @return   array                    Array of database objects.
	 */
	public static function get_top_referral_sources( $limit = 5, $since = null, $until = null ) {
		global $wpdb;

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
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
	 * @since    1.3.0 Added the $since and $until parameters.
	 * @param    int            $limit    Max pages to fetch.
	 * @param    string|null    $since    Optional inclusive start, site-local 'Y-m-d H:i:s'.
	 * @param    string|null    $until    Optional exclusive end, site-local 'Y-m-d H:i:s'. Requires $since.
	 * @return   array                    Array of database objects.
	 */
	public static function get_top_target_pages( $limit = 5, $since = null, $until = null ) {
		global $wpdb;

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $table_name ) );
	}

	/**
	 * Delete referral rows older than a cutoff, in batches.
	 *
	 * @since    1.3.0
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
	 * Fetch all logs for CSV export.
	 *
	 * @since    1.0.0
	 * @return   array    All referral log objects.
	 */
	public static function get_all_referrals_for_csv() {
		global $wpdb;

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! self::referrals_has_method_column() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_results( $wpdb->prepare( "SELECT timestamp, referrer, target_url, 'referrer' AS method FROM %i ORDER BY id DESC", $table_name ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( $wpdb->prepare( 'SELECT timestamp, referrer, target_url, method FROM %i ORDER BY id DESC', $table_name ) );
	}

	/*
	|--------------------------------------------------------------------------
	| AI crawler visits
	|--------------------------------------------------------------------------
	*/

	/**
	 * Count one crawler request. Rows are aggregated per crawler, day and path.
	 *
	 * @since    1.3.0
	 * @param    string    $bot     Crawler slug.
	 * @param    string    $path    Requested URL path.
	 * @return   int|false          Rows affected or false on failure.
	 */
	public static function record_crawler_hit( $bot, $path ) {
		global $wpdb;

		$table_name = self::get_crawler_table_name();
		if ( ! self::table_exists( $table_name ) ) {
			return false;
		}

		$bot  = substr( sanitize_key( $bot ), 0, 64 );
		$path = substr( (string) $path, 0, 255 );
		$now  = current_time( 'mysql' );

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
	 * @since    1.3.0
	 * @param    string         $since    Inclusive start date, site-local 'Y-m-d'.
	 * @param    string|null    $until    Optional exclusive end date, site-local 'Y-m-d'.
	 * @return   array                    Crawler slug => object( hits, last_seen ).
	 */
	public static function get_crawler_summary( $since, $until = null ) {
		global $wpdb;

		$table_name = self::get_crawler_table_name();
		if ( ! self::table_exists( $table_name ) ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT bot, SUM(hits) AS hits, MAX(last_seen) AS last_seen FROM %i WHERE hit_date >= %s AND hit_date < %s GROUP BY bot ORDER BY hits DESC', $table_name, $since, null !== $until ? $until : '9999-12-31' ) );

		$summary = array();
		foreach ( (array) $rows as $row ) {
			$summary[ $row->bot ] = (object) array(
				'hits'      => intval( $row->hits ),
				'last_seen' => $row->last_seen,
			);
		}

		return $summary;
	}

	/**
	 * Most requested paths across all AI crawlers.
	 *
	 * @since    1.3.0
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
	 * @since    1.3.0
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
	 * @since    1.3.0
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
	 * Delete crawler rows older than a cutoff date, in batches.
	 *
	 * @since    1.3.0
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
	 * @since    1.3.0
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
	 * @since    1.3.0
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
