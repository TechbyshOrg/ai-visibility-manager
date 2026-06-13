<?php
/**
 * View for llms.txt tab
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Tavc
 * @subpackage Tavc/admin/partials
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

$tavc_llms_txt_url = home_url( '/llms.txt' );

// Check if cached
$tavc_cache_val = get_transient( 'tavc_llms_txt_cache' );
$tavc_is_cached = ( false !== $tavc_cache_val );

// If not cached, generate live preview
if ( ! $tavc_is_cached ) {
	$tavc_llms_txt_class = new TAVC_LLMS_Txt();
	// We will call the public generator method
	$tavc_preview_content = $tavc_llms_txt_class->generate_llms_txt();
	$tavc_status_label    = __( 'Not Cached (Will cache on next visit)', 'tbsh-ai-visibility-control' );
	$tavc_status_class    = 'status-yellow';
} else {
	$tavc_preview_content = $tavc_cache_val;
	$tavc_status_label    = __( 'Cached (Active)', 'tbsh-ai-visibility-control' );
	$tavc_status_class    = 'status-green';
}

$tavc_file_size = strlen( $tavc_preview_content );
?>

<div class="tavc-card">
	<h3 class="tavc-card-title">
		<span class="dashicons dashicons-media-text"></span>
		<?php esc_html_e( 'llms.txt Generator & Settings', 'tbsh-ai-visibility-control' ); ?>
	</h3>
	
	<p style="margin-bottom: 20px; color: var(--tavc-text-muted); line-height: 1.5;">
		<?php esc_html_e( 'The llms.txt file is a new web standard used to provide structured, lightweight, markdown-formatted content specifically optimized for Large Language Models (LLMs) and AI search agents.', 'tbsh-ai-visibility-control' ); ?>
	</p>
	
	<!-- File Status Board -->
	<table class="tavc-table" style="margin-bottom: 25px; border: 1px solid var(--tavc-border);">
		<thead>
			<tr>
				<th colspan="2"><?php esc_html_e( 'Endpoint Configurations', 'tbsh-ai-visibility-control' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td style="width: 30%;"><strong><?php esc_html_e( 'Endpoint URL', 'tbsh-ai-visibility-control' ); ?></strong></td>
				<td>
					<a href="<?php echo esc_url( $tavc_llms_txt_url ); ?>" target="_blank" rel="noopener noreferrer" style="font-family: monospace; font-size: 13px;">
						<?php echo esc_url( $tavc_llms_txt_url ); ?> 
						<span class="dashicons dashicons-external" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span>
					</a>
				</td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Cache Status', 'tbsh-ai-visibility-control' ); ?></strong></td>
				<td>
					<span class="tavc-status-tag <?php echo esc_attr( $tavc_status_class ); ?>">
						<?php echo esc_html( $tavc_status_label ); ?>
					</span>
				</td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'File Size', 'tbsh-ai-visibility-control' ); ?></strong></td>
				<td>
					<?php echo esc_html( size_format( $tavc_file_size ) ); ?> (<?php echo esc_html( number_format_i18n( $tavc_file_size ) ); ?> <?php esc_html_e( 'bytes', 'tbsh-ai-visibility-control' ); ?>)
				</td>
			</tr>
		</tbody>
	</table>

	<!-- Preview Section -->
	<h4 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 600; display: flex; align-items: center; justify-content: space-between;">
		<span><?php esc_html_e( 'File Content Preview (Markdown)', 'tbsh-ai-visibility-control' ); ?></span>
		<span style="font-size: 11px; font-weight: normal; color: var(--tavc-text-muted);">
			<?php esc_html_e( 'Lists public posts/pages.', 'tbsh-ai-visibility-control' ); ?>
		</span>
	</h4>
	
	<div class="tavc-preview-box"><?php echo esc_html( $tavc_preview_content ); ?></div>
	
	<div style="margin-top: 20px; display: flex; gap: 10px;">
		<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=tavc_tool_action&tool=rebuild_cache' ), 'tavc_tool_nonce' ) ); ?>" class="button button-secondary">
			<?php esc_html_e( 'Rebuild Cache', 'tbsh-ai-visibility-control' ); ?>
		</a>
	</div>
</div>
