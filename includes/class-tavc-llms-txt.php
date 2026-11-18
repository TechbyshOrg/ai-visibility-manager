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
 * Since 1.3.0 it also serves the optional /llms-full.txt file and honours the
 * llms.txt settings (content types, exclusions, noindex, intro and excerpts).
 * Since 1.4.0 it can serve a Markdown version of each listed page at the
 * page URL plus ".md" (for example /about.md), following the same rules.
 *
 * @since      1.0.0
 * @package    Tavc
 * @subpackage Tavc/includes
 * @author     Techbysh
 */
class TAVC_LLMS_Txt {

	/**
	 * Transient holding the generated llms.txt.
	 */
	const CACHE_KEY = 'tavc_llms_txt_cache';

	/**
	 * Transient holding the generated llms-full.txt.
	 */
	const FULL_CACHE_KEY = 'tavc_llms_full_txt_cache';

	/**
	 * Post meta flag that leaves a single post out of llms.txt and llms-full.txt.
	 */
	const EXCLUDE_META_KEY = '_tavc_llms_exclude';

	/**
	 * Query var that asks for the Markdown version of the queried post.
	 */
	const MARKDOWN_QUERY_VAR = 'tavc_markdown';

	/**
	 * Register rewrite rules for /llms.txt and /llms-full.txt
	 *
	 * @since    1.0.0
	 */
	public function register_rewrite_rule() {
		add_rewrite_rule( '^llms\.txt$', 'index.php?tavc_llms_txt=1', 'top' );
		add_rewrite_rule( '^llms-full\.txt$', 'index.php?tavc_llms_txt=full', 'top' );
	}

	/**
	 * Register custom query variables.
	 *
	 * @since    1.0.0
	 * @param    array    $vars    The array of query variables.
	 * @return   array             The modified query variables.
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'tavc_llms_txt';
		$vars[] = self::MARKDOWN_QUERY_VAR;
		return $vars;
	}

	/**
	 * Intercept the request and output llms.txt or llms-full.txt.
	 *
	 * @since    1.0.0
	 */
	public function render_llms_txt() {
		$type = $this->get_requested_type();
		if ( ! $type ) {
			return;
		}

		if ( 'full' === $type ) {
			$settings = TAVC_Settings::get_llms_settings();
			if ( empty( $settings['full_enabled'] ) ) {
				// Disabled: let the theme render a normal 404 page.
				$this->send_not_found();
				return;
			}

			$output = get_transient( self::FULL_CACHE_KEY );
			if ( false === $output ) {
				$output = $this->generate_llms_full_txt();
				set_transient( self::FULL_CACHE_KEY, $output, WEEK_IN_SECONDS );
			}
		} else {
			// Check cache first
			$output = get_transient( self::CACHE_KEY );
			if ( false === $output ) {
				$output = $this->generate_llms_txt();
				set_transient( self::CACHE_KEY, $output, WEEK_IN_SECONDS );
				update_option( 'tavc_llms_txt_last_rebuild', current_time( 'timestamp' ), 'no' );
			}
		}

		// Disable caching in browsers/proxies for live fetching if needed, but output as plain text
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex, follow' );

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Stop WordPress from redirecting /llms.txt to /llms.txt/.
	 *
	 * redirect_canonical() runs on template_redirect before the file is served
	 * and would add a trailing slash on sites whose permalinks end with "/".
	 *
	 * @since    1.3.0
	 * @param    string|false    $redirect_url    Canonical redirect target.
	 * @return   string|false
	 */
	public function prevent_canonical_redirect( $redirect_url ) {
		if ( $this->get_requested_type() || $this->is_markdown_request() ) {
			return false;
		}

		return $redirect_url;
	}

	/**
	 * Which file the current request asks for.
	 *
	 * Uses the rewrite query var, with a path fallback for when rewrite rules
	 * have not been flushed yet.
	 *
	 * @since    1.3.0
	 * @return   string|false    'index', 'full', or false.
	 */
	private function get_requested_type() {
		$query_var = (string) get_query_var( 'tavc_llms_txt' );
		if ( '1' === $query_var ) {
			return 'index';
		}
		if ( 'full' === $query_var ) {
			return 'full';
		}

		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$request_path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! is_string( $request_path ) || '' === $request_path ) {
			return false;
		}

		$request_path = untrailingslashit( strtolower( $request_path ) );
		$home_path    = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$home_path    = is_string( $home_path ) ? untrailingslashit( strtolower( $home_path ) ) : '';

		if ( $request_path === $home_path . '/llms.txt' ) {
			return 'index';
		}
		if ( $request_path === $home_path . '/llms-full.txt' ) {
			return 'full';
		}

		return false;
	}

