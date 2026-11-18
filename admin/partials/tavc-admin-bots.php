<?php
/**
 * View for the AI Crawlers tab
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Tavc
 * @subpackage Tavc/admin/partials
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

// Fetch currently blocked bots option
$tavc_blocked_bots    = TAVC_Bots::get_blocked_settings();
$tavc_bots_definition = TAVC_Bots::get_definitions();
$tavc_bot_categories  = TAVC_Bots::get_categories();
$tavc_bot_presets     = TAVC_Bots::get_presets();

// Crawler activity (last 30 days)
$tavc_tracking_settings = TAVC_Settings::get_tracking_settings();
$tavc_activity_since    = wp_date( 'Y-m-d', time() - ( 29 * DAY_IN_SECONDS ) );
$tavc_crawler_summary   = TAVC_DB::get_crawler_summary( $tavc_activity_since );
$tavc_crawler_paths     = TAVC_DB::get_crawler_top_paths( $tavc_activity_since, 10 );

// Custom rules, content signals and the resulting robots.txt block
$tavc_custom_rules_text = TAVC_Settings::custom_robots_rules_to_text( TAVC_Settings::get_custom_robots_rules() );
$tavc_content_signals   = TAVC_Settings::get_content_signals();
$tavc_signal_defs       = TAVC_Settings::get_content_signal_definitions();
$tavc_bot_manager       = new TAVC_Bot_Manager();
$tavc_robots_preview    = trim( $tavc_bot_manager->build_rules() );
$tavc_physical_robots   = file_exists( ABSPATH . 'robots.txt' );

// Which quick-setup option matches the saved switches ("custom" when none does).
$tavc_current_preset = 'custom';
foreach ( $tavc_bot_presets as $tavc_preset_key => $tavc_preset ) {
	$tavc_matches = true;
	foreach ( $tavc_bots_definition as $tavc_slug => $tavc_bot ) {
		$tavc_should_block = in_array( $tavc_bot['category'], $tavc_preset['block'], true ) ? 1 : 0;
		if ( $tavc_blocked_bots[ $tavc_slug ] !== $tavc_should_block ) {
			$tavc_matches = false;
			break;
		}
	}
	if ( $tavc_matches ) {
		$tavc_current_preset = $tavc_preset_key;
		break;
	}
}

// Invalid custom rules from the last save, shown next to the editor.
$tavc_rule_errors = get_settings_errors( 'tavc_custom_robots_rules' );
?>

<div class="tavc-card">
	<h2 class="tavc-card-title">
		<span aria-hidden="true" class="dashicons dashicons-buddicons-buddypress-logo"></span>
		<?php esc_html_e( 'Who Can Read Your Site', 'tbsh-ai-visibility-control' ); ?>
	</h2>

	<p class="tavc-intro">
		<?php esc_html_e( 'Choose which AI crawlers may read your content. Your choices are published as rules in your site\'s robots.txt file when you save.', 'tbsh-ai-visibility-control' ); ?>
	</p>

	<form method="post" action="options.php" class="tavc-crawler-form">
		<?php
		settings_fields( 'tavc_bots_settings_group' );
		?>

		<?php // The preset radios are a shortcut for the switches below; options.php ignores this field. ?>
		<fieldset class="tavc-preset-group">
			<legend class="tavc-field-label"><?php esc_html_e( 'Quick setup', 'tbsh-ai-visibility-control' ); ?></legend>
			<div class="tavc-preset-options">
				<?php foreach ( $tavc_bot_presets as $tavc_preset_key => $tavc_preset ) : ?>
					<label class="tavc-preset-option">
						<input type="radio" name="tavc_crawler_preset" value="<?php echo esc_attr( $tavc_preset_key ); ?>" data-block="<?php echo esc_attr( implode( ',', $tavc_preset['block'] ) ); ?>" <?php checked( $tavc_current_preset, $tavc_preset_key ); ?>>
						<?php echo esc_html( $tavc_preset['label'] ); ?>
					</label>
				<?php endforeach; ?>
				<label class="tavc-preset-option">
					<input type="radio" name="tavc_crawler_preset" value="custom" data-custom="1" <?php checked( $tavc_current_preset, 'custom' ); ?>>
					<?php esc_html_e( 'Custom (choose below)', 'tbsh-ai-visibility-control' ); ?>
				</label>
			</div>
			<p class="tavc-preset-status" id="tavc-preset-status" role="status" aria-live="polite" data-message="<?php esc_attr_e( 'Switches updated. Click "Save Crawler Settings" to publish the change to robots.txt.', 'tbsh-ai-visibility-control' ); ?>"></p>
		</fieldset>

		<?php foreach ( $tavc_bot_categories as $tavc_category_key => $tavc_category ) : ?>
			<?php
			$tavc_category_bots = array();
			foreach ( $tavc_bots_definition as $tavc_slug => $tavc_bot ) {
				if ( $tavc_bot['category'] === $tavc_category_key ) {
					$tavc_category_bots[ $tavc_slug ] = $tavc_bot;
				}
			}
			if ( empty( $tavc_category_bots ) ) {
				continue;
			}
			$tavc_blocked_count = 0;
			foreach ( array_keys( $tavc_category_bots ) as $tavc_slug ) {
				$tavc_blocked_count += $tavc_blocked_bots[ $tavc_slug ];
			}
			?>
			<fieldset class="tavc-bot-group" data-tavc-group>
				<legend class="tavc-bot-group-title">
					<?php echo esc_html( $tavc_category['label'] ); ?>
					<span class="tavc-bot-group-count" data-tavc-group-count data-total="<?php echo esc_attr( count( $tavc_category_bots ) ); ?>">
						<?php
						printf(
							/* translators: 1: number of blocked crawlers, 2: number of crawlers in the group. */
							esc_html__( '%1$s of %2$s blocked', 'tbsh-ai-visibility-control' ),
							esc_html( number_format_i18n( $tavc_blocked_count ) ),
							esc_html( number_format_i18n( count( $tavc_category_bots ) ) )
						);
						?>
					</span>
				</legend>
				<p class="tavc-bot-group-desc"><?php echo esc_html( $tavc_category['desc'] ); ?></p>

				<div class="tavc-bot-list">
					<?php foreach ( $tavc_category_bots as $tavc_slug => $tavc_bot ) : ?>
						<?php
						$tavc_input_id = 'tavc_bot_' . $tavc_slug;
						$tavc_desc_id  = 'tavc_bot_desc_' . $tavc_slug;
						$tavc_activity = isset( $tavc_crawler_summary[ $tavc_slug ] ) ? $tavc_crawler_summary[ $tavc_slug ] : null;
						?>
						<div class="tavc-bot-row">
							<div class="tavc-bot-info">
								<div>
									<div class="tavc-bot-name">
										<label for="<?php echo esc_attr( $tavc_input_id ); ?>">
											<span class="screen-reader-text"><?php esc_html_e( 'Block', 'tbsh-ai-visibility-control' ); ?> </span><?php echo esc_html( $tavc_bot['name'] ); ?>
										</label>
										<?php if ( '' !== $tavc_bot['vendor'] ) : ?>
											<span class="tavc-bot-vendor"><?php echo esc_html( $tavc_bot['vendor'] ); ?></span>
										<?php endif; ?>
									</div>
									<div class="tavc-bot-desc" id="<?php echo esc_attr( $tavc_desc_id ); ?>"><?php echo esc_html( $tavc_bot['desc'] ); ?></div>
									<?php if ( '' !== $tavc_bot['ua'] && $tavc_tracking_settings['crawler_logging'] ) : ?>
										<div class="tavc-bot-activity">
											<?php
											if ( $tavc_activity ) {
												printf(
													/* translators: 1: relative time such as "2 hours", 2: number of requests. */
													esc_html__( 'Last seen %1$s ago · %2$s requests in the last 30 days', 'tbsh-ai-visibility-control' ),
													esc_html( human_time_diff( mysql2date( 'U', $tavc_activity->last_seen ), time() ) ),
													esc_html( number_format_i18n( $tavc_activity->hits ) )
												);
											} else {
												esc_html_e( 'Not seen in the last 30 days', 'tbsh-ai-visibility-control' );
											}
											?>
										</div>
									<?php endif; ?>
								</div>
							</div>
							<div class="tavc-bot-toggle">
								<!-- Store option value. Use hidden field to handle unchecked states cleanly -->
								<input type="hidden" name="tavc_blocked_bots[<?php echo esc_attr( $tavc_slug ); ?>]" value="0">
								<input type="checkbox" name="tavc_blocked_bots[<?php echo esc_attr( $tavc_slug ); ?>]" id="<?php echo esc_attr( $tavc_input_id ); ?>" value="1" data-category="<?php echo esc_attr( $tavc_bot['category'] ); ?>" aria-describedby="<?php echo esc_attr( $tavc_desc_id ); ?>" <?php checked( $tavc_blocked_bots[ $tavc_slug ], 1 ); ?>>
								<?php // Visible state; the checkbox itself already announces "checked" to screen readers. ?>
								<span class="tavc-toggle-state<?php echo $tavc_blocked_bots[ $tavc_slug ] ? ' is-blocked' : ''; ?>" aria-hidden="true"><?php echo $tavc_blocked_bots[ $tavc_slug ] ? esc_html__( 'Blocked', 'tbsh-ai-visibility-control' ) : esc_html__( 'Allowed', 'tbsh-ai-visibility-control' ); ?></span>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</fieldset>
		<?php endforeach; ?>

		<div class="tavc-actions">
			<?php submit_button( __( 'Save Crawler Settings', 'tbsh-ai-visibility-control' ), 'primary', 'tavc_submit_bots', false ); ?>
		</div>
	</form>
