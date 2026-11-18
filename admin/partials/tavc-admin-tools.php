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

$tavc_tracking          = TAVC_Settings::get_tracking_settings();
$tavc_retention_choices = TAVC_Settings::get_retention_choices();
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

		<!-- Clear Crawler Logs -->
		<div class="tavc-tool-card">
			<h4 style="margin: 0 0 10px 0; font-size: 15px; font-weight: 600;"><?php esc_html_e( 'Clear Crawler Logs', 'tbsh-ai-visibility-control' ); ?></h4>
			<p><?php esc_html_e( 'Deletes all recorded AI crawler visits. This action is irreversible.', 'tbsh-ai-visibility-control' ); ?></p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=tavc_tool_action&tool=clear_crawler_logs' ), 'tavc_tool_nonce' ) ); ?>" class="button button-link-delete" style="width: 100%; border: 1px solid var(--tavc-border); text-align: center; line-height: 26px; border-radius: 3px;" onclick="return confirm('<?php esc_attr_e( 'Delete all recorded AI crawler visits? This cannot be undone.', 'tbsh-ai-visibility-control' ); ?>');">
				<?php esc_html_e( 'Delete Crawler Logs', 'tbsh-ai-visibility-control' ); ?>
			</a>
		</div>

		<!-- Send Test Summary -->
		<div class="tavc-tool-card">
			<h4 style="margin: 0 0 10px 0; font-size: 15px; font-weight: 600;"><?php esc_html_e( 'Send Test Summary', 'tbsh-ai-visibility-control' ); ?></h4>
			<p>
				<?php
				printf(
					/* translators: %s: email address. */
					esc_html__( 'Sends the weekly AI visibility summary to %s now.', 'tbsh-ai-visibility-control' ),
					esc_html( TAVC_Settings::get_digest_recipient() )
				);
				?>
			</p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=tavc_tool_action&tool=send_digest' ), 'tavc_tool_nonce' ) ); ?>" class="button button-secondary" style="width: 100%;">
				<?php esc_html_e( 'Send Test Email', 'tbsh-ai-visibility-control' ); ?>
			</a>
		</div>
	</div>
</div>

