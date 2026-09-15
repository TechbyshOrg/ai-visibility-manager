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

// Delete transients
delete_transient( 'tavc_llms_txt_cache' );
delete_transient( 'tavc_health_llms_txt' );
delete_transient( 'tavc_health_robots' );
delete_transient( 'tavc_recent_referrals' );

// Drop database table
global $wpdb;
$tavc_table_name = $wpdb->prefix . 'tavc_referrals';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $tavc_table_name ) );