	/**
	 * Mark the current request as a 404 so the theme's 404 template is used.
	 *
	 * @since    1.3.0
	 */
	private function send_not_found() {
		global $wp_query;

		if ( $wp_query instanceof WP_Query ) {
			$wp_query->set_404();
		}
		status_header( 404 );
		nocache_headers();
	}

	/**
	 * Generate the llms.txt content.
	 *
	 * @since    1.0.0
	 * @return   string    The generated llms.txt markdown text.
	 */
	public function generate_llms_txt() {
		$settings = TAVC_Settings::get_llms_settings();

		$output  = $this->build_header( $settings );
		$posts   = $this->get_included_posts( $settings, 'index' );
		$grouped = ! empty( $settings['group_by_type'] ) && count( $posts ) > 1;

		if ( $grouped ) {
			foreach ( $posts as $post_type => $type_posts ) {
				$type_object = get_post_type_object( $post_type );
				$label       = $type_object ? $type_object->labels->name : $post_type;

				$output .= '## ' . $this->plain_text( $label ) . "\n\n";
				foreach ( $type_posts as $post ) {
					$output .= $this->format_post_line( $post, $settings );
				}
				$output .= "\n";
			}
			$output = rtrim( $output, "\n" ) . "\n";
		} else {
			$output .= "## Recent Content\n\n";

			$all_posts = $this->flatten_posts( $posts );
			if ( ! empty( $all_posts ) ) {
				foreach ( $all_posts as $post ) {
					$output .= $this->format_post_line( $post, $settings );
				}
			} else {
				$output .= __( 'No content found.', 'tbsh-ai-visibility-control' ) . "\n";
			}
		}

		$filtered = apply_filters( 'tavc_llms_txt_output', $output );
		return is_string( $filtered ) ? $filtered : $output;
	}

	/**
	 * Generate the llms-full.txt content: the full text of recent content as Markdown.
	 *
	 * @since    1.3.0
	 * @return   string
	 */
	public function generate_llms_full_txt() {
		$settings = TAVC_Settings::get_llms_settings();

		/**
		 * Filters the maximum size of llms-full.txt in bytes.
		 *
		 * The file is cached in the database, so very large values can exceed
		 * the MySQL max_allowed_packet limit on some hosts.
		 *
		 * @since 1.3.0
		 *
		 * @param int $max_bytes Default 2 MB.
		 */
		$max_bytes = absint( apply_filters( 'tavc_llms_full_txt_max_bytes', 2 * MB_IN_BYTES ) );

		$output    = $this->build_header( $settings );
		$posts     = $this->flatten_posts( $this->get_included_posts( $settings, 'full' ) );
		$truncated = false;

		if ( empty( $posts ) ) {
			$output .= __( 'No content found.', 'tbsh-ai-visibility-control' ) . "\n";
		}

		foreach ( $posts as $post ) {
			$section = $this->format_full_section( $post );

			if ( $max_bytes > 0 && ( strlen( $output ) + strlen( $section ) ) > $max_bytes ) {
				$truncated = true;
				break;
			}

			$output .= $section;
		}

		if ( $truncated ) {
			$output .= "\n" . __( 'Some content was left out because the file reached its size limit. See /llms.txt for the full list of pages.', 'tbsh-ai-visibility-control' ) . "\n";
		}

		/**
		 * Filters the generated llms-full.txt content.
		 *
		 * @since 1.3.0
		 *
		 * @param string $output Markdown text.
		 */
		$filtered = apply_filters( 'tavc_llms_full_txt_output', $output );
		return is_string( $filtered ) ? $filtered : $output;
	}