<div class="tavc-card" style="margin-top: 25px;">
	<h3 class="tavc-card-title">
		<span class="dashicons dashicons-database" style="color: var(--tavc-primary);"></span>
		<?php esc_html_e( 'Tracking & Data Settings', 'tbsh-ai-visibility-control' ); ?>
	</h3>

	<form method="post" action="options.php">
		<?php settings_fields( 'tavc_tracking_settings_group' ); ?>

		<fieldset class="tavc-field">
			<legend class="tavc-field-label"><?php esc_html_e( 'AI referral detection', 'tbsh-ai-visibility-control' ); ?></legend>

			<label class="tavc-checkbox-row">
				<input type="hidden" name="tavc_tracking_settings[utm_detection]" value="0">
				<input type="checkbox" name="tavc_tracking_settings[utm_detection]" value="1" aria-describedby="tavc_utm_help" <?php checked( $tavc_tracking['utm_detection'], 1 ); ?>>
				<?php esc_html_e( 'Detect AI visits from UTM tags', 'tbsh-ai-visibility-control' ); ?>
			</label>
			<p class="tavc-field-help" id="tavc_utm_help">
				<?php esc_html_e( 'Many AI assistants hide the referrer but add a tag such as utm_source=chatgpt.com to their links. Turn this on to count those visits too.', 'tbsh-ai-visibility-control' ); ?>
			</p>

			<label class="tavc-checkbox-row">
				<input type="hidden" name="tavc_tracking_settings[beacon_tracking]" value="0">
				<input type="checkbox" name="tavc_tracking_settings[beacon_tracking]" value="1" aria-describedby="tavc_beacon_help" <?php checked( $tavc_tracking['beacon_tracking'], 1 ); ?>>
				<?php esc_html_e( 'Cache-compatible referral tracking', 'tbsh-ai-visibility-control' ); ?>
			</label>
			<p class="tavc-field-help" id="tavc_beacon_help">
				<?php esc_html_e( 'Turn on if your site uses a page cache or CDN, which hides visits from WordPress. Adds a small script (under 1 KB) to your public pages that reports AI visits only. No cookies are used. While this is on, visits are recorded by the script instead of the server, so they are not counted twice.', 'tbsh-ai-visibility-control' ); ?>
			</p>
		</fieldset>

		<fieldset class="tavc-field">
			<legend class="tavc-field-label"><?php esc_html_e( 'AI crawler logging', 'tbsh-ai-visibility-control' ); ?></legend>
			<label class="tavc-checkbox-row">
				<input type="hidden" name="tavc_tracking_settings[crawler_logging]" value="0">
				<input type="checkbox" name="tavc_tracking_settings[crawler_logging]" value="1" aria-describedby="tavc_crawler_help" <?php checked( $tavc_tracking['crawler_logging'], 1 ); ?>>
				<?php esc_html_e( 'Log AI crawler visits', 'tbsh-ai-visibility-control' ); ?>
			</label>
			<p class="tavc-field-help" id="tavc_crawler_help">
				<?php esc_html_e( 'Counts requests from the AI crawlers listed on the AI Bots tab, per day and page. No IP addresses are stored.', 'tbsh-ai-visibility-control' ); ?>
			</p>
		</fieldset>

		<fieldset class="tavc-field">
			<legend class="tavc-field-label"><?php esc_html_e( 'Data retention', 'tbsh-ai-visibility-control' ); ?></legend>
			<div class="tavc-field-grid">
				<div>
					<label for="tavc_referral_retention" class="tavc-field-sublabel"><?php esc_html_e( 'Keep referral logs for', 'tbsh-ai-visibility-control' ); ?></label>
					<select name="tavc_tracking_settings[referral_retention_days]" id="tavc_referral_retention">
						<?php foreach ( $tavc_retention_choices as $tavc_days => $tavc_label ) : ?>
							<option value="<?php echo esc_attr( $tavc_days ); ?>" <?php selected( $tavc_tracking['referral_retention_days'], $tavc_days ); ?>><?php echo esc_html( $tavc_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label for="tavc_crawler_retention" class="tavc-field-sublabel"><?php esc_html_e( 'Keep crawler logs for', 'tbsh-ai-visibility-control' ); ?></label>
					<select name="tavc_tracking_settings[crawler_retention_days]" id="tavc_crawler_retention">
						<?php foreach ( $tavc_retention_choices as $tavc_days => $tavc_label ) : ?>
							<option value="<?php echo esc_attr( $tavc_days ); ?>" <?php selected( $tavc_tracking['crawler_retention_days'], $tavc_days ); ?>><?php echo esc_html( $tavc_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<p class="tavc-field-help"><?php esc_html_e( 'Older entries are deleted automatically once a day.', 'tbsh-ai-visibility-control' ); ?></p>
		</fieldset>

		<fieldset class="tavc-field">
			<legend class="tavc-field-label"><?php esc_html_e( 'Weekly summary email', 'tbsh-ai-visibility-control' ); ?></legend>
			<label class="tavc-checkbox-row">
				<input type="hidden" name="tavc_tracking_settings[digest_enabled]" value="0">
				<input type="checkbox" name="tavc_tracking_settings[digest_enabled]" value="1" aria-describedby="tavc_digest_help" <?php checked( $tavc_tracking['digest_enabled'], 1 ); ?>>
				<?php esc_html_e( 'Email a weekly summary of AI referrals and crawler visits', 'tbsh-ai-visibility-control' ); ?>
			</label>
			<p class="tavc-field-help" id="tavc_digest_help"><?php esc_html_e( 'Sent every Monday morning (site time).', 'tbsh-ai-visibility-control' ); ?></p>
			<label for="tavc_digest_email" class="tavc-field-sublabel"><?php esc_html_e( 'Send to', 'tbsh-ai-visibility-control' ); ?></label>
			<input type="email" name="tavc_tracking_settings[digest_email]" id="tavc_digest_email" class="regular-text" value="<?php echo esc_attr( $tavc_tracking['digest_email'] ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" aria-describedby="tavc_digest_email_help">
			<p class="tavc-field-help" id="tavc_digest_email_help"><?php esc_html_e( 'Leave empty to use the site admin email.', 'tbsh-ai-visibility-control' ); ?></p>
		</fieldset>

		<div style="margin-top: 20px; display: flex; justify-content: flex-start;">
			<?php submit_button( __( 'Save Tracking Settings', 'tbsh-ai-visibility-control' ), 'primary', 'tavc_submit_tracking', false ); ?>
		</div>
	</form>
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
