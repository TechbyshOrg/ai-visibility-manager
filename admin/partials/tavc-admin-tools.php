<?php
/**
 * View for the Settings & Tools tab
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Tavc
 * @subpackage Tavc/admin/partials
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

$tavc_tracking            = TAVC_Settings::get_tracking_settings();
$tavc_retention_choices   = TAVC_Settings::get_retention_choices();
$tavc_delete_on_uninstall = get_option( 'tavc_delete_on_uninstall', 0 );

// Numbers used in the confirmation messages of the Danger Zone.
$tavc_referral_total = TAVC_DB::get_total_referrals();
$tavc_crawler_total  = TAVC_DB::get_crawler_hits_between( '1000-01-01', '9999-12-31' );
$tavc_blocked_total  = array_sum( TAVC_Bots::get_blocked_settings() );

$tavc_tool_url = function ( $tool ) {
	return wp_nonce_url( admin_url( 'admin-post.php?action=tavc_tool_action&tool=' . $tool ), 'tavc_tool_nonce' );
};
?>

<div class="tavc-card">
	<h2 class="tavc-card-title">
		<span aria-hidden="true" class="dashicons dashicons-database"></span>
		<?php esc_html_e( 'Tracking & Data Settings', 'tbsh-ai-visibility-control' ); ?>
	</h2>

	<form method="post" action="options.php">
		<?php settings_fields( 'tavc_tracking_settings_group' ); ?>

		<fieldset class="tavc-field">
			<legend class="tavc-field-label"><?php esc_html_e( 'AI traffic detection', 'tbsh-ai-visibility-control' ); ?></legend>

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
				<?php esc_html_e( 'Turn on if your site uses a page cache or CDN, which hides visits from WordPress. Adds a small script (under 2 KB) to your public pages that reports AI visits only. No cookies are used. While this is on, visits are recorded by the script instead of the server, so they are not counted twice.', 'tbsh-ai-visibility-control' ); ?>
			</p>
		</fieldset>

		<fieldset class="tavc-field">
			<legend class="tavc-field-label"><?php esc_html_e( 'AI crawlers', 'tbsh-ai-visibility-control' ); ?></legend>
			<label class="tavc-checkbox-row">
				<input type="hidden" name="tavc_tracking_settings[crawler_logging]" value="0">
				<input type="checkbox" name="tavc_tracking_settings[crawler_logging]" value="1" aria-describedby="tavc_crawler_help" <?php checked( $tavc_tracking['crawler_logging'], 1 ); ?>>
				<?php esc_html_e( 'Log AI crawler visits', 'tbsh-ai-visibility-control' ); ?>
			</label>
			<p class="tavc-field-help" id="tavc_crawler_help">
				<?php esc_html_e( 'Counts requests from the AI crawlers listed on the AI Crawlers tab, per day and page. No IP addresses are stored.', 'tbsh-ai-visibility-control' ); ?>
			</p>
		</fieldset>

		<fieldset class="tavc-field">
			<legend class="tavc-field-label"><?php esc_html_e( 'Keep data for', 'tbsh-ai-visibility-control' ); ?></legend>
			<div class="tavc-field-grid">
				<div>
					<label for="tavc_referral_retention" class="tavc-field-sublabel"><?php esc_html_e( 'AI traffic logs', 'tbsh-ai-visibility-control' ); ?></label>
					<select name="tavc_tracking_settings[referral_retention_days]" id="tavc_referral_retention">
						<?php foreach ( $tavc_retention_choices as $tavc_days => $tavc_label ) : ?>
							<option value="<?php echo esc_attr( $tavc_days ); ?>" <?php selected( $tavc_tracking['referral_retention_days'], $tavc_days ); ?>><?php echo esc_html( $tavc_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label for="tavc_crawler_retention" class="tavc-field-sublabel"><?php esc_html_e( 'AI crawler logs', 'tbsh-ai-visibility-control' ); ?></label>
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
			<legend class="tavc-field-label"><?php esc_html_e( 'Summary email', 'tbsh-ai-visibility-control' ); ?></legend>
			<label class="tavc-checkbox-row">
				<input type="hidden" name="tavc_tracking_settings[digest_enabled]" value="0">
				<input type="checkbox" name="tavc_tracking_settings[digest_enabled]" value="1" aria-describedby="tavc_digest_help" <?php checked( $tavc_tracking['digest_enabled'], 1 ); ?>>
				<?php esc_html_e( 'Email a summary of AI traffic and crawler visits', 'tbsh-ai-visibility-control' ); ?>
			</label>
			<label for="tavc_digest_frequency" class="tavc-field-sublabel"><?php esc_html_e( 'How often', 'tbsh-ai-visibility-control' ); ?></label>
			<select name="tavc_tracking_settings[digest_frequency]" id="tavc_digest_frequency" aria-describedby="tavc_digest_help">
				<?php foreach ( TAVC_Settings::get_digest_frequency_choices() as $tavc_frequency => $tavc_label ) : ?>
					<option value="<?php echo esc_attr( $tavc_frequency ); ?>" <?php selected( $tavc_tracking['digest_frequency'], $tavc_frequency ); ?>><?php echo esc_html( $tavc_label ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="tavc-field-help" id="tavc_digest_help"><?php esc_html_e( 'Sent at 8 AM site time. The weekly summary covers the last 7 days; the monthly summary covers the previous calendar month.', 'tbsh-ai-visibility-control' ); ?></p>
			<label for="tavc_digest_email" class="tavc-field-sublabel"><?php esc_html_e( 'Send to', 'tbsh-ai-visibility-control' ); ?></label>
			<input type="email" name="tavc_tracking_settings[digest_email]" id="tavc_digest_email" class="regular-text" autocomplete="email" value="<?php echo esc_attr( $tavc_tracking['digest_email'] ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" aria-describedby="tavc_digest_email_help">
			<p class="tavc-field-help" id="tavc_digest_email_help"><?php esc_html_e( 'Leave empty to use the site admin email.', 'tbsh-ai-visibility-control' ); ?></p>
		</fieldset>

		<div class="tavc-actions">
			<?php submit_button( __( 'Save Tracking Settings', 'tbsh-ai-visibility-control' ), 'primary', 'tavc_submit_tracking', false ); ?>
		</div>
	</form>
</div>

<div class="tavc-card">
	<h2 class="tavc-card-title">
		<span aria-hidden="true" class="dashicons dashicons-admin-tools"></span>
		<?php esc_html_e( 'Maintenance', 'tbsh-ai-visibility-control' ); ?>
	</h2>

	<div class="tavc-tools-layout">
		<div class="tavc-tool-card">
			<h3><?php esc_html_e( 'Rebuild llms.txt', 'tbsh-ai-visibility-control' ); ?></h3>
			<p><?php esc_html_e( 'Creates a fresh copy of your llms.txt file now and refreshes its web address.', 'tbsh-ai-visibility-control' ); ?></p>
			<a href="<?php echo esc_url( $tavc_tool_url( 'rebuild_cache' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Rebuild llms.txt', 'tbsh-ai-visibility-control' ); ?></a>
		</div>

		<div class="tavc-tool-card">
			<h3><?php esc_html_e( 'Export AI Traffic (CSV)', 'tbsh-ai-visibility-control' ); ?></h3>
			<p><?php esc_html_e( 'Downloads a spreadsheet of every logged AI visit: date, AI platform, page visited and how it was detected.', 'tbsh-ai-visibility-control' ); ?></p>
			<a href="<?php echo esc_url( $tavc_tool_url( 'export_csv' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Download CSV', 'tbsh-ai-visibility-control' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(AI traffic)', 'tbsh-ai-visibility-control' ); ?></span></a>
		</div>

		<div class="tavc-tool-card">
			<h3><?php esc_html_e( 'Export AI Crawler Visits (CSV)', 'tbsh-ai-visibility-control' ); ?></h3>
			<p><?php esc_html_e( 'Downloads a spreadsheet of logged AI crawler requests per day: crawler, path, number of requests and how many were refused.', 'tbsh-ai-visibility-control' ); ?></p>
			<a href="<?php echo esc_url( $tavc_tool_url( 'export_crawler_csv' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Download CSV', 'tbsh-ai-visibility-control' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(AI crawler visits)', 'tbsh-ai-visibility-control' ); ?></span></a>
		</div>

		<div class="tavc-tool-card">
			<h3><?php esc_html_e( 'Send Test Summary', 'tbsh-ai-visibility-control' ); ?></h3>
			<p>
				<?php
				printf(
					/* translators: %s: email address. */
					esc_html__( 'Sends the AI visibility summary to %s now, using the frequency chosen above.', 'tbsh-ai-visibility-control' ),
					esc_html( TAVC_Settings::get_digest_recipient() )
				);
				?>
			</p>
			<a href="<?php echo esc_url( $tavc_tool_url( 'send_digest' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Send Test Email', 'tbsh-ai-visibility-control' ); ?></a>
		</div>
	</div>
