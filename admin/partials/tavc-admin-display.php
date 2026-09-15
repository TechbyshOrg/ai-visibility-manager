<?php
/**
 * Provide an admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Tavc
 * @subpackage Tavc/admin/partials
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

if ( ! isset( $tavc_active_tab ) ) {
	$tavc_active_tab = 'overview';
}
?>
<div class="wrap tavc-wrap">
	<!-- Plugin Header -->
	<header class="tavc-header">
		<div class="tavc-header-content">
			<h1>
				<span class="dashicons dashicons-visibility"></span> 
				<?php esc_html_e( 'AI Visibility Control', 'tbsh-ai-visibility-control' ); ?>
				<span style="font-size: 14px; font-weight: normal; background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 12px; margin-left: 10px;">
					<?php printf( esc_html__( 'Free v%s', 'tbsh-ai-visibility-control' ), esc_html( $this->version ) ); ?>
				</span>
			</h1>
			<p><?php esc_html_e( 'Control what AI bots crawl, publish an automated llms.txt file, and monitor incoming traffic referrals from AI search engines.', 'tbsh-ai-visibility-control' ); ?></p>
		</div>
	</header>

	<!-- Tabs Navigation -->
	<nav class="tavc-tabs">
		<a href="#overview" class="tavc-tab-nav<?php echo 'overview' === $tavc_active_tab ? ' active' : ''; ?>" data-tab="overview">
			<span class="dashicons dashicons-dashboard"></span> <?php esc_html_e( 'Overview', 'tbsh-ai-visibility-control' ); ?>
		</a>
		<a href="#bots" class="tavc-tab-nav<?php echo 'bots' === $tavc_active_tab ? ' active' : ''; ?>" data-tab="bots">
			<span class="dashicons dashicons-buddicons-buddypress-logo"></span> <?php esc_html_e( 'AI Bots', 'tbsh-ai-visibility-control' ); ?>
		</a>
		<a href="#llmstxt" class="tavc-tab-nav<?php echo 'llmstxt' === $tavc_active_tab ? ' active' : ''; ?>" data-tab="llmstxt">
			<span class="dashicons dashicons-media-text"></span> <?php esc_html_e( 'llms.txt', 'tbsh-ai-visibility-control' ); ?>
		</a>
		<a href="#referrals" class="tavc-tab-nav<?php echo 'referrals' === $tavc_active_tab ? ' active' : ''; ?>" data-tab="referrals">
			<span class="dashicons dashicons-chart-area"></span> <?php esc_html_e( 'AI Referrals', 'tbsh-ai-visibility-control' ); ?>
		</a>
		<a href="#tools" class="tavc-tab-nav<?php echo 'tools' === $tavc_active_tab ? ' active' : ''; ?>" data-tab="tools">
			<span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Tools', 'tbsh-ai-visibility-control' ); ?>
		</a>
		<a href="#status" class="tavc-tab-nav<?php echo 'status' === $tavc_active_tab ? ' active' : ''; ?>" data-tab="status">
			<span class="dashicons dashicons-info-outline"></span> <?php esc_html_e( 'System Status', 'tbsh-ai-visibility-control' ); ?>
		</a>
	</nav>

	<!-- Main Layout -->
	<div class="tavc-grid">
		<!-- Left Main Content Column -->
		<main class="tavc-main-column">
			<!-- Overview Tab -->
			<div id="tavc-tab-overview" class="tavc-tab-content<?php echo 'overview' === $tavc_active_tab ? ' active' : ''; ?>">
				<?php require_once plugin_dir_path( __FILE__ ) . 'tavc-admin-overview.php'; ?>
			</div>

			<!-- AI Bots Tab -->
			<div id="tavc-tab-bots" class="tavc-tab-content<?php echo 'bots' === $tavc_active_tab ? ' active' : ''; ?>">
				<?php require_once plugin_dir_path( __FILE__ ) . 'tavc-admin-bots.php'; ?>
			</div>

			<!-- llms.txt Tab -->
			<div id="tavc-tab-llmstxt" class="tavc-tab-content<?php echo 'llmstxt' === $tavc_active_tab ? ' active' : ''; ?>">
				<?php require_once plugin_dir_path( __FILE__ ) . 'tavc-admin-llmstxt.php'; ?>
			</div>

			<!-- AI Referrals Tab -->
			<div id="tavc-tab-referrals" class="tavc-tab-content<?php echo 'referrals' === $tavc_active_tab ? ' active' : ''; ?>">
				<?php require_once plugin_dir_path( __FILE__ ) . 'tavc-admin-referrals.php'; ?>
			</div>

			<!-- Tools Tab -->
			<div id="tavc-tab-tools" class="tavc-tab-content<?php echo 'tools' === $tavc_active_tab ? ' active' : ''; ?>">
				<?php require_once plugin_dir_path( __FILE__ ) . 'tavc-admin-tools.php'; ?>
			</div>

			<!-- System Status Tab -->
			<div id="tavc-tab-status" class="tavc-tab-content<?php echo 'status' === $tavc_active_tab ? ' active' : ''; ?>">
				<?php require_once plugin_dir_path( __FILE__ ) . 'tavc-admin-status.php'; ?>
			</div>
		</main>

		<!-- Right Sidebar Column -->
		<aside class="tavc-sidebar-column">
			<!-- Pro Upgrade Card -->
			<div class="tavc-sidebar-box">
				<h3>
					<span class="dashicons dashicons-awards"></span> 
					<?php esc_html_e( 'Upgrade to Pro', 'tbsh-ai-visibility-control' ); ?>
				</h3>
				<p><?php esc_html_e( 'Take total control over AI crawlers and protect your content from unauthorized data harvesting.', 'tbsh-ai-visibility-control' ); ?></p>
				
				<ul class="tavc-pro-feature-list">
					<li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'llms-full.txt generation', 'tbsh-ai-visibility-control' ); ?></li>
					<li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Full markdown page export', 'tbsh-ai-visibility-control' ); ?></li>
					<li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Real-time AI crawl firewall', 'tbsh-ai-visibility-control' ); ?></li>
					<li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Bot rate limiting', 'tbsh-ai-visibility-control' ); ?></li>
					<li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'AI traffic trends & charts', 'tbsh-ai-visibility-control' ); ?></li>
					<li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'WooCommerce AI attribution', 'tbsh-ai-visibility-control' ); ?></li>
					<li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Advanced entity graph generation', 'tbsh-ai-visibility-control' ); ?></li>
				</ul>
				
				<p style="font-size: 12px; color: rgba(255, 255, 255, 0.85); margin: 15px 0 10px 0; line-height: 1.4;">
					<?php esc_html_e( 'We are currently running a Pro Beta program. Join early by sending us an email at', 'tbsh-ai-visibility-control' ); ?> <strong>support@techbysh.com</strong>
				</p>
				
				<a href="mailto:support@techbysh.com?subject=AI%20Visibility%20Control%20Pro%20Beta%20Request" class="tavc-pro-btn">
					<?php esc_html_e( 'Join Pro Beta', 'tbsh-ai-visibility-control' ); ?>
				</a>
			</div>

			<!-- Helpful Links Card -->
			<div class="tavc-card" style="padding: 20px;">
				<h4 style="margin: 0 0 12px 0; font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
					<span class="dashicons dashicons-sos" style="color: var(--tavc-primary);"></span>
					<?php esc_html_e( 'Documentation & Support', 'tbsh-ai-visibility-control' ); ?>
				</h4>
				<p style="font-size: 13px; color: var(--tavc-text-muted); line-height: 1.4; margin-bottom: 12px;">
					<?php esc_html_e( 'Need help configuring or want to learn more about how AI bots scan your site?', 'tbsh-ai-visibility-control' ); ?>
				</p>
				<p style="margin: 0; font-size: 13px; display: flex; flex-direction: column; gap: 8px;">
					<a href="https://www.techbysh.com/docs/tbsh-ai-visibility-control.html" target="_blank" rel="noopener noreferrer" style="text-decoration: none; font-weight: 500; color: var(--tavc-primary);">
						<?php esc_html_e( 'Read Documentation', 'tbsh-ai-visibility-control' ); ?> &rarr;
					</a>
					<a href="mailto:support@techbysh.com?subject=AI%20Visibility%20Control%20Support%20Inquiry" style="text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 5px; color: var(--tavc-text-muted);">
						<span class="dashicons dashicons-email" style="font-size: 16px; width: 16px; height: 16px; color: var(--tavc-primary);"></span>
						<span>support@techbysh.com</span>
					</a>
				</p>
			</div>
		</aside>
	</div>
</div>
