<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Tavc
 * @subpackage Tavc/admin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and registers hooks for backend settings,
 * menus, styles, diagnostics, and tools processing.
 *
 * @package    Tavc
 * @subpackage Tavc/admin
 * @author     Techbysh
 */
class TAVC_Admin {

	/**
	 * Admin page slug (also the plugin name). Part of saved URLs: do not change.
	 */
	const PAGE_SLUG = 'tbsh-ai-visibility-control';

	/**
	 * Screen ID / hook suffix of the top-level admin page.
	 */
	const SCREEN_ID = 'toplevel_page_tbsh-ai-visibility-control';

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Health checks computed in this request.
	 *
	 * @since    1.3.0
	 * @var      array|null
	 */
	private $health_status = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version           The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets and scripts for the admin area.
	 *
	 * @since    1.0.0
	 * @param    string    $hook    The current page hook name.
	 */
	public function enqueue_assets( $hook ) {
		if ( self::SCREEN_ID !== $hook ) {
			return;
		}

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/tavc-admin.css', array(), $this->version, 'all' );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/tavc-admin.js', array(), $this->version, true );

		wp_localize_script(
			$this->plugin_name,
			'tavcAdmin',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'healthNonce' => wp_create_nonce( 'tavc_health_check' ),
				'i18n'        => array(
					'healthDone'   => __( 'Health check complete.', 'tbsh-ai-visibility-control' ),
					'healthError'  => __( 'The health check could not be completed. Reload the page to try again.', 'tbsh-ai-visibility-control' ),
					'allowed'      => __( 'Allowed', 'tbsh-ai-visibility-control' ),
					'blocked'      => __( 'Blocked', 'tbsh-ai-visibility-control' ),
					/* translators: 1: number of blocked crawlers, 2: number of crawlers in the group. */
					'groupCount'   => __( '%1$s of %2$s blocked', 'tbsh-ai-visibility-control' ),
					'unsaved'      => __( 'Unsaved changes', 'tbsh-ai-visibility-control' ),
					'stepDone'     => __( 'Done:', 'tbsh-ai-visibility-control' ),
					/* translators: 1: completed steps, 2: total steps. */
					'stepsDone'    => __( '%1$s of %2$s done', 'tbsh-ai-visibility-control' ),
					'confirmOther' => __( 'You have unsaved changes in another section of this page. They will be lost if you save this section now. Continue?', 'tbsh-ai-visibility-control' ),
				),
			)
		);
	}

	/**
	 * Add the top-level "AI Visibility" admin menu.
	 *
	 * @since    1.0.0
	 * @since    1.3.0 Moved from Settings to a top-level menu.
	 */
	public function add_plugin_admin_menu() {
		add_menu_page(
			__( 'AI Visibility Control', 'tbsh-ai-visibility-control' ),
			__( 'AI Visibility', 'tbsh-ai-visibility-control' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'display_plugin_admin_page' ),
			'dashicons-visibility',
			81 // Just below Settings.
		);
	}

	/**
	 * URL of the plugin admin page.
	 *
	 * @since    1.3.0
	 * @param    array    $args    Extra query arguments, e.g. array( 'tab' => 'bots' ).
	 * @return   string
	 */
	public static function get_page_url( $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::PAGE_SLUG ), $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * Send the pre-1.3.0 address (Settings > AI Visibility Control) to the new page.
	 *
	 * Keeps bookmarks, documentation links and older summary emails working.
	 * Hooked to admin_init, which runs before WordPress rejects unknown pages.
	 *
	 * @since    1.3.0
	 */
	public function redirect_legacy_page_url() {
		global $pagenow;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only redirect.
		if ( 'options-general.php' !== $pagenow || ! isset( $_GET['page'] ) || self::PAGE_SLUG !== $_GET['page'] ) {
			return;
		}

		$args = array();
		foreach ( array( 'tab', 'tavc_range', 'tavc_msg', 'tavc_health', 'settings-updated' ) as $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only redirect.
			if ( isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only redirect.
				$args[ $key ] = sanitize_key( wp_unslash( $_GET[ $key ] ) );
			}
		}

		wp_safe_redirect( self::get_page_url( $args ) );
		exit;
	}

	/**
	 * Render the settings page view.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		$tavc_active_tab = $this->get_active_settings_tab();
		require_once plugin_dir_path( __FILE__ ) . 'partials/tavc-admin-display.php';
	}

	/**
	 * Register Settings API settings.
	 *
	 * @since    1.0.0
	 */
	public function register_plugin_settings() {
		register_setting(
			'tavc_bots_settings_group',
			'tavc_blocked_bots',
			array( $this, 'sanitize_blocked_bots' )
		);

		register_setting(
			'tavc_uninstall_settings_group',
			'tavc_delete_on_uninstall',
			array( $this, 'sanitize_delete_on_uninstall' )
		);

		// Each form saves its own group so saving one never resets another.
		register_setting(
			'tavc_custom_robots_settings_group',
			'tavc_custom_robots_rules',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'TAVC_Settings', 'sanitize_custom_robots_rules' ),
				'default'           => array(),
			)
		);

		register_setting(
			'tavc_content_signals_settings_group',
			'tavc_content_signals',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'TAVC_Settings', 'sanitize_content_signals' ),
				'default'           => array(),
			)
		);

		register_setting(
			'tavc_llms_settings_group',
			'tavc_llms_txt_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'TAVC_Settings', 'sanitize_llms_settings' ),
				'default'           => array(),
			)
		);

		register_setting(
			'tavc_tracking_settings_group',
			'tavc_tracking_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'TAVC_Settings', 'sanitize_tracking_settings' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize blocked bots setting input array.
	 *
	 * @since    1.0.0
	 * @since    1.3.0 Only known crawler slugs are stored.
	 * @param    array    $input    Incoming array of bots values.
	 * @return   array              Sanitized options array.
	 */
	public function sanitize_blocked_bots( $input ) {
		if ( ! is_array( $input ) ) {
			$existing = get_option( 'tavc_blocked_bots', array() );
			return is_array( $existing ) ? $existing : array();
		}

		$sanitized = TAVC_Bots::get_default_settings();

		foreach ( $input as $bot_slug => $value ) {
			$bot_slug = sanitize_key( $bot_slug );
			if ( array_key_exists( $bot_slug, $sanitized ) ) {
				$sanitized[ $bot_slug ] = intval( $value ) === 1 ? 1 : 0;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize the uninstall data-removal checkbox.
	 *
	 * @since    1.1.0
	 * @param    mixed    $input    Incoming value.
	 * @return   int                1 when enabled, otherwise 0.
	 */
	public function sanitize_delete_on_uninstall( $input ) {
		return absint( $input ) === 1 ? 1 : 0;
	}

	/**
	 * Allowed settings tabs for query-string restoration after tool redirects.
	 *
	 * @since    1.1.0
	 * @return   array
	 */
	private function get_allowed_settings_tabs() {
		return array( 'overview', 'bots', 'llmstxt', 'referrals', 'tools', 'status' );
	}

	/**
	 * Resolve the tab that should be visible when the settings page renders.
	 *
	 * @since    1.1.0
	 * @return   string
	 */
	private function get_active_settings_tab() {
		$allowed = $this->get_allowed_settings_tabs();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['tab'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
			if ( in_array( $tab, $allowed, true ) ) {
				return $tab;
			}
		}

		return 'overview';
	}

	/**
	 * Handle admin post tool actions.
	 *
	 * @since    1.0.0
	 */
	public function handle_tool_actions() {
		// Nonce check
		check_admin_referer( 'tavc_tool_nonce', '_wpnonce' );

		// Capability check
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'tbsh-ai-visibility-control' ) );
		}

		$tool   = isset( $_GET['tool'] ) ? sanitize_key( $_GET['tool'] ) : '';
		$tab    = 'tools';
		$msg    = '';

		switch ( $tool ) {
			case 'rebuild_cache':
				$llms_txt = new TAVC_LLMS_Txt();
				$llms_txt->register_rewrite_rule();
				flush_rewrite_rules();
				$llms_txt->clear_llms_txt_cache();

				// Generate immediately to prime the cache (llms-full.txt is rebuilt on its next request)
				$content = $llms_txt->generate_llms_txt();
				set_transient( TAVC_LLMS_Txt::CACHE_KEY, $content, WEEK_IN_SECONDS );
				update_option( 'tavc_llms_txt_last_rebuild', current_time( 'timestamp' ), 'no' );

				// Delete health checks transients so they rebuild
				delete_transient( 'tavc_health_llms_txt' );
				delete_transient( 'tavc_health_robots' );
				
				$msg = 'cache_rebuilt';
				$tab = 'llmstxt';
				break;

			case 'clear_logs':
				TAVC_DB::clear_referral_logs();
				$msg = 'logs_cleared';
				$tab = 'referrals';
				break;

			case 'reset_settings':
				update_option( 'tavc_blocked_bots', TAVC_Bots::get_default_settings() );

				$msg = 'settings_reset';
				$tab = 'bots';
				break;

			case 'clear_crawler_logs':
				TAVC_DB::clear_crawler_logs();
				$msg = 'crawler_logs_cleared';
				$tab = 'bots';
				break;

			case 'send_digest':
				$msg = TAVC_Digest::send() ? 'digest_sent' : 'digest_failed';
				break;

			case 'export_csv':
				$this->export_referrals_csv();
				exit;

			default:
				break;
		}

		// Redirect back
		wp_safe_redirect(
			self::get_page_url(
				array(
					'tab'      => $tab,
					'tavc_msg' => $msg,
				)
			)
		);
		exit;
	}

	/**
	 * Export referral database logs to CSV format.
	 *
	 * @since    1.0.0
	 */
	private function export_referrals_csv() {
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="tbsh-ai-referrals-log-' . current_time( 'Y-m-d' ) . '.csv"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );
		
		// Header row
		fputcsv( $output, array(
			__( 'Timestamp', 'tbsh-ai-visibility-control' ),
			__( 'Source AI Bot', 'tbsh-ai-visibility-control' ),
			__( 'Destination Page URL', 'tbsh-ai-visibility-control' ),
			__( 'Detected Via', 'tbsh-ai-visibility-control' )
		) );

		// Data rows
		$logs = TAVC_DB::get_all_referrals_for_csv();
		foreach ( $logs as $log ) {
			fputcsv( $output, array(
				$log->timestamp,
				self::csv_safe( $log->referrer ),
				self::csv_safe( $log->target_url ),
				TAVC_AI_Sources::get_method_label( isset( $log->method ) ? $log->method : '' )
			) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $output );
		exit;
	}

	/**
	 * Display custom feedback notices in admin backend.
	 *
	 * @since    1.0.0
	 */
	public function display_admin_notices() {
		// Ensure we are only showing notifications on our settings page
		$screen = get_current_screen();
		if ( ! $screen || self::SCREEN_ID !== $screen->id ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$msg = isset( $_GET['tavc_msg'] ) ? sanitize_key( wp_unslash( $_GET['tavc_msg'] ) ) : '';
		if ( empty( $msg ) ) {
			return;
		}

		$class   = 'notice notice-success is-dismissible';
		$message = '';

		switch ( $msg ) {
			case 'cache_rebuilt':
				$message = __( 'The llms.txt cache file has been rebuilt successfully.', 'tbsh-ai-visibility-control' );
				break;
			case 'logs_cleared':
				$message = __( 'AI Referral visitor logs have been deleted successfully.', 'tbsh-ai-visibility-control' );
				break;
			case 'settings_reset':
				$message = __( 'AI Bot Manager rules have been reset to defaults.', 'tbsh-ai-visibility-control' );
				break;
			case 'crawler_logs_cleared':
				$message = __( 'AI crawler visit logs have been deleted successfully.', 'tbsh-ai-visibility-control' );
				break;
			case 'digest_sent':
				$message = sprintf(
					/* translators: %s: email address. */
					__( 'A test summary email was sent to %s.', 'tbsh-ai-visibility-control' ),
					TAVC_Settings::get_digest_recipient()
				);
				break;
			case 'digest_failed':
				$class   = 'notice notice-error is-dismissible';
				$message = __( 'The summary email could not be sent. Check that your site can send email, for example with an SMTP plugin.', 'tbsh-ai-visibility-control' );
				break;
			default:
				return;
		}

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	/**
	 * Compile site metrics diagnostics check.
	 *
	 * @since    1.0.0
	 * @return   array    Diagnoses metrics arrays.
	 */
	public function get_health_status() {
		// The Overview and Status tabs both show the checks; compute them once per request.
		if ( null !== $this->health_status ) {
			return $this->health_status;
		}

		$status = array();

		// 1. Permalinks
		$permalink_structure = get_option( 'permalink_structure' );
		if ( empty( $permalink_structure ) ) {
			$status['permalinks'] = array(
				'status'         => 'fail',
				'title'          => __( 'Pretty Permalinks Disabled', 'tbsh-ai-visibility-control' ),
				'desc'           => __( 'Dynamic virtual routing requires custom URL structure.', 'tbsh-ai-visibility-control' ),
				'recommendation' => __( 'Navigate to Settings > Permalinks and change from Plain to Post Name.', 'tbsh-ai-visibility-control' ),
			);
		} else {
			$status['permalinks'] = array(
				'status' => 'pass',
				'title'  => __( 'Pretty Permalinks Enabled', 'tbsh-ai-visibility-control' ),
				'desc'   => __( 'URL structure supports custom rewrite mapping.', 'tbsh-ai-visibility-control' ),
			);
		}

		// 2. llms.txt Reachability
		$llms_check = get_transient( 'tavc_health_llms_txt' );
		if ( false === $llms_check ) {
			$response = $this->probe_public_endpoint( home_url( '/llms.txt' ) );

			if ( $this->response_looks_like_plain_text( $response ) ) {
				$llms_check = 'pass';
			} else {
				$llms_check = 'fail';
			}
			set_transient( 'tavc_health_llms_txt', $llms_check, HOUR_IN_SECONDS );
		}

		if ( $llms_check === 'pass' ) {
			$status['llmstxt'] = array(
				'status' => 'pass',
				'title'  => __( 'llms.txt Endpoint Reachable', 'tbsh-ai-visibility-control' ),
				'desc'   => __( 'The site responded with a valid output on /llms.txt request.', 'tbsh-ai-visibility-control' ),
			);
		} else {
			$status['llmstxt'] = array(
				'status'         => 'fail',
				'title'          => __( 'llms.txt Endpoint Unreachable', 'tbsh-ai-visibility-control' ),
				'desc'           => __( 'Local request did not return a successful 200 response.', 'tbsh-ai-visibility-control' ),
				'recommendation' => __( 'Click "Rebuild llms.txt" to refresh the file and its web address.', 'tbsh-ai-visibility-control' ),
			);
		}

		// 3. robots.txt Reachability
		if ( file_exists( ABSPATH . 'robots.txt' ) ) {
			$robots_check = 'warning';
		} else {
			$robots_check = get_transient( 'tavc_health_robots' );
			if ( false === $robots_check ) {
				$response = $this->probe_public_endpoint( home_url( '/robots.txt' ) );

				if ( $this->response_looks_like_plain_text( $response ) ) {
					$robots_check = 'pass';
				} else {
					$robots_check = 'warning';
				}
				set_transient( 'tavc_health_robots', $robots_check, HOUR_IN_SECONDS );
			}
		}

		if ( $robots_check === 'pass' ) {
			$status['robots'] = array(
				'status' => 'pass',
				'title'  => __( 'robots.txt Reachable', 'tbsh-ai-visibility-control' ),
				'desc'   => __( 'The robots.txt file is loaded correctly by client crawlers.', 'tbsh-ai-visibility-control' ),
			);
		} else {
			$status['robots'] = array(
				'status'         => 'warning',
				'title'          => __( 'robots.txt Unreachable', 'tbsh-ai-visibility-control' ),
				'desc'           => __( 'Dynamic file could not be fetched or standard physical robots.txt block is present.', 'tbsh-ai-visibility-control' ),
				'recommendation' => __( 'Verify that no physical robots.txt file exists in root directory.', 'tbsh-ai-visibility-control' ),
			);
		}

		// 4. Search visibility
		$blog_public = get_option( 'blog_public' );
		if ( intval( $blog_public ) === 0 ) {
			$status['visibility'] = array(
				'status'         => 'warning',
				'title'          => __( 'Search Engines Discouraged', 'tbsh-ai-visibility-control' ),
				'desc'           => __( 'Your website has search engine indexing visibility disabled.', 'tbsh-ai-visibility-control' ),
				'recommendation' => __( 'Turn off "Discourage search engines from indexing this site" under Settings > Reading.', 'tbsh-ai-visibility-control' ),
			);
		} else {
			$status['visibility'] = array(
				'status' => 'pass',
				'title'  => __( 'Search Visibility Enabled', 'tbsh-ai-visibility-control' ),
				'desc'   => __( 'Standard search crawlers are allowed to parse content.', 'tbsh-ai-visibility-control' ),
			);
		}

		// 5. Site public
		if ( file_exists( ABSPATH . '.maintenance' ) ) {
			$status['public'] = array(
				'status'         => 'warning',
				'title'          => __( 'Site Under Maintenance', 'tbsh-ai-visibility-control' ),
				'desc'           => __( 'The site active state is blocked by maintenance flag.', 'tbsh-ai-visibility-control' ),
				'recommendation' => __( 'Remove the .maintenance file from root once updates are completed.', 'tbsh-ai-visibility-control' ),
			);
		} else {
			$status['public'] = array(
				'status' => 'pass',
				'title'  => __( 'Site State Public', 'tbsh-ai-visibility-control' ),
				'desc'   => __( 'No active maintenance or offline triggers detected.', 'tbsh-ai-visibility-control' ),
			);
		}

		$this->health_status = $status;

		return $status;
	}

	/**
	 * Print the health check block: a one-line summary followed by the list.
	 *
	 * @since    1.3.0
	 */
	public function render_health_section() {
		if ( ! $this->can_render_health_now() ) {
			// Loopback checks are not cached: show a placeholder and load them in the background.
			?>
			<div class="tavc-health-section" data-tavc-health="section" data-tavc-health-pending aria-busy="true">
				<p class="tavc-health-loading">
					<span class="spinner is-active" aria-hidden="true"></span>
					<?php esc_html_e( 'Checking whether your llms.txt and robots.txt files are reachable...', 'tbsh-ai-visibility-control' ); ?>
				</p>
				<noscript>
					<p><a href="<?php echo esc_url( self::get_page_url( array( 'tavc_health' => 'now' ) ) ); ?>"><?php esc_html_e( 'Run the check now', 'tbsh-ai-visibility-control' ); ?></a></p>
				</noscript>
			</div>
			<?php
			return;
		}

		$status = $this->get_health_status();

		echo '<div class="tavc-health-section" data-tavc-health="section">';
		$this->render_health_summary( $status );
		$this->render_health_list( $status );
		echo '</div>';
	}

	/**
	 * Whether health checks can be shown without slow loopback requests.
	 *
	 * True when the results are cached, already computed in this request, or
	 * explicitly requested (no-JavaScript fallback link).
	 *
	 * @since    1.3.0
	 * @return   bool
	 */
	private function can_render_health_now() {
		if ( null !== $this->health_status ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display switch.
		if ( isset( $_GET['tavc_health'] ) ) {
			return true;
		}

		if ( false === get_transient( 'tavc_health_llms_txt' ) ) {
			return false;
		}

		return file_exists( ABSPATH . 'robots.txt' ) || false !== get_transient( 'tavc_health_robots' );
	}

	/**
	 * AJAX: run the health checks and return the rendered markup.
	 *
	 * @since    1.3.0
	 */
	public function ajax_health_check() {
		check_ajax_referer( 'tavc_health_check', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to run this check.', 'tbsh-ai-visibility-control' ) ), 403 );
		}

		$status = $this->get_health_status();

		ob_start();
		$this->render_health_summary( $status );
		$this->render_health_list( $status );
		$section = ob_get_clean();

		ob_start();
		$this->render_llms_tile();
		$llms_tile = ob_get_clean();

		wp_send_json_success(
			array(
				'section'   => $section,
				'llms_tile' => $llms_tile,
			)
		);
	}

	/**
	 * Print the llms.txt status shown on the Overview tile.
	 *
	 * @since    1.3.0
	 */
	public function render_llms_tile() {
		if ( ! $this->can_render_health_now() ) {
			printf(
				'<span class="tavc-status-tag status-neutral" data-tavc-health="llms-tile">%s</span>',
				esc_html__( 'Checking...', 'tbsh-ai-visibility-control' )
			);
			return;
		}

		$status = $this->get_health_status();

		if ( isset( $status['llmstxt']['status'] ) && 'pass' === $status['llmstxt']['status'] ) {
			printf(
				'<span class="tavc-status-tag status-green"><span class="tavc-pulse" aria-hidden="true"></span> %s</span>',
				esc_html__( 'Live', 'tbsh-ai-visibility-control' )
			);
		} else {
			printf(
				'<span class="tavc-status-tag status-red">%s</span>',
				esc_html__( 'Not reachable', 'tbsh-ai-visibility-control' )
			);
		}
	}

	/**
	 * Print "All checks passed" or "N checks need attention".
	 *
	 * @since    1.3.0
	 * @param    array    $status    Result of get_health_status().
	 */
	public function render_health_summary( $status ) {
		$failed  = 0;
		$warning = 0;
		foreach ( $status as $check ) {
			if ( 'fail' === $check['status'] ) {
				$failed++;
			} elseif ( 'warning' === $check['status'] ) {
				$warning++;
			}
		}

		$issues = $failed + $warning;
		if ( 0 === $issues ) {
			$class = 'status-green';
			$text  = __( 'All checks passed', 'tbsh-ai-visibility-control' );
		} else {
			$class = $failed ? 'status-red' : 'status-yellow';
			/* translators: %s: number of health checks that need attention. */
			$text = sprintf( _n( '%s check needs attention', '%s checks need attention', $issues, 'tbsh-ai-visibility-control' ), number_format_i18n( $issues ) );
		}

		printf( '<p class="tavc-health-summary"><span class="tavc-status-tag %1$s">%2$s</span></p>', esc_attr( $class ), esc_html( $text ) );
	}

	/**
	 * Print the health check list.
	 *
	 * Problems are listed first. Each item carries a screen-reader status
	 * ("Passed", "Warning", "Problem") so the state never depends on color or
	 * symbols alone.
	 *
	 * @since    1.3.0
	 * @param    array    $status    Result of get_health_status().
	 */
	public function render_health_list( $status ) {
		$order  = array(
			'fail'    => 0,
			'warning' => 1,
			'pass'    => 2,
		);
		$labels = array(
			'pass'    => __( 'Passed:', 'tbsh-ai-visibility-control' ),
			'warning' => __( 'Warning:', 'tbsh-ai-visibility-control' ),
			'fail'    => __( 'Problem:', 'tbsh-ai-visibility-control' ),
		);
		$icons  = array(
			'pass'    => '✓',
			'warning' => '⚠',
			'fail'    => '✗',
		);

		uasort(
			$status,
			function ( $a, $b ) use ( $order ) {
				return $order[ $a['status'] ] - $order[ $b['status'] ];
			}
		);

		echo '<ul class="tavc-health-list">';
		foreach ( $status as $key => $check ) {
			$state  = isset( $labels[ $check['status'] ] ) ? $check['status'] : 'fail';
			$action = $this->get_health_action( $key, $state );
			?>
			<li class="tavc-health-item health-<?php echo esc_attr( $state ); ?>">
				<span class="tavc-health-icon" aria-hidden="true"><?php echo esc_html( $icons[ $state ] ); ?></span>
				<div>
					<div class="tavc-health-title">
						<span class="screen-reader-text"><?php echo esc_html( $labels[ $state ] ); ?> </span><?php echo esc_html( $check['title'] ); ?>
					</div>
					<div class="tavc-health-desc">
						<?php echo esc_html( $check['desc'] ); ?>
						<?php if ( ! empty( $check['recommendation'] ) ) : ?>
							<br><strong class="tavc-health-label"><?php esc_html_e( 'How to fix:', 'tbsh-ai-visibility-control' ); ?></strong> <?php echo esc_html( $check['recommendation'] ); ?>
						<?php endif; ?>
					</div>
					<?php if ( $action ) : ?>
						<a href="<?php echo esc_url( $action['url'] ); ?>" class="button button-small tavc-health-action"><?php echo esc_html( $action['label'] ); ?></a>
					<?php endif; ?>
				</div>
			</li>
			<?php
		}
		echo '</ul>';
	}

	/**
	 * Button that fixes, or leads to the fix for, a failing health check.
	 *
	 * @since    1.3.0
	 * @param    string    $key      Health check key.
	 * @param    string    $state    pass, warning or fail.
	 * @return   array|null          array( 'url', 'label' ) or null.
	 */
	private function get_health_action( $key, $state ) {
		if ( 'pass' === $state ) {
			return null;
		}

		switch ( $key ) {
			case 'permalinks':
				return array(
					'url'   => admin_url( 'options-permalink.php' ),
					'label' => __( 'Open Permalink Settings', 'tbsh-ai-visibility-control' ),
				);
			case 'llmstxt':
				return array(
					'url'   => wp_nonce_url( admin_url( 'admin-post.php?action=tavc_tool_action&tool=rebuild_cache' ), 'tavc_tool_nonce' ),
					'label' => __( 'Rebuild llms.txt', 'tbsh-ai-visibility-control' ),
				);
			case 'visibility':
				return array(
					'url'   => admin_url( 'options-reading.php' ),
					'label' => __( 'Open Reading Settings', 'tbsh-ai-visibility-control' ),
				);
			default:
				return null;
		}
	}

	/**
	 * Fetch a public endpoint for health diagnostics.
	 *
	 * @since    1.1.0
	 * @param    string    $url    Absolute URL to request.
	 * @return   array|WP_Error    HTTP response.
	 */
	private function probe_public_endpoint( $url ) {
		return wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 5,
				'redirection'         => 2,
				'limit_response_size' => 65536,
				// Same as WordPress core loopback checks: self-signed certificates on
				// local and staging sites must not report the endpoint as unreachable.
				'sslverify'           => apply_filters( 'https_local_ssl_verify', false ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core hook.
			)
		);
	}

	/**
	 * Whether a loopback response looks like a successful plain-text file, not a theme HTML page.
	 *
	 * @since    1.1.0
	 * @param    array|WP_Error    $response    HTTP response.
	 * @return   bool
	 */
	private function response_looks_like_plain_text( $response ) {
		if ( is_wp_error( $response ) ) {
			return false;
		}

		if ( (int) wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( ! is_string( $body ) || '' === trim( $body ) ) {
			return false;
		}

		return 1 !== preg_match( '/<\s*html/i', $body );
	}

	/**
	 * Query post counts for indexing report.
	 *
	 * @since    1.0.0
	 * @return   array    Counts of published content and rebuild dates.
	 */
	public function get_discoverability_stats() {
		// Counts
		$posts_stats = wp_count_posts( 'post' );
		$pages_stats = wp_count_posts( 'page' );

		$posts_count = isset( $posts_stats->publish ) ? intval( $posts_stats->publish ) : 0;
		$pages_count = isset( $pages_stats->publish ) ? intval( $pages_stats->publish ) : 0;

		// Sum up draft, private, pending, trash for both post and page
		$excluded_count = 0;
		$ignored_statuses = array( 'draft', 'private', 'pending', 'trash', 'future' );
		
		foreach ( $ignored_statuses as $status ) {
			if ( isset( $posts_stats->$status ) ) {
				$excluded_count += intval( $posts_stats->$status );
			}
			if ( isset( $pages_stats->$status ) ) {
				$excluded_count += intval( $pages_stats->$status );
			}
		}

		$last_rebuild = get_option( 'tavc_llms_txt_last_rebuild', 0 );

		return array(
			'posts_count'    => $posts_count,
			'pages_count'    => $pages_count,
			'excluded_count' => $excluded_count,
			'last_rebuild'   => intval( $last_rebuild ),
		);
	}

	/**
	 * Prevent spreadsheet formula injection in exported CSV cells.
	 *
	 * @since    1.3.0
	 * @param    string    $value    Cell value.
	 * @return   string
	 */
	private static function csv_safe( $value ) {
		$value = (string) $value;
		if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $value;
		}

		return $value;
	}

	/**
	 * One-line summary of crawler fetches for a file, e.g. "12 (GPTBot 8, ClaudeBot 4)".
	 *
	 * @since    1.3.0
	 * @param    array    $fetches        Crawler slug => hits.
	 * @param    array    $definitions    Crawler definitions.
	 * @return   string
	 */
	public function format_fetch_summary( $fetches, $definitions ) {
		$settings = TAVC_Settings::get_tracking_settings();
		if ( empty( $settings['crawler_logging'] ) ) {
			return __( 'Crawler logging is off.', 'tbsh-ai-visibility-control' );
		}

		if ( empty( $fetches ) ) {
			return __( 'Not fetched by a known AI crawler yet.', 'tbsh-ai-visibility-control' );
		}

		$parts = array();
		foreach ( array_slice( $fetches, 0, 4, true ) as $slug => $hits ) {
			$name    = isset( $definitions[ $slug ] ) ? $definitions[ $slug ]['name'] : $slug;
			$parts[] = $name . ' ' . number_format_i18n( $hits );
		}

		return sprintf(
			/* translators: 1: total number of fetches, 2: list such as "GPTBot 8, ClaudeBot 4". */
			__( '%1$s fetches (%2$s)', 'tbsh-ai-visibility-control' ),
			number_format_i18n( array_sum( $fetches ) ),
			implode( ', ', $parts )
		);
	}

	/**
	 * Referral report ranges.
	 *
	 * @since    1.3.0
	 * @return   array    Range key => label.
	 */
	public function get_referral_ranges() {
		return array(
			'7'   => __( 'Last 7 days', 'tbsh-ai-visibility-control' ),
			'30'  => __( 'Last 30 days', 'tbsh-ai-visibility-control' ),
			'90'  => __( 'Last 90 days', 'tbsh-ai-visibility-control' ),
			'all' => __( 'All time', 'tbsh-ai-visibility-control' ),
		);
	}

	/**
	 * Range selected on the AI Traffic tab.
	 *
	 * @since    1.3.0
	 * @return   string    '7', '30', '90' or 'all' (default, matches the pre-1.3.0 report).
	 */
	public function get_active_referral_range() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$range = isset( $_GET['tavc_range'] ) ? sanitize_key( wp_unslash( $_GET['tavc_range'] ) ) : 'all';

		return array_key_exists( $range, $this->get_referral_ranges() ) ? $range : 'all';
	}

	/**
	 * Register the "Exclude from llms.txt" box for the content types listed in llms.txt.
	 *
	 * @since    1.3.0
	 */
	public function register_meta_boxes() {
		$settings = TAVC_Settings::get_llms_settings();

		add_meta_box(
			'tavc-llms-visibility',
			__( 'AI Visibility', 'tbsh-ai-visibility-control' ),
			array( $this, 'render_llms_meta_box' ),
			$settings['post_types'],
			'side',
			'low'
		);
	}

	/**
	 * Render the "Exclude from llms.txt" box.
	 *
	 * @since    1.3.0
	 * @param    WP_Post    $post    Current post.
	 */
	public function render_llms_meta_box( $post ) {
		$excluded = '1' === (string) get_post_meta( $post->ID, TAVC_LLMS_Txt::EXCLUDE_META_KEY, true );

		wp_nonce_field( 'tavc_llms_meta_box', 'tavc_llms_meta_nonce' );
		?>
		<p>
			<label for="tavc_llms_exclude">
				<input type="checkbox" name="tavc_llms_exclude" id="tavc_llms_exclude" value="1" <?php checked( $excluded ); ?> aria-describedby="tavc_llms_exclude_help">
				<?php esc_html_e( 'Exclude from llms.txt', 'tbsh-ai-visibility-control' ); ?>
			</label>
		</p>
		<p class="description" id="tavc_llms_exclude_help">
			<?php esc_html_e( 'Leaves this content out of llms.txt and llms-full.txt. It does not block search engines or AI crawlers.', 'tbsh-ai-visibility-control' ); ?>
		</p>
		<?php
	}

	/**
	 * Save the "Exclude from llms.txt" box.
	 *
	 * @since    1.3.0
	 * @param    int        $post_id    Post ID.
	 * @param    WP_Post    $post       Post object.
	 */
	public function save_llms_meta_box( $post_id, $post = null ) {
		// Only handle saves that come from the editor form containing the box.
		if ( ! isset( $_POST['tavc_llms_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tavc_llms_meta_nonce'] ) ), 'tavc_llms_meta_box' ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! empty( $_POST['tavc_llms_exclude'] ) ) {
			update_post_meta( $post_id, TAVC_LLMS_Txt::EXCLUDE_META_KEY, '1' );
		} else {
			delete_post_meta( $post_id, TAVC_LLMS_Txt::EXCLUDE_META_KEY );
		}

		// The flag can change after the core save_post cache clear has already run.
		delete_transient( TAVC_LLMS_Txt::CACHE_KEY );
		delete_transient( TAVC_LLMS_Txt::FULL_CACHE_KEY );
	}

	/**
	 * Suggest privacy policy text under Settings > Privacy.
	 *
	 * @since    1.3.0
	 */
	public function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content  = '<p class="privacy-policy-tutorial">' . esc_html__( 'AI Visibility Control does not store personal data. Suggested text:', 'tbsh-ai-visibility-control' ) . '</p>';
		$content .= '<p>' . esc_html__( 'When you arrive on this website from an AI assistant such as ChatGPT, Claude or Perplexity, we record the time of the visit, the name of the AI assistant and the page you visited. We do not store your IP address: it is used in hashed form for up to five minutes to avoid counting the same visit twice, and is then discarded. No cookies are set for this purpose.', 'tbsh-ai-visibility-control' ) . '</p>';

		if ( TAVC_WooCommerce::is_available() ) {
			$content .= '<p>' . esc_html__( 'If you place an order after arriving from an AI assistant, the name of that assistant is saved with your order.', 'tbsh-ai-visibility-control' ) . '</p>';
		}

		wp_add_privacy_policy_content( __( 'AI Visibility Control', 'tbsh-ai-visibility-control' ), wp_kses_post( $content ) );
	}

	/**
	 * Add settings action link to plugin listing page.
	 *
	 * @since    1.0.0
	 * @param    array    $links    Existing links.
	 * @return   array              Modified links.
	 */
	public function add_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( self::get_page_url() ) . '">' . esc_html__( 'Settings', 'tbsh-ai-visibility-control' ) . '</a>';
		$support_link = '<a href="mailto:support@techbysh.com?subject=AI%20Visibility%20Control%20Pro%20Beta%20Request" style="font-weight: bold; color: #3858e9;">' . esc_html__( 'Pro Beta', 'tbsh-ai-visibility-control' ) . '</a>';
		array_unshift( $links, $support_link );
		array_unshift( $links, $settings_link );
		return $links;
	}
}
