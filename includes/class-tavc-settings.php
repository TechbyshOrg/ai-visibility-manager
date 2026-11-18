<?php
/**
 * Settings defaults, getters and sanitizers.
 *
 * Every option added in 1.3.0 is read through this class so callers always get
 * a complete, validated array even on installs that have never saved it.
 *
 * @link       https://techbysh.com
 * @since      1.3.0
 *
 * @package    Tavc
 * @subpackage Tavc/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings defaults, getters and sanitizers.
 *
 * @since      1.3.0
 * @package    Tavc
 * @subpackage Tavc/includes
 * @author     Techbysh
 */
class TAVC_Settings {

	/**
	 * Maximum number of custom robots.txt groups.
	 */
	const MAX_ROBOTS_GROUPS = 50;

	/**
	 * Maximum number of User-agent or Disallow lines per custom group.
	 */
	const MAX_ROBOTS_LINES_PER_GROUP = 50;

	/**
	 * Allowed crawl rate limits, in requests per minute per crawler.
	 */
	const RATE_LIMITS = array( 10, 30, 60, 120, 300 );

	/**
	 * Settings errors already reported in this request, to avoid duplicates when
	 * WordPress runs a sanitizer twice for a brand-new option.
	 *
	 * @since    1.3.0
	 * @var      array
	 */
	private static $reported_errors = array();

	/*
	|--------------------------------------------------------------------------
	| llms.txt settings (option: tavc_llms_txt_settings)
	|--------------------------------------------------------------------------
	*/

	/**
	 * Default llms.txt settings. These reproduce the pre-1.3.0 output.
	 *
	 * @since    1.3.0
	 * @return   array
	 */
	public static function get_llms_defaults() {
		return array(
			'post_types'       => array( 'post', 'page' ),
			'intro'            => '',
			'include_excerpts' => 0,
			'group_by_type'    => 0,
			'max_per_type'     => 0,
			'exclude_noindex'  => 1,
			'full_enabled'     => 0,
			'full_max_items'   => 100,
			'markdown_pages'   => 0,
			'markdown_links'   => 0,
		);
	}

	/**
	 * Saved llms.txt settings merged over the defaults.
	 *
	 * @since    1.3.0
	 * @return   array
	 */
	public static function get_llms_settings() {
		$saved    = get_option( 'tavc_llms_txt_settings', array() );
		$defaults = self::get_llms_defaults();
		$settings = wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );

		$settings['post_types'] = is_array( $settings['post_types'] ) ? array_values( array_filter( array_map( 'sanitize_key', $settings['post_types'] ) ) ) : $defaults['post_types'];
		if ( empty( $settings['post_types'] ) ) {
			$settings['post_types'] = $defaults['post_types'];
		}

		$settings['intro']            = (string) $settings['intro'];
		$settings['include_excerpts'] = self::to_flag( $settings['include_excerpts'] );
		$settings['group_by_type']    = self::to_flag( $settings['group_by_type'] );
		$settings['max_per_type']     = absint( $settings['max_per_type'] );
		$settings['exclude_noindex']  = self::to_flag( $settings['exclude_noindex'] );
		$settings['full_enabled']     = self::to_flag( $settings['full_enabled'] );
		$settings['full_max_items']   = max( 1, absint( $settings['full_max_items'] ) );
		$settings['markdown_pages']   = self::to_flag( $settings['markdown_pages'] );
		$settings['markdown_links']   = self::to_flag( $settings['markdown_links'] );

