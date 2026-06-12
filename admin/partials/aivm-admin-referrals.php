<?php
/**
 * View for AI Referrals tab
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Aivm
 * @subpackage Aivm/admin/partials
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

// Fetch DB queries
$top_sources = AIVM_DB::get_top_referral_sources( 5 );
$top_pages   = AIVM_DB::get_top_target_pages( 5 );
$recent_logs = AIVM_DB::get_recent_referrals( 20 );
?>

<div class="aivm-card">
	<h3 class="aivm-card-title">
		<span class="dashicons dashicons-chart-area"></span>
		<?php esc_html_e( 'AI Search Referrals Analytics', 'ai-visibility-manager' ); ?>
	</h3>
	
	<p style="margin-bottom: 25px; color: var(--aivm-text-muted); line-height: 1.5;">
		<?php esc_html_e( 'Monitor how AI search engines like ChatGPT, Claude, and Perplexity refer visitors to your site when answering conversational search queries.', 'ai-visibility-manager' ); ?>
	</p>

	<?php if ( empty( $recent_logs ) ) : ?>
		<div style="text-align: center; padding: 40px 20px; border: 1px dashed var(--aivm-border); border-radius: 8px; background: var(--aivm-bg);">
			<span class="dashicons dashicons-chart-line" style="font-size: 48px; width: 48px; height: 48px; color: var(--aivm-text-muted); margin-bottom: 15px;"></span>
			<h4 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 600;"><?php esc_html_e( 'No AI referral traffic logged yet', 'ai-visibility-manager' ); ?></h4>
			<p style="margin: 0; font-size: 13px; color: var(--aivm-text-muted); max-width: 400px; margin: 0 auto;">
				<?php esc_html_e( 'Traffic from ChatGPT, Claude, Perplexity, Gemini, Copilot, or Bard will appear here as soon as referrals hit public pages.', 'ai-visibility-manager' ); ?>
			</p>
		</div>
	<?php else : ?>
		<!-- Analytics Grid (Top Sources & Top Pages) -->
		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
			<!-- Top AI Sources Table -->
			<div style="border: 1px solid var(--aivm-border); border-radius: 8px; padding: 16px; background: #ffffff;">
				<h4 style="margin: 0 0 12px 0; font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
					<span class="dashicons dashicons-networking" style="color: var(--aivm-primary);"></span>
					<?php esc_html_e( 'Top AI Referral Sources', 'ai-visibility-manager' ); ?>
				</h4>
				<table class="aivm-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Source Bot', 'ai-visibility-manager' ); ?></th>
							<th style="text-align: right; width: 30%;"><?php esc_html_e( 'Visits', 'ai-visibility-manager' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $top_sources as $source ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $source->referrer ); ?></strong></td>
								<td style="text-align: right; font-weight: 500;"><?php echo number_format_i18n( $source->count ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<!-- Top Pages Visited Table -->
			<div style="border: 1px solid var(--aivm-border); border-radius: 8px; padding: 16px; background: #ffffff;">
				<h4 style="margin: 0 0 12px 0; font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
					<span class="dashicons dashicons-admin-page" style="color: var(--aivm-primary);"></span>
					<?php esc_html_e( 'Most Visited Pages from AI', 'ai-visibility-manager' ); ?>
				</h4>
				<table class="aivm-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Destination Page', 'ai-visibility-manager' ); ?></th>
							<th style="text-align: right; width: 30%;"><?php esc_html_e( 'Visits', 'ai-visibility-manager' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $top_pages as $page ) : ?>
							<tr>
								<td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
									<a href="<?php echo esc_url( $page->target_url ); ?>" target="_blank" rel="noopener noreferrer">
										<?php 
										$path = wp_parse_url( $page->target_url, PHP_URL_PATH );
										echo esc_html( empty( $path ) || $path === '/' ? '/' : $path );
										?>
									</a>
								</td>
								<td style="text-align: right; font-weight: 500;"><?php echo number_format_i18n( $page->count ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Last 20 Referrals Log List -->
		<div style="border: 1px solid var(--aivm-border); border-radius: 8px; padding: 16px; background: #ffffff;">
			<h4 style="margin: 0 0 12px 0; font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
				<span class="dashicons dashicons-list-view" style="color: var(--aivm-primary);"></span>
				<?php esc_html_e( 'Recent AI Referrals Logs (Last 20)', 'ai-visibility-manager' ); ?>
			</h4>
			<table class="aivm-table">
				<thead>
					<tr>
						<th style="width: 25%;"><?php esc_html_e( 'Timestamp', 'ai-visibility-manager' ); ?></th>
						<th style="width: 25%;"><?php esc_html_e( 'Source Bot', 'ai-visibility-manager' ); ?></th>
						<th><?php esc_html_e( 'Target Page', 'ai-visibility-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $recent_logs as $log ) : ?>
						<tr>
							<td style="color: var(--aivm-text-muted);">
								<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), mysql2date( 'G', $log->timestamp ) ) ); ?>
							</td>
							<td>
								<span class="aivm-status-tag status-green" style="font-weight: 500;"><?php echo esc_html( $log->referrer ); ?></span>
							</td>
							<td style="max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
								<a href="<?php echo esc_url( $log->target_url ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_url( $log->target_url ); ?>">
									<?php echo esc_html( $log->target_url ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>

<style>
@media (max-width: 782px) {
	div[style*="grid-template-columns: 1fr 1fr"] {
		grid-template-columns: 1fr !important;
	}
}
</style>
