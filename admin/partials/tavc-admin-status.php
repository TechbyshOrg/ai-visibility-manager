<?php
/**
 * View for the Status tab
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
	<h2 class="tavc-card-title">
		<span aria-hidden="true" class="dashicons dashicons-heart"></span>
		<?php esc_html_e( 'AI Visibility Health Check', 'tbsh-ai-visibility-control' ); ?>
	</h2>
	<?php $this->render_health_section(); ?>
</div>

<div class="tavc-card">
	<h2 class="tavc-card-title">
		<span aria-hidden="true" class="dashicons dashicons-info-outline"></span>
		<?php esc_html_e( 'System Status & Diagnostics', 'tbsh-ai-visibility-control' ); ?>
	</h2>

	<p class="tavc-intro">
		<?php esc_html_e( 'The information below summarizes your server environment and active WordPress setup. This detail is helpful when troubleshooting crawl rules or requesting plugin support.', 'tbsh-ai-visibility-control' ); ?>
	</p>

	<div class="tavc-table-scroll">
		<table class="tavc-table tavc-status-table tavc-table-bordered">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Environment Parameter', 'tbsh-ai-visibility-control' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Active Value', 'tbsh-ai-visibility-control' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'AI Visibility Control Version', 'tbsh-ai-visibility-control' ); ?></th>
					<td><code><?php echo esc_html( $tavc_sys_info['plugin_version'] ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'WordPress Version', 'tbsh-ai-visibility-control' ); ?></th>
					<td><code><?php echo esc_html( $tavc_sys_info['wp_version'] ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'PHP Version', 'tbsh-ai-visibility-control' ); ?></th>
					<td><code><?php echo esc_html( $tavc_sys_info['php_version'] ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'MySQL/MariaDB Version', 'tbsh-ai-visibility-control' ); ?></th>
					<td><code><?php echo esc_html( $tavc_sys_info['mysql_version'] ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'PHP Memory Limit', 'tbsh-ai-visibility-control' ); ?></th>
					<td><code><?php echo esc_html( $tavc_sys_info['memory_limit'] ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Max Execution Time', 'tbsh-ai-visibility-control' ); ?></th>
					<td><code><?php echo esc_html( $tavc_sys_info['execution_time'] ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'WordPress Multisite', 'tbsh-ai-visibility-control' ); ?></th>
					<td><?php echo esc_html( $tavc_sys_info['multisite'] ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Permalink Structure', 'tbsh-ai-visibility-control' ); ?></th>
					<td><code><?php echo esc_html( $tavc_sys_info['permalink_style'] ); ?></code></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<?php require plugin_dir_path( __FILE__ ) . 'tavc-admin-help.php'; ?>
