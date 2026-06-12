<?php
/**
 * View for System Status tab
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Aivm
 * @subpackage Aivm/admin/partials
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

global $wpdb;

$sys_info = array(
	'wp_version'      => get_bloginfo( 'version' ),
	'php_version'     => PHP_VERSION,
	'mysql_version'   => $wpdb->db_version(),
	'memory_limit'    => ini_get( 'memory_limit' ),
	'execution_time'  => ini_get( 'max_execution_time' ) . 's',
	'plugin_version'  => $this->version,
	'multisite'       => is_multisite() ? __( 'Yes', 'ai-visibility-manager' ) : __( 'No', 'ai-visibility-manager' ),
	'permalink_style' => get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : __( 'Plain (Default)', 'ai-visibility-manager' ),
);
?>

<div class="aivm-card">
	<h3 class="aivm-card-title">
		<span class="dashicons dashicons-info-outline"></span>
		<?php esc_html_e( 'System Status & Diagnostics', 'ai-visibility-manager' ); ?>
	</h3>
	
	<p style="margin-bottom: 20px; color: var(--aivm-text-muted); line-height: 1.5;">
		<?php esc_html_e( 'The information below summarizes your server environment and active WordPress setup. This detail is helpful when troubleshooting crawl rules or requesting plugin support.', 'ai-visibility-manager' ); ?>
	</p>
	
	<table class="aivm-table aivm-status-table" style="border: 1px solid var(--aivm-border);">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Environment Parameter', 'ai-visibility-manager' ); ?></th>
				<th><?php esc_html_e( 'Active Value', 'ai-visibility-manager' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><strong><?php esc_html_e( 'AI Visibility Manager Version', 'ai-visibility-manager' ); ?></strong></td>
				<td><code><?php echo esc_html( $sys_info['plugin_version'] ); ?></code></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'WordPress Version', 'ai-visibility-manager' ); ?></strong></td>
				<td><code><?php echo esc_html( $sys_info['wp_version'] ); ?></code></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'PHP Version', 'ai-visibility-manager' ); ?></strong></td>
				<td><code><?php echo esc_html( $sys_info['php_version'] ); ?></code></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'MySQL/MariaDB Version', 'ai-visibility-manager' ); ?></strong></td>
				<td><code><?php echo esc_html( $sys_info['mysql_version'] ); ?></code></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'PHP Memory Limit', 'ai-visibility-manager' ); ?></strong></td>
				<td><code><?php echo esc_html( $sys_info['memory_limit'] ); ?></code></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Max Execution Time', 'ai-visibility-manager' ); ?></strong></td>
				<td><code><?php echo esc_html( $sys_info['execution_time'] ); ?></code></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'WordPress Multisite', 'ai-visibility-manager' ); ?></strong></td>
				<td><?php echo esc_html( $sys_info['multisite'] ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Permalink Structure', 'ai-visibility-manager' ); ?></strong></td>
				<td><code><?php echo esc_html( $sys_info['permalink_style'] ); ?></code></td>
			</tr>
		</tbody>
	</table>
</div>
