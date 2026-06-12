<?php
/**
 * Database Helper Class
 *
 * Handles custom database queries and schema checks.
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
 * Database Helper Class.
 *
 * Handles database operations for the custom table.
 *
 * @since      1.0.0
 * @package    Aivm
 * @subpackage Aivm/includes
 * @author     Techbysh
 */
class AIVM_DB {

	/**
	 * Retrieve table name with proper prefix.
	 *
	 * @since    1.0.0
	 * @return   string    Table name.
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'aivm_referrals';
	}

	/**
	 * Log a referral entry.
	 *
	 * @since    1.0.0
	 * @param    string    $referrer     AI Referral Source.
	 * @param    string    $target_url   Requested Destination URL.
	 * @return   int|false               Rows affected or false on failure.
	 */
	public static function insert_referral( $referrer, $target_url ) {
		global $wpdb;

		$table_name = self::get_table_name();

		// Insert into the database safely
		return $wpdb->insert(
			$table_name,
			array(
				'timestamp'  => current_time( 'mysql' ),
				'referrer'   => sanitize_text_field( $referrer ),
				'target_url' => esc_url_raw( $target_url ),
			),
			array(
				'%s',
				'%s',
				'%s',
			)
		);
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
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return 0;
		}

		$count = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );

		return intval( $count );
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
		
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return null;
		}

		return $wpdb->get_row( "SELECT timestamp, referrer FROM $table_name ORDER BY id DESC LIMIT 1" );
	}

	/**
	 * Get top AI referral sources.
	 *
	 * @since    1.0.0
	 * @param    int       $limit    Max sources to fetch.
	 * @return   array               Array of database objects.
	 */
	public static function get_top_referral_sources( $limit = 5 ) {
		global $wpdb;

		$table_name = self::get_table_name();
		
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return array();
		}

		$limit = intval( $limit );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT referrer, COUNT(id) as count FROM $table_name GROUP BY referrer ORDER BY count DESC LIMIT %d",
				$limit
			)
		);
	}

	/**
	 * Get top visited pages from AI sources.
	 *
	 * @since    1.0.0
	 * @param    int       $limit    Max pages to fetch.
	 * @return   array               Array of database objects.
	 */
	public static function get_top_target_pages( $limit = 5 ) {
		global $wpdb;

		$table_name = self::get_table_name();
		
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return array();
		}

		$limit = intval( $limit );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT target_url, COUNT(id) as count FROM $table_name GROUP BY target_url ORDER BY count DESC LIMIT %d",
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
		
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return array();
		}

		$limit = intval( $limit );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT timestamp, referrer, target_url FROM $table_name ORDER BY id DESC LIMIT %d",
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
		
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return false;
		}

		return $wpdb->query( "TRUNCATE TABLE $table_name" );
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
		
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return array();
		}

		return $wpdb->get_results( "SELECT timestamp, referrer, target_url FROM $table_name ORDER BY id DESC" );
	}
}
