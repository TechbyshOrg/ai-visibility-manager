<?php
/**
 * View for Tools tab
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Tavc
 * @subpackage Tavc/admin/partials
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;
?>

<div class="tavc-card">
	<h3 class="tavc-card-title">
		<span class="dashicons dashicons-admin-tools"></span>
		<?php esc_html_e( 'Plugin Maintenance Tools', 'tbsh-ai-visibility-control' ); ?>
	</h3>
	
	<p style="margin-bottom: 25px; color: var(--tavc-text-muted); line-height: 1.5;">
		<?php esc_html_e( 'Perform diagnostics, manage referral databases, flush caches, or reset options. All actions below require active administrator credentials.', 'tbsh-ai-visibility-control' ); ?>
	</p>
	
	<div class="tavc-tools-layout">
		<!-- Rebuild llms.txt Cache -->
		<div class="tavc-tool-card">
			<h4 style="margin: 0 0 10px 0; font-size: 15px; font-weight: 600;"><?php esc_html_e( 'Rebuild llms.txt Cache', 'tbsh-ai-visibility-control' ); ?></h4>
			<p><?php esc_html_e( 'Forces the dynamic generator to rebuild the public listing and save it in transients.', 'tbsh-ai-visibility-control' ); ?></p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=tavc_tool_action&tool=rebuild_cache' ), 'tavc_tool_nonce' ) ); ?>" class="button button-secondary" style="width: 100%;">
				<?php esc_html_e( 'Rebuild Cache', 'tbsh-ai-visibility-control' ); ?>
			</a>
		</div>

		<!-- Export Referral Logs CSV -->
		<div class="tavc-tool-card">
			<h4 style="margin: 0 0 10px 0; font-size: 15px; font-weight: 600;"><?php esc_html_e( 'Export Referrals (CSV)', 'tbsh-ai-visibility-control' ); ?></h4>
			<p><?php esc_html_e( 'Downloads a spreadsheet containing all logged visits, referrer engines, and URLs.', 'tbsh-ai-visibility-control' ); ?></p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=tavc_tool_action&tool=export_csv' ), 'tavc_tool_nonce' ) ); ?>" class="button button-secondary" style="width: 100%;">
				<?php esc_html_e( 'Download CSV', 'tbsh-ai-visibility-control' ); ?>
			</a>
		</div>

		<!-- Clear Referral Logs -->
		<div class="tavc-tool-card">
			<h4 style="margin: 0 0 10px 0; font-size: 15px; font-weight: 600;"><?php esc_html_e( 'Clear Referrals Logs', 'tbsh-ai-visibility-control' ); ?></h4>
			<p><?php esc_html_e( 'Deletes all referral data from the database. This action is irreversible.', 'tbsh-ai-visibility-control' ); ?></p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=tavc_tool_action&tool=clear_logs' ), 'tavc_tool_nonce' ) ); ?>" class="button button-link-delete" style="width: 100%; border: 1px solid var(--tavc-border); text-align: center; line-height: 26px; border-radius: 3px;" onclick="return confirm('<?php esc_attr_e( 'Are you absolutely sure you want to delete all logged referrals? This cannot be undone.', 'tbsh-ai-visibility-control' ); ?>');">
				<?php esc_html_e( 'Delete All Logs', 'tbsh-ai-visibility-control' ); ?>
			</a>
		</div>

		<!-- Reset Settings -->
		<div class="tavc-tool-card">
			<h4 style="margin: 0 0 10px 0; font-size: 15px; font-weight: 600;"><?php esc_html_e( 'Reset Plugin Settings', 'tbsh-ai-visibility-control' ); ?></h4>
			<p><?php esc_html_e( 'Restores all AI crawler bot blocking checkboxes to their default (unchecked) state.', 'tbsh-ai-visibility-control' ); ?></p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=tavc_tool_action&tool=reset_settings' ), 'tavc_tool_nonce' ) ); ?>" class="button button-link-delete" style="width: 100%; border: 1px solid var(--tavc-border); text-align: center; line-height: 26px; border-radius: 3px;" onclick="return confirm('<?php esc_attr_e( 'Reset all settings back to defaults?', 'tbsh-ai-visibility-control' ); ?>');">
				<?php esc_html_e( 'Reset Settings', 'tbsh-ai-visibility-control' ); ?>
			</a>
		</div>
	</div>
</div>

<div class="tavc-card" style="margin-top: 25px;">
	<h3 class="tavc-card-title">
		<span class="dashicons dashicons-trash" style="color: var(--tavc-primary);"></span>
		<?php esc_html_e( 'Uninstall Preferences', 'tbsh-ai-visibility-control' ); ?>
	</h3>
	
	<p style="margin-bottom: 20px; color: var(--tavc-text-muted); line-height: 1.5;">
		<?php esc_html_e( 'Configure how the plugin behaves when it is deleted from the WordPress Plugins page.', 'tbsh-ai-visibility-control' ); ?>
	</p>
	
	<form method="post" action="options.php">
		<?php
		settings_fields( 'tavc_uninstall_settings_group' );
		$tavc_delete_on_uninstall = get_option( 'tavc_delete_on_uninstall', 0 );
		?>
		
		<div style="background: var(--tavc-bg); padding: 16px; border-radius: 8px; border: 1px solid var(--tavc-border);">
			<label style="display: flex; align-items: center; gap: 8px; font-weight: 500; cursor: pointer;">
				<input type="hidden" name="tavc_delete_on_uninstall" value="0">
				<input type="checkbox" name="tavc_delete_on_uninstall" id="tavc_delete_on_uninstall" value="1" <?php checked( $tavc_delete_on_uninstall, 1 ); ?>>
				<?php esc_html_e( 'Completely Remove Data on Deletion', 'tbsh-ai-visibility-control' ); ?>
			</label>
			<div style="font-size: 13px; color: var(--tavc-text-muted); margin-top: 6px; padding-left: 24px; line-height: 1.4;">
				<?php esc_html_e( 'If checked, all referral traffic logs, saved options, and cache transients will be permanently deleted from your database when this plugin is deleted.', 'tbsh-ai-visibility-control' ); ?>
			</div>
		</div>
		
		<div style="margin-top: 20px; display: flex; justify-content: flex-start;">
			<?php submit_button( __( 'Save Uninstall Preferences', 'tbsh-ai-visibility-control' ), 'secondary', 'submit', false ); ?>
		</div>
	</form>
</div>
