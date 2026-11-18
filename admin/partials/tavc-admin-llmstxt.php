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

$tavc_llms_txt_url      = home_url( '/llms.txt' );
$tavc_llms_full_txt_url = home_url( '/llms-full.txt' );
$tavc_llms_settings     = TAVC_Settings::get_llms_settings();
$tavc_llms_type_choices = TAVC_Settings::get_llms_post_type_choices();
$tavc_full_cache_val    = get_transient( TAVC_LLMS_Txt::FULL_CACHE_KEY );

// AI crawler fetches of both files in the last 30 days
$tavc_llms_fetch_since = wp_date( 'Y-m-d', time() - ( 29 * DAY_IN_SECONDS ) );
$tavc_llms_fetches     = TAVC_DB::get_crawler_hits_for_path( wp_parse_url( $tavc_llms_txt_url, PHP_URL_PATH ), $tavc_llms_fetch_since );
$tavc_full_fetches     = TAVC_DB::get_crawler_hits_for_path( wp_parse_url( $tavc_llms_full_txt_url, PHP_URL_PATH ), $tavc_llms_fetch_since );
$tavc_bot_names        = TAVC_Bots::get_definitions();

// Content excluded by hand from the editor
$tavc_llms_excluded = get_posts(
	array(
		'post_type'      => $tavc_llms_settings['post_types'],
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'no_found_rows'  => true,
		'meta_key'       => TAVC_LLMS_Txt::EXCLUDE_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
		'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_value
	)
);

// Check if cached
$tavc_cache_val = get_transient( TAVC_LLMS_Txt::CACHE_KEY );
$tavc_is_cached = ( false !== $tavc_cache_val );

// If not cached, generate live preview
if ( ! $tavc_is_cached ) {
	$tavc_llms_txt_class = new TAVC_LLMS_Txt();
	// We will call the public generator method
	$tavc_preview_content = $tavc_llms_txt_class->generate_llms_txt();
	$tavc_status_label    = __( 'Not saved yet (created on the next visit)', 'tbsh-ai-visibility-control' );
	$tavc_status_class    = 'status-yellow';
} else {
	$tavc_preview_content = $tavc_cache_val;
	$tavc_status_label    = __( 'Saved and served', 'tbsh-ai-visibility-control' );
	$tavc_status_class    = 'status-green';
}

$tavc_file_size = strlen( $tavc_preview_content );
?>

