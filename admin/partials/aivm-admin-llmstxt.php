<?php
/**
 * View for llms.txt tab
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Aivm
 * @subpackage Aivm/admin/partials
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

$aivm_llms_txt_url = home_url( '/llms.txt' );

// Check if cached
$aivm_cache_val = get_transient( 'aivm_llms_txt_cache' );
$aivm_is_cached = ( false !== $aivm_cache_val );

// If not cached, generate live preview
if ( ! $aivm_is_cached ) {
	$aivm_llms_txt_class = new AIVM_LLMS_Txt();
	// We will call the public generator method
	$aivm_preview_content = $aivm_llms_txt_class->generate_llms_txt();
	$aivm_status_label    = __( 'Not Cached (Will cache on next visit)', 'ai-visibility-manager' );
	$aivm_status_class    = 'status-yellow';
} else {
	$aivm_preview_content = $aivm_cache_val;
	$aivm_status_label    = __( 'Cached (Active)', 'ai-visibility-manager' );
	$aivm_status_class    = 'status-green';
}

$aivm_file_size = strlen( $aivm_preview_content );
?>

<div class="aivm-card">
	<h3 class="aivm-card-title">
		<span class="dashicons dashicons-media-text"></span>
		<?php esc_html_e( 'llms.txt Generator & Settings', 'ai-visibility-manager' ); ?>
	</h3>
	
	<p style="margin-bottom: 20px; color: var(--aivm-text-muted); line-height: 1.5;">
		<?php esc_html_e( 'The llms.txt file is a new web standard used to provide structured, lightweight, markdown-formatted content specifically optimized for Large Language Models (LLMs) and AI search agents.', 'ai-visibility-manager' ); ?>
	</p>
	
	<!-- File Status Board -->
	<table class="aivm-table" style="margin-bottom: 25px; border: 1px solid var(--aivm-border);">
		<thead>
			<tr>
				<th colspan="2"><?php esc_html_e( 'Endpoint Configurations', 'ai-visibility-manager' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td style="width: 30%;"><strong><?php esc_html_e( 'Endpoint URL', 'ai-visibility-manager' ); ?></strong></td>
				<td>
					<a href="<?php echo esc_url( $aivm_llms_txt_url ); ?>" target="_blank" rel="noopener noreferrer" style="font-family: monospace; font-size: 13px;">
						<?php echo esc_url( $aivm_llms_txt_url ); ?> 
						<span class="dashicons dashicons-external" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span>
					</a>
				</td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Cache Status', 'ai-visibility-manager' ); ?></strong></td>
				<td>
					<span class="aivm-status-tag <?php echo esc_attr( $aivm_status_class ); ?>">
						<?php echo esc_html( $aivm_status_label ); ?>
					</span>
				</td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'File Size', 'ai-visibility-manager' ); ?></strong></td>
				<td>
					<?php echo esc_html( size_format( $aivm_file_size ) ); ?> (<?php echo esc_html( number_format_i18n( $aivm_file_size ) ); ?> <?php esc_html_e( 'bytes', 'ai-visibility-manager' ); ?>)
				</td>
			</tr>
		</tbody>
	</table>

	<!-- Preview Section -->
	<h4 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 600; display: flex; align-items: center; justify-content: space-between;">
		<span><?php esc_html_e( 'File Content Preview (Markdown)', 'ai-visibility-manager' ); ?></span>
		<span style="font-size: 11px; font-weight: normal; color: var(--aivm-text-muted);">
			<?php esc_html_e( 'Lists latest 100 public posts/pages.', 'ai-visibility-manager' ); ?>
		</span>
	</h4>
	
	<div class="aivm-preview-box"><?php echo esc_html( $aivm_preview_content ); ?></div>
	
	<div style="margin-top: 20px; display: flex; gap: 10px;">
		<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=aivm_tool_action&tool=rebuild_cache' ), 'aivm_tool_nonce' ) ); ?>" class="button button-secondary">
			<?php esc_html_e( 'Rebuild Cache', 'ai-visibility-manager' ); ?>
		</a>
	</div>
</div>