</div>

<div class="tavc-card tavc-danger-zone">
	<h2 class="tavc-card-title">
		<span aria-hidden="true" class="dashicons dashicons-warning"></span>
		<?php esc_html_e( 'Danger Zone', 'tbsh-ai-visibility-control' ); ?>
	</h2>
	<p class="tavc-intro"><?php esc_html_e( 'These actions cannot be undone. Export your data first if you may need it later.', 'tbsh-ai-visibility-control' ); ?></p>

	<div class="tavc-tools-layout">
		<div class="tavc-tool-card">
			<h3><?php esc_html_e( 'Delete AI Traffic Logs', 'tbsh-ai-visibility-control' ); ?></h3>
			<p><?php esc_html_e( 'Permanently deletes every logged AI visit.', 'tbsh-ai-visibility-control' ); ?></p>
			<?php
			$tavc_confirm = sprintf(
				/* translators: %s: number of logged visits. */
				_n( 'Delete %s logged AI visit? This cannot be undone.', 'Delete all %s logged AI visits? This cannot be undone.', $tavc_referral_total, 'tbsh-ai-visibility-control' ),
				number_format_i18n( $tavc_referral_total )
			);
			?>
			<?php if ( $tavc_referral_total > 0 ) : ?>
				<a href="<?php echo esc_url( $tavc_tool_url( 'clear_logs' ) ); ?>" class="button tavc-button-danger" data-tavc-confirm="<?php echo esc_attr( $tavc_confirm ); ?>"><?php esc_html_e( 'Delete AI Traffic Logs', 'tbsh-ai-visibility-control' ); ?></a>
			<?php else : ?>
				<button type="button" class="button" disabled aria-describedby="tavc_no_referrals"><?php esc_html_e( 'Delete AI Traffic Logs', 'tbsh-ai-visibility-control' ); ?></button>
				<span class="tavc-disabled-reason" id="tavc_no_referrals"><?php esc_html_e( 'Nothing to delete yet.', 'tbsh-ai-visibility-control' ); ?></span>
			<?php endif; ?>
		</div>

		<div class="tavc-tool-card">
			<h3><?php esc_html_e( 'Delete Crawler Logs', 'tbsh-ai-visibility-control' ); ?></h3>
			<p><?php esc_html_e( 'Permanently deletes all recorded AI crawler visits.', 'tbsh-ai-visibility-control' ); ?></p>
			<?php
			$tavc_confirm = sprintf(
				/* translators: %s: number of recorded crawler requests. */
				_n( 'Delete %s recorded crawler request? This cannot be undone.', 'Delete all %s recorded crawler requests? This cannot be undone.', $tavc_crawler_total, 'tbsh-ai-visibility-control' ),
				number_format_i18n( $tavc_crawler_total )
			);
			?>
			<?php if ( $tavc_crawler_total > 0 ) : ?>
				<a href="<?php echo esc_url( $tavc_tool_url( 'clear_crawler_logs' ) ); ?>" class="button tavc-button-danger" data-tavc-confirm="<?php echo esc_attr( $tavc_confirm ); ?>"><?php esc_html_e( 'Delete Crawler Logs', 'tbsh-ai-visibility-control' ); ?></a>
			<?php else : ?>
				<button type="button" class="button" disabled aria-describedby="tavc_no_crawler_logs"><?php esc_html_e( 'Delete Crawler Logs', 'tbsh-ai-visibility-control' ); ?></button>
				<span class="tavc-disabled-reason" id="tavc_no_crawler_logs"><?php esc_html_e( 'Nothing to delete yet.', 'tbsh-ai-visibility-control' ); ?></span>
			<?php endif; ?>
		</div>

		<div class="tavc-tool-card">
			<h3><?php esc_html_e( 'Reset Crawler Blocks', 'tbsh-ai-visibility-control' ); ?></h3>
			<p><?php esc_html_e( 'Allows all AI crawlers again. Other settings are not changed.', 'tbsh-ai-visibility-control' ); ?></p>
			<?php
			$tavc_confirm = sprintf(
				/* translators: %s: number of blocked crawlers. */
				_n( 'Allow %s blocked crawler again? Your other settings are not changed.', 'Allow all %s blocked crawlers again? Your other settings are not changed.', $tavc_blocked_total, 'tbsh-ai-visibility-control' ),
				number_format_i18n( $tavc_blocked_total )
			);
			?>
			<?php if ( $tavc_blocked_total > 0 ) : ?>
				<a href="<?php echo esc_url( $tavc_tool_url( 'reset_settings' ) ); ?>" class="button tavc-button-danger" data-tavc-confirm="<?php echo esc_attr( $tavc_confirm ); ?>"><?php esc_html_e( 'Reset Crawler Blocks', 'tbsh-ai-visibility-control' ); ?></a>
			<?php else : ?>
				<button type="button" class="button" disabled aria-describedby="tavc_no_blocks"><?php esc_html_e( 'Reset Crawler Blocks', 'tbsh-ai-visibility-control' ); ?></button>
				<span class="tavc-disabled-reason" id="tavc_no_blocks"><?php esc_html_e( 'All AI crawlers are already allowed.', 'tbsh-ai-visibility-control' ); ?></span>
			<?php endif; ?>
		</div>
	</div>