<div class="tavc-card">
	<h2 class="tavc-card-title">
		<span aria-hidden="true" class="dashicons dashicons-media-text"></span>
		<?php esc_html_e( 'llms.txt Generator & Settings', 'tbsh-ai-visibility-control' ); ?>
	</h2>
	
	<p class="tavc-intro">
		<?php esc_html_e( 'The llms.txt file is a new web standard used to provide structured, lightweight, markdown-formatted content specifically optimized for Large Language Models (LLMs) and AI search agents.', 'tbsh-ai-visibility-control' ); ?>
	</p>
	
	<!-- File Status Board -->
	<div class="tavc-table-scroll">
	<table class="tavc-table tavc-table-bordered">
		<thead>
			<tr>
				<th colspan="2"><?php esc_html_e( 'File Details', 'tbsh-ai-visibility-control' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="tavc-col-30"><strong><?php esc_html_e( 'Web Address', 'tbsh-ai-visibility-control' ); ?></strong></td>
				<td>
					<a href="<?php echo esc_url( $tavc_llms_txt_url ); ?>" target="_blank" rel="noopener noreferrer" class="tavc-mono-link">
						<?php echo esc_url( $tavc_llms_txt_url ); ?> 
						<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'tbsh-ai-visibility-control' ); ?></span>
						<span aria-hidden="true" class="dashicons dashicons-external tavc-icon-sm"></span>
					</a>
				</td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Saved Copy', 'tbsh-ai-visibility-control' ); ?></strong></td>
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
			<tr>
				<td><strong><?php esc_html_e( 'AI Crawler Fetches (30 days)', 'tbsh-ai-visibility-control' ); ?></strong></td>
				<td><?php echo esc_html( $this->format_fetch_summary( $tavc_llms_fetches, $tavc_bot_names ) ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'llms-full.txt', 'tbsh-ai-visibility-control' ); ?></strong></td>
				<td>
					<?php if ( $tavc_llms_settings['full_enabled'] ) : ?>
						<a href="<?php echo esc_url( $tavc_llms_full_txt_url ); ?>" target="_blank" rel="noopener noreferrer" class="tavc-mono-link">
							<?php echo esc_url( $tavc_llms_full_txt_url ); ?>
							<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'tbsh-ai-visibility-control' ); ?></span>
							<span class="dashicons dashicons-external tavc-icon-sm" aria-hidden="true"></span>
						</a>
						<br>
						<span class="tavc-muted-text tavc-small-text">
							<?php
							if ( false !== $tavc_full_cache_val ) {
								printf(
									/* translators: %s: file size. */
									esc_html__( 'Cached, %s.', 'tbsh-ai-visibility-control' ),
									esc_html( size_format( strlen( $tavc_full_cache_val ) ) )
								);
							} else {
								esc_html_e( 'Not cached yet. It is built on the next visit.', 'tbsh-ai-visibility-control' );
							}
							echo ' ';
							echo esc_html( $this->format_fetch_summary( $tavc_full_fetches, $tavc_bot_names ) );
							?>
						</span>
					<?php else : ?>
						<span class="tavc-status-tag status-yellow"><?php esc_html_e( 'Off', 'tbsh-ai-visibility-control' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		</tbody>
	</table>
	</div>

	<!-- Preview Section -->
	<h3 class="tavc-subheading">
		<span><?php esc_html_e( 'File Content Preview (Markdown)', 'tbsh-ai-visibility-control' ); ?></span>
		<span class="tavc-subheading-note">
			<?php esc_html_e( 'Lists public content of the selected types.', 'tbsh-ai-visibility-control' ); ?>
		</span>
	</h3>

	<div class="tavc-preview-box" tabindex="0" aria-label="<?php esc_attr_e( 'llms.txt preview', 'tbsh-ai-visibility-control' ); ?>"><?php echo esc_html( $tavc_preview_content ); ?></div>

	<div class="tavc-actions">
		<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=tavc_tool_action&tool=rebuild_cache' ), 'tavc_tool_nonce' ) ); ?>" class="button button-secondary">
			<?php esc_html_e( 'Rebuild llms.txt', 'tbsh-ai-visibility-control' ); ?>
		</a>
	</div>
</div>

<div class="tavc-card">
	<h2 class="tavc-card-title">
		<span aria-hidden="true" class="dashicons dashicons-admin-settings"></span>
		<?php esc_html_e( 'llms.txt Content Settings', 'tbsh-ai-visibility-control' ); ?>
	</h2>

	<form method="post" action="options.php">
		<?php settings_fields( 'tavc_llms_settings_group' ); ?>

		<fieldset class="tavc-field">
			<legend class="tavc-field-label"><?php esc_html_e( 'Content types to list', 'tbsh-ai-visibility-control' ); ?></legend>
			<div class="tavc-checkbox-list">
				<?php foreach ( $tavc_llms_type_choices as $tavc_type_slug => $tavc_type_object ) : ?>
					<label class="tavc-checkbox-row">
						<input type="checkbox" name="tavc_llms_txt_settings[post_types][]" value="<?php echo esc_attr( $tavc_type_slug ); ?>" <?php checked( in_array( $tavc_type_slug, $tavc_llms_settings['post_types'], true ) ); ?>>
						<?php echo esc_html( $tavc_type_object->labels->name ); ?>
						<code><?php echo esc_html( $tavc_type_slug ); ?></code>
					</label>
				<?php endforeach; ?>
			</div>
			<p class="tavc-field-help"><?php esc_html_e( 'Only published, non-password-protected content is listed.', 'tbsh-ai-visibility-control' ); ?></p>
		</fieldset>

		<div class="tavc-field">
			<label for="tavc_llms_intro" class="tavc-field-label"><?php esc_html_e( 'About text', 'tbsh-ai-visibility-control' ); ?></label>
			<textarea name="tavc_llms_txt_settings[intro]" id="tavc_llms_intro" rows="4" class="large-text" maxlength="2000" aria-describedby="tavc_llms_intro_help" placeholder="<?php echo esc_attr( get_bloginfo( 'description' ) ); ?>"><?php echo esc_textarea( $tavc_llms_settings['intro'] ); ?></textarea>
			<p class="tavc-field-help" id="tavc_llms_intro_help"><?php esc_html_e( 'A short description of your site and what it offers, shown under "About". Leave empty to use your site tagline.', 'tbsh-ai-visibility-control' ); ?></p>
		</div>

		<div class="tavc-field">
			<label class="tavc-checkbox-row">
				<input type="hidden" name="tavc_llms_txt_settings[include_excerpts]" value="0">
				<input type="checkbox" name="tavc_llms_txt_settings[include_excerpts]" value="1" aria-describedby="tavc_llms_excerpts_help" <?php checked( $tavc_llms_settings['include_excerpts'], 1 ); ?>>
				<?php esc_html_e( 'Add a short description after each link', 'tbsh-ai-visibility-control' ); ?>
			</label>
			<p class="tavc-field-help" id="tavc_llms_excerpts_help"><?php esc_html_e( 'Uses the excerpt, or the first 30 words of the content. Makes the file larger.', 'tbsh-ai-visibility-control' ); ?></p>
		</div>

		<div class="tavc-field">
			<label class="tavc-checkbox-row">
				<input type="hidden" name="tavc_llms_txt_settings[group_by_type]" value="0">
				<input type="checkbox" name="tavc_llms_txt_settings[group_by_type]" value="1" <?php checked( $tavc_llms_settings['group_by_type'], 1 ); ?>>
				<?php esc_html_e( 'Group links by content type (for example "Posts" and "Pages")', 'tbsh-ai-visibility-control' ); ?>
			</label>
		</div>

		<div class="tavc-field">
			<label for="tavc_llms_max_per_type" class="tavc-field-label"><?php esc_html_e( 'Maximum items per content type', 'tbsh-ai-visibility-control' ); ?></label>
			<input type="number" name="tavc_llms_txt_settings[max_per_type]" id="tavc_llms_max_per_type" class="small-text" min="0" max="5000" step="1" value="<?php echo esc_attr( $tavc_llms_settings['max_per_type'] ); ?>" aria-describedby="tavc_llms_max_help">
			<p class="tavc-field-help" id="tavc_llms_max_help"><?php esc_html_e( 'The newest items are kept. Use 0 for no limit.', 'tbsh-ai-visibility-control' ); ?></p>
		</div>

		<div class="tavc-field">
			<label class="tavc-checkbox-row">
				<input type="hidden" name="tavc_llms_txt_settings[exclude_noindex]" value="0">
				<input type="checkbox" name="tavc_llms_txt_settings[exclude_noindex]" value="1" aria-describedby="tavc_llms_noindex_help" <?php checked( $tavc_llms_settings['exclude_noindex'], 1 ); ?>>
				<?php esc_html_e( 'Leave out content marked "noindex" in your SEO plugin', 'tbsh-ai-visibility-control' ); ?>
			</label>
			<p class="tavc-field-help" id="tavc_llms_noindex_help"><?php esc_html_e( 'Supports Yoast SEO, Rank Math, All in One SEO, SEOPress and The SEO Framework (per-post settings).', 'tbsh-ai-visibility-control' ); ?></p>
		</div>

		<fieldset class="tavc-field tavc-subsection">
			<legend class="tavc-field-label"><?php esc_html_e( 'llms-full.txt', 'tbsh-ai-visibility-control' ); ?></legend>
			<label class="tavc-checkbox-row">
				<input type="hidden" name="tavc_llms_txt_settings[full_enabled]" value="0">
				<input type="checkbox" name="tavc_llms_txt_settings[full_enabled]" value="1" aria-describedby="tavc_llms_full_help" <?php checked( $tavc_llms_settings['full_enabled'], 1 ); ?>>
				<?php esc_html_e( 'Publish /llms-full.txt with the full text of your content', 'tbsh-ai-visibility-control' ); ?>
			</label>
			<p class="tavc-field-help" id="tavc_llms_full_help">
				<?php esc_html_e( 'Converts your newest content to Markdown in a single file that AI tools can read. If you use a membership or paywall plugin, check the file before turning this on: WordPress reports members-only content as public, so it would be included.', 'tbsh-ai-visibility-control' ); ?>
			</p>
			<label for="tavc_llms_full_max" class="tavc-field-label"><?php esc_html_e( 'Maximum items in llms-full.txt', 'tbsh-ai-visibility-control' ); ?></label>
			<input type="number" name="tavc_llms_txt_settings[full_max_items]" id="tavc_llms_full_max" class="small-text" min="1" max="500" step="1" value="<?php echo esc_attr( $tavc_llms_settings['full_max_items'] ); ?>">
		</fieldset>

		<fieldset class="tavc-field tavc-subsection">
			<legend class="tavc-field-label"><?php esc_html_e( 'Markdown versions of pages', 'tbsh-ai-visibility-control' ); ?></legend>
			<label class="tavc-checkbox-row">
				<input type="hidden" name="tavc_llms_txt_settings[markdown_pages]" value="0">
				<input type="checkbox" name="tavc_llms_txt_settings[markdown_pages]" value="1" aria-describedby="tavc_llms_markdown_help" <?php checked( $tavc_llms_settings['markdown_pages'], 1 ); ?>>
				<?php esc_html_e( 'Serve a Markdown version of each listed page', 'tbsh-ai-visibility-control' ); ?>
			</label>
			<p class="tavc-field-help" id="tavc_llms_markdown_help">
				<?php
				printf(
					/* translators: %s: example address such as https://example.com/about.md */
					esc_html__( 'Adds ".md" to the address of every page listed in llms.txt, for example %s, as the llms.txt proposal recommends. AI tools can read these clean text versions instead of the full web page. Each page also gets a link to its Markdown version in its HTML head. Content that llms.txt leaves out (other content types, excluded, noindex or password-protected) has no Markdown version.', 'tbsh-ai-visibility-control' ),
					'<code>' . esc_html( home_url( '/about.md' ) ) . '</code>'
				);
				?>
			</p>
			<label class="tavc-checkbox-row">
				<input type="hidden" name="tavc_llms_txt_settings[markdown_links]" value="0">
				<input type="checkbox" name="tavc_llms_txt_settings[markdown_links]" value="1" aria-describedby="tavc_llms_markdown_links_help" <?php checked( $tavc_llms_settings['markdown_links'], 1 ); ?>>
				<?php esc_html_e( 'Link to the Markdown versions in llms.txt', 'tbsh-ai-visibility-control' ); ?>
			</label>
			<p class="tavc-field-help" id="tavc_llms_markdown_links_help"><?php esc_html_e( 'Only used while Markdown versions are on. Otherwise llms.txt links to the normal pages.', 'tbsh-ai-visibility-control' ); ?></p>
		</fieldset>

		<div class="tavc-actions">
			<?php submit_button( __( 'Save llms.txt Settings', 'tbsh-ai-visibility-control' ), 'primary', 'tavc_submit_llms', false ); ?>
		</div>
	</form>
</div>

<div class="tavc-card">
	<h2 class="tavc-card-title">
		<span aria-hidden="true" class="dashicons dashicons-hidden"></span>
		<?php esc_html_e( 'Excluded Content', 'tbsh-ai-visibility-control' ); ?>
	</h2>

	<p class="tavc-intro">
		<?php esc_html_e( 'To leave a single post or page out of llms.txt, open it in the editor and tick "Exclude from llms.txt" in the AI Visibility box.', 'tbsh-ai-visibility-control' ); ?>
	</p>

	<?php if ( empty( $tavc_llms_excluded ) ) : ?>
		<p class="tavc-muted-text"><?php esc_html_e( 'No content is excluded by hand.', 'tbsh-ai-visibility-control' ); ?></p>
	<?php else : ?>
		<div class="tavc-table-scroll">
		<table class="tavc-table">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Title', 'tbsh-ai-visibility-control' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Type', 'tbsh-ai-visibility-control' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $tavc_llms_excluded as $tavc_excluded_post ) : ?>
					<?php $tavc_edit_link = get_edit_post_link( $tavc_excluded_post->ID ); ?>
					<tr>
						<td>
							<?php if ( $tavc_edit_link ) : ?>
								<a href="<?php echo esc_url( $tavc_edit_link ); ?>"><?php echo esc_html( get_the_title( $tavc_excluded_post ) ? get_the_title( $tavc_excluded_post ) : __( '(no title)', 'tbsh-ai-visibility-control' ) ); ?></a>
							<?php else : ?>
								<?php echo esc_html( get_the_title( $tavc_excluded_post ) ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( isset( $tavc_llms_type_choices[ $tavc_excluded_post->post_type ] ) ? $tavc_llms_type_choices[ $tavc_excluded_post->post_type ]->labels->singular_name : $tavc_excluded_post->post_type ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		</div>
	<?php endif; ?>
</div>
