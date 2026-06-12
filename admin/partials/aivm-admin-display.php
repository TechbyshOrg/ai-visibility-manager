<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Aivm
 * @subpackage Aivm/admin/partials
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

// Retrieve tab parameter if set
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'overview';
?>
<div class="wrap aivm-wrap">
	<!-- Plugin Header -->
	<header class="aivm-header">
		<div class="aivm-header-content">
			<h1>
				<span class="dashicons dashicons-visibility"></span> 
				<?php esc_html_e( 'AI Visibility Manager', 'ai-visibility-manager' ); ?>
				<span style="font-size: 14px; font-weight: normal; background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 12px; margin-left: 10px;">
					<?php esc_html_e( 'Free v1.0.0', 'ai-visibility-manager' ); ?>
				</span>
			</h1>
			<p><?php esc_html_e( 'Control what AI bots crawl, publish an automated llms.txt file, and monitor incoming traffic referrals from AI search engines.', 'ai-visibility-manager' ); ?></p>
		</div>
	</header>

	<!-- Tabs Navigation -->
	<nav class="aivm-tabs">
		<a href="#overview" class="aivm-tab-nav active" data-tab="overview">
			<span class="dashicons dashicons-dashboard"></span> <?php esc_html_e( 'Overview', 'ai-visibility-manager' ); ?>
		</a>
		<a href="#bots" class="aivm-tab-nav" data-tab="bots">
			<span class="dashicons dashicons-buddicons-buddypress-logo"></span> <?php esc_html_e( 'AI Bots', 'ai-visibility-manager' ); ?>
		</a>
		<a href="#llmstxt" class="aivm-tab-nav" data-tab="llmstxt">
			<span class="dashicons dashicons-media-text"></span> <?php esc_html_e( 'llms.txt', 'ai-visibility-manager' ); ?>
		</a>
		<a href="#referrals" class="aivm-tab-nav" data-tab="referrals">
			<span class="dashicons dashicons-chart-area"></span> <?php esc_html_e( 'AI Referrals', 'ai-visibility-manager' ); ?>
		</a>
		<a href="#tools" class="aivm-tab-nav" data-tab="tools">
			<span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Tools', 'ai-visibility-manager' ); ?>
		</a>
		<a href="#status" class="aivm-tab-nav" data-tab="status">
			<span class="dashicons dashicons-info-outline"></span> <?php esc_html_e( 'System Status', 'ai-visibility-manager' ); ?>
		</a>
	</nav>

	<!-- Main Layout -->
	<div class="aivm-grid">
		<!-- Left Main Content Column -->
		<main class="aivm-main-column">
			<!-- Overview Tab -->
			<div id="aivm-tab-overview" class="aivm-tab-content active">
				<?php require_once plugin_dir_path( __FILE__ ) . 'aivm-admin-overview.php'; ?>
			</div>

			<!-- AI Bots Tab -->
			<div id="aivm-tab-bots" class="aivm-tab-content">
				<?php require_once plugin_dir_path( __FILE__ ) . 'aivm-admin-bots.php'; ?>
			</div>

			<!-- llms.txt Tab -->
			<div id="aivm-tab-llmstxt" class="aivm-tab-content">
				<?php require_once plugin_dir_path( __FILE__ ) . 'aivm-admin-llmstxt.php'; ?>
			</div>

			<!-- AI Referrals Tab -->
			<div id="aivm-tab-referrals" class="aivm-tab-content">
				<?php require_once plugin_dir_path( __FILE__ ) . 'aivm-admin-referrals.php'; ?>
			</div>

			<!-- Tools Tab -->
			<div id="aivm-tab-tools" class="aivm-tab-content">
				<?php require_once plugin_dir_path( __FILE__ ) . 'aivm-admin-tools.php'; ?>
			</div>

			<!-- System Status Tab -->
			<div id="aivm-tab-status" class="aivm-tab-content">
				<?php require_once plugin_dir_path( __FILE__ ) . 'aivm-admin-status.php'; ?>
			</div>
		</main>

		<!-- Right Sidebar Column -->
		<aside class="aivm-sidebar-column">
			<!-- Pro Upgrade Card -->
			<div class="aivm-sidebar-box">
				<h3>
					<span class="dashicons dashicons-awards"></span> 
					<?php esc_html_e( 'Upgrade to Pro', 'ai-visibility-manager' ); ?>
				</h3>
				<p><?php esc_html_e( 'Take total control over AI crawlers and protect your content from unauthorized data harvesting.', 'ai-visibility-manager' ); ?></p>
				
				<ul class="aivm-pro-feature-list">
					<li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'llms-full.txt generation', 'ai-visibility-manager' ); ?></li>
					<li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Full markdown page export', 'ai-visibility-manager' ); ?></li>
					<li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Real-time AI crawl firewall', 'ai-visibility-manager' ); ?></li>
					<li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Bot rate limiting', 'ai-visibility-manager' ); ?></li>
					<li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'AI traffic trends & charts', 'ai-visibility-manager' ); ?></li>
					<li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'WooCommerce AI attribution', 'ai-visibility-manager' ); ?></li>
					<li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Advanced entity graph generation', 'ai-visibility-manager' ); ?></li>
				</ul>
				
				<div class="aivm-pro-btn" style="background: rgba(255, 255, 255, 0.15); color: #ffffff !important; cursor: default; border: 1px dashed rgba(255, 255, 255, 0.3);">
					<?php esc_html_e( 'Coming Soon', 'ai-visibility-manager' ); ?>
				</div>
			</div>

			<!-- Helpful Links Card -->
			<div class="aivm-card" style="padding: 20px;">
				<h4 style="margin: 0 0 12px 0; font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
					<span class="dashicons dashicons-sos" style="color: var(--aivm-primary);"></span>
					<?php esc_html_e( 'Documentation & Support', 'ai-visibility-manager' ); ?>
				</h4>
				<p style="font-size: 13px; color: var(--aivm-text-muted); line-height: 1.4; margin-bottom: 12px;">
					<?php esc_html_e( 'Need help configuring or want to learn more about how AI bots scan your site?', 'ai-visibility-manager' ); ?>
				</p>
				<p style="margin: 0; font-size: 13px;">
					<a href="https://www.techbysh.com/docs/ai-visibility-manager.html" target="_blank" rel="noopener noreferrer" style="text-decoration: none; font-weight: 500;">
						<?php esc_html_e( 'Read Documentation', 'ai-visibility-manager' ); ?> &rarr;
					</a>
				</p>
			</div>
		</aside>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const tabs = document.querySelectorAll('.aivm-tab-nav');
	const contents = document.querySelectorAll('.aivm-tab-content');
	
	tabs.forEach(tab => {
		tab.addEventListener('click', function(e) {
			e.preventDefault();
			const target = this.getAttribute('data-tab');
			
			tabs.forEach(t => t.classList.remove('active'));
			contents.forEach(c => c.classList.remove('active'));
			
			this.classList.add('active');
			const targetContent = document.getElementById('aivm-tab-' + target);
			if (targetContent) {
				targetContent.classList.add('active');
			}
			
			// Store active tab in URL hash/localStorage
			localStorage.setItem('aivm_active_tab', target);
			
			// Update URL hash without jumping page
			history.replaceState(null, null, '#' + target);
		});
	});
	
	// Restore active tab from hash or localStorage
	let activeTab = window.location.hash ? window.location.hash.substring(1) : localStorage.getItem('aivm_active_tab');
	
	if (activeTab) {
		const tabEl = document.querySelector(`.aivm-tab-nav[data-tab="${activeTab}"]`);
		if (tabEl) {
			// Trigger a click event
			tabEl.dispatchEvent(new Event('click'));
		}
	}
});
</script>
