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

// Fetch DB stats
$tavc_total_referrals = TAVC_DB::get_total_referrals();
$tavc_last_referral   = TAVC_DB::get_last_referral();

// Fetch Health Checks and Discoverability
$tavc_health_status        = $this->get_health_status();
$tavc_discoverability_stats = $this->get_discoverability_stats();

// Determine Overall Discoverability Status (Green, Yellow, Red)
$tavc_overall_status = 'green';
$tavc_fail_count     = 0;
$tavc_warn_count     = 0;

foreach ( $tavc_health_status as $tavc_check ) {
	if ( $tavc_check['status'] === 'fail' ) {
		$tavc_fail_count++;
	} elseif ( $tavc_check['status'] === 'warning' ) {
		$tavc_warn_count++;
	}
}

if ( $tavc_fail_count > 0 ) {
	$tavc_overall_status = 'red';
} elseif ( $tavc_warn_count > 0 || empty( $tavc_discoverability_stats['posts_count'] ) ) {
	$tavc_overall_status = 'yellow';
}
?>

<!-- Overview Stats Grid -->
<div class="tavc-overview-stats">
	<!-- Total Referrals -->
	<div class="tavc-stat-widget">
		<div class="tavc-stat-val"><?php echo esc_html( number_format_i18n( $tavc_total_referrals ) ); ?></div>
		<div class="tavc-stat-label"><?php esc_html_e( 'Total AI Referrals', 'tbsh-ai-visibility-control' ); ?></div>
	</div>

	<!-- Last Referral -->
	<div class="tavc-stat-widget">
		<div class="tavc-stat-val" style="font-size: 18px; padding: 7px 0;">
			<?php 
			if ( $tavc_last_referral ) {
				echo esc_html( $tavc_last_referral->referrer ) . '<br><span style="font-size: 11px; font-weight: normal; color: var(--tavc-text-muted);">' . esc_html( human_time_diff( mysql2date( 'U', $tavc_last_referral->timestamp ), current_time( 'timestamp' ) ) ) . ' ' . esc_html__( 'ago', 'tbsh-ai-visibility-control' ) . '</span>';
			} else {
				esc_html_e( 'No visits yet', 'tbsh-ai-visibility-control' );
			}
			?>
		</div>
		<div class="tavc-stat-label"><?php esc_html_e( 'Last AI Referral', 'tbsh-ai-visibility-control' ); ?></div>
	</div>

	<!-- llms.txt Reachability -->
	<div class="tavc-stat-widget">
		<div class="tavc-stat-val" style="font-size: 18px; padding: 7px 0;">
			<?php if ( isset( $tavc_health_status['llmstxt']['status'] ) && $tavc_health_status['llmstxt']['status'] === 'pass' ) : ?>
				<span class="tavc-status-tag status-green"><span class="tavc-pulse"></span> <?php esc_html_e( 'Active', 'tbsh-ai-visibility-control' ); ?></span>
			<?php else : ?>
				<span class="tavc-status-tag status-red"><?php esc_html_e( 'Inactive', 'tbsh-ai-visibility-control' ); ?></span>
			<?php endif; ?>
		</div>
		<div class="tavc-stat-label"><?php esc_html_e( 'llms.txt Status', 'tbsh-ai-visibility-control' ); ?></div>
	</div>

	<!-- robots.txt Status -->
	<div class="tavc-stat-widget">
		<div class="tavc-stat-val" style="font-size: 18px; padding: 7px 0;">
			<?php if ( isset( $tavc_health_status['robots']['status'] ) && $tavc_health_status['robots']['status'] === 'pass' ) : ?>
				<span class="tavc-status-tag status-green"><span class="tavc-pulse"></span> <?php esc_html_e( 'Active', 'tbsh-ai-visibility-control' ); ?></span>
			<?php else : ?>
				<span class="tavc-status-tag status-yellow"><?php esc_html_e( 'Check Rules', 'tbsh-ai-visibility-control' ); ?></span>
			<?php endif; ?>
		</div>
		<div class="tavc-stat-label"><?php esc_html_e( 'robots.txt Status', 'tbsh-ai-visibility-control' ); ?></div>
	</div>
