<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Data is removed only on sites where "Delete all data when uninstalling" is on.
 * On a multisite network every site is checked with its own setting.
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Tavc
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove the plugin's data from the current site, if the site opted in.
 *
 * @since    2.0.0
 * @return   bool    Whether data was removed.
 */
function tavc_uninstall_site() {
	global $wpdb;

	if ( ! get_option( 'tavc_delete_on_uninstall', 0 ) ) {
		return false;
	}

	$options = array(
		'tavc_blocked_bots',
		'tavc_delete_on_uninstall',
		'tavc_llms_txt_last_rebuild',
		'tavc_plugin_version',
		'tavc_db_version',
		'tavc_llms_txt_settings',
		'tavc_tracking_settings',
		'tavc_content_signals',
		'tavc_custom_robots_rules',
		'tavc_enforcement_settings',
		'tavc_llms_generation',
		'tavc_llms_store_index',
		'tavc_llms_store_full',
		'tavc_llms_lock_index',
		'tavc_llms_lock_full',
		'tavc_upgrade_lock',
	);
	foreach ( $options as $option ) {
		delete_option( $option );
	}

	$transients = array(
		'tavc_llms_txt_cache',
		'tavc_llms_full_txt_cache',
		'tavc_health_llms_txt',
		'tavc_health_robots',
		'tavc_recent_referrals',
		'tavc_beacon_rate',
		'tavc_beacon_hourly',
	);
	foreach ( $transients as $transient ) {
		delete_transient( $transient );
	}

	// Scheduled events.
	wp_clear_scheduled_hook( 'tavc_daily_maintenance' );
	wp_clear_scheduled_hook( 'tavc_weekly_digest' );

	// Cached WooCommerce reports (keys include a date) and crawl rate limit counters from development builds (one per crawler).
	foreach ( array( 'tavc_wc_report_', 'tavc_rate_limit_' ) as $prefix ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_' . $prefix ) . '%', $wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%' ) );
	}

	// Per-post "Exclude from llms.txt" flags.
	delete_post_meta_by_key( '_tavc_llms_exclude' );

	// AI source saved on WooCommerce orders (classic post storage and HPOS).
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_tavc_ai_source' ) );
	$orders_meta_table = $wpdb->prefix . 'wc_orders_meta';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $orders_meta_table ) ) ) === $orders_meta_table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE meta_key = %s', $orders_meta_table, '_tavc_ai_source' ) );
	}

	// Database tables.
	foreach ( array( 'tavc_referrals', 'tavc_crawler_hits', 'tavc_counters' ) as $table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . $table ) );
	}

	return true;
}

$tavc_removed = false;

if ( is_multisite() ) {
	foreach ( get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) as $tavc_site_id ) {
		switch_to_blog( $tavc_site_id );
		$tavc_removed = tavc_uninstall_site() || $tavc_removed;
		restore_current_blog();
	}
} else {
	$tavc_removed = tavc_uninstall_site();
}

// "Get started" progress is stored per user, and user meta is shared by all sites of a network.
if ( $tavc_removed ) {
	delete_metadata( 'user', 0, 'tavc_get_started', '', true );
}
