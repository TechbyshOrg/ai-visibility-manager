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

// Tab slug => array( icon, label ). Slugs are part of saved URLs and must not change.
$tavc_tabs = array(
	'overview'  => array( 'dashicons-dashboard', __( 'Overview', 'tbsh-ai-visibility-control' ) ),
	'bots'      => array( 'dashicons-buddicons-buddypress-logo', __( 'AI Crawlers', 'tbsh-ai-visibility-control' ) ),
	'llmstxt'   => array( 'dashicons-media-text', __( 'llms.txt', 'tbsh-ai-visibility-control' ) ),
	'referrals' => array( 'dashicons-chart-area', __( 'AI Traffic', 'tbsh-ai-visibility-control' ) ),
	'tools'     => array( 'dashicons-admin-settings', __( 'Settings & Tools', 'tbsh-ai-visibility-control' ) ),
	'status'    => array( 'dashicons-info-outline', __( 'Status', 'tbsh-ai-visibility-control' ) ),
);

// The partial file name is built from the slug, so only known slugs are accepted.
if ( ! isset( $tavc_tabs[ $tavc_active_tab ] ) ) {
	$tavc_active_tab = 'overview';
}
?>
<div class="wrap tavc-wrap">
	<!-- Plugin Header -->
	<header class="tavc-header">
		<div class="tavc-header-content">
			<h1>
				<span aria-hidden="true" class="dashicons dashicons-visibility"></span>
				<?php esc_html_e( 'AI Visibility Control', 'tbsh-ai-visibility-control' ); ?>
				<span class="tavc-version-badge">
					<?php
					/* translators: %s: plugin version number. */
					printf( esc_html__( 'Version %s', 'tbsh-ai-visibility-control' ), esc_html( $this->version ) );
					?>
				</span>
			</h1>
			<p><?php esc_html_e( 'Choose which AI crawlers can read your site, publish an llms.txt file, and see the visits AI assistants send you.', 'tbsh-ai-visibility-control' ); ?></p>
		</div>
	</header>

	<!-- WordPress moves admin notices (e.g. "Settings saved") right after this marker instead of inside the header. -->
	<hr class="wp-header-end">

	<?php
	// Top-level pages do not get WordPress's options-head.php, so print "Settings saved." and
	// settings errors here (this also lets the AI Crawlers tab show custom-rule errors inline).
	// On the AI Crawlers tab, custom-rule errors are shown next to the rules field instead,
	// so they are not printed twice.
	$tavc_error_settings = array_unique( wp_list_pluck( get_settings_errors(), 'setting' ) );
	foreach ( $tavc_error_settings as $tavc_error_setting ) {
		if ( 'bots' !== $tavc_active_tab || 'tavc_custom_robots_rules' !== $tavc_error_setting ) {
			settings_errors( $tavc_error_setting );
		}
	}
	?>

	<!-- Tabs Navigation: real links, so each tab loads (and runs its queries) only when opened. -->
	<nav class="tavc-tabs" aria-label="<?php esc_attr_e( 'AI Visibility Control sections', 'tbsh-ai-visibility-control' ); ?>">
		<?php foreach ( $tavc_tabs as $tavc_tab_slug => $tavc_tab ) : ?>
			<a href="<?php echo esc_url( TAVC_Admin::get_page_url( array( 'tab' => $tavc_tab_slug ) ) ); ?>" class="tavc-tab-nav<?php echo $tavc_tab_slug === $tavc_active_tab ? ' active' : ''; ?>"<?php echo $tavc_tab_slug === $tavc_active_tab ? ' aria-current="page"' : ''; ?> data-tab="<?php echo esc_attr( $tavc_tab_slug ); ?>">
				<span aria-hidden="true" class="dashicons <?php echo esc_attr( $tavc_tab[0] ); ?>"></span> <?php echo esc_html( $tavc_tab[1] ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<!-- Announces background updates (e.g. "Health check complete.") to screen readers. -->
	<div id="tavc-live" class="screen-reader-text" role="status" aria-live="polite"></div>

	<main class="tavc-main-column">
		<div id="tavc-tab-<?php echo esc_attr( $tavc_active_tab ); ?>" class="tavc-tab-content active">
			<?php require plugin_dir_path( __FILE__ ) . 'tavc-admin-' . $tavc_active_tab . '.php'; ?>
		</div>
	</main>
</div>
