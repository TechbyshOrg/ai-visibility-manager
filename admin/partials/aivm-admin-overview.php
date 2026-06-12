<?php
/**
 * View for Overview tab
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Aivm
 * @subpackage Aivm/admin/partials
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

// Fetch DB stats
$total_referrals = AIVM_DB::get_total_referrals();
$last_referral   = AIVM_DB::get_last_referral();

// Fetch Health Checks and Discoverability
$health_status        = $this->get_health_status();
$discoverability_stats = $this->get_discoverability_stats();

// Determine Overall Discoverability Status (Green, Yellow, Red)
$overall_status = 'green';
$fail_count = 0;
$warn_count = 0;

foreach ( $health_status as $check ) {
	if ( $check['status'] === 'fail' ) {
		$fail_count++;
	} elseif ( $check['status'] === 'warning' ) {
		$warn_count++;
	}
}

if ( $fail_count > 0 ) {
	$overall_status = 'red';
} elseif ( $warn_count > 0 || empty( $discoverability_stats['posts_count'] ) ) {
	$overall_status = 'yellow';
}
?>

<!-- Overview Stats Grid -->
<div class="aivm-overview-stats">
	<!-- Total Referrals -->
	<div class="aivm-stat-widget">
		<div class="aivm-stat-val"><?php echo number_format_i18n( $total_referrals ); ?></div>
		<div class="aivm-stat-label"><?php esc_html_e( 'Total AI Referrals', 'ai-visibility-manager' ); ?></div>
	</div>

	<!-- Last Referral -->
	<div class="aivm-stat-widget">
		<div class="aivm-stat-val" style="font-size: 18px; padding: 7px 0;">
			<?php 
			if ( $last_referral ) {
				echo esc_html( $last_referral->referrer ) . '<br><span style="font-size: 11px; font-weight: normal; color: var(--aivm-text-muted);">' . esc_html( human_time_diff( mysql2date( 'U', $last_referral->timestamp ), current_time( 'timestamp' ) ) ) . ' ' . esc_html__( 'ago', 'ai-visibility-manager' ) . '</span>';
			} else {
				esc_html_e( 'No visits yet', 'ai-visibility-manager' );
			}
			?>
		</div>
		<div class="aivm-stat-label"><?php esc_html_e( 'Last AI Referral', 'ai-visibility-manager' ); ?></div>
	</div>

	<!-- llms.txt Reachability -->
	<div class="aivm-stat-widget">
		<div class="aivm-stat-val" style="font-size: 18px; padding: 7px 0;">
			<?php if ( isset( $health_status['llmstxt']['status'] ) && $health_status['llmstxt']['status'] === 'pass' ) : ?>
				<span class="aivm-status-tag status-green"><span class="aivm-pulse"></span> <?php esc_html_e( 'Active', 'ai-visibility-manager' ); ?></span>
			<?php else : ?>
				<span class="aivm-status-tag status-red"><?php esc_html_e( 'Inactive', 'ai-visibility-manager' ); ?></span>
			<?php endif; ?>
		</div>
		<div class="aivm-stat-label"><?php esc_html_e( 'llms.txt Status', 'ai-visibility-manager' ); ?></div>
	</div>

	<!-- robots.txt Status -->
	<div class="aivm-stat-widget">
		<div class="aivm-stat-val" style="font-size: 18px; padding: 7px 0;">
			<?php if ( isset( $health_status['robots']['status'] ) && $health_status['robots']['status'] === 'pass' ) : ?>
				<span class="aivm-status-tag status-green"><span class="aivm-pulse"></span> <?php esc_html_e( 'Active', 'ai-visibility-manager' ); ?></span>
			<?php else : ?>
				<span class="aivm-status-tag status-yellow"><?php esc_html_e( 'Check Rules', 'ai-visibility-manager' ); ?></span>
			<?php endif; ?>
		</div>
		<div class="aivm-stat-label"><?php esc_html_e( 'robots.txt Status', 'ai-visibility-manager' ); ?></div>
	</div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
	
	<!-- Health Check Card -->
	<div class="aivm-card">
		<h3 class="aivm-card-title">
			<span class="dashicons dashicons-heart"></span>
			<?php esc_html_e( 'AI Visibility Health Check', 'ai-visibility-manager' ); ?>
		</h3>
		
		<div class="aivm-health-list">
			<?php foreach ( $health_status as $key => $check ) : ?>
				<div class="aivm-health-item health-<?php echo esc_attr( $check['status'] ); ?>">
					<span class="aivm-health-icon">
						<?php 
						if ( $check['status'] === 'pass' ) {
							echo '✓';
						} elseif ( $check['status'] === 'warning' ) {
							echo '⚠';
						} else {
							echo '✗';
						}
						?>
					</span>
					<div>
						<div class="aivm-health-title"><?php echo esc_html( $check['title'] ); ?></div>
						<div class="aivm-health-desc">
							<?php echo esc_html( $check['desc'] ); ?>
							<?php if ( ! empty( $check['recommendation'] ) ) : ?>
								<br><strong style="color: var(--aivm-text-dark);"><?php esc_html_e( 'Recommendation:', 'ai-visibility-manager' ); ?></strong> <?php echo esc_html( $check['recommendation'] ); ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Content Discoverability Report Card -->
	<div class="aivm-card">
		<h3 class="aivm-card-title">
			<span class="dashicons dashicons-analytics"></span>
			<?php esc_html_e( 'Content Discoverability Report', 'ai-visibility-manager' ); ?>
			
			<span style="margin-left: auto;">
				<?php if ( $overall_status === 'green' ) : ?>
					<span class="aivm-status-tag status-green"><span class="aivm-pulse"></span> <?php esc_html_e( 'Excellent', 'ai-visibility-manager' ); ?></span>
				<?php elseif ( $overall_status === 'yellow' ) : ?>
					<span class="aivm-status-tag status-yellow"><?php esc_html_e( 'Needs Review', 'ai-visibility-manager' ); ?></span>
				<?php else : ?>
					<span class="aivm-status-tag status-red"><?php esc_html_e( 'Action Required', 'ai-visibility-manager' ); ?></span>
				<?php endif; ?>
			</span>
		</h3>
		
		<table class="aivm-table" style="margin-bottom: 20px;">
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Posts indexed for llms.txt', 'ai-visibility-manager' ); ?></strong></td>
					<td style="text-align: right; font-weight: 500;"><?php echo intval( $discoverability_stats['posts_count'] ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Pages indexed for llms.txt', 'ai-visibility-manager' ); ?></strong></td>
					<td style="text-align: right; font-weight: 500;"><?php echo intval( $discoverability_stats['pages_count'] ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Excluded Content (Non-Public)', 'ai-visibility-manager' ); ?></strong></td>
					<td style="text-align: right; font-weight: 500; color: var(--aivm-text-muted);"><?php echo intval( $discoverability_stats['excluded_count'] ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Last Cache Rebuild', 'ai-visibility-manager' ); ?></strong></td>
					<td style="text-align: right; font-weight: 500;">
						<?php 
						if ( $discoverability_stats['last_rebuild'] ) {
							echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $discoverability_stats['last_rebuild'] ) );
						} else {
							esc_html_e( 'Never rebuilt', 'ai-visibility-manager' );
						}
						?>
					</td>
				</tr>
			</tbody>
		</table>
		
		<div style="background: var(--aivm-bg); padding: 15px; border-radius: 8px; font-size: 13px; color: var(--aivm-text-muted); line-height: 1.4;">
			<span class="dashicons dashicons-editor-help" style="font-size: 16px; width: 16px; height: 16px; margin-right: 4px; vertical-align: text-top; color: var(--aivm-primary);"></span>
			<?php esc_html_e( 'The llms.txt file is cached via Transients to keep page load times instant. Cache is automatically purged whenever you publish, edit, or delete public content.', 'ai-visibility-manager' ); ?>
		</div>
	</div>
</div>

<style>
@media (max-width: 782px) {
	div[style*="grid-template-columns: 1fr 1fr"] {
		grid-template-columns: 1fr !important;
	}
}
</style>
