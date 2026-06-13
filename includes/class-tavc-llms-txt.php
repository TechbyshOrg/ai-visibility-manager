<?php
/**
 * Automatically generates llms.txt file
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
 * Automatically generates llms.txt file.
 *
 * Registers the rewrite rules, generates the output in plain text markdown format,
 * handles transient caching, and hooks into save/delete actions to clear cache.
 *
 * @since      1.0.0
 * @package    Tavc
 * @subpackage Tavc/includes
 * @author     Techbysh
 */
class TAVC_LLMS_Txt {

	/**
	 * Register rewrite rule for /llms.txt
	 *
	 * @since    1.0.0
	 */
	public function register_rewrite_rule() {
		add_rewrite_rule( '^llms\.txt$', 'index.php?tavc_llms_txt=1', 'top' );
	}

	/**
	 * Register the query variable.
	 *
	 * @since    1.0.0
	 * @param    array    $vars    Existing query variables.
	 * @return   array             Modified query variables.
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'tavc_llms_txt';
		return $vars;
	}

	/**
	 * Render the llms.txt content and exit.
	 *
	 * @since    1.0.0
	 */
	public function render_llms_txt() {
		if ( get_query_var( 'tavc_llms_txt' ) != 1 ) {
			return;
		}

		// Check cache first
		$output = get_transient( 'tavc_llms_txt_cache' );

		if ( false === $output ) {
			$output = $this->generate_llms_txt();
			set_transient( 'tavc_llms_txt_cache', $output, WEEK_IN_SECONDS );
			update_option( 'tavc_llms_txt_last_rebuild', current_time( 'timestamp' ), 'no' );
		}

		// Disable caching in browsers/proxies for live fetching if needed, but output as plain text
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex, follow' );
		
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Generate the markdown structure for llms.txt
	 *
	 * @since    1.0.0
	 * @return   string    Generated plain text markdown.
	 */
	public function generate_llms_txt() {
		$site_name = get_bloginfo( 'name' );
		$site_description = get_bloginfo( 'description' );

		$output = "# " . $site_name . "\n\n";
		$output .= "## About\n\n";
		if ( ! empty( $site_description ) ) {
			$output .= $site_description . "\n\n";
		} else {
			$output .= __( 'No description provided.', 'tbsh-ai-visibility-control' ) . "\n\n";
		}

		$output .= "## Recent Content\n\n";

		// Query all public posts and pages (Removed artificial 100 limit)
		$args = array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true, // Performance optimization
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$title = get_the_title();
				$permalink = get_permalink();
				
				// Standard markdown formatting for llms.txt list
				$output .= sprintf( "- [%s](%s)\n", $title, $permalink );
			}
			wp_reset_postdata();
		} else {
			$output .= __( 'No content found.', 'tbsh-ai-visibility-control' ) . "\n";
		}

		return $output;
	}

	/**
	 * Clear the generated llms.txt cache.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    The post ID.
	 */
	public function clear_llms_txt_cache( $post_id = 0 ) {
		// Ignore revisions or autosaves
		if ( $post_id && ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) ) {
			return;
		}
		delete_transient( 'tavc_llms_txt_cache' );
	}
}
