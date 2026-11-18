<?php
/**
 * View for AI Referrals tab
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Tavc
 * @subpackage Tavc/admin/partials
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

// Selected range ("all" keeps the original all-time report)
$tavc_range       = $this->get_active_referral_range();
$tavc_ranges      = $this->get_referral_ranges();
$tavc_range_days  = 'all' === $tavc_range ? 0 : intval( $tavc_range );
$tavc_today       = new DateTimeImmutable( 'today', wp_timezone() );
$tavc_tomorrow    = $tavc_today->modify( '+1 day' );
$tavc_since       = null;
$tavc_change_text = '';

if ( $tavc_range_days > 0 ) {
	$tavc_since_dt  = $tavc_today->modify( '-' . ( $tavc_range_days - 1 ) . ' days' );
	$tavc_since     = $tavc_since_dt->format( 'Y-m-d H:i:s' );
	$tavc_prev_dt   = $tavc_since_dt->modify( '-' . $tavc_range_days . ' days' );
	$tavc_range_cnt = TAVC_DB::get_referral_count_between( $tavc_since, $tavc_tomorrow->format( 'Y-m-d H:i:s' ) );
	$tavc_prev_cnt  = TAVC_DB::get_referral_count_between( $tavc_prev_dt->format( 'Y-m-d H:i:s' ), $tavc_since );

	if ( $tavc_prev_cnt > 0 ) {
		$tavc_change      = round( ( ( $tavc_range_cnt - $tavc_prev_cnt ) / $tavc_prev_cnt ) * 100 );
		$tavc_change_text = sprintf(
			/* translators: 1: signed percentage such as +12%, 2: number of days. */
			__( '%1$s compared with the previous %2$s days', 'tbsh-ai-visibility-control' ),
			( $tavc_change > 0 ? '+' : '' ) . number_format_i18n( $tavc_change ) . '%',
			number_format_i18n( $tavc_range_days )
		);
	} else {
		$tavc_change_text = sprintf(
			/* translators: %s: number of days. */
			__( 'No visits in the previous %s days', 'tbsh-ai-visibility-control' ),
			number_format_i18n( $tavc_range_days )
		);
	}
} else {
	$tavc_range_cnt = TAVC_DB::get_total_referrals();
}

// Daily chart: the selected range, or the last 90 days for "All time"
$tavc_chart_days  = $tavc_range_days > 0 ? $tavc_range_days : 90;
$tavc_chart_start = $tavc_today->modify( '-' . ( $tavc_chart_days - 1 ) . ' days' );
$tavc_daily       = TAVC_DB::get_daily_referral_counts( $tavc_chart_start->format( 'Y-m-d H:i:s' ) );
$tavc_series      = array();
for ( $tavc_i = 0; $tavc_i < $tavc_chart_days; $tavc_i++ ) {
	$tavc_day                 = $tavc_chart_start->modify( '+' . $tavc_i . ' days' );
	$tavc_key                 = $tavc_day->format( 'Y-m-d' );
	$tavc_series[ $tavc_key ] = array(
		'label' => wp_date( get_option( 'date_format' ), $tavc_day->getTimestamp() ),
		'count' => isset( $tavc_daily[ $tavc_key ] ) ? $tavc_daily[ $tavc_key ] : 0,
	);
}
$tavc_chart_max   = max( 1, max( wp_list_pluck( $tavc_series, 'count' ) ) );
$tavc_chart_total = array_sum( wp_list_pluck( $tavc_series, 'count' ) );

// Fetch DB queries
$tavc_top_sources = TAVC_DB::get_top_referral_sources( 5, $tavc_since );
$tavc_top_pages   = TAVC_DB::get_top_target_pages( 5, $tavc_since );
$tavc_recent_logs = TAVC_DB::get_recent_referrals( 20 );

// WooCommerce orders from AI platforms
$tavc_wc_report = null;
if ( TAVC_WooCommerce::is_available() ) {
	$tavc_wc_report = TAVC_WooCommerce::get_report( $tavc_range_days > 0 ? TAVC_WooCommerce::get_range_start_timestamp( $tavc_range_days ) : 0 );
}

// SVG chart geometry
$tavc_svg_width  = 600;
$tavc_svg_height = 160;
$tavc_slot_width = $tavc_svg_width / $tavc_chart_days;
$tavc_bar_width  = max( 1, $tavc_slot_width * 0.7 );
?>

