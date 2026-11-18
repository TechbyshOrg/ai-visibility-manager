<?php
/**
 * Tools > Site Health integration.
 *
 * @link       https://techbysh.com
 * @since      1.3.0
 *
 * @package    Tavc
 * @subpackage Tavc/admin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tools > Site Health integration.
 *
 * Adds the plugin's diagnostics to the Status tab and its configuration to
 * the Info tab, reusing TAVC_Admin::get_health_status().
 *
 * @since      1.3.0
 * @package    Tavc
 * @subpackage Tavc/admin
 * @author     Techbysh
 */
class TAVC_Site_Health {

	/**
	 * Admin instance providing the health checks.
	 *
	 * @since    1.3.0
	 * @var      TAVC_Admin
	 */
	private $admin;

	/**
	 * Health checks computed once per request.
	 *
	 * @since    1.3.0
	 * @var      array|null
	 */
	private $status = null;

	/**
	 * Initialize the class.
	 *
	 * @since    1.3.0
	 * @param    TAVC_Admin    $admin    Admin instance.
	 */
	public function __construct( $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Register the Site Health tests.
	 *
	 * @since    1.3.0
	 * @param    array    $tests    Registered tests.
	 * @return   array
	 */
	public function add_tests( $tests ) {
		if ( ! is_array( $tests ) ) {
			return $tests;
		}

		$tests['direct']['tavc_llms_txt']   = array(
			'label' => __( 'llms.txt is reachable', 'tbsh-ai-visibility-control' ),
			'test'  => array( $this, 'test_llms_txt' ),
		);
		$tests['direct']['tavc_robots_txt'] = array(
			'label' => __( 'AI crawler rules are published in robots.txt', 'tbsh-ai-visibility-control' ),
			'test'  => array( $this, 'test_robots_txt' ),
		);

		return $tests;
	}

	/**
	 * Site Health test: /llms.txt responds.
	 *
	 * @since    1.3.0
	 * @return   array
	 */
	public function test_llms_txt() {
		$status = $this->get_status();
		$checks = array( 'permalinks', 'llmstxt' );

		return $this->build_result( 'tavc_llms_txt', $checks, $status, __( 'Your llms.txt file is reachable', 'tbsh-ai-visibility-control' ) );
	}

	/**
	 * Site Health test: robots.txt can carry the AI crawler rules.
	 *
	 * @since    1.3.0
	 * @return   array
	 */
	public function test_robots_txt() {
		$status = $this->get_status();
		$checks = array( 'robots' );

		return $this->build_result( 'tavc_robots_txt', $checks, $status, __( 'Your robots.txt includes the AI crawler rules', 'tbsh-ai-visibility-control' ) );
	}

	/**
	 * Convert plugin health checks into a Site Health result.
	 *
	 * @since    1.3.0
	 * @param    string    $test_id     Test ID.
	 * @param    array     $checks      Keys from TAVC_Admin::get_health_status().
	 * @param    array     $status      Health checks.
	 * @param    string    $pass_label  Label when every check passes.
	 * @return   array
	 */
	private function build_result( $test_id, $checks, $status, $pass_label ) {
		$problem = null;
		foreach ( $checks as $key ) {
			if ( isset( $status[ $key ] ) && 'pass' !== $status[ $key ]['status'] ) {
				$problem = $status[ $key ];
				break;
			}
		}

		$settings_url = TAVC_Admin::get_page_url( array( 'tab' => 'status' ) );
		$result       = array(
			'label'       => $pass_label,
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'AI Visibility', 'tbsh-ai-visibility-control' ),
				'color' => 'blue',
			),
			'description' => '',
			'actions'     => sprintf(
				'<p><a href="%1$s">%2$s</a></p>',
				esc_url( $settings_url ),
				esc_html__( 'Open AI Visibility Control', 'tbsh-ai-visibility-control' )
			),
			'test'        => $test_id,
		);

		if ( null === $problem ) {
			$descriptions = array();
			foreach ( $checks as $key ) {
				if ( isset( $status[ $key ]['desc'] ) ) {
					$descriptions[] = $status[ $key ]['desc'];
				}
			}
			$result['description'] = '<p>' . esc_html( implode( ' ', $descriptions ) ) . '</p>';

			return $result;
		}

