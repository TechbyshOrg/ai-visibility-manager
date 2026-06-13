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

// Fetch DB queries
$tavc_top_sources = TAVC_DB::get_top_referral_sources( 5 );
$tavc_top_pages   = TAVC_DB::get_top_target_pages( 5 );
$tavc_recent_logs = TAVC_DB::get_recent_referrals( 20 );
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
				<?php esc_html_e( 'Traffic from ChatGPT, Claude, Perplexity, Gemini, Copilot, or Bard will appear here as soon as referrals hit public pages.', 'tbsh-ai-visibility-control' ); ?>
			</p>
		</div>
	<?php else : ?>
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
			<table class="tavc-table">
				<thead>
					<tr>
						<th style="width: 25%;"><?php esc_html_e( 'Timestamp', 'tbsh-ai-visibility-control' ); ?></th>
						<th style="width: 25%;"><?php esc_html_e( 'Source Bot', 'tbsh-ai-visibility-control' ); ?></th>
						<th><?php esc_html_e( 'Target Page', 'tbsh-ai-visibility-control' ); ?></th>
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
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
