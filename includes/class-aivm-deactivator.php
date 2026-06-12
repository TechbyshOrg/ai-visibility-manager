<?php
/**
 * Fired during plugin deactivation
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
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Aivm
 * @subpackage Aivm/includes
 * @author     Techbysh
 */
class AIVM_Deactivator {

	/**
	 * Short description.
	 *
	 * Long description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		flush_rewrite_rules();

		// Clear transients
		delete_transient( 'aivm_llms_txt_cache' );
	}
}
