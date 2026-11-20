<?php
/**
 * Help and support card (shown on the Status tab).
 *
 * @link       https://techbysh.com
 * @since      2.0.0
 *
 * @package    Tavc
 * @subpackage Tavc/admin/partials
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;
?>
<div class="tavc-card">
	<h2 class="tavc-card-title">
		<span aria-hidden="true" class="dashicons dashicons-sos"></span>
		<?php esc_html_e( 'Help & Support', 'tbsh-ai-visibility-control' ); ?>
	</h2>
	<p class="tavc-intro">
		<?php esc_html_e( 'Need help configuring the plugin or have a question about how AI crawlers read your site?', 'tbsh-ai-visibility-control' ); ?>
	</p>
	<ul class="tavc-link-list">
		<li>
			<a href="https://wordpress.org/support/plugin/tbsh-ai-visibility-control/" target="_blank" rel="noopener noreferrer" class="tavc-text-link">
				<?php esc_html_e( 'Ask in the WordPress.org support forum', 'tbsh-ai-visibility-control' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'tbsh-ai-visibility-control' ); ?></span>
				<span aria-hidden="true" class="dashicons dashicons-external tavc-icon-sm"></span>
			</a>
		</li>
		<li>
			<a href="mailto:support@techbysh.com?subject=AI%20Visibility%20Control%20Support%20Inquiry" class="tavc-text-link">
				<span aria-hidden="true" class="dashicons dashicons-email tavc-icon-md"></span>
				<?php
				/* translators: %s: support email address. */
				printf( esc_html__( 'Email support: %s', 'tbsh-ai-visibility-control' ), 'support@techbysh.com' );
				?>
			</a>
		</li>
	</ul>
</div>