</div>

<div class="tavc-card">
	<h2 class="tavc-card-title">
		<span aria-hidden="true" class="dashicons dashicons-trash"></span>
		<?php esc_html_e( 'When the Plugin Is Deleted', 'tbsh-ai-visibility-control' ); ?>
	</h2>

	<form method="post" action="options.php">
		<?php settings_fields( 'tavc_uninstall_settings_group' ); ?>

		<div class="tavc-option-box">
			<label class="tavc-checkbox-row" for="tavc_delete_on_uninstall">
				<input type="hidden" name="tavc_delete_on_uninstall" value="0">
				<input type="checkbox" name="tavc_delete_on_uninstall" id="tavc_delete_on_uninstall" value="1" aria-describedby="tavc_delete_on_uninstall_help" <?php checked( $tavc_delete_on_uninstall, 1 ); ?>>
				<?php esc_html_e( 'Completely Remove Data on Deletion', 'tbsh-ai-visibility-control' ); ?>
			</label>
			<p class="tavc-field-help tavc-indent" id="tavc_delete_on_uninstall_help">
				<?php esc_html_e( 'If checked, all AI traffic and crawler logs, saved settings and cached files are permanently deleted from your database when this plugin is deleted from the Plugins page.', 'tbsh-ai-visibility-control' ); ?>
			</p>
		</div>

		<div class="tavc-actions">
			<?php submit_button( __( 'Save Uninstall Preferences', 'tbsh-ai-visibility-control' ), 'secondary', 'submit', false ); ?>
		</div>
	</form>
</div>