	/**
	 * Site title and About section shared by both files.
	 *
	 * @since    1.3.0
	 * @param    array    $settings    llms.txt settings.
	 * @return   string
	 */
	private function build_header( $settings ) {
		$site_name        = $this->plain_text( get_bloginfo( 'name' ) );
		$site_description = $this->plain_text( get_bloginfo( 'description' ) );

		$output  = '# ' . $site_name . "\n\n";
		$output .= "## About\n\n";

		if ( '' !== trim( $settings['intro'] ) ) {
			$output .= trim( $settings['intro'] ) . "\n\n";
		} elseif ( ! empty( $site_description ) ) {
			$output .= $site_description . "\n\n";
		} else {
			$output .= __( 'No description provided.', 'tbsh-ai-visibility-control' ) . "\n\n";
		}

		return $output;
	}

	/**
	 * Published, public content to list, grouped by post type.
	 *
	 * @since    1.3.0
	 * @param    array     $settings    llms.txt settings.
	 * @param    string    $context     'index' for llms.txt, 'full' for llms-full.txt.
	 * @return   array                  Post type => WP_Post[] (newest first).
	 */
	private function get_included_posts( $settings, $context ) {
		$post_types = apply_filters( 'tavc_llms_txt_post_types', $settings['post_types'] );
		if ( ! is_array( $post_types ) || empty( $post_types ) ) {
			$post_types = array( 'post', 'page' );
		}
		$post_types = array_values( array_filter( array_map( 'sanitize_key', $post_types ) ) );
		if ( empty( $post_types ) ) {
			$post_types = array( 'post', 'page' );
		}

		// Query public posts and pages. Password-protected entries are omitted
		// because those URLs are not useful to crawlers without credentials.
		$args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'has_password'   => false,
			'no_found_rows'  => true, // Performance optimization
		);

		$excluded_ids = $this->get_excluded_ids( $post_types, $settings );
		if ( ! empty( $excluded_ids ) ) {
			$args['post__not_in'] = $excluded_ids;
		}

		$grouped = array_fill_keys( $post_types, array() );

		if ( 'full' === $context ) {
			$args['posts_per_page'] = $settings['full_max_items'];
			$this->collect_posts( $args, $settings, $context, $grouped );
		} elseif ( $settings['max_per_type'] > 0 ) {
			foreach ( $post_types as $post_type ) {
				$type_args                   = $args;
				$type_args['post_type']      = $post_type;
				$type_args['posts_per_page'] = $settings['max_per_type'];
				$this->collect_posts( $type_args, $settings, $context, $grouped );
			}
		} else {
			$this->collect_posts( $args, $settings, $context, $grouped );
		}