		// These issues limit AI visibility but do not break the site, so they are recommendations.
		$result['status']      = 'recommended';
		$result['label']       = $problem['title'];
		$result['description'] = '<p>' . esc_html( $problem['desc'] ) . '</p>';
		if ( ! empty( $problem['recommendation'] ) ) {
			$result['description'] .= '<p>' . esc_html( $problem['recommendation'] ) . '</p>';
		}

		return $result;
	}

	/**
	 * Health checks, computed once per request.
	 *
	 * @since    1.3.0
	 * @return   array
	 */
	private function get_status() {
		if ( null === $this->status ) {
			$this->status = $this->admin->get_health_status();
		}

		return $this->status;
	}

	/**
	 * Add the plugin configuration to Tools > Site Health > Info.
	 *
	 * @since    1.3.0
	 * @param    array    $info    Debug information sections.
	 * @return   array
	 */
	public function add_debug_information( $info ) {
		if ( ! is_array( $info ) ) {
			return $info;
		}

		$definitions = TAVC_Bots::get_definitions();
		$blocked     = array();
		foreach ( TAVC_Bots::get_blocked_settings() as $slug => $flag ) {
			if ( $flag && isset( $definitions[ $slug ] ) ) {
				$blocked[] = $definitions[ $slug ]['name'];
			}
		}

		$llms     = TAVC_Settings::get_llms_settings();
		$tracking = TAVC_Settings::get_tracking_settings();
		$signals  = array();
		foreach ( TAVC_Settings::get_content_signals() as $key => $value ) {
			if ( '' !== $value ) {
				$signals[] = $key . '=' . $value;
			}
		}

		$yes = __( 'Yes', 'tbsh-ai-visibility-control' );
		$no  = __( 'No', 'tbsh-ai-visibility-control' );

		$info['tavc'] = array(
			'label'  => __( 'AI Visibility Control', 'tbsh-ai-visibility-control' ),
			'fields' => array(
				'version'          => array(
					'label' => __( 'Version', 'tbsh-ai-visibility-control' ),
					'value' => defined( 'TAVC_VERSION' ) ? TAVC_VERSION : '',
				),
				'blocked_crawlers' => array(
					'label' => __( 'Blocked AI crawlers', 'tbsh-ai-visibility-control' ),
					'value' => empty( $blocked ) ? __( 'None', 'tbsh-ai-visibility-control' ) : implode( ', ', $blocked ),
				),
				'custom_rules'     => array(
					'label' => __( 'Custom robots.txt rule groups', 'tbsh-ai-visibility-control' ),
					'value' => count( TAVC_Settings::get_custom_robots_rules() ),
				),
				'content_signals'  => array(
					'label' => __( 'Content signals', 'tbsh-ai-visibility-control' ),
					'value' => empty( $signals ) ? __( 'Not set', 'tbsh-ai-visibility-control' ) : implode( ', ', $signals ),
				),
				'llms_post_types'  => array(
					'label' => __( 'llms.txt content types', 'tbsh-ai-visibility-control' ),
					'value' => implode( ', ', $llms['post_types'] ),
				),
				'llms_full'        => array(
					'label' => __( 'llms-full.txt enabled', 'tbsh-ai-visibility-control' ),
					'value' => $llms['full_enabled'] ? $yes : $no,
				),
				'utm_detection'    => array(
					'label' => __( 'UTM referral detection', 'tbsh-ai-visibility-control' ),
					'value' => $tracking['utm_detection'] ? $yes : $no,
				),
				'beacon_tracking'  => array(
					'label' => __( 'Cache-compatible referral tracking', 'tbsh-ai-visibility-control' ),
					'value' => $tracking['beacon_tracking'] ? $yes : $no,
				),
				'crawler_logging'  => array(
					'label' => __( 'AI crawler logging', 'tbsh-ai-visibility-control' ),
					'value' => $tracking['crawler_logging'] ? $yes : $no,
				),
				'retention'        => array(
					'label' => __( 'Log retention (referrals / crawlers, days, 0 = forever)', 'tbsh-ai-visibility-control' ),
					'value' => $tracking['referral_retention_days'] . ' / ' . $tracking['crawler_retention_days'],
				),
				'digest'           => array(
					'label' => __( 'Weekly summary email', 'tbsh-ai-visibility-control' ),
					'value' => $tracking['digest_enabled'] ? $yes : $no,
				),
			),
		);

		return $info;
	}
}
