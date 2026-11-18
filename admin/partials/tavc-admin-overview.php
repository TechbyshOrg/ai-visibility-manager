<?php
/**
 * View for Overview tab
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Tavc
 * @subpackage Tavc/admin/partials
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

// AI visits in the last 30 days, compared with the 30 days before.
$tavc_today        = new DateTimeImmutable( 'today', wp_timezone() );
$tavc_period_start = $tavc_today->modify( '-29 days' );
$tavc_prev_start   = $tavc_period_start->modify( '-30 days' );
$tavc_visits_30    = TAVC_DB::get_referral_count_between( $tavc_period_start->format( 'Y-m-d H:i:s' ), $tavc_today->modify( '+1 day' )->format( 'Y-m-d H:i:s' ) );
$tavc_visits_prev  = TAVC_DB::get_referral_count_between( $tavc_prev_start->format( 'Y-m-d H:i:s' ), $tavc_period_start->format( 'Y-m-d H:i:s' ) );
$tavc_total_visits = TAVC_DB::get_total_referrals();
$tavc_last_visit   = TAVC_DB::get_last_referral();

if ( $tavc_visits_prev > 0 ) {
	$tavc_change      = round( ( ( $tavc_visits_30 - $tavc_visits_prev ) / $tavc_visits_prev ) * 100 );
	$tavc_change_text = sprintf(
		/* translators: %s: signed percentage such as +12%. */
		__( '%s vs. previous 30 days', 'tbsh-ai-visibility-control' ),
		( $tavc_change > 0 ? '+' : '' ) . number_format_i18n( $tavc_change ) . '%'
	);
} else {
	$tavc_change_text = sprintf(
		/* translators: %s: total number of logged visits. */
		__( '%s in total', 'tbsh-ai-visibility-control' ),
		number_format_i18n( $tavc_total_visits )
	);
}

// Crawler blocks.
$tavc_blocked       = TAVC_Bots::get_blocked_settings();
$tavc_blocked_count = array_sum( $tavc_blocked );
$tavc_bot_count     = count( $tavc_blocked );

// "Get started" steps.
$tavc_custom_rules     = TAVC_Settings::get_custom_robots_rules();
$tavc_signals          = TAVC_Settings::get_content_signals();
$tavc_progress         = TAVC_Admin::get_get_started_progress();
// A step is done once the user opened its tab (stored per user), or when the site already shows it is set up.
$tavc_step_crawlers    = ! empty( $tavc_progress['bots'] ) || $tavc_blocked_count > 0 || ! empty( $tavc_custom_rules ) || '' !== implode( '', $tavc_signals );
$tavc_step_llms        = ! empty( $tavc_progress['llmstxt'] );
$tavc_step_traffic     = ! empty( $tavc_progress['referrals'] ) || $tavc_total_visits > 0;
$tavc_steps_done       = (int) $tavc_step_crawlers + (int) $tavc_step_llms + (int) $tavc_step_traffic;
$tavc_show_get_started = $tavc_steps_done < 3 && empty( $tavc_progress['hidden'] );

$tavc_steps = array(
	array(
		'done'   => $tavc_step_crawlers,
		'key'    => 'crawlers',
		'title'  => __( 'Choose which AI crawlers can read your site', 'tbsh-ai-visibility-control' ),
		'desc'   => __( 'For example, block AI training but stay visible in AI search answers.', 'tbsh-ai-visibility-control' ),
		'tab'    => 'bots',
		'action' => __( 'Choose crawler access', 'tbsh-ai-visibility-control' ),
	),
	array(
		'done'   => $tavc_step_llms,
		'key'    => 'llms',
		'title'  => __( 'Check your llms.txt file', 'tbsh-ai-visibility-control' ),
		'desc'   => __( 'Review what AI tools see and leave out anything that should not be listed.', 'tbsh-ai-visibility-control' ),
		'tab'    => 'llmstxt',
		'action' => __( 'Review llms.txt', 'tbsh-ai-visibility-control' ),
	),
	array(
		'done'   => $tavc_step_traffic,
		'key'    => 'traffic',
		'title'  => __( 'See visits from AI assistants', 'tbsh-ai-visibility-control' ),
		'desc'   => __( 'Visits from ChatGPT, Perplexity, Claude and others appear as soon as they arrive.', 'tbsh-ai-visibility-control' ),
		'tab'    => 'referrals',
		'action' => __( 'View AI traffic', 'tbsh-ai-visibility-control' ),
	),
);

