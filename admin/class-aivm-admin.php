<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Aivm
 * @subpackage Aivm/admin
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
 * @package    Aivm
 * @subpackage Aivm/admin
 * @author     Techbysh
 */
class AIVM_Admin {

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
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 * @param    string    $hook    The current page hook name.
	 */
	public function enqueue_styles( $hook ) {
		if ( 'settings_page_ai-visibility-manager' !== $hook ) {
			return;
		}

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/aivm-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Add administration menu for Settings > AI Visibility Manager.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		add_options_page(
			__( 'AI Visibility Manager Settings', 'ai-visibility-manager' ),
			__( 'AI Visibility Manager', 'ai-visibility-manager' ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'display_plugin_admin_page' )
		);
	}

	/**
	 * Render the settings page view.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/aivm-admin-display.php';
	}

	/**
	 * Register Settings API settings.
	 *
	 * @since    1.0.0
	 */
	public function register_plugin_settings() {
		register_setting(
			'aivm_settings_group',
			'aivm_blocked_bots',
			array( $this, 'sanitize_blocked_bots' )
		);

		register_setting(
			'aivm_settings_group',
			'aivm_delete_on_uninstall',
			'absint'
		);
	}

	/**
	 * Sanitize blocked bots setting input array.
	 *
	 * @since    1.0.0
	 * @param    array    $input    Incoming array of bots values.
	 * @return   array              Sanitized options array.
	 */
	public function sanitize_blocked_bots( $input ) {
		$sanitized = array();
		
		if ( is_array( $input ) ) {
			foreach ( $input as $bot_slug => $value ) {
				$sanitized[ sanitize_key( $bot_slug ) ] = intval( $value ) === 1 ? 1 : 0;
			}
		}

		return $sanitized;
	}