<div class="tavc-card">
	<h3 class="tavc-card-title">
		<span class="dashicons dashicons-chart-area"></span>
		<?php esc_html_e( 'AI Search Referrals Analytics', 'tbsh-ai-visibility-control' ); ?>
	</h3>

	<p style="margin-bottom: 25px; color: var(--tavc-text-muted); line-height: 1.5;">
		<?php esc_html_e( 'Monitor how AI search engines like ChatGPT, Claude, and Perplexity refer visitors to your site when answering conversational search queries.', 'tbsh-ai-visibility-control' ); ?>
	</p>

	<?php if ( empty( $tavc_recent_logs ) ) : ?>
		<div style="text-align: center; padding: 40px 20px; border: 1px dashed var(--tavc-border); border-radius: 8px; background: var(--tavc-bg);">
			<span class="dashicons dashicons-chart-line" style="font-size: 48px; width: 48px; height: 48px; color: var(--tavc-text-muted); margin-bottom: 15px;"></span>
			<h4 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 600;"><?php esc_html_e( 'No AI referral traffic logged yet', 'tbsh-ai-visibility-control' ); ?></h4>
			<p style="margin: 0; font-size: 13px; color: var(--tavc-text-muted); max-width: 400px; margin: 0 auto;">
				<?php esc_html_e( 'Traffic from ChatGPT, Claude, Perplexity, Gemini, Copilot and other AI assistants will appear here as soon as referrals hit public pages.', 'tbsh-ai-visibility-control' ); ?>
			</p>
		</div>
	<?php else : ?>
		<!-- Range selector -->
		<nav class="tavc-range-nav" aria-label="<?php esc_attr_e( 'Report period', 'tbsh-ai-visibility-control' ); ?>">
			<?php foreach ( $tavc_ranges as $tavc_range_key => $tavc_range_label ) : ?>
				<?php
				$tavc_range_url = add_query_arg(
					array(
						'page'       => 'tbsh-ai-visibility-control',
						'tab'        => 'referrals',
						'tavc_range' => $tavc_range_key,
					),
					admin_url( 'options-general.php' )
				);
				?>
				<a href="<?php echo esc_url( $tavc_range_url ); ?>" class="tavc-range-link<?php echo $tavc_range_key === $tavc_range ? ' is-active' : ''; ?>"<?php echo $tavc_range_key === $tavc_range ? ' aria-current="page"' : ''; ?>>
					<?php echo esc_html( $tavc_range_label ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<!-- Period summary -->
		<div class="tavc-overview-stats" style="margin-bottom: 20px;">
			<div class="tavc-stat-widget">
				<div class="tavc-stat-val"><?php echo esc_html( number_format_i18n( $tavc_range_cnt ) ); ?></div>
				<div class="tavc-stat-label">
					<?php
					printf(
						/* translators: %s: report period such as "Last 30 days". */
						esc_html__( 'AI referral visits (%s)', 'tbsh-ai-visibility-control' ),
						esc_html( $tavc_ranges[ $tavc_range ] )
					);
					?>
				</div>
				<?php if ( '' !== $tavc_change_text ) : ?>
					<div class="tavc-stat-change"><?php echo esc_html( $tavc_change_text ); ?></div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Daily chart -->
		<figure class="tavc-chart">
			<figcaption class="tavc-chart-caption" id="tavc-chart-title">
				<?php
				printf(
					/* translators: 1: number of days, 2: total visits in the chart. */
					esc_html__( 'AI referral visits per day, last %1$s days (%2$s in total)', 'tbsh-ai-visibility-control' ),
					esc_html( number_format_i18n( $tavc_chart_days ) ),
					esc_html( number_format_i18n( $tavc_chart_total ) )
				);
				?>
			</figcaption>
			<div class="tavc-chart-max" aria-hidden="true">
				<?php
				printf(
					/* translators: %s: highest daily value. */
					esc_html__( 'Max %s / day', 'tbsh-ai-visibility-control' ),
					esc_html( number_format_i18n( $tavc_chart_max ) )
				);
				?>
			</div>
			<svg class="tavc-chart-svg" viewBox="0 0 <?php echo esc_attr( $tavc_svg_width ); ?> <?php echo esc_attr( $tavc_svg_height ); ?>" preserveAspectRatio="none" role="img" aria-labelledby="tavc-chart-title" focusable="false">
				<line x1="0" y1="<?php echo esc_attr( $tavc_svg_height - 0.5 ); ?>" x2="<?php echo esc_attr( $tavc_svg_width ); ?>" y2="<?php echo esc_attr( $tavc_svg_height - 0.5 ); ?>" class="tavc-chart-baseline" />
				<?php $tavc_i = 0; ?>
				<?php foreach ( $tavc_series as $tavc_point ) : ?>
					<?php
					$tavc_bar_height = $tavc_point['count'] > 0 ? max( 2, ( $tavc_point['count'] / $tavc_chart_max ) * ( $tavc_svg_height - 4 ) ) : 0;
					$tavc_bar_x      = ( $tavc_i * $tavc_slot_width ) + ( ( $tavc_slot_width - $tavc_bar_width ) / 2 );
					?>
					<?php if ( $tavc_bar_height > 0 ) : ?>
						<rect class="tavc-chart-bar" x="<?php echo esc_attr( round( $tavc_bar_x, 2 ) ); ?>" y="<?php echo esc_attr( round( $tavc_svg_height - $tavc_bar_height, 2 ) ); ?>" width="<?php echo esc_attr( round( $tavc_bar_width, 2 ) ); ?>" height="<?php echo esc_attr( round( $tavc_bar_height, 2 ) ); ?>">
							<title>
								<?php
								printf(
									/* translators: 1: date, 2: number of visits. */
									esc_html__( '%1$s: %2$s visits', 'tbsh-ai-visibility-control' ),
									esc_html( $tavc_point['label'] ),
									esc_html( number_format_i18n( $tavc_point['count'] ) )
								);
								?>
							</title>
						</rect>
					<?php endif; ?>
					<?php $tavc_i++; ?>
				<?php endforeach; ?>
			</svg>
			<div class="tavc-chart-axis" aria-hidden="true">
				<span><?php echo esc_html( reset( $tavc_series )['label'] ); ?></span>
				<span><?php echo esc_html( end( $tavc_series )['label'] ); ?></span>
			</div>
			<details class="tavc-chart-data">
				<summary><?php esc_html_e( 'Show chart data as a table', 'tbsh-ai-visibility-control' ); ?></summary>
				<table class="tavc-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Date', 'tbsh-ai-visibility-control' ); ?></th>
							<th scope="col" class="tavc-num"><?php esc_html_e( 'Visits', 'tbsh-ai-visibility-control' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( array_reverse( $tavc_series ) as $tavc_point ) : ?>
							<tr>
								<td><?php echo esc_html( $tavc_point['label'] ); ?></td>
								<td class="tavc-num"><?php echo esc_html( number_format_i18n( $tavc_point['count'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</details>
		</figure>

		<!-- Analytics Grid (Top Sources & Top Pages) -->
		<div class="tavc-two-column-grid" style="margin-bottom: 30px;">
			<!-- Top AI Sources Table -->
			<div style="border: 1px solid var(--tavc-border); border-radius: 8px; padding: 16px; background: #ffffff;">
				<h4 style="margin: 0 0 12px 0; font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
					<span class="dashicons dashicons-networking" style="color: var(--tavc-primary);"></span>
					<?php esc_html_e( 'Top AI Referral Sources', 'tbsh-ai-visibility-control' ); ?>
				</h4>
				<table class="tavc-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Source Bot', 'tbsh-ai-visibility-control' ); ?></th>
							<th style="text-align: right; width: 30%;"><?php esc_html_e( 'Visits', 'tbsh-ai-visibility-control' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $tavc_top_sources ) ) : ?>
							<tr><td colspan="2" class="tavc-muted-text"><?php esc_html_e( 'No AI referral visits in this period.', 'tbsh-ai-visibility-control' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $tavc_top_sources as $tavc_source ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $tavc_source->referrer ); ?></strong></td>
								<td style="text-align: right; font-weight: 500;"><?php echo esc_html( number_format_i18n( $tavc_source->count ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<!-- Top Pages Visited Table -->
			<div style="border: 1px solid var(--tavc-border); border-radius: 8px; padding: 16px; background: #ffffff;">
				<h4 style="margin: 0 0 12px 0; font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
					<span class="dashicons dashicons-admin-page" style="color: var(--tavc-primary);"></span>
					<?php esc_html_e( 'Most Visited Pages from AI', 'tbsh-ai-visibility-control' ); ?>
				</h4>
				<table class="tavc-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Destination Page', 'tbsh-ai-visibility-control' ); ?></th>
							<th style="text-align: right; width: 30%;"><?php esc_html_e( 'Visits', 'tbsh-ai-visibility-control' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $tavc_top_pages ) ) : ?>
							<tr><td colspan="2" class="tavc-muted-text"><?php esc_html_e( 'No AI referral visits in this period.', 'tbsh-ai-visibility-control' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $tavc_top_pages as $tavc_page ) : ?>
							<tr>
								<td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
									<a href="<?php echo esc_url( $tavc_page->target_url ); ?>" target="_blank" rel="noopener noreferrer">
										<?php
										$tavc_path = wp_parse_url( $tavc_page->target_url, PHP_URL_PATH );
										echo esc_html( empty( $tavc_path ) || $tavc_path === '/' ? '/' : $tavc_path );
										?>
									</a>
								</td>
								<td style="text-align: right; font-weight: 500;"><?php echo esc_html( number_format_i18n( $tavc_page->count ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Last 20 Referrals Log List -->
		<div style="border: 1px solid var(--tavc-border); border-radius: 8px; padding: 16px; background: #ffffff;">
			<h4 style="margin: 0 0 12px 0; font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
				<span class="dashicons dashicons-list-view" style="color: var(--tavc-primary);"></span>
				<?php esc_html_e( 'Recent AI Referrals Logs (Last 20)', 'tbsh-ai-visibility-control' ); ?>
			</h4>
			<div class="tavc-table-scroll">
				<table class="tavc-table">
					<thead>
						<tr>
							<th style="width: 25%;"><?php esc_html_e( 'Timestamp', 'tbsh-ai-visibility-control' ); ?></th>
							<th style="width: 20%;"><?php esc_html_e( 'Source Bot', 'tbsh-ai-visibility-control' ); ?></th>
							<th><?php esc_html_e( 'Target Page', 'tbsh-ai-visibility-control' ); ?></th>
							<th style="width: 15%;"><?php esc_html_e( 'Detected Via', 'tbsh-ai-visibility-control' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $tavc_recent_logs as $tavc_log ) : ?>
							<tr>
								<td style="color: var(--tavc-text-muted);">
									<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), mysql2date( 'G', $tavc_log->timestamp ) ) ); ?>
								</td>
								<td>
									<span class="tavc-status-tag status-green" style="font-weight: 500;"><?php echo esc_html( $tavc_log->referrer ); ?></span>
								</td>
								<td style="max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
									<a href="<?php echo esc_url( $tavc_log->target_url ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_url( $tavc_log->target_url ); ?>">
										<?php echo esc_html( $tavc_log->target_url ); ?>
									</a>
								</td>
								<td class="tavc-muted-text"><?php echo esc_html( TAVC_AI_Sources::get_method_label( isset( $tavc_log->method ) ? $tavc_log->method : '' ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php endif; ?>
</div>

<?php if ( null !== $tavc_wc_report ) : ?>
	<div class="tavc-card">
		<h3 class="tavc-card-title">
			<span class="dashicons dashicons-cart"></span>
			<?php esc_html_e( 'WooCommerce Orders from AI', 'tbsh-ai-visibility-control' ); ?>
		</h3>

		<?php if ( empty( $tavc_wc_report['rows'] ) ) : ?>
			<p class="tavc-muted-text" style="margin-top: 0; line-height: 1.5;">
				<?php esc_html_e( 'No paid orders from AI assistants in this period yet. Orders are matched using WooCommerce Order Attribution, so it must be turned on (WooCommerce > Settings > Advanced > Features). Orders placed before this plugin was updated are not included.', 'tbsh-ai-visibility-control' ); ?>
			</p>
		<?php else : ?>
			<table class="tavc-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'AI Source', 'tbsh-ai-visibility-control' ); ?></th>
						<th scope="col" class="tavc-num"><?php esc_html_e( 'Paid Orders', 'tbsh-ai-visibility-control' ); ?></th>
						<th scope="col" class="tavc-num"><?php esc_html_e( 'Revenue (after refunds)', 'tbsh-ai-visibility-control' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $tavc_wc_report['rows'] as $tavc_wc_row ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $tavc_wc_row['source'] ); ?></strong></td>
							<td class="tavc-num"><?php echo esc_html( number_format_i18n( $tavc_wc_row['orders'] ) ); ?></td>
							<td class="tavc-num"><?php echo wp_kses_post( wc_price( $tavc_wc_row['revenue'], array( 'currency' => $tavc_wc_row['currency'] ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p class="tavc-muted-text tavc-small-text">
				<?php
				if ( $tavc_wc_report['limited'] ) {
					esc_html_e( 'Only the most recent 2,000 orders are included.', 'tbsh-ai-visibility-control' );
					echo ' ';
				}
				esc_html_e( 'Paid orders only. Updated every 15 minutes.', 'tbsh-ai-visibility-control' );
				?>
			</p>
		<?php endif; ?>
	</div>
<?php endif; ?>