// Discoverability details.
$tavc_discoverability_stats = $this->get_discoverability_stats();
?>

<?php if ( $tavc_show_get_started ) : ?>
	<section class="tavc-card tavc-get-started" id="tavc-get-started" aria-labelledby="tavc-get-started-title">
		<div class="tavc-get-started-head">
			<h2 class="tavc-card-title" id="tavc-get-started-title">
				<span aria-hidden="true" class="dashicons dashicons-flag"></span>
				<?php esc_html_e( 'Get Started', 'tbsh-ai-visibility-control' ); ?>
				<span class="tavc-get-started-progress">
					<?php
					/* translators: 1: completed steps, 2: total steps. */
					printf( esc_html__( '%1$s of %2$s done', 'tbsh-ai-visibility-control' ), esc_html( number_format_i18n( $tavc_steps_done ) ), esc_html( number_format_i18n( 3 ) ) );
					?>
				</span>
			</h2>
			<a href="<?php echo esc_url( TAVC_Admin::get_tool_url( 'hide_get_started' ) ); ?>" class="tavc-get-started-hide">
				<?php esc_html_e( 'Hide', 'tbsh-ai-visibility-control' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( 'the Get Started steps', 'tbsh-ai-visibility-control' ); ?></span>
			</a>
		</div>

		<ol class="tavc-steps">
			<?php foreach ( $tavc_steps as $tavc_step ) : ?>
				<li class="tavc-step<?php echo $tavc_step['done'] ? ' is-done' : ''; ?>" data-tavc-step="<?php echo esc_attr( $tavc_step['key'] ); ?>">
					<span class="tavc-step-mark" aria-hidden="true"><?php echo $tavc_step['done'] ? '&#10003;' : ''; ?></span>
					<div class="tavc-step-body">
						<span class="tavc-step-title">
							<span class="screen-reader-text" data-tavc-step-state><?php echo $tavc_step['done'] ? esc_html__( 'Done:', 'tbsh-ai-visibility-control' ) : esc_html__( 'To do:', 'tbsh-ai-visibility-control' ); ?> </span>
							<?php echo esc_html( $tavc_step['title'] ); ?>
						</span>
						<span class="tavc-step-desc"><?php echo esc_html( $tavc_step['desc'] ); ?></span>
					</div>
					<a href="<?php echo esc_url( TAVC_Admin::get_page_url( array( 'tab' => $tavc_step['tab'] ) ) ); ?>" class="button<?php echo $tavc_step['done'] ? '' : ' button-primary'; ?> tavc-step-action">
						<?php echo esc_html( $tavc_step['action'] ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ol>
	</section>
<?php endif; ?>

<!-- Status tiles -->
<div class="tavc-overview-stats">
	<div class="tavc-stat-widget">
		<div class="tavc-stat-val"><?php echo esc_html( number_format_i18n( $tavc_visits_30 ) ); ?></div>
		<div class="tavc-stat-label"><?php esc_html_e( 'AI visits (last 30 days)', 'tbsh-ai-visibility-control' ); ?></div>
		<div class="tavc-stat-change"><?php echo esc_html( $tavc_change_text ); ?></div>
	</div>

	<div class="tavc-stat-widget">
		<div class="tavc-stat-val is-text">
			<?php if ( $tavc_last_visit ) : ?>
				<?php echo esc_html( $tavc_last_visit->referrer ); ?>
				<span class="tavc-stat-sub">
					<?php
					/* translators: %s: relative time such as "2 hours". */
					printf( esc_html__( '%s ago', 'tbsh-ai-visibility-control' ), esc_html( human_time_diff( (int) mysql2date( 'U', $tavc_last_visit->timestamp ), time() ) ) );
					?>
				</span>
			<?php else : ?>
				<?php esc_html_e( 'No visits yet', 'tbsh-ai-visibility-control' ); ?>
			<?php endif; ?>
		</div>
		<div class="tavc-stat-label"><?php esc_html_e( 'Last AI visit', 'tbsh-ai-visibility-control' ); ?></div>
	</div>

	<div class="tavc-stat-widget">
		<div class="tavc-stat-val is-text">
			<?php $this->render_llms_tile(); ?>
		</div>
		<div class="tavc-stat-label"><?php esc_html_e( 'llms.txt', 'tbsh-ai-visibility-control' ); ?></div>
	</div>

	<div class="tavc-stat-widget">
		<div class="tavc-stat-val is-text">
			<?php
			/* translators: 1: number of blocked crawlers, 2: number of known crawlers. */
			printf( esc_html__( '%1$s of %2$s', 'tbsh-ai-visibility-control' ), esc_html( number_format_i18n( $tavc_blocked_count ) ), esc_html( number_format_i18n( $tavc_bot_count ) ) );
			?>
		</div>
		<div class="tavc-stat-label"><?php esc_html_e( 'AI crawlers blocked', 'tbsh-ai-visibility-control' ); ?></div>
	</div>
</div>

<!-- Health check -->
<div class="tavc-card">
	<h2 class="tavc-card-title">
		<span aria-hidden="true" class="dashicons dashicons-heart"></span>
		<?php esc_html_e( 'AI Visibility Health Check', 'tbsh-ai-visibility-control' ); ?>
	</h2>
	<?php $this->render_health_section(); ?>
</div>

<!-- Discoverability details -->
<details class="tavc-advanced">
	<summary class="tavc-advanced-summary">
		<span class="tavc-advanced-title"><?php esc_html_e( 'Content discoverability details', 'tbsh-ai-visibility-control' ); ?></span>
		<span class="tavc-advanced-hint"><?php esc_html_e( 'Published content counts and the last llms.txt rebuild', 'tbsh-ai-visibility-control' ); ?></span>
	</summary>

	<div class="tavc-card">
		<div class="tavc-table-scroll">
			<table class="tavc-table">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Published posts', 'tbsh-ai-visibility-control' ); ?></th>
						<td class="tavc-num"><?php echo esc_html( number_format_i18n( $tavc_discoverability_stats['posts_count'] ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Published pages', 'tbsh-ai-visibility-control' ); ?></th>
						<td class="tavc-num"><?php echo esc_html( number_format_i18n( $tavc_discoverability_stats['pages_count'] ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Not public (drafts, private, scheduled, trash)', 'tbsh-ai-visibility-control' ); ?></th>
						<td class="tavc-num tavc-muted-text"><?php echo esc_html( number_format_i18n( $tavc_discoverability_stats['excluded_count'] ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Last llms.txt rebuild', 'tbsh-ai-visibility-control' ); ?></th>
						<td class="tavc-num">
							<?php
							if ( $tavc_discoverability_stats['last_rebuild'] ) {
								echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $tavc_discoverability_stats['last_rebuild'] ) );
							} else {
								esc_html_e( 'Never rebuilt', 'tbsh-ai-visibility-control' );
							}
							?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<p class="tavc-info-box">
			<span aria-hidden="true" class="dashicons dashicons-editor-help tavc-icon-md"></span>
			<?php esc_html_e( 'A saved copy of llms.txt is served so it loads instantly. The copy is refreshed automatically whenever you publish, edit or delete content. The llms.txt tab shows exactly which content types are listed.', 'tbsh-ai-visibility-control' ); ?>
		</p>
	</div>
</details>