		return array_filter( $grouped );
	}

	/**
	 * Run a query and add the posts that pass the exclusion checks.
	 *
	 * @since    1.3.0
	 * @param    array     $args        WP_Query arguments.
	 * @param    array     $settings    llms.txt settings.
	 * @param    string    $context     'index' or 'full'.
	 * @param    array     $grouped     Post type => WP_Post[], by reference.
	 */
	private function collect_posts( $args, $settings, $context, &$grouped ) {
		$query = new WP_Query( $args );

		foreach ( $query->posts as $post ) {
			if ( ! isset( $grouped[ $post->post_type ] ) || $this->is_post_excluded( $post, $settings, $context ) ) {
				continue;
			}
			$grouped[ $post->post_type ][] = $post;
		}
	}

	/**
	 * Merge grouped posts into one list, newest first.
	 *
	 * @since    1.3.0
	 * @param    array    $grouped    Post type => WP_Post[].
	 * @return   WP_Post[]
	 */
	private function flatten_posts( $grouped ) {
		$posts = array();
		foreach ( $grouped as $type_posts ) {
			foreach ( $type_posts as $post ) {
				$posts[] = $post;
			}
		}

		if ( count( $grouped ) > 1 ) {
			usort(
				$posts,
				function ( $a, $b ) {
					return strcmp( $b->post_date, $a->post_date );
				}
			);
		}

		return $posts;
	}

	/**
	 * IDs left out on purpose: the per-post exclusion flag and, when enabled,
	 * All in One SEO noindex entries (stored in its own table).
	 *
	 * @since    1.3.0
	 * @param    array    $post_types    Post types.
	 * @param    array    $settings      llms.txt settings.
	 * @return   int[]
	 */
	private function get_excluded_ids( $post_types, $settings ) {
		global $wpdb;

		$ids = get_posts(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => self::EXCLUDE_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
				'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_value
			)
		);

		if ( ! empty( $settings['exclude_noindex'] ) ) {
			$aioseo_table = $wpdb->prefix . 'aioseo_posts';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $aioseo_table ) ) ) === $aioseo_table ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$aioseo_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT post_id FROM %i WHERE robots_default = 0 AND robots_noindex = 1', $aioseo_table ) );
				if ( is_array( $aioseo_ids ) ) {
					$ids = array_merge( $ids, $aioseo_ids );
				}
			}
		}

		return array_values( array_unique( array_map( 'intval', $ids ) ) );
	}

	/**
	 * Whether a queried post must be left out.
	 *
	 * @since    1.3.0
	 * @param    WP_Post    $post        Post.
	 * @param    array      $settings    llms.txt settings.
	 * @param    string     $context     'index' or 'full'.
	 * @return   bool
	 */
	private function is_post_excluded( $post, $settings, $context ) {
		if ( ! empty( $settings['exclude_noindex'] ) && $this->is_noindex( $post->ID ) ) {
			return true;
		}

		/**
		 * Filters whether a post is listed in llms.txt or llms-full.txt.
		 *
		 * Use this to hide members-only or otherwise restricted content, which
		 * WordPress itself reports as public.
		 *
		 * @since 1.3.0
		 *
		 * @param bool    $include Whether to include the post.
		 * @param WP_Post $post    Post.
		 * @param string  $context 'index' for llms.txt, 'full' for llms-full.txt,
		 *                         'markdown' for the page's Markdown version (since 1.4.0).
		 */
		return ! apply_filters( 'tavc_llms_txt_include_post', true, $post, $context );
	}

	/**
	 * Whether an SEO plugin marks a post as noindex.
	 *
	 * Supports Yoast SEO, Rank Math, SEOPress and The SEO Framework (post meta).
	 * All in One SEO is handled in get_excluded_ids().
	 *
	 * @since    1.3.0
	 * @param    int    $post_id    Post ID.
	 * @return   bool
	 */
	private function is_noindex( $post_id ) {
		if ( '1' === (string) get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true ) ) {
			return true;
		}

		$rank_math = get_post_meta( $post_id, 'rank_math_robots', true );
		if ( is_array( $rank_math ) && in_array( 'noindex', $rank_math, true ) ) {
			return true;
		}

		if ( 'yes' === (string) get_post_meta( $post_id, '_seopress_robots_index', true ) ) {
			return true;
		}

		if ( '1' === (string) get_post_meta( $post_id, '_genesis_noindex', true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * One "- [Title](url)" line, optionally followed by a short description.
	 *
	 * @since    1.3.0
	 * @param    WP_Post    $post        Post.
	 * @param    array      $settings    llms.txt settings.
	 * @return   string
	 */
	private function format_post_line( $post, $settings ) {
		$url = ( ! empty( $settings['markdown_pages'] ) && ! empty( $settings['markdown_links'] ) ) ? $this->get_markdown_url( $post ) : get_permalink( $post );

		$line = sprintf( '- [%s](%s)', TAVC_Markdown::escape_brackets( $this->get_post_title( $post ) ), $url );

		if ( ! empty( $settings['include_excerpts'] ) ) {
			$excerpt = $this->get_post_summary( $post );
			if ( '' !== $excerpt ) {
				$line .= ': ' . $excerpt;
			}
		}

		return $line . "\n";
	}

	/**
	 * Full-text section for llms-full.txt.
	 *
	 * @since    1.3.0
	 * @param    WP_Post    $post    Post.
	 * @return   string
	 */
	private function format_full_section( $post ) {
		$markdown = $this->get_post_markdown( $post );

		$section  = "\n## " . $this->get_post_title( $post ) . "\n\n";
		$section .= 'URL: ' . get_permalink( $post ) . "\n";
		$section .= 'Published: ' . get_the_date( 'Y-m-d', $post ) . "\n";
		if ( get_the_modified_date( 'Y-m-d', $post ) !== get_the_date( 'Y-m-d', $post ) ) {
			$section .= 'Updated: ' . get_the_modified_date( 'Y-m-d', $post ) . "\n";
		}
		$section .= "\n" . ( '' !== $markdown ? $markdown : $this->get_post_summary( $post ) ) . "\n";

		return $section;
	}

	/**
	 * Plain-text post title.
	 *
	 * @since    1.3.0
	 * @param    WP_Post    $post    Post.
	 * @return   string
	 */
	private function get_post_title( $post ) {
		$title = $this->plain_text( get_the_title( $post ) );

		return '' !== $title ? $title : __( '(no title)', 'tbsh-ai-visibility-control' );
	}

	/**
	 * Short plain-text description: the manual excerpt, or the first 30 words.
	 *
	 * @since    1.3.0
	 * @param    WP_Post    $post    Post.
	 * @return   string
	 */
	private function get_post_summary( $post ) {
		if ( has_excerpt( $post ) ) {
			$text = $post->post_excerpt;
		} else {
			$text = $this->strip_shortcodes( excerpt_remove_blocks( $post->post_content ) );
		}

		return wp_trim_words( $this->plain_text( $text ), 30, '…' );
	}

	/**
	 * Remove shortcodes, including ones whose plugin does not register them in
	 * the current request (they would otherwise appear as raw "[tag]" text).
	 *
	 * @since    1.3.0
	 * @param    string    $content    Post content.
	 * @return   string
	 */
	private function strip_shortcodes( $content ) {
		$content = strip_shortcodes( (string) $content );

		return preg_replace( '/\[\/?[a-z][a-z0-9_\-]*(?:\s[^\[\]]*)?\/?\]/', '', $content );
	}

	/**
	 * Strip tags, decode entities and collapse whitespace.
	 *
	 * @since    1.3.0
	 * @param    string    $text    Text or HTML.
	 * @return   string
	 */
	private function plain_text( $text ) {
		$text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' );

		return trim( preg_replace( '/\s+/u', ' ', $text ) );
	}

	/**
	 * Post content as Markdown.
	 *
	 * @since    1.4.0
	 * @param    WP_Post    $post    Post.
	 * @return   string
	 */
	private function get_post_markdown( $post ) {
		$content = $this->strip_shortcodes( $post->post_content );
		if ( ! has_blocks( $content ) ) {
			$content = wpautop( $content );
		}

		return TAVC_Markdown::from_html( $content );
	}

	/*
	|--------------------------------------------------------------------------
	| Markdown versions of pages (1.4.0)
	|--------------------------------------------------------------------------
	*/

	/**
	 * Address of a post's Markdown version: the permalink plus ".md", or
	 * ?tavc_markdown=1 when the site uses plain permalinks.
	 *
	 * @since    1.4.0
	 * @param    WP_Post|int    $post    Post or ID.
	 * @return   string
	 */
	public function get_markdown_url( $post ) {
		$post      = get_post( $post );
		$permalink = $post ? get_permalink( $post ) : '';
		if ( ! $permalink ) {
			return '';
		}

		// Plain permalinks, or a permalink that carries a query string (some
		// translation plugins add ?lang=): use the query var.
		if ( '' === (string) get_option( 'permalink_structure' ) || false !== strpos( $permalink, '?' ) ) {
			return add_query_arg( self::MARKDOWN_QUERY_VAR, '1', $permalink );
		}

		// A static front page lives at the home URL, which has no slug to extend.
		if ( 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) === (int) $post->ID ) {
			return home_url( '/index.md' );
		}

		return untrailingslashit( $permalink ) . '.md';
	}

	/**
	 * Point "/some-page.md" requests at the post behind "/some-page".
	 *
	 * Hooked to the "request" filter: the rewrite rules do not know the ".md"
	 * suffix, so the post is resolved from the path and the query is rewritten
	 * to that post, flagged with the Markdown query var.
	 *
	 * @since    1.4.0
	 * @param    array    $query_vars    Parsed query vars.
	 * @return   array
	 */
	public function route_markdown_request( $query_vars ) {
		$path = $this->get_markdown_request_path();
		if ( null === $path ) {
			return $query_vars;
		}

		$settings = TAVC_Settings::get_llms_settings();
		if ( empty( $settings['markdown_pages'] ) ) {
			return $query_vars;
		}

		if ( '/index.md' === strtolower( $path ) ) {
			$post_id = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
		} else {
			$post_id = url_to_postid( home_url( substr( $path, 0, -3 ) ) );
		}

		if ( $post_id <= 0 ) {
			return $query_vars;
		}

		$post_type = get_post_type( $post_id );
		if ( ! $post_type ) {
			return $query_vars;
		}

		return array(
			'p'                      => $post_id,
			'post_type'              => $post_type,
			self::MARKDOWN_QUERY_VAR => '1',
		);
	}

	/**
	 * Serve the Markdown version of the queried post.
	 *
	 * Runs on template_redirect before the canonical redirect. For posts that
	 * llms.txt would not list (other content types, excluded, noindex,
	 * password-protected), a ".md" address gets a normal 404 page and the
	 * ?tavc_markdown=1 form shows the normal page. While the feature is off
	 * the query var is ignored.
	 *
	 * @since    1.4.0
	 */
	public function render_markdown_page() {
		if ( '1' !== (string) get_query_var( self::MARKDOWN_QUERY_VAR ) ) {
			return;
		}

		$settings = TAVC_Settings::get_llms_settings();
		if ( empty( $settings['markdown_pages'] ) ) {
			return;
		}

		$post = get_queried_object();
		if ( ! ( $post instanceof WP_Post ) || ! $this->is_markdown_available( $post, $settings ) ) {
			if ( null !== $this->get_markdown_request_path() ) {
				$this->send_not_found();
			}
			return;
		}

		$output = $this->generate_markdown_page( $post );

		header( 'Content-Type: text/markdown; charset=utf-8' );
		header( 'X-Robots-Tag: noindex, follow' );

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text Markdown, not HTML.
		exit;
	}

	/**
	 * Markdown document for one post.
	 *
	 * @since    1.4.0
	 * @param    WP_Post    $post    Post.
	 * @return   string
	 */
	public function generate_markdown_page( $post ) {
		$markdown = $this->get_post_markdown( $post );

		$output  = '# ' . $this->get_post_title( $post ) . "\n\n";
		$output .= 'URL: ' . get_permalink( $post ) . "\n";
		$output .= 'Published: ' . get_the_date( 'Y-m-d', $post ) . "\n";
		if ( get_the_modified_date( 'Y-m-d', $post ) !== get_the_date( 'Y-m-d', $post ) ) {
			$output .= 'Updated: ' . get_the_modified_date( 'Y-m-d', $post ) . "\n";
		}
		$body = '' !== $markdown ? $markdown : $this->get_post_summary( $post );
		if ( '' !== $body ) {
			$output .= "\n" . $body . "\n";
		}

		/**
		 * Filters the Markdown version of a post.
		 *
		 * @since 1.4.0
		 *
		 * @param string  $output Markdown text.
		 * @param WP_Post $post   Post.
		 */
		$filtered = apply_filters( 'tavc_markdown_page_output', $output, $post );

		return is_string( $filtered ) ? $filtered : $output;
	}

	/**
	 * Advertise the Markdown version in the page head.
	 *
	 * @since    1.4.0
	 */
	public function print_markdown_alternate_link() {
		if ( ! is_singular() ) {
			return;
		}

		$settings = TAVC_Settings::get_llms_settings();
		$post     = get_queried_object();
		if ( empty( $settings['markdown_pages'] ) || ! ( $post instanceof WP_Post ) || ! $this->is_markdown_available( $post, $settings ) ) {
			return;
		}

		printf( '<link rel="alternate" type="text/markdown" href="%s">' . "\n", esc_url( $this->get_markdown_url( $post ) ) );
	}

	/**
	 * Whether a post has a Markdown version: the same rules as llms.txt.
	 *
	 * @since    1.4.0
	 * @param    WP_Post    $post        Post.
	 * @param    array      $settings    llms.txt settings.
	 * @return   bool
	 */
	public function is_markdown_available( $post, $settings ) {
		if ( 'publish' !== $post->post_status || '' !== (string) $post->post_password ) {
			return false;
		}

		if ( ! in_array( $post->post_type, $this->get_selected_post_types( $settings ), true ) || ! is_post_publicly_viewable( $post ) ) {
			return false;
		}

		if ( '1' === (string) get_post_meta( $post->ID, self::EXCLUDE_META_KEY, true ) ) {
			return false;
		}

		if ( ! empty( $settings['exclude_noindex'] ) && $this->is_aioseo_noindex( $post->ID ) ) {
			return false;
		}

		return ! $this->is_post_excluded( $post, $settings, 'markdown' );
	}

	/**
	 * Content types chosen for llms.txt, after the tavc_llms_txt_post_types filter.
	 *
	 * @since    1.4.0
	 * @param    array    $settings    llms.txt settings.
	 * @return   string[]
	 */
	private function get_selected_post_types( $settings ) {
		/** This filter is documented in includes/class-tavc-llms-txt.php */
		$post_types = apply_filters( 'tavc_llms_txt_post_types', $settings['post_types'] );
		$post_types = is_array( $post_types ) ? array_values( array_filter( array_map( 'sanitize_key', $post_types ) ) ) : array();

		return empty( $post_types ) ? array( 'post', 'page' ) : $post_types;
	}

	/**
	 * Whether All in One SEO marks one post as noindex (stored in its own table).
	 *
	 * @since    1.4.0
	 * @param    int    $post_id    Post ID.
	 * @return   bool
	 */
	private function is_aioseo_noindex( $post_id ) {
		global $wpdb;

		$aioseo_table = $wpdb->prefix . 'aioseo_posts';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $aioseo_table ) ) ) !== $aioseo_table ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT 1 FROM %i WHERE post_id = %d AND robots_default = 0 AND robots_noindex = 1', $aioseo_table, $post_id ) );
	}

	/**
	 * Whether the current request asks for a Markdown version.
	 *
	 * @since    1.4.0
	 * @return   bool
	 */
	private function is_markdown_request() {
		if ( '1' === (string) get_query_var( self::MARKDOWN_QUERY_VAR ) ) {
			return true;
		}

		if ( null === $this->get_markdown_request_path() ) {
			return false;
		}

		// When the feature is off, ".md" URLs keep the normal 404 handling.
		$settings = TAVC_Settings::get_llms_settings();

		return ! empty( $settings['markdown_pages'] );
	}

	/**
	 * Request path relative to the home URL when it ends in ".md", else null.
	 *
	 * @since    1.4.0
	 * @return   string|null    For example "/about.md".
	 */
	private function get_markdown_request_path() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return null;
		}

		$path = wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
		if ( ! is_string( $path ) || strlen( $path ) < 5 || '.md' !== strtolower( substr( $path, -3 ) ) ) {
			return null;
		}

		$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$home_path = is_string( $home_path ) ? untrailingslashit( $home_path ) : '';
		if ( '' !== $home_path ) {
			if ( 0 !== strpos( $path, $home_path . '/' ) ) {
				return null;
			}
			$path = substr( $path, strlen( $home_path ) );
		}

		return $path;
	}

	/**
	 * Clear cache when a post is saved, updated or deleted.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    The post ID.
	 */
	public function clear_llms_txt_cache( $post_id = 0 ) {
		// Ignore revisions or autosaves
		if ( $post_id && ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) ) {
			return;
		}

		delete_transient( self::CACHE_KEY );
		delete_transient( self::FULL_CACHE_KEY );
	}

	/**
	 * Clear both caches after the llms.txt settings change.
	 *
	 * @since    1.3.0
	 */
	public function clear_cache_on_settings_change() {
		$this->clear_llms_txt_cache();
	}
}
