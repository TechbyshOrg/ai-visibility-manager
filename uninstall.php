<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Aivm
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if user opted-in to delete data on uninstall
$aivm_delete_data = get_option( 'aivm_delete_on_uninstall', 0 );
if ( ! $aivm_delete_data ) {
	return;
}

// Clear options
delete_option( 'aivm_blocked_bots' );
delete_option( 'aivm_delete_on_uninstall' );
delete_option( 'aivm_llms_txt_last_rebuild' );

// Delete transients
delete_transient( 'aivm_llms_txt_cache' );
delete_transient( 'aivm_health_llms_txt' );
delete_transient( 'aivm_health_robots' );

// Drop database table
global $wpdb;
$aivm_table_name = $wpdb->prefix . 'aivm_referrals';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $aivm_table_name ) );
