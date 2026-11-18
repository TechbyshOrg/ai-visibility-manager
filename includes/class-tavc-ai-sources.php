<?php
/**
 * AI referral source registry.
 *
 * Maps AI assistant referrer domains and utm_source values to a display name,
 * and detects which AI platform sent a visitor.
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
 * AI referral source registry.
 *
 * @since      1.3.0
 * @package    Tavc
 * @subpackage Tavc/includes
 * @author     Techbysh
 */
class TAVC_AI_Sources {

	/**
	 * How a referral was detected: HTTP Referer header.
	 */
	const METHOD_REFERRER = 'referrer';

	/**
	 * How a referral was detected: utm_source query parameter.
	 */
	const METHOD_UTM = 'utm';

	/**
	 * How a referral was detected: cache-compatible browser beacon.
	 */
	const METHOD_BEACON = 'beacon';

	/**
	 * Referrer domains of AI platforms. Subdomains match too.
	 *
	 * @since    1.3.0
	 * @return   array    Lower-case domain => display name.
	 */
	public static function get_referrer_domains() {
		$domains = array(
			'chatgpt.com'           => 'ChatGPT',
			'chat.openai.com'       => 'ChatGPT',
			'perplexity.ai'         => 'Perplexity',
			'claude.ai'             => 'Claude',
			'copilot.microsoft.com' => 'Copilot',
			'copilot.com'           => 'Copilot',
			'gemini.google.com'     => 'Gemini',
			'bard.google.com'       => 'Bard',
			'grok.com'              => 'Grok',
			'grok.x.ai'             => 'Grok',
			'meta.ai'               => 'Meta AI',
			'chat.deepseek.com'     => 'DeepSeek',
			'chat.mistral.ai'       => 'Mistral',
			'you.com'               => 'You.com',
			'phind.com'             => 'Phind',
			'poe.com'               => 'Poe',
			'duck.ai'               => 'Duck.ai',
		);

		/**
		 * Filters the AI referrer domains used to detect AI referral visits.
		 *
		 * @since 1.3.0
		 *
		 * @param array $domains Domain => display name. Subdomains of each domain also match.
		 */
		$filtered = apply_filters( 'tavc_ai_referrer_domains', $domains );

		return self::normalize_map( is_array( $filtered ) ? $filtered : $domains );
	}

	/**
	 * utm_source values that AI platforms add to the links they cite.
	 *
	 * @since    1.3.0
	 * @return   array    Lower-case utm_source value => display name.
	 */
	public static function get_utm_sources() {
		$sources = array(
			'chatgpt.com'       => 'ChatGPT',
			'chatgpt'           => 'ChatGPT',
			'openai'            => 'ChatGPT',
			'perplexity'        => 'Perplexity',
			'perplexity.ai'     => 'Perplexity',
			'claude'            => 'Claude',
			'claude.ai'         => 'Claude',
			'copilot'           => 'Copilot',
			'copilot.com'       => 'Copilot',
			'gemini'            => 'Gemini',
			'gemini.google.com' => 'Gemini',
			'grok'              => 'Grok',
			'grok.com'          => 'Grok',
			'meta.ai'           => 'Meta AI',
			'deepseek'          => 'DeepSeek',
			'mistral'           => 'Mistral',
			'you.com'           => 'You.com',
			'phind'             => 'Phind',
			'poe'               => 'Poe',
		);

		/**
		 * Filters the utm_source values that identify an AI referral visit.
		 *
		 * @since 1.3.0
		 *
		 * @param array $sources utm_source value (exact, case-insensitive) => display name.
		 */
		$filtered = apply_filters( 'tavc_ai_utm_sources', $sources );

		return self::normalize_map( is_array( $filtered ) ? $filtered : $sources );
	}

	/**
	 * Detect an AI platform from a referrer URL.
	 *
	 * @since    1.3.0
	 * @param    string    $referrer_url    Referrer URL.
	 * @return   string|false               Display name, or false when not an AI platform.
	 */
	public static function detect_from_referrer( $referrer_url ) {
		if ( ! is_string( $referrer_url ) || '' === $referrer_url ) {
			return false;
		}

		$host = wp_parse_url( $referrer_url, PHP_URL_HOST );
		if ( empty( $host ) || ! is_string( $host ) ) {
			return false;
		}

		$host = strtolower( $host );

		foreach ( self::get_referrer_domains() as $domain => $name ) {
			if ( $host === $domain || substr( $host, -strlen( '.' . $domain ) ) === '.' . $domain ) {
				return $name;
			}
		}

		return false;
	}

	/**
	 * Detect an AI platform from a utm_source value.
	 *
	 * @since    1.3.0
	 * @param    string    $utm_source    utm_source query value.
	 * @return   string|false             Display name, or false when not an AI platform.
	 */
	public static function detect_from_utm( $utm_source ) {
		if ( ! is_string( $utm_source ) ) {
			return false;
		}

		$utm_source = strtolower( trim( $utm_source ) );
		if ( '' === $utm_source ) {
			return false;
		}

		$sources = self::get_utm_sources();

		return isset( $sources[ $utm_source ] ) ? $sources[ $utm_source ] : false;
	}

	/**
	 * Human-readable label for a detection method.
	 *
	 * @since    1.3.0
	 * @param    string    $method    Stored method slug.
	 * @return   string
	 */
	public static function get_method_label( $method ) {
		switch ( $method ) {
			case self::METHOD_UTM:
				return __( 'UTM tag', 'tbsh-ai-visibility-control' );
			case self::METHOD_BEACON:
				return __( 'Browser beacon', 'tbsh-ai-visibility-control' );
			default:
				return __( 'Referrer', 'tbsh-ai-visibility-control' );
		}
	}

	/**
	 * Lower-case keys and keep only string => non-empty string pairs.
	 *
	 * @since    1.3.0
	 * @param    array    $map    Raw map.
	 * @return   array
	 */
	private static function normalize_map( $map ) {
		$clean = array();

		foreach ( $map as $key => $name ) {
			if ( ! is_string( $key ) || ! is_string( $name ) || '' === trim( $key ) || '' === trim( $name ) ) {
				continue;
			}
			$clean[ strtolower( trim( $key ) ) ] = sanitize_text_field( $name );
		}

		return $clean;
	}
}
