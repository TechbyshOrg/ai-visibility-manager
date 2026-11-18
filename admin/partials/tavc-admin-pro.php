<?php
/**
 * Compact Pro beta box (shown at the end of the Overview tab only).
 *
 * @link       https://techbysh.com
 * @since      1.3.0
 *
 * @package    Tavc
 * @subpackage Tavc/admin/partials
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;
?>
<section class="tavc-pro-box" aria-labelledby="tavc-pro-title">
	<div class="tavc-pro-box-text">
		<h2 id="tavc-pro-title">
			<span aria-hidden="true" class="dashicons dashicons-awards"></span>
			<?php esc_html_e( 'Pro beta', 'tbsh-ai-visibility-control' ); ?>
		</h2>
		<p><?php esc_html_e( 'Coming in Pro: a real-time AI crawl firewall, bot rate limiting, full Markdown page export and entity graph generation.', 'tbsh-ai-visibility-control' ); ?></p>
	</div>
	<a href="mailto:support@techbysh.com?subject=AI%20Visibility%20Control%20Pro%20Beta%20Request" class="tavc-pro-btn">
		<?php esc_html_e( 'Join the Pro beta', 'tbsh-ai-visibility-control' ); ?>
	</a>
</section>
