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
$tavc_subfolder_site    = TAVC_Admin::is_subfolder_install();

// WordPress does not answer /robots.txt in a sub-folder, but ?robots=1 still shows the generated rules.
$tavc_robots_view_url = $tavc_subfolder_site ? add_query_arg( 'robots', '1', home_url( '/' ) ) : home_url( '/robots.txt' );

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

// Server-side enforcement (1.4.0).
$tavc_enforcement   = TAVC_Settings::get_enforcement_settings();
$tavc_page_cache_on = defined( 'WP_CACHE' ) && WP_CACHE;
$tavc_blocked_users = array();
foreach ( $tavc_bots_definition as $tavc_slug => $tavc_bot ) {
	if ( TAVC_Bots::CATEGORY_USER === $tavc_bot['category'] && ! empty( $tavc_blocked_bots[ $tavc_slug ] ) ) {
		$tavc_blocked_users[] = $tavc_bot['name'];
	}
}
$tavc_any_refused = false;
foreach ( $tavc_crawler_summary as $tavc_activity ) {
	if ( ! empty( $tavc_activity->blocked ) || ! empty( $tavc_activity->limited ) ) {
		$tavc_any_refused = true;
		break;
	}
}
?>

<div class="tavc-card">
	<h2 class="tavc-card-title">
		<span aria-hidden="true" class="dashicons dashicons-buddicons-buddypress-logo"></span>
		<?php esc_html_e( 'Who Can Read Your Site', 'tbsh-ai-visibility-control' ); ?>
	</h2>

	<p class="tavc-intro">
		<?php esc_html_e( 'Choose which AI crawlers may read your content. Your choices are published as rules in your site\'s robots.txt file when you save.', 'tbsh-ai-visibility-control' ); ?>
	</p>

	<?php
	$tavc_llms_publish = TAVC_Settings::get_llms_settings();
	if ( $tavc_llms_publish['enabled'] && count( $tavc_blocked_bots ) > 0 && ! in_array( 0, array_map( 'intval', $tavc_blocked_bots ), true ) ) :
		?>
		<p class="tavc-notice-inline tavc-notice-warning">
			<?php esc_html_e( 'Every AI crawler is blocked, but your llms.txt file still publishes a list of your content. If you do not want that, turn off "Publish llms.txt" on the llms.txt tab.', 'tbsh-ai-visibility-control' ); ?>
			<a href="<?php echo esc_url( TAVC_Admin::get_page_url( array( 'tab' => 'llmstxt' ) ) ); ?>"><?php esc_html_e( 'Open llms.txt settings', 'tbsh-ai-visibility-control' ); ?></a>
		</p>
	<?php endif; ?>

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
		<span aria-hidden="true" class="dashicons dashicons-shield"></span>
		<?php esc_html_e( 'Enforce Your Choices', 'tbsh-ai-visibility-control' ); ?>
	</h2>

	<p class="tavc-intro">
		<?php esc_html_e( 'robots.txt only asks crawlers to stay away, and some ignore it. Enforcement makes your site itself turn blocked crawlers away.', 'tbsh-ai-visibility-control' ); ?>
	</p>

	<form method="post" action="options.php">
		<?php settings_fields( 'tavc_enforcement_settings_group' ); ?>

		<fieldset class="tavc-field">
			<legend class="tavc-field-label"><?php esc_html_e( 'Blocked crawlers', 'tbsh-ai-visibility-control' ); ?></legend>
			<label class="tavc-checkbox-row">
				<input type="hidden" name="tavc_enforcement_settings[enforce_blocks]" value="0">
				<input type="checkbox" name="tavc_enforcement_settings[enforce_blocks]" value="1" aria-describedby="tavc_enforce_help" <?php checked( $tavc_enforcement['enforce_blocks'], 1 ); ?>>
				<?php esc_html_e( 'Refuse requests from blocked crawlers', 'tbsh-ai-visibility-control' ); ?>
			</label>
			<p class="tavc-field-help" id="tavc_enforce_help">
				<?php esc_html_e( 'Crawlers you block above get a short "403 Forbidden" response instead of your pages. They can still read robots.txt and llms.txt, so they can see your rules. Crawlers are recognised by their User-Agent.', 'tbsh-ai-visibility-control' ); ?>
			</p>
		</fieldset>

		<fieldset class="tavc-field">
			<legend class="tavc-field-label"><?php esc_html_e( 'Crawl rate', 'tbsh-ai-visibility-control' ); ?></legend>
			<label class="tavc-checkbox-row">
				<input type="hidden" name="tavc_enforcement_settings[rate_limit]" value="0">
				<input type="checkbox" name="tavc_enforcement_settings[rate_limit]" value="1" aria-describedby="tavc_rate_limit_help" <?php checked( $tavc_enforcement['rate_limit'], 1 ); ?>>
				<?php esc_html_e( 'Limit how fast allowed AI crawlers can request pages', 'tbsh-ai-visibility-control' ); ?>
			</label>
			<label for="tavc_rate_limit_per_minute" class="tavc-field-sublabel"><?php esc_html_e( 'Maximum per crawler', 'tbsh-ai-visibility-control' ); ?></label>
			<select name="tavc_enforcement_settings[rate_limit_per_minute]" id="tavc_rate_limit_per_minute" aria-describedby="tavc_rate_limit_help">
				<?php foreach ( TAVC_Settings::get_rate_limit_choices() as $tavc_limit => $tavc_label ) : ?>
					<option value="<?php echo esc_attr( $tavc_limit ); ?>" <?php selected( $tavc_enforcement['rate_limit_per_minute'], $tavc_limit ); ?>><?php echo esc_html( $tavc_label ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="tavc-field-help" id="tavc_rate_limit_help">
				<?php esc_html_e( 'Requests above the limit get a "429 Too Many Requests" response asking the crawler to slow down; well-behaved crawlers retry later. Each AI training and search crawler is counted separately for each network it connects from, so a fake crawler cannot use up the real crawler\x27s limit. User-requested fetchers such as ChatGPT-User are never limited, because a person is waiting for the answer.', 'tbsh-ai-visibility-control' ); ?>
			</p>
		</fieldset>

		<?php if ( $tavc_page_cache_on ) : ?>
			<p class="tavc-notice-inline tavc-notice-warning">
				<?php esc_html_e( 'A page cache is active on this site. Pages served from the cache (or from a CDN) never reach WordPress, so those requests cannot be refused or counted. To block crawlers completely, also add the web server rules below, or a User-Agent rule in your cache plugin or CDN.', 'tbsh-ai-visibility-control' ); ?>
			</p>
		<?php endif; ?>

		<?php
		$tavc_apache_rules = TAVC_Bot_Manager::get_server_rules( 'apache' );
		$tavc_nginx_rules  = TAVC_Bot_Manager::get_server_rules( 'nginx' );
		?>
		<?php if ( '' !== $tavc_apache_rules ) : ?>
			<details class="tavc-details"<?php echo $tavc_page_cache_on ? ' open' : ''; ?>>
				<summary><?php esc_html_e( 'Web server rules for the blocked crawlers (optional)', 'tbsh-ai-visibility-control' ); ?></summary>
				<p class="tavc-field-help">
					<?php esc_html_e( 'These rules refuse the blocked crawlers in the web server itself, before WordPress or a page cache runs, so they also work for cached pages. The plugin never edits server files: copy the rules for your server, and update them when you change the crawlers above. Crawlers that are only a robots.txt name (Google-Extended, Applebot-Extended) are not included. Like every user-agent check, this cannot stop a crawler that pretends to be a normal browser.', 'tbsh-ai-visibility-control' ); ?>
				</p>
				<h4 class="tavc-field-sublabel" id="tavc-apache-rules-label"><?php esc_html_e( 'Apache (.htaccess, above the WordPress rules)', 'tbsh-ai-visibility-control' ); ?></h4>
				<pre class="tavc-preview-box" role="region" tabindex="0" aria-labelledby="tavc-apache-rules-label"><?php echo esc_html( $tavc_apache_rules ); ?></pre>
				<h4 class="tavc-field-sublabel" id="tavc-nginx-rules-label"><?php esc_html_e( 'nginx (inside the server block)', 'tbsh-ai-visibility-control' ); ?></h4>
				<pre class="tavc-preview-box" role="region" tabindex="0" aria-labelledby="tavc-nginx-rules-label"><?php echo esc_html( $tavc_nginx_rules ); ?></pre>
			</details>
		<?php endif; ?>

		<?php if ( $tavc_enforcement['enforce_blocks'] && $tavc_blocked_users ) : ?>
			<p class="tavc-notice-inline tavc-notice-warning">
				<?php
				printf(
					/* translators: %s: comma-separated crawler names, e.g. ChatGPT-User. */
					esc_html__( 'You block these user-requested fetchers: %s. With enforcement on, AI assistants cannot open your pages even when a person asks them to.', 'tbsh-ai-visibility-control' ),
					esc_html( implode( ', ', $tavc_blocked_users ) )
				);
				?>
			</p>
		<?php endif; ?>

		<div class="tavc-actions">
			<?php submit_button( __( 'Save Enforcement Settings', 'tbsh-ai-visibility-control' ), 'secondary', 'tavc_submit_enforcement', false ); ?>
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
							<?php if ( $tavc_any_refused ) : ?>
								<th scope="col" class="tavc-num"><?php esc_html_e( 'Refused', 'tbsh-ai-visibility-control' ); ?></th>
							<?php endif; ?>
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
								<?php if ( $tavc_any_refused ) : ?>
									<td class="tavc-num"><?php echo esc_html( number_format_i18n( $tavc_activity->blocked + $tavc_activity->limited ) ); ?></td>
								<?php endif; ?>
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
								<td class="tavc-truncate" title="<?php echo esc_attr( $tavc_path_row->url_path ); ?>"><code><?php echo esc_html( TAVC_DB::get_path_label( $tavc_path_row->url_path ) ); ?></code></td>
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
			<?php if ( $tavc_any_refused ) : ?>
				<?php esc_html_e( '"Refused" counts requests turned away because the crawler is blocked (403) or over the crawl rate limit (429).', 'tbsh-ai-visibility-control' ); ?>
			<?php endif; ?>
		</p>
	<?php endif; ?>
</div>

<?php
// Advanced options stay collapsed until they are used (or need attention).
$tavc_advanced_open = '' !== $tavc_custom_rules_text || '' !== implode( '', $tavc_content_signals ) || ! empty( $tavc_rule_errors ) || $tavc_subfolder_site || $tavc_physical_robots;
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

<div class="tavc-card" id="tavc-robots-preview">
	<h2 class="tavc-card-title">
		<span aria-hidden="true" class="dashicons dashicons-media-text"></span>
		<?php esc_html_e( 'Rules Added to robots.txt', 'tbsh-ai-visibility-control' ); ?>
		<a href="<?php echo esc_url( $tavc_robots_view_url ); ?>" target="_blank" rel="noopener noreferrer" class="tavc-title-link">
			<?php $tavc_subfolder_site ? esc_html_e( 'View generated rules', 'tbsh-ai-visibility-control' ) : esc_html_e( 'View robots.txt', 'tbsh-ai-visibility-control' ); ?>
			<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'tbsh-ai-visibility-control' ); ?></span>
			<span class="dashicons dashicons-external" aria-hidden="true"></span>
		</a>
	</h2>

	<?php if ( $tavc_subfolder_site ) : ?>
		<p class="tavc-notice-inline tavc-notice-warning">
			<?php
			printf(
				/* translators: 1: site folder such as /blog/, 2: robots.txt URL at the domain root. */
				esc_html__( 'Your site runs in the %1$s folder. Crawlers only read robots.txt at %2$s, which WordPress cannot publish, so copy the rules below into that file (or ask your host to). Turning blocked crawlers away still works without it.', 'tbsh-ai-visibility-control' ),
				'<code>' . esc_html( trailingslashit( TAVC_Admin::get_site_folder() ) ) . '</code>',
				'<code>' . esc_html( TAVC_Admin::get_root_robots_url() ) . '</code>'
			);
			?>
		</p>
	<?php elseif ( $tavc_physical_robots ) : ?>
		<p class="tavc-notice-inline tavc-notice-warning">
			<?php esc_html_e( 'A physical robots.txt file exists in your WordPress folder. Web servers serve that file directly, so the rules below are not published until it is removed.', 'tbsh-ai-visibility-control' ); ?>
		</p>
	<?php endif; ?>

	<?php if ( '' === $tavc_robots_preview ) : ?>
		<p class="tavc-muted-text"><?php esc_html_e( 'No rules are added at the moment. Block a crawler, add a custom rule or set a content signal to add rules.', 'tbsh-ai-visibility-control' ); ?></p>
	<?php else : ?>
		<div class="tavc-preview-box" role="region" tabindex="0" aria-label="<?php esc_attr_e( 'Rules added to robots.txt', 'tbsh-ai-visibility-control' ); ?>"><?php echo esc_html( $tavc_robots_preview ); ?></div>
	<?php endif; ?>
</div>
</details>
