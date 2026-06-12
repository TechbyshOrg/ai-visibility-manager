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
 * @package    Aivm
 * @subpackage Aivm/includes
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
 * @package    Aivm
 * @subpackage Aivm/includes
 * @author     Techbysh
 */
class AIVM {

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
	 * @var      AIVM    $instance
	 */
	private static $instance = null;

	/**
	 * Instances of internal classes.
	 */
	private $llms_txt;
	private $bot_manager;
	private $referral_logger;
	private $admin;

	/**
	 * Returns the single instance of the class.
	 *
	 * @since    1.0.0
	 * @return   AIVM    The single instance.
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
		$this->plugin_name = 'ai-visibility-manager';
		$this->version     = '1.0.0';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - AIVM_DB. Database query and management class.
	 * - AIVM_LLMS_Txt. Handles generating /llms.txt.
	 * - AIVM_Bot_Manager. Manages robots.txt rules for AI bots.
	 * - AIVM_Referral_Logger. Logs referrals from AI tools.
	 * - AIVM_Admin. Defines all hooks for the admin area.
	 *
	 * @since    1.0.0
	 */
	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-aivm-db.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-aivm-llms-txt.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-aivm-bot-manager.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-aivm-referral-logger.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-aivm-admin.php';

		// Instantiate helper and functionality classes
		$this->llms_txt        = new AIVM_LLMS_Txt();
		$this->bot_manager     = new AIVM_Bot_Manager();
		$this->referral_logger = new AIVM_Referral_Logger();
		$this->admin           = new AIVM_Admin( $this->get_plugin_name(), $this->get_version() );
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
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_styles' ) );

		// Tools page actions (clear logs, reset settings, rebuild cache, export CSV)
		add_action( 'admin_post_aivm_tool_action', array( $this->admin, 'handle_tool_actions' ) );
		add_action( 'admin_notices', array( $this->admin, 'display_admin_notices' ) );

		// Plugin action links settings shortcut
		$plugin_basename = plugin_basename( plugin_dir_path( dirname( __FILE__ ) ) . 'ai-visibility-manager.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this->admin, 'add_action_links' ) );
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

		// Clear cache on content updates
		add_action( 'save_post', array( $this->llms_txt, 'clear_llms_txt_cache' ) );
		add_action( 'deleted_post', array( $this->llms_txt, 'clear_llms_txt_cache' ) );
		add_action( 'trashed_post', array( $this->llms_txt, 'clear_llms_txt_cache' ) );

		// robots.txt manager
		add_filter( 'robots_txt', array( $this->bot_manager, 'add_robots_txt_rules' ), 100, 2 );

		// referral logger
		add_action( 'template_redirect', array( $this->referral_logger, 'log_incoming_referral' ) );
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
