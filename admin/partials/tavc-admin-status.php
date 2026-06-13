<?php
/**
 * View for System Status tab
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Tavc
 * @subpackage Tavc/admin/partials
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

global $wpdb;

$tavc_sys_info = array(
	'wp_version'      => get_bloginfo( 'version' ),
	'php_version'     => PHP_VERSION,
	'mysql_version'   => $wpdb->db_version(),
	'memory_limit'    => ini_get( 'memory_limit' ),
	'execution_time'  => ini_get( 'max_execution_time' ) . 's',
	'plugin_version'  => $this->version,
	'multisite'       => is_multisite() ? __( 'Yes', 'tbsh-ai-visibility-control' ) : __( 'No', 'tbsh-ai-visibility-control' ),
	'permalink_style' => get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : __( 'Plain (Default)', 'tbsh-ai-visibility-control' ),
);
?>

<div class="tavc-card">
	<h3 class="tavc-card-title">
		<span class="dashicons dashicons-info-outline"></span>
		<?php esc_html_e( 'System Status & Diagnostics', 'tbsh-ai-visibility-control' ); ?>
	</h3>
	
	<p style="margin-bottom: 20px; color: var(--tavc-text-muted); line-height: 1.5;">
		<?php esc_html_e( 'The information below summarizes your server environment and active WordPress setup. This detail is helpful when troubleshooting crawl rules or requesting plugin support.', 'tbsh-ai-visibility-control' ); ?>
	</p>
	
	<table class="tavc-table tavc-status-table" style="border: 1px solid var(--tavc-border);">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Environment Parameter', 'tbsh-ai-visibility-control' ); ?></th>
				<th><?php esc_html_e( 'Active Value', 'tbsh-ai-visibility-control' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><strong><?php esc_html_e( 'TBSH AI Visibility Control Version', 'tbsh-ai-visibility-control' ); ?></strong></td>
				<td><code><?php echo esc_html( $tavc_sys_info['plugin_version'] ); ?></code></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'WordPress Version', 'tbsh-ai-visibility-control' ); ?></strong></td>
				<td><code><?php echo esc_html( $tavc_sys_info['wp_version'] ); ?></code></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'PHP Version', 'tbsh-ai-visibility-control' ); ?></strong></td>
				<td><code><?php echo esc_html( $tavc_sys_info['php_version'] ); ?></code></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'MySQL/MariaDB Version', 'tbsh-ai-visibility-control' ); ?></strong></td>
				<td><code><?php echo esc_html( $tavc_sys_info['mysql_version'] ); ?></code></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'PHP Memory Limit', 'tbsh-ai-visibility-control' ); ?></strong></td>
				<td><code><?php echo esc_html( $tavc_sys_info['memory_limit'] ); ?></code></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Max Execution Time', 'tbsh-ai-visibility-control' ); ?></strong></td>
				<td><code><?php echo esc_html( $tavc_sys_info['execution_time'] ); ?></code></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'WordPress Multisite', 'tbsh-ai-visibility-control' ); ?></strong></td>
				<td><?php echo esc_html( $tavc_sys_info['multisite'] ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Permalink Structure', 'tbsh-ai-visibility-control' ); ?></strong></td>
				<td><code><?php echo esc_html( $tavc_sys_info['permalink_style'] ); ?></code></td>
			</tr>
		</tbody>
	</table>
</div>
