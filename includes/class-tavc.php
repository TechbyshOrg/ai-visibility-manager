<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Tavc
 * @subpackage Tavc/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Tavc
 * @subpackage Tavc/includes
 * @author     Techbysh
 */
class TAVC {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The string used to identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The single instance of the class.
	 *
	 * @since    1.0.0
	 * @var      TAVC    $instance
	 */
	private static $instance = null;

	/**
	 * Instances of internal classes.
	 */
	private $llms_txt;
	private $bot_manager;
	private $referral_logger;
	private $crawler_logger;
	private $enforcer;
	private $woocommerce;
	private $admin;
	private $site_health;

	/**
	 * Returns the single instance of the class.
	 *
	 * @since    1.0.0
	 * @return   TAVC    The single instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	private function __construct() {
		$this->plugin_name = 'tbsh-ai-visibility-control';
		$this->version     = TAVC_VERSION;

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		add_action( 'init', array( $this, 'maybe_upgrade' ), 1 );
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - TAVC_DB. Database query and management class.
	 * - TAVC_LLMS_Txt. Handles generating /llms.txt.
	 * - TAVC_Bot_Manager. Manages robots.txt rules for AI bots.
	 * - TAVC_Referral_Logger. Logs referrals from AI tools.
	 * - TAVC_Admin. Defines all hooks for the admin area.
	 *
	 * @since    1.0.0
	 */
	private function load_dependencies() {
		$plugin_dir = plugin_dir_path( dirname( __FILE__ ) );

		require_once $plugin_dir . 'includes/class-tavc-bots.php';
		require_once $plugin_dir . 'includes/class-tavc-ai-sources.php';
		require_once $plugin_dir . 'includes/class-tavc-settings.php';
		require_once $plugin_dir . 'includes/class-tavc-db.php';
		require_once $plugin_dir . 'includes/class-tavc-markdown.php';
		require_once $plugin_dir . 'includes/class-tavc-llms-txt.php';
		require_once $plugin_dir . 'includes/class-tavc-bot-manager.php';
		require_once $plugin_dir . 'includes/class-tavc-referral-logger.php';
		require_once $plugin_dir . 'includes/class-tavc-crawler-logger.php';
		require_once $plugin_dir . 'includes/class-tavc-enforcer.php';
		require_once $plugin_dir . 'includes/class-tavc-digest.php';
		require_once $plugin_dir . 'includes/class-tavc-cron.php';
		require_once $plugin_dir . 'includes/class-tavc-woocommerce.php';
		require_once $plugin_dir . 'admin/class-tavc-admin.php';
		require_once $plugin_dir . 'admin/class-tavc-site-health.php';

		// Instantiate helper and functionality classes
		$this->llms_txt        = new TAVC_LLMS_Txt();
		$this->bot_manager     = new TAVC_Bot_Manager();
		$this->referral_logger = new TAVC_Referral_Logger();
		$this->crawler_logger  = new TAVC_Crawler_Logger();
		$this->enforcer        = new TAVC_Enforcer();
		$this->woocommerce     = new TAVC_WooCommerce();
		$this->admin           = new TAVC_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->site_health     = new TAVC_Site_Health( $this->admin );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	private function define_admin_hooks() {
		// Admin menus and settings setup
		add_action( 'admin_menu', array( $this->admin, 'add_plugin_admin_menu' ) );
		add_action( 'admin_init', array( $this->admin, 'register_plugin_settings' ) );
		add_action( 'admin_init', array( $this->admin, 'redirect_legacy_page_url' ) );
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_assets' ) );

		// Tools page actions (clear logs, reset settings, rebuild cache, export CSV)
		add_action( 'admin_post_tavc_tool_action', array( $this->admin, 'handle_tool_actions' ) );

		// Health checks loaded in the background on the settings page (admins only).
		add_action( 'wp_ajax_tavc_health_check', array( $this->admin, 'ajax_health_check' ) );
		add_action( 'admin_notices', array( $this->admin, 'display_admin_notices' ) );
		add_filter( 'removable_query_args', array( $this->admin, 'add_removable_query_args' ) );

		// Plugin action links settings shortcut
		$plugin_basename = plugin_basename( plugin_dir_path( dirname( __FILE__ ) ) . 'tbsh-ai-visibility-control.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this->admin, 'add_action_links' ) );

		// Per-post "Exclude from llms.txt" box in the post editor.
		add_action( 'add_meta_boxes', array( $this->admin, 'register_meta_boxes' ) );
		add_action( 'save_post', array( $this->admin, 'save_llms_meta_box' ), 10, 2 );

		// Privacy policy guide text.
		add_action( 'admin_init', array( $this->admin, 'add_privacy_policy_content' ) );

		// Tools > Site Health integration.
		add_filter( 'site_status_tests', array( $this->site_health, 'add_tests' ) );
		add_action( 'wp_ajax_health-check-tavc-llms-txt', array( $this->site_health, 'ajax_test_llms_txt' ) );
		add_action( 'wp_ajax_health-check-tavc-robots-txt', array( $this->site_health, 'ajax_test_robots_txt' ) );
		add_filter( 'debug_information', array( $this->site_health, 'add_debug_information' ) );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	private function define_public_hooks() {
		// llms.txt rewrite rules and templates
		add_action( 'init', array( $this->llms_txt, 'register_rewrite_rule' ) );
		add_filter( 'query_vars', array( $this->llms_txt, 'add_query_vars' ) );
		add_action( 'template_redirect', array( $this->llms_txt, 'render_llms_txt' ) );
		add_filter( 'redirect_canonical', array( $this->llms_txt, 'prevent_canonical_redirect' ) );

		// Markdown versions of pages (/some-page.md), opt-in on the llms.txt tab.
		// Served before redirect_canonical (template_redirect priority 10).
		add_filter( 'request', array( $this->llms_txt, 'route_markdown_request' ) );
		add_action( 'template_redirect', array( $this->llms_txt, 'render_markdown_page' ), 5 );
		add_action( 'wp_head', array( $this->llms_txt, 'print_markdown_alternate_link' ), 5 );

		// Clear cache on content updates
		// Only changes to published posts of the selected types (trashing included) mark the files stale.
		add_action( 'transition_post_status', array( $this->llms_txt, 'on_post_status_change' ), 10, 3 );
		add_action( 'deleted_post', array( $this->llms_txt, 'on_post_deleted' ), 10, 2 );

		// Clear cache when the llms.txt settings change
		add_action( 'add_option_tavc_llms_txt_settings', array( $this->llms_txt, 'clear_cache_on_settings_change' ) );
		add_action( 'update_option_tavc_llms_txt_settings', array( $this->llms_txt, 'clear_cache_on_settings_change' ) );
		add_action( 'delete_option_tavc_llms_txt_settings', array( $this->llms_txt, 'clear_cache_on_settings_change' ) );

		// robots.txt manager
		add_filter( 'robots_txt', array( $this->bot_manager, 'add_robots_txt_rules' ), 100, 2 );

		// Refuse blocked AI crawlers (opt-in). init priority 0: every plugin has
		// loaded, but most init work and the theme have not run yet.
		add_action( 'init', array( $this->enforcer, 'maybe_refuse_request' ), 0 );

		// AI crawler visits: priority 1 so /llms.txt and robots.txt requests are counted before they are served.
		add_action( 'template_redirect', array( $this->crawler_logger, 'log_crawler_visit' ), 1 );

		// referral logger
		add_action( 'template_redirect', array( $this->referral_logger, 'log_incoming_referral' ) );

		// Cache-compatible referral tracking (opt-in)
		add_action( 'wp_enqueue_scripts', array( $this->referral_logger, 'enqueue_tracker' ) );
		add_action( 'rest_api_init', array( $this->referral_logger, 'register_rest_routes' ) );

		// Scheduled tasks: log retention and weekly summary email
		add_action( TAVC_Cron::DAILY_HOOK, array( 'TAVC_Cron', 'run_daily_maintenance' ) );
		add_action( TAVC_Cron::DIGEST_HOOK, array( 'TAVC_Cron', 'run_weekly_digest' ) );
		add_action( 'add_option_tavc_tracking_settings', array( 'TAVC_Cron', 'sync_digest_schedule' ) );
		add_action( 'update_option_tavc_tracking_settings', array( 'TAVC_Cron', 'sync_digest_schedule' ) );

		// WooCommerce: attribute orders to AI platforms
		add_action( 'woocommerce_order_save_attribution_data', array( $this->woocommerce, 'save_order_ai_source' ), 20, 2 );
		add_action( 'woocommerce_admin_order_data_after_order_details', array( $this->woocommerce, 'render_order_ai_source' ) );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		// Hooks are hooked immediately in the define_admin_hooks/define_public_hooks methods.
	}

	/**
	 * Apply database and rewrite upgrades when the stored plugin version changes.
	 *
	 * @since    1.1.0
	 */
	public function maybe_upgrade() {
		if ( get_option( 'tavc_plugin_version', '' ) === $this->version && TAVC_DB::schema_is_current() ) {
			return;
		}

		// Only one request runs the upgrade; others carry on with the old schema
		// (TAVC_DB falls back to its runtime checks until tavc_db_version is set).
		$lock = 'tavc_upgrade_lock';
		if ( ! add_option( $lock, time(), '', false ) ) {
			if ( time() - (int) get_option( $lock, 0 ) < MINUTE_IN_SECONDS ) {
				return;
			}
			update_option( $lock, time(), false );
		}

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-tavc-activator.php';

		// This runs at init priority 1, before other plugins register their
		// rewrite rules, so flushing now would drop their rules. Flush once
		// every plugin has finished registering.
		TAVC_Activator::activate( false );
		add_action( 'wp_loaded', 'flush_rewrite_rules' );

		delete_transient( 'tavc_health_llms_txt' );
		delete_transient( 'tavc_health_robots' );

		// The llms.txt output format improved; rebuild it on the next request.
		TAVC_LLMS_Txt::clear_cache();

		// Storage used before 2.0.0, replaced by the tavc_counters table and the llms.txt store.
		foreach ( array( TAVC_LLMS_Txt::CACHE_KEY, TAVC_LLMS_Txt::FULL_CACHE_KEY, 'tavc_recent_referrals', 'tavc_beacon_rate', 'tavc_beacon_hourly' ) as $transient ) {
			delete_transient( $transient );
		}

		delete_option( $lock );
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
