<?php
/**
 * Fired when the plugin is uninstalled.
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

// Check if user opted-in to delete data on uninstall
$tavc_delete_data = get_option( 'tavc_delete_on_uninstall', 0 );
if ( ! $tavc_delete_data ) {
	return;
}

// Clear options
delete_option( 'tavc_blocked_bots' );
delete_option( 'tavc_delete_on_uninstall' );
delete_option( 'tavc_llms_txt_last_rebuild' );
delete_option( 'tavc_plugin_version' );
delete_option( 'tavc_llms_txt_settings' );
delete_option( 'tavc_tracking_settings' );
delete_option( 'tavc_content_signals' );
delete_option( 'tavc_custom_robots_rules' );
delete_option( 'tavc_enforcement_settings' );

// Delete transients
delete_transient( 'tavc_llms_txt_cache' );
delete_transient( 'tavc_llms_full_txt_cache' );
delete_transient( 'tavc_health_llms_txt' );
delete_transient( 'tavc_health_robots' );
delete_transient( 'tavc_recent_referrals' );
delete_transient( 'tavc_beacon_rate' );
delete_transient( 'tavc_beacon_hourly' );

// Scheduled events
wp_clear_scheduled_hook( 'tavc_daily_maintenance' );
wp_clear_scheduled_hook( 'tavc_weekly_digest' );

global $wpdb;

// Cached WooCommerce reports (keys include a date) and crawl rate limit counters (one per crawler)
foreach ( array( 'tavc_wc_report_', 'tavc_rate_limit_' ) as $tavc_transient_prefix ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_' . $tavc_transient_prefix ) . '%', $wpdb->esc_like( '_transient_timeout_' . $tavc_transient_prefix ) . '%' ) );
}

// Per-post "Exclude from llms.txt" flags
delete_post_meta_by_key( '_tavc_llms_exclude' );

// AI source saved on WooCommerce orders (classic post storage and HPOS)
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_tavc_ai_source' ) );
$tavc_orders_meta_table = $wpdb->prefix . 'wc_orders_meta';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $tavc_orders_meta_table ) ) ) === $tavc_orders_meta_table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE meta_key = %s', $tavc_orders_meta_table, '_tavc_ai_source' ) );
}

// Drop database tables
$tavc_table_name = $wpdb->prefix . 'tavc_referrals';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $tavc_table_name ) );
$tavc_crawler_table_name = $wpdb->prefix . 'tavc_crawler_hits';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $tavc_crawler_table_name ) );