	/**
	 * Handle admin post tool actions.
	 *
	 * @since    1.0.0
	 */
	public function handle_tool_actions() {
		// Nonce check
		check_admin_referer( 'aivm_tool_nonce', '_wpnonce' );

		// Capability check
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'ai-visibility-manager' ) );
		}

		$tool   = isset( $_GET['tool'] ) ? sanitize_key( $_GET['tool'] ) : '';
		$tab    = 'tools';
		$msg    = '';

		switch ( $tool ) {
			case 'rebuild_cache':
				$llms_txt = new AIVM_LLMS_Txt();
				$llms_txt->clear_llms_txt_cache();
				
				// Generate immediately to prime the cache
				$content = $llms_txt->generate_llms_txt();
				set_transient( 'aivm_llms_txt_cache', $content, WEEK_IN_SECONDS );
				update_option( 'aivm_llms_txt_last_rebuild', current_time( 'timestamp' ), 'no' );

				// Delete health checks transients so they rebuild
				delete_transient( 'aivm_health_llms_txt' );
				delete_transient( 'aivm_health_robots' );
				
				$msg = 'cache_rebuilt';
				$tab = 'llmstxt';
				break;

			case 'clear_logs':
				AIVM_DB::clear_referral_logs();
				$msg = 'logs_cleared';
				$tab = 'referrals';
				break;

			case 'reset_settings':
				$default_bots = array(
					'gptbot'            => 0,
					'claudebot'         => 0,
					'perplexitybot'     => 0,
					'google-extended'   => 0,
					'ccbot'             => 0,
					'amazonbot'         => 0,
					'applebot-extended' => 0,
				);
				update_option( 'aivm_blocked_bots', $default_bots );
				
				$msg = 'settings_reset';
				$tab = 'bots';
				break;

			case 'export_csv':
				$this->export_referrals_csv();
				exit;

			default:
				break;
		}

		// Redirect back
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => $this->plugin_name,
					'tab'      => $tab,
					'aivm_msg' => $msg,
				),
				admin_url( 'options-general.php' )
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
		header( 'Content-Disposition: attachment; filename="ai-referrals-log-' . current_time( 'Y-m-d' ) . '.csv"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );
		
		// Header row
		fputcsv( $output, array(
			__( 'Timestamp', 'ai-visibility-manager' ),
			__( 'Source AI Bot', 'ai-visibility-manager' ),
			__( 'Destination Page URL', 'ai-visibility-manager' )
		) );

		// Data rows
		$logs = AIVM_DB::get_all_referrals_for_csv();
		foreach ( $logs as $log ) {
			fputcsv( $output, array(
				$log->timestamp,
				$log->referrer,
				$log->target_url
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
		if ( ! $screen || 'settings_page_ai-visibility-manager' !== $screen->id ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$msg = isset( $_GET['aivm_msg'] ) ? sanitize_key( wp_unslash( $_GET['aivm_msg'] ) ) : '';
		if ( empty( $msg ) ) {
			return;
		}

		$class   = 'notice notice-success is-dismissible';
		$message = '';

		switch ( $msg ) {
			case 'cache_rebuilt':
				$message = __( 'The llms.txt cache file has been rebuilt successfully.', 'ai-visibility-manager' );
				break;
			case 'logs_cleared':
				$message = __( 'AI Referral visitor logs have been deleted successfully.', 'ai-visibility-manager' );
				break;
			case 'settings_reset':
				$message = __( 'AI Bot Manager rules have been reset to defaults.', 'ai-visibility-manager' );
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
		$status = array();

		// 1. Permalinks
		$permalink_structure = get_option( 'permalink_structure' );
		if ( empty( $permalink_structure ) ) {
			$status['permalinks'] = array(
				'status'         => 'fail',
				'title'          => __( 'Pretty Permalinks Disabled', 'ai-visibility-manager' ),
				'desc'           => __( 'Dynamic virtual routing requires custom URL structure.', 'ai-visibility-manager' ),
				'recommendation' => __( 'Navigate to Settings > Permalinks and change from Plain to Post Name.', 'ai-visibility-manager' ),
			);
		} else {
			$status['permalinks'] = array(
				'status' => 'pass',
				'title'  => __( 'Pretty Permalinks Enabled', 'ai-visibility-manager' ),
				'desc'   => __( 'URL structure supports custom rewrite mapping.', 'ai-visibility-manager' ),
			);
		}

		// 2. llms.txt Reachability
		$llms_check = get_transient( 'aivm_health_llms_txt' );
		if ( false === $llms_check ) {
			$url      = home_url( '/llms.txt' );
			$response = wp_safe_remote_get( $url, array( 'timeout' => 2, 'sslverify' => false ) );
			
			if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
				$llms_check = 'pass';
			} else {
				$llms_check = 'fail';
			}
			set_transient( 'aivm_health_llms_txt', $llms_check, HOUR_IN_SECONDS );
		}

		if ( $llms_check === 'pass' ) {
			$status['llmstxt'] = array(
				'status' => 'pass',
				'title'  => __( 'llms.txt Endpoint Reachable', 'ai-visibility-manager' ),
				'desc'   => __( 'The site responded with a valid output on /llms.txt request.', 'ai-visibility-manager' ),
			);
		} else {
			$status['llmstxt'] = array(
				'status'         => 'fail',
				'title'          => __( 'llms.txt Endpoint Unreachable', 'ai-visibility-manager' ),
				'desc'           => __( 'Local request did not return a successful 200 response.', 'ai-visibility-manager' ),
				'recommendation' => __( 'Flush rewrites using Rebuild Cache button under Tools tab.', 'ai-visibility-manager' ),
			);
		}

		// 3. robots.txt Reachability
		$robots_check = get_transient( 'aivm_health_robots' );
		if ( false === $robots_check ) {
			$url      = home_url( '/robots.txt' );
			$response = wp_safe_remote_get( $url, array( 'timeout' => 2, 'sslverify' => false ) );
			
			if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
				$robots_check = 'pass';
			} else {
				$robots_check = 'warning';
			}
			set_transient( 'aivm_health_robots', $robots_check, HOUR_IN_SECONDS );
		}

		if ( $robots_check === 'pass' ) {
			$status['robots'] = array(
				'status' => 'pass',
				'title'  => __( 'robots.txt Reachable', 'ai-visibility-manager' ),
				'desc'   => __( 'The robots.txt file is loaded correctly by client crawlers.', 'ai-visibility-manager' ),
			);
		} else {
			$status['robots'] = array(
				'status'         => 'warning',
				'title'          => __( 'robots.txt Unreachable', 'ai-visibility-manager' ),
				'desc'           => __( 'Dynamic file could not be fetched or standard physical robots.txt block is present.', 'ai-visibility-manager' ),
				'recommendation' => __( 'Verify that no physical robots.txt file exists in root directory.', 'ai-visibility-manager' ),
			);
		}

		// 4. Search visibility
		$blog_public = get_option( 'blog_public' );
		if ( intval( $blog_public ) === 0 ) {
			$status['visibility'] = array(
				'status'         => 'warning',
				'title'          => __( 'Search Engines Discouraged', 'ai-visibility-manager' ),
				'desc'           => __( 'Your website has search engine indexing visibility disabled.', 'ai-visibility-manager' ),
				'recommendation' => __( 'Turn off "Discourage search engines from indexing this site" under Settings > Reading.', 'ai-visibility-manager' ),
			);
		} else {
			$status['visibility'] = array(
				'status' => 'pass',
				'title'  => __( 'Search Visibility Enabled', 'ai-visibility-manager' ),
				'desc'   => __( 'Standard search crawlers are allowed to parse content.', 'ai-visibility-manager' ),
			);
		}

		// 5. Site public
		if ( file_exists( ABSPATH . '.maintenance' ) ) {
			$status['public'] = array(
				'status'         => 'warning',
				'title'          => __( 'Site Under Maintenance', 'ai-visibility-manager' ),
				'desc'           => __( 'The site active state is blocked by maintenance flag.', 'ai-visibility-manager' ),
				'recommendation' => __( 'Remove the .maintenance file from root once updates are completed.', 'ai-visibility-manager' ),
			);
		} else {
			$status['public'] = array(
				'status' => 'pass',
				'title'  => __( 'Site State Public', 'ai-visibility-manager' ),
				'desc'   => __( 'No active maintenance or offline triggers detected.', 'ai-visibility-manager' ),
			);
		}

		return $status;
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

		$last_rebuild = get_option( 'aivm_llms_txt_last_rebuild', 0 );

		return array(
			'posts_count'    => $posts_count,
			'pages_count'    => $pages_count,
			'excluded_count' => $excluded_count,
			'last_rebuild'   => intval( $last_rebuild ),
		);
	}

	/**
	 * Add settings action link to plugin listing page.
	 *
	 * @since    1.0.0
	 * @param    array    $links    Existing links.
	 * @return   array              Modified links.
	 */
	public function add_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=ai-visibility-manager' ) ) . '">' . esc_html__( 'Settings', 'ai-visibility-manager' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
}