</div>

<div class="tavc-two-column-grid">
	
	<!-- Health Check Card -->
	<div class="tavc-card">
		<h3 class="tavc-card-title">
			<span class="dashicons dashicons-heart"></span>
			<?php esc_html_e( 'AI Visibility Health Check', 'tbsh-ai-visibility-control' ); ?>
		</h3>
		
		<div class="tavc-health-list">
			<?php foreach ( $tavc_health_status as $tavc_key => $tavc_check ) : ?>
				<div class="tavc-health-item health-<?php echo esc_attr( $tavc_check['status'] ); ?>">
					<span class="tavc-health-icon">
						<?php 
						if ( $tavc_check['status'] === 'pass' ) {
							echo '✓';
						} elseif ( $tavc_check['status'] === 'warning' ) {
							echo '⚠';
						} else {
							echo '✗';
						}
						?>
					</span>
					<div>
						<div class="tavc-health-title"><?php echo esc_html( $tavc_check['title'] ); ?></div>
						<div class="tavc-health-desc">
							<?php echo esc_html( $tavc_check['desc'] ); ?>
							<?php if ( ! empty( $tavc_check['recommendation'] ) ) : ?>
								<br><strong style="color: var(--tavc-text-dark);"><?php esc_html_e( 'Recommendation:', 'tbsh-ai-visibility-control' ); ?></strong> <?php echo esc_html( $tavc_check['recommendation'] ); ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Content Discoverability Report Card -->
	<div class="tavc-card">
		<h3 class="tavc-card-title">
			<span class="dashicons dashicons-analytics"></span>
			<?php esc_html_e( 'Content Discoverability Report', 'tbsh-ai-visibility-control' ); ?>
			
			<span style="margin-left: auto;">
				<?php if ( $tavc_overall_status === 'green' ) : ?>
					<span class="tavc-status-tag status-green"><span class="tavc-pulse"></span> <?php esc_html_e( 'Excellent', 'tbsh-ai-visibility-control' ); ?></span>
				<?php elseif ( $tavc_overall_status === 'yellow' ) : ?>
					<span class="tavc-status-tag status-yellow"><?php esc_html_e( 'Needs Review', 'tbsh-ai-visibility-control' ); ?></span>
				<?php else : ?>
					<span class="tavc-status-tag status-red"><?php esc_html_e( 'Action Required', 'tbsh-ai-visibility-control' ); ?></span>
				<?php endif; ?>
			</span>
		</h3>
		
		<table class="tavc-table" style="margin-bottom: 20px;">
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Posts indexed for llms.txt', 'tbsh-ai-visibility-control' ); ?></strong></td>
					<td style="text-align: right; font-weight: 500;"><?php echo intval( $tavc_discoverability_stats['posts_count'] ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Pages indexed for llms.txt', 'tbsh-ai-visibility-control' ); ?></strong></td>
					<td style="text-align: right; font-weight: 500;"><?php echo intval( $tavc_discoverability_stats['pages_count'] ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Excluded Content (Non-Public)', 'tbsh-ai-visibility-control' ); ?></strong></td>
					<td style="text-align: right; font-weight: 500; color: var(--tavc-text-muted);"><?php echo intval( $tavc_discoverability_stats['excluded_count'] ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Last Cache Rebuild', 'tbsh-ai-visibility-control' ); ?></strong></td>
					<td style="text-align: right; font-weight: 500;">
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
		
		<div style="background: var(--tavc-bg); padding: 15px; border-radius: 8px; font-size: 13px; color: var(--tavc-text-muted); line-height: 1.4;">
			<span class="dashicons dashicons-editor-help" style="font-size: 16px; width: 16px; height: 16px; margin-right: 4px; vertical-align: text-top; color: var(--tavc-primary);"></span>
			<?php esc_html_e( 'The llms.txt file is cached via Transients to keep page load times instant. Cache is automatically purged whenever you publish, edit, or delete public content.', 'tbsh-ai-visibility-control' ); ?>
		</div>
	</div>
</div>