</div>

<div class="tavc-card">
	<h2 class="tavc-card-title">
		<span aria-hidden="true" class="dashicons dashicons-visibility"></span>
		<?php esc_html_e( 'AI Crawler Activity (Last 30 Days)', 'tbsh-ai-visibility-control' ); ?>
	</h2>

	<?php if ( ! $tavc_tracking_settings['crawler_logging'] ) : ?>
		<p class="tavc-muted-text">
			<?php esc_html_e( 'AI crawler logging is turned off. Turn on "Log AI crawler visits" on the Settings & Tools tab to see which AI crawlers visit your site.', 'tbsh-ai-visibility-control' ); ?>
		</p>
	<?php elseif ( empty( $tavc_crawler_summary ) ) : ?>
		<div class="tavc-empty-state">
			<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
			<h3><?php esc_html_e( 'No AI crawler visits recorded yet', 'tbsh-ai-visibility-control' ); ?></h3>
			<p><?php esc_html_e( 'Visits from GPTBot, ClaudeBot, PerplexityBot and other AI crawlers will appear here. If your site uses a page cache or CDN, cached visits are not seen by WordPress and will not be counted.', 'tbsh-ai-visibility-control' ); ?></p>
		</div>
	<?php else : ?>
		<div class="tavc-two-column-grid">
			<div class="tavc-panel">
				<h3 class="tavc-panel-title"><?php esc_html_e( 'Requests by Crawler', 'tbsh-ai-visibility-control' ); ?></h3>
				<div class="tavc-table-scroll">
				<table class="tavc-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Crawler', 'tbsh-ai-visibility-control' ); ?></th>
							<th scope="col" class="tavc-num"><?php esc_html_e( 'Requests', 'tbsh-ai-visibility-control' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Last Seen', 'tbsh-ai-visibility-control' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $tavc_crawler_summary as $tavc_slug => $tavc_activity ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( isset( $tavc_bots_definition[ $tavc_slug ] ) ? $tavc_bots_definition[ $tavc_slug ]['name'] : $tavc_slug ); ?></strong>
									<?php if ( ! empty( $tavc_blocked_bots[ $tavc_slug ] ) ) : ?>
										<span class="tavc-status-tag status-red"><?php esc_html_e( 'Blocked', 'tbsh-ai-visibility-control' ); ?></span>
									<?php endif; ?>
								</td>
								<td class="tavc-num"><?php echo esc_html( number_format_i18n( $tavc_activity->hits ) ); ?></td>
								<td class="tavc-muted-text">
									<?php
									printf(
										/* translators: %s: relative time such as "2 hours". */
										esc_html__( '%s ago', 'tbsh-ai-visibility-control' ),
										esc_html( human_time_diff( mysql2date( 'U', $tavc_activity->last_seen ), time() ) )
									);
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				</div>
			</div>

			<div class="tavc-panel">
				<h3 class="tavc-panel-title"><?php esc_html_e( 'Most Crawled Paths', 'tbsh-ai-visibility-control' ); ?></h3>
				<div class="tavc-table-scroll">
				<table class="tavc-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Path', 'tbsh-ai-visibility-control' ); ?></th>
							<th scope="col" class="tavc-num"><?php esc_html_e( 'Requests', 'tbsh-ai-visibility-control' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $tavc_crawler_paths as $tavc_path_row ) : ?>
							<tr>
								<td class="tavc-truncate" title="<?php echo esc_attr( $tavc_path_row->url_path ); ?>"><code><?php echo esc_html( $tavc_path_row->url_path ); ?></code></td>
								<td class="tavc-num"><?php echo esc_html( number_format_i18n( $tavc_path_row->hits ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				</div>
			</div>
		</div>
		<p class="tavc-muted-text tavc-small-text">
			<?php esc_html_e( 'Crawlers are identified by their User-Agent, which can be imitated. Requests served from a page cache or CDN are not counted.', 'tbsh-ai-visibility-control' ); ?>
		</p>
	<?php endif; ?>
</div>

<?php
// Advanced options stay collapsed until they are used (or need attention).
$tavc_advanced_open = '' !== $tavc_custom_rules_text || '' !== implode( '', $tavc_content_signals ) || ! empty( $tavc_rule_errors );
?>
<details class="tavc-advanced"<?php echo $tavc_advanced_open ? ' open' : ''; ?>>
	<summary class="tavc-advanced-summary">
		<span class="tavc-advanced-title"><?php esc_html_e( 'Advanced', 'tbsh-ai-visibility-control' ); ?></span>
		<span class="tavc-advanced-hint"><?php esc_html_e( 'Custom robots.txt rules, Content Signals and a preview of the published rules', 'tbsh-ai-visibility-control' ); ?></span>
	</summary>

<div class="tavc-card">
	<h2 class="tavc-card-title">
		<span aria-hidden="true" class="dashicons dashicons-editor-code"></span>
		<?php esc_html_e( 'Custom robots.txt Rules', 'tbsh-ai-visibility-control' ); ?>
	</h2>

	<form method="post" action="options.php">
		<?php settings_fields( 'tavc_custom_robots_settings_group' ); ?>

		<label for="tavc_custom_robots_rules" class="tavc-field-label"><?php esc_html_e( 'Additional rules', 'tbsh-ai-visibility-control' ); ?></label>
		<p class="tavc-field-help" id="tavc_custom_robots_rules_help">
			<?php esc_html_e( 'Block crawlers that are not listed above, or block a crawler from part of your site only. Write one "User-agent:" line followed by one or more "Disallow:" lines. Paths must start with "/". Lines starting with # are ignored.', 'tbsh-ai-visibility-control' ); ?>
			<br>
			<strong><?php esc_html_e( 'Careful:', 'tbsh-ai-visibility-control' ); ?></strong>
			<?php esc_html_e( '"User-agent: *" applies to every crawler, including Google and Bing.', 'tbsh-ai-visibility-control' ); ?>
		</p>
		<textarea name="tavc_custom_robots_rules" id="tavc_custom_robots_rules" class="tavc-code-textarea" rows="8" spellcheck="false" aria-describedby="tavc_custom_robots_rules_help<?php echo $tavc_rule_errors ? ' tavc_custom_robots_rules_error' : ''; ?>" placeholder="<?php echo esc_attr( "User-agent: ExampleBot\nDisallow: /private/\nDisallow: /members/" ); ?>"><?php echo esc_textarea( $tavc_custom_rules_text ); ?></textarea>
		<?php if ( $tavc_rule_errors ) : ?>
			<p class="tavc-notice-inline tavc-notice-warning tavc-field-error" id="tavc_custom_robots_rules_error">
				<?php echo esc_html( $tavc_rule_errors[0]['message'] ); ?>
			</p>
		<?php endif; ?>

		<div class="tavc-actions">
			<?php submit_button( __( 'Save Custom Rules', 'tbsh-ai-visibility-control' ), 'secondary', 'tavc_submit_custom_rules', false ); ?>
		</div>
	</form>
</div>

<div class="tavc-card">
	<h2 class="tavc-card-title">
		<span aria-hidden="true" class="dashicons dashicons-flag"></span>
		<?php esc_html_e( 'Content Signals', 'tbsh-ai-visibility-control' ); ?>
	</h2>

	<p class="tavc-intro">
		<?php esc_html_e( 'State how you allow your content to be used, with a Content-Signal line in robots.txt. This is a newer, voluntary convention: some crawlers respect it, others ignore it, and search tools may report it as an unknown line. It does not block anything on its own.', 'tbsh-ai-visibility-control' ); ?>
	</p>

	<form method="post" action="options.php">
		<?php settings_fields( 'tavc_content_signals_settings_group' ); ?>

		<div class="tavc-field-grid">
			<?php foreach ( $tavc_signal_defs as $tavc_signal_key => $tavc_signal ) : ?>
				<?php $tavc_signal_id = 'tavc_signal_' . $tavc_signal_key; ?>
				<div class="tavc-field">
					<label for="<?php echo esc_attr( $tavc_signal_id ); ?>" class="tavc-field-label"><?php echo esc_html( $tavc_signal['label'] ); ?> <code><?php echo esc_html( $tavc_signal['token'] ); ?></code></label>
					<select name="tavc_content_signals[<?php echo esc_attr( $tavc_signal_key ); ?>]" id="<?php echo esc_attr( $tavc_signal_id ); ?>" aria-describedby="<?php echo esc_attr( $tavc_signal_id . '_help' ); ?>">
						<option value="" <?php selected( $tavc_content_signals[ $tavc_signal_key ], '' ); ?>><?php esc_html_e( 'No preference', 'tbsh-ai-visibility-control' ); ?></option>
						<option value="yes" <?php selected( $tavc_content_signals[ $tavc_signal_key ], 'yes' ); ?>><?php esc_html_e( 'Allowed (yes)', 'tbsh-ai-visibility-control' ); ?></option>
						<option value="no" <?php selected( $tavc_content_signals[ $tavc_signal_key ], 'no' ); ?>><?php esc_html_e( 'Not allowed (no)', 'tbsh-ai-visibility-control' ); ?></option>
					</select>
					<p class="tavc-field-help" id="<?php echo esc_attr( $tavc_signal_id . '_help' ); ?>"><?php echo esc_html( $tavc_signal['desc'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="tavc-actions">
			<?php submit_button( __( 'Save Content Signals', 'tbsh-ai-visibility-control' ), 'secondary', 'tavc_submit_content_signals', false ); ?>
		</div>
	</form>
</div>

<div class="tavc-card">
	<h2 class="tavc-card-title">
		<span aria-hidden="true" class="dashicons dashicons-media-text"></span>
		<?php esc_html_e( 'Rules Added to robots.txt', 'tbsh-ai-visibility-control' ); ?>
		<a href="<?php echo esc_url( home_url( '/robots.txt' ) ); ?>" target="_blank" rel="noopener noreferrer" class="tavc-title-link">
			<?php esc_html_e( 'View robots.txt', 'tbsh-ai-visibility-control' ); ?>
			<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'tbsh-ai-visibility-control' ); ?></span>
			<span class="dashicons dashicons-external" aria-hidden="true"></span>
		</a>
	</h2>

	<?php if ( $tavc_physical_robots ) : ?>
		<p class="tavc-notice-inline tavc-notice-warning">
			<?php esc_html_e( 'A physical robots.txt file exists in your WordPress folder. Web servers serve that file directly, so the rules below are not published until it is removed.', 'tbsh-ai-visibility-control' ); ?>
		</p>
	<?php endif; ?>

	<?php if ( '' === $tavc_robots_preview ) : ?>
		<p class="tavc-muted-text"><?php esc_html_e( 'No rules are added at the moment. Block a crawler, add a custom rule or set a content signal to add rules.', 'tbsh-ai-visibility-control' ); ?></p>
	<?php else : ?>
		<div class="tavc-preview-box" tabindex="0" aria-label="<?php esc_attr_e( 'Rules added to robots.txt', 'tbsh-ai-visibility-control' ); ?>"><?php echo esc_html( $tavc_robots_preview ); ?></div>
	<?php endif; ?>
</div>
</details>
