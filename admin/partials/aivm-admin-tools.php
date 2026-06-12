<?php
/**
 * View for Tools tab
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Aivm
 * @subpackage Aivm/admin/partials
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;
?>

<div class="aivm-card">
	<h3 class="aivm-card-title">
		<span class="dashicons dashicons-admin-tools"></span>
		<?php esc_html_e( 'Plugin Maintenance Tools', 'ai-visibility-manager' ); ?>
	</h3>
	
	<p style="margin-bottom: 25px; color: var(--aivm-text-muted); line-height: 1.5;">
		<?php esc_html_e( 'Perform diagnostics, manage referral databases, flush caches, or reset options. All actions below require active administrator credentials.', 'ai-visibility-manager' ); ?>
	</p>
	
	<div class="aivm-tools-layout">
		<!-- Rebuild llms.txt Cache -->
		<div class="aivm-tool-card">
			<h4 style="margin: 0 0 10px 0; font-size: 15px; font-weight: 600;"><?php esc_html_e( 'Rebuild llms.txt Cache', 'ai-visibility-manager' ); ?></h4>
			<p><?php esc_html_e( 'Forces the dynamic generator to rebuild the public listing and save it in transients.', 'ai-visibility-manager' ); ?></p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=aivm_tool_action&tool=rebuild_cache' ), 'aivm_tool_nonce' ) ); ?>" class="button button-secondary" style="width: 100%;">
				<?php esc_html_e( 'Rebuild Cache', 'ai-visibility-manager' ); ?>
			</a>
		</div>

		<!-- Export Referral Logs CSV -->
		<div class="aivm-tool-card">
			<h4 style="margin: 0 0 10px 0; font-size: 15px; font-weight: 600;"><?php esc_html_e( 'Export Referrals (CSV)', 'ai-visibility-manager' ); ?></h4>
			<p><?php esc_html_e( 'Downloads a spreadsheet containing all logged visits, referrer engines, and URLs.', 'ai-visibility-manager' ); ?></p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=aivm_tool_action&tool=export_csv' ), 'aivm_tool_nonce' ) ); ?>" class="button button-secondary" style="width: 100%;">
				<?php esc_html_e( 'Download CSV', 'ai-visibility-manager' ); ?>
			</a>
		</div>

		<!-- Clear Referral Logs -->
		<div class="aivm-tool-card">
			<h4 style="margin: 0 0 10px 0; font-size: 15px; font-weight: 600;"><?php esc_html_e( 'Clear Referrals Logs', 'ai-visibility-manager' ); ?></h4>
			<p><?php esc_html_e( 'Deletes all referral data from the database. This action is irreversible.', 'ai-visibility-manager' ); ?></p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=aivm_tool_action&tool=clear_logs' ), 'aivm_tool_nonce' ) ); ?>" class="button button-link-delete" style="width: 100%; border: 1px solid var(--aivm-border); text-align: center; line-height: 26px; border-radius: 3px;" onclick="return confirm('<?php esc_attr_e( 'Are you absolutely sure you want to delete all logged referrals? This cannot be undone.', 'ai-visibility-manager' ); ?>');">
				<?php esc_html_e( 'Delete All Logs', 'ai-visibility-manager' ); ?>
			</a>
		</div>

		<!-- Reset Settings -->
		<div class="aivm-tool-card">
			<h4 style="margin: 0 0 10px 0; font-size: 15px; font-weight: 600;"><?php esc_html_e( 'Reset Plugin Settings', 'ai-visibility-manager' ); ?></h4>
			<p><?php esc_html_e( 'Restores all AI crawler bot blocking checkboxes to their default (unchecked) state.', 'ai-visibility-manager' ); ?></p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=aivm_tool_action&tool=reset_settings' ), 'aivm_tool_nonce' ) ); ?>" class="button button-link-delete" style="width: 100%; border: 1px solid var(--aivm-border); text-align: center; line-height: 26px; border-radius: 3px;" onclick="return confirm('<?php esc_attr_e( 'Reset all settings back to defaults?', 'ai-visibility-manager' ); ?>');">
				<?php esc_html_e( 'Reset Settings', 'ai-visibility-manager' ); ?>
			</a>
		</div>
	</div>
</div>

<div class="aivm-card" style="margin-top: 25px;">
	<h3 class="aivm-card-title">
		<span class="dashicons dashicons-trash" style="color: var(--aivm-primary);"></span>
		<?php esc_html_e( 'Uninstall Preferences', 'ai-visibility-manager' ); ?>
	</h3>
	
	<p style="margin-bottom: 20px; color: var(--aivm-text-muted); line-height: 1.5;">
		<?php esc_html_e( 'Configure how the plugin behaves when it is deleted from the WordPress Plugins page.', 'ai-visibility-manager' ); ?>
	</p>
	
	<form method="post" action="options.php">
		<?php
		settings_fields( 'aivm_settings_group' );
		$delete_on_uninstall = get_option( 'aivm_delete_on_uninstall', 0 );
		?>
		
		<div style="background: var(--aivm-bg); padding: 16px; border-radius: 8px; border: 1px solid var(--aivm-border);">
			<label style="display: flex; align-items: center; gap: 8px; font-weight: 500; cursor: pointer;">
				<input type="checkbox" name="aivm_delete_on_uninstall" id="aivm_delete_on_uninstall" value="1" <?php checked( $delete_on_uninstall, 1 ); ?>>
				<?php esc_html_e( 'Completely Remove Data on Deletion', 'ai-visibility-manager' ); ?>
			</label>
			<div style="font-size: 13px; color: var(--aivm-text-muted); margin-top: 6px; padding-left: 24px; line-height: 1.4;">
				<?php esc_html_e( 'If checked, all referral traffic logs, saved options, and cache transients will be permanently deleted from your database when this plugin is deleted.', 'ai-visibility-manager' ); ?>
			</div>
		</div>
		
		<div style="margin-top: 20px; display: flex; justify-content: flex-start;">
			<?php submit_button( __( 'Save Uninstall Preferences', 'ai-visibility-manager' ), 'secondary', 'submit', false ); ?>
		</div>
	</form>
</div>