		return array_intersect_key( $settings, $defaults );
	}

	/**
	 * Public post types that can be listed in llms.txt.
	 *
	 * @since    1.3.0
	 * @return   array    Post type slug => WP_Post_Type.
	 */
	public static function get_llms_post_type_choices() {
		$types = get_post_types( array( 'public' => true ), 'objects' );
		unset( $types['attachment'] );

		return $types;
	}

	/**
	 * Sanitize the llms.txt settings form.
	 *
	 * @since    1.3.0
	 * @param    mixed    $input    Submitted value.
	 * @return   array
	 */
	public static function sanitize_llms_settings( $input ) {
		if ( ! is_array( $input ) ) {
			return self::get_llms_settings();
		}

		$defaults = self::get_llms_defaults();
		$choices  = array_keys( self::get_llms_post_type_choices() );

		$post_types = isset( $input['post_types'] ) && is_array( $input['post_types'] ) ? array_map( 'sanitize_key', $input['post_types'] ) : array();
		$post_types = array_values( array_intersect( $post_types, $choices ) );

		if ( empty( $post_types ) ) {
			$post_types = array_values( array_intersect( $defaults['post_types'], $choices ) );
			self::report_error(
				'tavc_llms_txt_settings',
				'tavc_llms_no_post_types',
				__( 'Select at least one content type for llms.txt. Posts and Pages were selected for you.', 'tbsh-ai-visibility-control' )
			);
		}

		// options.php has already unslashed the submitted values.
		$intro = isset( $input['intro'] ) ? sanitize_textarea_field( (string) $input['intro'] ) : '';
		if ( function_exists( 'mb_substr' ) ) {
			$intro = mb_substr( $intro, 0, 2000 );
		} else {
			$intro = substr( $intro, 0, 2000 );
		}

		return array(
			'post_types'       => $post_types,
			'intro'            => $intro,
			'include_excerpts' => self::to_flag( isset( $input['include_excerpts'] ) ? $input['include_excerpts'] : 0 ),
			'group_by_type'    => self::to_flag( isset( $input['group_by_type'] ) ? $input['group_by_type'] : 0 ),
			'max_per_type'     => min( 5000, absint( isset( $input['max_per_type'] ) ? $input['max_per_type'] : 0 ) ),
			'exclude_noindex'  => self::to_flag( isset( $input['exclude_noindex'] ) ? $input['exclude_noindex'] : 0 ),
			'full_enabled'     => self::to_flag( isset( $input['full_enabled'] ) ? $input['full_enabled'] : 0 ),
			'full_max_items'   => min( 500, max( 1, absint( isset( $input['full_max_items'] ) ? $input['full_max_items'] : $defaults['full_max_items'] ) ) ),
			'markdown_pages'   => self::to_flag( isset( $input['markdown_pages'] ) ? $input['markdown_pages'] : 0 ),
			'markdown_links'   => self::to_flag( isset( $input['markdown_links'] ) ? $input['markdown_links'] : 0 ),
		);
	}

	/*
	|--------------------------------------------------------------------------
	| Tracking & data settings (option: tavc_tracking_settings)
	|--------------------------------------------------------------------------
	*/

	/**
	 * Default tracking settings.
	 *
	 * Referral logs are kept forever by default so upgrading never deletes data
	 * that earlier versions kept.
	 *
	 * @since    1.3.0
	 * @return   array
	 */
	public static function get_tracking_defaults() {
		return array(
			'utm_detection'           => 1,
			'beacon_tracking'         => 0,
			'crawler_logging'         => 1,
			'referral_retention_days' => 0,
			'crawler_retention_days'  => 90,
			'digest_enabled'          => 0,
			'digest_email'            => '',
			'digest_frequency'        => 'weekly',
		);
	}

	/**
	 * Saved tracking settings merged over the defaults.
	 *
	 * @since    1.3.0
	 * @return   array
	 */
	public static function get_tracking_settings() {
		$saved    = get_option( 'tavc_tracking_settings', array() );
		$defaults = self::get_tracking_defaults();
		$settings = wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
		$choices  = array_keys( self::get_retention_choices() );

		$settings['utm_detection']   = self::to_flag( $settings['utm_detection'] );
		$settings['beacon_tracking'] = self::to_flag( $settings['beacon_tracking'] );
		$settings['crawler_logging'] = self::to_flag( $settings['crawler_logging'] );
		$settings['digest_enabled']  = self::to_flag( $settings['digest_enabled'] );
		$settings['digest_email']    = is_string( $settings['digest_email'] ) ? $settings['digest_email'] : '';

		if ( ! in_array( $settings['digest_frequency'], array( 'weekly', 'monthly' ), true ) ) {
			$settings['digest_frequency'] = $defaults['digest_frequency'];
		}

		foreach ( array( 'referral_retention_days', 'crawler_retention_days' ) as $key ) {
			$settings[ $key ] = absint( $settings[ $key ] );
			if ( ! in_array( $settings[ $key ], $choices, true ) ) {
				$settings[ $key ] = $defaults[ $key ];
			}
		}

		return array_intersect_key( $settings, $defaults );
	}

	/**
	 * Log retention choices.
	 *
	 * @since    1.3.0
	 * @return   array    Days (0 = forever) => label.
	 */
	public static function get_retention_choices() {
		return array(
			0   => __( 'Forever', 'tbsh-ai-visibility-control' ),
			30  => __( '30 days', 'tbsh-ai-visibility-control' ),
			90  => __( '90 days', 'tbsh-ai-visibility-control' ),
			180 => __( '180 days', 'tbsh-ai-visibility-control' ),
			365 => __( '1 year', 'tbsh-ai-visibility-control' ),
		);
	}

	/**
	 * Summary email frequencies.
	 *
	 * @since    1.4.0
	 * @return   array    Frequency => label.
	 */
	public static function get_digest_frequency_choices() {
		return array(
			'weekly'  => __( 'Weekly (Monday morning)', 'tbsh-ai-visibility-control' ),
			'monthly' => __( 'Monthly (1st of the month)', 'tbsh-ai-visibility-control' ),
		);
	}

	/**
	 * Address the weekly summary is sent to.
	 *
	 * @since    1.3.0
	 * @return   string
	 */
	public static function get_digest_recipient() {
		$settings = self::get_tracking_settings();
		$email    = sanitize_email( $settings['digest_email'] );

		return is_email( $email ) ? $email : (string) get_option( 'admin_email' );
	}

	/**
	 * Sanitize the tracking settings form.
	 *
	 * @since    1.3.0
	 * @param    mixed    $input    Submitted value.
	 * @return   array
	 */
	public static function sanitize_tracking_settings( $input ) {
		if ( ! is_array( $input ) ) {
			return self::get_tracking_settings();
		}

		$defaults = self::get_tracking_defaults();
		$choices  = array_keys( self::get_retention_choices() );
		$output   = array();

		foreach ( array( 'utm_detection', 'beacon_tracking', 'crawler_logging', 'digest_enabled' ) as $key ) {
			$output[ $key ] = self::to_flag( isset( $input[ $key ] ) ? $input[ $key ] : 0 );
		}

		foreach ( array( 'referral_retention_days', 'crawler_retention_days' ) as $key ) {
			$days           = absint( isset( $input[ $key ] ) ? $input[ $key ] : $defaults[ $key ] );
			$output[ $key ] = in_array( $days, $choices, true ) ? $days : $defaults[ $key ];
		}

		$email = isset( $input['digest_email'] ) ? sanitize_email( (string) $input['digest_email'] ) : '';
		if ( '' !== $email && ! is_email( $email ) ) {
			$email = '';
		}
		if ( isset( $input['digest_email'] ) && '' !== trim( (string) $input['digest_email'] ) && '' === $email ) {
			self::report_error(
				'tavc_tracking_settings',
				'tavc_digest_email_invalid',
				__( 'The summary email address is not valid. The site admin email will be used instead.', 'tbsh-ai-visibility-control' )
			);
		}
		$output['digest_email'] = $email;

		$frequency                  = isset( $input['digest_frequency'] ) ? sanitize_key( (string) $input['digest_frequency'] ) : 'weekly';
		$output['digest_frequency'] = in_array( $frequency, array( 'weekly', 'monthly' ), true ) ? $frequency : 'weekly';

		return $output;
	}

	/*
	|--------------------------------------------------------------------------
	| Crawler enforcement (option: tavc_enforcement_settings)
	|--------------------------------------------------------------------------
	*/

	/**
	 * Default enforcement settings. Everything is off: robots.txt rules alone
	 * are the pre-1.4.0 behaviour.
	 *
	 * @since    1.4.0
	 * @return   array
	 */
	public static function get_enforcement_defaults() {
		return array(
			'enforce_blocks'        => 0,
			'rate_limit'            => 0,
			'rate_limit_per_minute' => 60,
		);
	}

	/**
	 * Requests-per-minute choices for the crawl rate limit.
	 *
	 * @since    1.4.0
	 * @return   array    Requests per minute => label.
	 */
	public static function get_rate_limit_choices() {
		$choices = array();
		foreach ( self::RATE_LIMITS as $limit ) {
			$choices[ $limit ] = sprintf(
				/* translators: %s: number of requests. */
				__( '%s requests per minute', 'tbsh-ai-visibility-control' ),
				number_format_i18n( $limit )
			);
		}

		return $choices;
	}

	/**
	 * Saved enforcement settings merged over the defaults.
	 *
	 * Runs on every front-end request, so it only reads the autoloaded option.
	 *
	 * @since    1.4.0
	 * @return   array
	 */
	public static function get_enforcement_settings() {
		$saved    = get_option( 'tavc_enforcement_settings', array() );
		$defaults = self::get_enforcement_defaults();
		$settings = wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );

		$settings['enforce_blocks']        = self::to_flag( $settings['enforce_blocks'] );
		$settings['rate_limit']            = self::to_flag( $settings['rate_limit'] );
		$settings['rate_limit_per_minute'] = self::validate_rate_limit( $settings['rate_limit_per_minute'] );

		return array_intersect_key( $settings, $defaults );
	}

	/**
	 * A rate limit from the allowed choices, or the default.
	 *
	 * Checked against a fixed list (not the translated labels) because the
	 * getter runs on front-end requests.
	 *
	 * @since    1.4.0
	 * @param    mixed    $value    Raw value.
	 * @return   int
	 */
	private static function validate_rate_limit( $value ) {
		$value = absint( $value );

		return in_array( $value, self::RATE_LIMITS, true ) ? $value : 60;
	}

	/**
	 * Sanitize the enforcement settings form.
	 *
	 * @since    1.4.0
	 * @param    mixed    $input    Submitted value.
	 * @return   array
	 */
	public static function sanitize_enforcement_settings( $input ) {
		if ( ! is_array( $input ) ) {
			return self::get_enforcement_settings();
		}

		return array(
			'enforce_blocks'        => self::to_flag( isset( $input['enforce_blocks'] ) ? $input['enforce_blocks'] : 0 ),
			'rate_limit'            => self::to_flag( isset( $input['rate_limit'] ) ? $input['rate_limit'] : 0 ),
			'rate_limit_per_minute' => self::validate_rate_limit( isset( $input['rate_limit_per_minute'] ) ? $input['rate_limit_per_minute'] : 60 ),
		);
	}

	/*
	|--------------------------------------------------------------------------
	| Content signals (option: tavc_content_signals)
	|--------------------------------------------------------------------------
	*/

	/**
	 * Content signal definitions.
	 *
	 * @since    1.3.0
	 * @return   array    Option key => array( 'token', 'label', 'desc' ).
	 */
	public static function get_content_signal_definitions() {
		return array(
			'search'   => array(
				'token' => 'search',
				'label' => __( 'Search', 'tbsh-ai-visibility-control' ),
				'desc'  => __( 'Building a search index and showing links and short excerpts in search results. Does not cover AI-generated summaries.', 'tbsh-ai-visibility-control' ),
			),
			'ai_input' => array(
				'token' => 'ai-input',
				'label' => __( 'AI input', 'tbsh-ai-visibility-control' ),
				'desc'  => __( 'Using your content as input for AI-generated answers, such as AI search summaries.', 'tbsh-ai-visibility-control' ),
			),
			'ai_train' => array(
				'token' => 'ai-train',
				'label' => __( 'AI training', 'tbsh-ai-visibility-control' ),
				'desc'  => __( 'Training or fine-tuning AI models on your content.', 'tbsh-ai-visibility-control' ),
			),
		);
	}

	/**
	 * Saved content signals. An empty string means "no preference stated".
	 *
	 * @since    1.3.0
	 * @return   array    Key => '', 'yes' or 'no'.
	 */
	public static function get_content_signals() {
		$saved   = get_option( 'tavc_content_signals', array() );
		$saved   = is_array( $saved ) ? $saved : array();
		$signals = array();

		foreach ( array_keys( self::get_content_signal_definitions() ) as $key ) {
			$value           = isset( $saved[ $key ] ) ? $saved[ $key ] : '';
			$signals[ $key ] = in_array( $value, array( 'yes', 'no' ), true ) ? $value : '';
		}

		return $signals;
	}

	/**
	 * Sanitize the content signals form.
	 *
	 * @since    1.3.0
	 * @param    mixed    $input    Submitted value.
	 * @return   array
	 */
	public static function sanitize_content_signals( $input ) {
		if ( ! is_array( $input ) ) {
			return self::get_content_signals();
		}

		$output = array();
		foreach ( array_keys( self::get_content_signal_definitions() ) as $key ) {
			$value          = isset( $input[ $key ] ) ? sanitize_key( $input[ $key ] ) : '';
			$output[ $key ] = in_array( $value, array( 'yes', 'no' ), true ) ? $value : '';
		}

		return $output;
	}

	/*
	|--------------------------------------------------------------------------
	| Custom robots.txt rules (option: tavc_custom_robots_rules)
	|--------------------------------------------------------------------------
	*/

	/**
	 * Saved custom rule groups.
	 *
	 * @since    1.3.0
	 * @return   array    List of array( 'agents' => string[], 'paths' => string[] ).
	 */
	public static function get_custom_robots_rules() {
		$saved = get_option( 'tavc_custom_robots_rules', array() );
		if ( ! is_array( $saved ) ) {
			return array();
		}

		$parsed = self::validate_rule_groups( $saved );

		return $parsed['groups'];
	}

	/**
	 * Convert stored groups back to the robots.txt-style text shown in the editor.
	 *
	 * @since    1.3.0
	 * @param    array    $groups    Rule groups.
	 * @return   string
	 */
	public static function custom_robots_rules_to_text( $groups ) {
		$blocks = array();

		foreach ( $groups as $group ) {
			$lines = array();
			foreach ( $group['agents'] as $agent ) {
				$lines[] = 'User-agent: ' . $agent;
			}
			foreach ( $group['paths'] as $path ) {
				$lines[] = 'Disallow: ' . $path;
			}
			$blocks[] = implode( "\n", $lines );
		}

		return implode( "\n\n", $blocks );
	}

	/**
	 * Sanitize the custom rules editor.
	 *
	 * Accepts the raw textarea string from the form, or an already structured
	 * array (WordPress may run the sanitizer a second time on its own output).
	 *
	 * @since    1.3.0
	 * @param    mixed    $input    Submitted value.
	 * @return   array
	 */
	public static function sanitize_custom_robots_rules( $input ) {
		if ( is_array( $input ) ) {
			$parsed = self::validate_rule_groups( $input );
			return $parsed['groups'];
		}

		if ( ! is_string( $input ) ) {
			return self::get_custom_robots_rules();
		}

		$parsed = self::parse_robots_rules_text( $input );

		if ( ! empty( $parsed['errors'] ) ) {
			// Quote the first few rejected lines so they are easy to find.
			$lines  = preg_split( '/\r\n|\r|\n/', $input );
			$quoted = array();
			foreach ( array_slice( $parsed['errors'], 0, 3 ) as $line_number ) {
				$text = isset( $lines[ $line_number - 1 ] ) ? trim( $lines[ $line_number - 1 ] ) : '';
				if ( function_exists( 'mb_strimwidth' ) ) {
					$text = mb_strimwidth( $text, 0, 60, '...', 'UTF-8' );
				} elseif ( strlen( $text ) > 60 ) {
					$text = substr( $text, 0, 57 ) . '...';
				}
				$quoted[] = sprintf(
					/* translators: 1: line number, 2: the rejected line. */
					__( 'line %1$s ("%2$s")', 'tbsh-ai-visibility-control' ),
					$line_number,
					$text
				);
			}
			if ( count( $parsed['errors'] ) > 3 ) {
				$quoted[] = sprintf(
					/* translators: %s: number of further rejected lines. */
					__( 'and %s more', 'tbsh-ai-visibility-control' ),
					count( $parsed['errors'] ) - 3
				);
			}

			self::report_error(
				'tavc_custom_robots_rules',
				'tavc_custom_robots_invalid',
				sprintf(
					/* translators: %s: list of rejected lines, e.g. line 3 ("Allow: /x"). */
					__( 'Some custom robots.txt lines were not saved because they are not valid: %s. Use "User-agent: Name" followed by one or more "Disallow: /path" lines.', 'tbsh-ai-visibility-control' ),
					implode( ', ', $quoted )
				)
			);
		}

		return $parsed['groups'];
	}

	/**
	 * Parse robots.txt-style text into validated rule groups.
	 *
	 * @since    1.3.0
	 * @param    string    $text    Raw editor text.
	 * @return   array              array( 'groups' => array, 'errors' => int[] line numbers ).
	 */
	public static function parse_robots_rules_text( $text ) {
		$lines   = preg_split( '/\r\n|\r|\n/', (string) $text );
		$groups  = array();
		$errors  = array();
		$current = null;

		foreach ( $lines as $index => $line ) {
			$line_number = $index + 1;
			$line        = trim( $line );

			if ( '' === $line || 0 === strpos( $line, '#' ) ) {
				continue;
			}

			if ( ! preg_match( '/^(user-agent|disallow)\s*:\s*(.*)$/i', $line, $match ) ) {
				$errors[] = $line_number;
				continue;
			}

			$directive = strtolower( $match[1] );
			$value     = trim( $match[2] );

			if ( 'user-agent' === $directive ) {
				if ( ! self::is_valid_agent( $value ) ) {
					$errors[] = $line_number;
					continue;
				}

				// A User-agent line after Disallow lines starts a new group.
				if ( null === $current || ! empty( $current['paths'] ) ) {
					if ( null !== $current ) {
						$groups[] = $current;
					}
					$current = array(
						'agents' => array(),
						'paths'  => array(),
						'line'   => $line_number,
					);
				}

				if ( count( $current['agents'] ) < self::MAX_ROBOTS_LINES_PER_GROUP && ! in_array( $value, $current['agents'], true ) ) {
					$current['agents'][] = $value;
				}
				continue;
			}

			// Disallow line.
			if ( null === $current || ! self::is_valid_path( $value ) ) {
				$errors[] = $line_number;
				continue;
			}

			if ( count( $current['paths'] ) < self::MAX_ROBOTS_LINES_PER_GROUP && ! in_array( $value, $current['paths'], true ) ) {
				$current['paths'][] = $value;
			}
		}

		if ( null !== $current ) {
			$groups[] = $current;
		}

		$valid = array();
		foreach ( $groups as $group ) {
			if ( empty( $group['paths'] ) ) {
				// User-agent lines without any Disallow rule.
				$errors[] = $group['line'];
				continue;
			}
			if ( count( $valid ) >= self::MAX_ROBOTS_GROUPS ) {
				$errors[] = $group['line'];
				continue;
			}
			$valid[] = array(
				'agents' => $group['agents'],
				'paths'  => $group['paths'],
			);
		}

		$errors = array_values( array_unique( $errors ) );
		sort( $errors );

		return array(
			'groups' => $valid,
			'errors' => $errors,
		);
	}

	/**
	 * Re-validate structured rule groups.
	 *
	 * @since    1.3.0
	 * @param    array    $groups    Candidate groups.
	 * @return   array               array( 'groups' => array ).
	 */
	private static function validate_rule_groups( $groups ) {
		$valid = array();

		foreach ( $groups as $group ) {
			if ( count( $valid ) >= self::MAX_ROBOTS_GROUPS ) {
				break;
			}
			if ( ! is_array( $group ) || empty( $group['agents'] ) || empty( $group['paths'] ) || ! is_array( $group['agents'] ) || ! is_array( $group['paths'] ) ) {
				continue;
			}

			$agents = array_values( array_unique( array_filter( array_map( 'strval', $group['agents'] ), array( __CLASS__, 'is_valid_agent' ) ) ) );
			$paths  = array_values( array_unique( array_filter( array_map( 'strval', $group['paths'] ), array( __CLASS__, 'is_valid_path' ) ) ) );

			if ( empty( $agents ) || empty( $paths ) ) {
				continue;
			}

			$valid[] = array(
				'agents' => array_slice( $agents, 0, self::MAX_ROBOTS_LINES_PER_GROUP ),
				'paths'  => array_slice( $paths, 0, self::MAX_ROBOTS_LINES_PER_GROUP ),
			);
		}

		return array( 'groups' => $valid );
	}

	/**
	 * Whether a User-agent token is safe to print in robots.txt.
	 *
	 * @since    1.3.0
	 * @param    string    $agent    Token.
	 * @return   bool
	 */
	public static function is_valid_agent( $agent ) {
		return is_string( $agent ) && 1 === preg_match( '/^(\*|[A-Za-z0-9][A-Za-z0-9._\-]{0,99})$/', $agent );
	}

	/**
	 * Whether a Disallow path is safe to print in robots.txt.
	 *
	 * @since    1.3.0
	 * @param    string    $path    Path starting with "/".
	 * @return   bool
	 */
	public static function is_valid_path( $path ) {
		return is_string( $path ) && 1 === preg_match( '/^\/[^\s#]{0,254}$/', $path );
	}

	/*
	|--------------------------------------------------------------------------
	| Helpers
	|--------------------------------------------------------------------------
	*/

	/**
	 * Normalize a checkbox-like value to 0 or 1.
	 *
	 * @since    1.3.0
	 * @param    mixed    $value    Raw value.
	 * @return   int
	 */
	public static function to_flag( $value ) {
		return 1 === absint( $value ) ? 1 : 0;
	}

	/**
	 * Add a settings error once per request.
	 *
	 * @since    1.3.0
	 * @param    string    $setting    Setting slug.
	 * @param    string    $code       Error code.
	 * @param    string    $message    Message.
	 */
	private static function report_error( $setting, $code, $message ) {
		if ( isset( self::$reported_errors[ $code ] ) || ! function_exists( 'add_settings_error' ) ) {
			return;
		}

		self::$reported_errors[ $code ] = true;
		add_settings_error( $setting, $code, $message, 'warning' );
	}
}
