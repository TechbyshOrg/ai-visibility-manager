<?php
/**
 * AI crawler registry.
 *
 * Single source of truth for every AI crawler the plugin knows about: its
 * robots.txt token, owner, purpose category, description and the User-Agent
 * fragment used to recognise its visits.
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
 * AI crawler registry.
 *
 * @since      1.3.0
 * @package    Tavc
 * @subpackage Tavc/includes
 * @author     Techbysh
 */
class TAVC_Bots {

	/**
	 * Crawlers that collect content to train AI models.
	 */
	const CATEGORY_TRAINING = 'training';

	/**
	 * Crawlers that index content so AI assistants can cite it.
	 */
	const CATEGORY_SEARCH = 'search';

	/**
	 * Fetchers that load a page because a person asked an AI assistant to.
	 */
	const CATEGORY_USER = 'user';

	/**
	 * Per-request cache of the User-Agent lookup map.
	 *
	 * @since    1.3.0
	 * @var      array|null
	 */
	private static $user_agent_map = null;

	/**
	 * Purpose categories, in display order.
	 *
	 * @since    1.3.0
	 * @return   array    Category slug => array( 'label', 'desc' ).
	 */
	public static function get_categories() {
		return array(
			self::CATEGORY_TRAINING => array(
				'label' => __( 'AI Training Crawlers', 'tbsh-ai-visibility-control' ),
				'desc'  => __( 'Collect content to train AI models. Blocking these does not remove your site from AI search answers.', 'tbsh-ai-visibility-control' ),
			),
			self::CATEGORY_SEARCH   => array(
				'label' => __( 'AI Search Crawlers', 'tbsh-ai-visibility-control' ),
				'desc'  => __( 'Index pages so AI assistants can cite and link to your site. Blocking these can reduce how often your site appears in AI answers.', 'tbsh-ai-visibility-control' ),
			),
			self::CATEGORY_USER     => array(
				'label' => __( 'User-Requested Fetchers', 'tbsh-ai-visibility-control' ),
				'desc'  => __( 'Load a page when a person asks an AI assistant to open it. Some providers state that these fetchers may not follow robots.txt.', 'tbsh-ai-visibility-control' ),
			),
		);
	}

	/**
	 * Full crawler definitions.
	 *
	 * Slugs of the seven crawlers shipped before 1.3.0 are unchanged so saved
	 * settings keep working.
	 *
	 * @since    1.3.0
	 * @return   array    Slug => array( 'name', 'vendor', 'category', 'desc', 'ua' ).
	 */
	public static function get_definitions() {
		$bots = array(
			// Training crawlers.
			'gptbot'               => array(
				'name'     => 'GPTBot',
				'vendor'   => 'OpenAI',
				'category' => self::CATEGORY_TRAINING,
				'desc'     => __( 'Collects public web content that may be used to train OpenAI\'s foundation models.', 'tbsh-ai-visibility-control' ),
				'ua'       => 'GPTBot',
			),
			'claudebot'            => array(
				'name'     => 'ClaudeBot',
				'vendor'   => 'Anthropic',
				'category' => self::CATEGORY_TRAINING,
				'desc'     => __( 'Collects public web content that may be used to train Anthropic\'s Claude models.', 'tbsh-ai-visibility-control' ),
				'ua'       => 'ClaudeBot',
			),
			'google-extended'      => array(
				'name'     => 'Google-Extended',
				'vendor'   => 'Google',
				'category' => self::CATEGORY_TRAINING,
				'desc'     => __( 'A robots.txt control token, not a separate crawler. It tells Google not to use your content for Gemini model training and grounding. It does not affect Google Search.', 'tbsh-ai-visibility-control' ),
				'ua'       => '',
			),
			'applebot-extended'    => array(
				'name'     => 'Applebot-Extended',
				'vendor'   => 'Apple',
				'category' => self::CATEGORY_TRAINING,
				'desc'     => __( 'A robots.txt control token that opts your content out of Apple Intelligence model training. Applebot still crawls for Siri and Spotlight.', 'tbsh-ai-visibility-control' ),
				'ua'       => '',
			),
			'ccbot'                => array(
				'name'     => 'CCBot',
				'vendor'   => 'Common Crawl',
				'category' => self::CATEGORY_TRAINING,
				'desc'     => __( 'Builds the open Common Crawl dataset, which is widely used to train AI models.', 'tbsh-ai-visibility-control' ),
				'ua'       => 'CCBot',
			),
			'amazonbot'            => array(
				'name'     => 'Amazonbot',
				'vendor'   => 'Amazon',
				'category' => self::CATEGORY_TRAINING,
				'desc'     => __( 'Amazon\'s crawler, used to improve Amazon products such as Alexa and to train Amazon AI models.', 'tbsh-ai-visibility-control' ),
				'ua'       => 'Amazonbot',
			),
			'bytespider'           => array(
				'name'     => 'Bytespider',
				'vendor'   => 'ByteDance',
				'category' => self::CATEGORY_TRAINING,
				'desc'     => __( 'ByteDance\'s crawler, used to collect data for its AI products and model training.', 'tbsh-ai-visibility-control' ),
				'ua'       => 'Bytespider',
			),
			'meta-externalagent'   => array(
				'name'     => 'meta-externalagent',
				'vendor'   => 'Meta',
				'category' => self::CATEGORY_TRAINING,
				'desc'     => __( 'Meta\'s crawler for training AI models and improving AI products.', 'tbsh-ai-visibility-control' ),
				'ua'       => 'meta-externalagent',
			),
			'ai2bot'               => array(
				'name'     => 'AI2Bot',
				'vendor'   => 'Allen Institute for AI',
				'category' => self::CATEGORY_TRAINING,
				'desc'     => __( 'Collects web content used to train open language models.', 'tbsh-ai-visibility-control' ),
				'ua'       => 'AI2Bot',
			),

			// Search crawlers.
			'oai-searchbot'        => array(
				'name'     => 'OAI-SearchBot',
				'vendor'   => 'OpenAI',
				'category' => self::CATEGORY_SEARCH,
				'desc'     => __( 'Indexes pages so they can appear and be linked in ChatGPT search results. Training is handled separately by GPTBot.', 'tbsh-ai-visibility-control' ),
				'ua'       => 'OAI-SearchBot',
			),
			'claude-searchbot'     => array(
				'name'     => 'Claude-SearchBot',
				'vendor'   => 'Anthropic',
				'category' => self::CATEGORY_SEARCH,
				'desc'     => __( 'Indexes pages to improve the quality of Claude\'s search results.', 'tbsh-ai-visibility-control' ),
				'ua'       => 'Claude-SearchBot',
			),
			'perplexitybot'        => array(
				'name'     => 'PerplexityBot',
				'vendor'   => 'Perplexity',
				'category' => self::CATEGORY_SEARCH,
				'desc'     => __( 'Indexes pages so they can appear and be linked in Perplexity answers. Perplexity states it is not used to crawl content for AI foundation models.', 'tbsh-ai-visibility-control' ),
				'ua'       => 'PerplexityBot',
			),
			'duckassistbot'        => array(
				'name'     => 'DuckAssistBot',
				'vendor'   => 'DuckDuckGo',
				'category' => self::CATEGORY_SEARCH,
				'desc'     => __( 'Fetches pages in real time for AI-assisted answers in DuckDuckGo search. DuckDuckGo states this data is not used to train AI models.', 'tbsh-ai-visibility-control' ),
				'ua'       => 'DuckAssistBot',
			),
			'youbot'               => array(
				'name'     => 'YouBot',
				'vendor'   => 'You.com',
				'category' => self::CATEGORY_SEARCH,
				'desc'     => __( 'Indexes pages for You.com AI search answers.', 'tbsh-ai-visibility-control' ),
				'ua'       => 'YouBot',
			),

			// User-requested fetchers.
			'chatgpt-user'         => array(
				'name'     => 'ChatGPT-User',
				'vendor'   => 'OpenAI',
				'category' => self::CATEGORY_USER,
				'desc'     => __( 'Visits a page when a ChatGPT user or custom GPT asks to open it. OpenAI states robots.txt rules may not apply to these requests.', 'tbsh-ai-visibility-control' ),
				'ua'       => 'ChatGPT-User',
			),
			'claude-user'          => array(
				'name'     => 'Claude-User',
				'vendor'   => 'Anthropic',
				'category' => self::CATEGORY_USER,
				'desc'     => __( 'Fetches a page when a Claude user asks a question that requires visiting it.', 'tbsh-ai-visibility-control' ),
				'ua'       => 'Claude-User',
			),
			'perplexity-user'      => array(
				'name'     => 'Perplexity-User',
				'vendor'   => 'Perplexity',
				'category' => self::CATEGORY_USER,
				'desc'     => __( 'Visits a page when a Perplexity user asks a question that requires it. Perplexity states it generally ignores robots.txt for these requests.', 'tbsh-ai-visibility-control' ),
				'ua'       => 'Perplexity-User',
			),
			'mistralai-user'       => array(
				'name'     => 'MistralAI-User',
				'vendor'   => 'Mistral AI',
				'category' => self::CATEGORY_USER,
				'desc'     => __( 'Fetches pages when a Mistral AI user asks for information that requires them. Mistral states it is not used for automatic crawling or AI training.', 'tbsh-ai-visibility-control' ),
				'ua'       => 'MistralAI-User',
			),
			'meta-externalfetcher' => array(
				'name'     => 'meta-externalfetcher',
				'vendor'   => 'Meta',
				'category' => self::CATEGORY_USER,
				'desc'     => __( 'Fetches individual links on behalf of Meta AI users. Meta states it may bypass robots.txt.', 'tbsh-ai-visibility-control' ),
				'ua'       => 'meta-externalfetcher',
			),
		);

		/**
		 * Filters the AI crawler definitions.
		 *
		 * Add an entry to make a crawler available on the AI Bots tab. Each entry
		 * needs 'name' (robots.txt User-agent token) and 'category' (training,
		 * search or user). 'vendor', 'desc' and 'ua' (User-Agent fragment used for
		 * crawler visit logging; empty to skip logging) are optional.
		 *
		 * @since 1.3.0
		 *
		 * @param array $bots Slug => definition.
		 */
		$filtered = apply_filters( 'tavc_bot_definitions', $bots );

		return self::normalize_definitions( is_array( $filtered ) ? $filtered : $bots );
	}

	/**
	 * Drop malformed entries and fill optional keys.
	 *
	 * @since    1.3.0
	 * @param    array    $bots    Raw definitions.
	 * @return   array
	 */
	private static function normalize_definitions( $bots ) {
		$categories = array_keys( self::get_categories() );
		$clean      = array();

		foreach ( $bots as $slug => $bot ) {
			$slug = sanitize_key( $slug );
			if ( '' === $slug || ! is_array( $bot ) || empty( $bot['name'] ) || ! is_string( $bot['name'] ) ) {
				continue;
			}

			// robots.txt tokens cannot contain whitespace or line breaks.
			$name = preg_replace( '/[^A-Za-z0-9._\-]/', '', $bot['name'] );
			if ( '' === $name ) {
				continue;
			}

			$clean[ $slug ] = array(
				'name'     => $name,
				'vendor'   => isset( $bot['vendor'] ) ? (string) $bot['vendor'] : '',
				'category' => ( isset( $bot['category'] ) && in_array( $bot['category'], $categories, true ) ) ? $bot['category'] : self::CATEGORY_TRAINING,
				'desc'     => isset( $bot['desc'] ) ? (string) $bot['desc'] : '',
				'ua'       => isset( $bot['ua'] ) ? (string) $bot['ua'] : '',
			);
		}

		return $clean;
	}

	/**
	 * Default stored value for the tavc_blocked_bots option (everything allowed).
	 *
	 * @since    1.3.0
	 * @return   array    Slug => 0.
	 */
	public static function get_default_settings() {
		return array_fill_keys( array_keys( self::get_definitions() ), 0 );
	}

	/**
	 * Saved block flags merged over the defaults.
	 *
	 * @since    1.3.0
	 * @return   array    Slug => 0|1 for every known crawler.
	 */
	public static function get_blocked_settings() {
		$saved = get_option( 'tavc_blocked_bots', array() );
		$saved = is_array( $saved ) ? $saved : array();
		$flags = array();

		foreach ( self::get_default_settings() as $slug => $default ) {
			$flags[ $slug ] = ( isset( $saved[ $slug ] ) && 1 === intval( $saved[ $slug ] ) ) ? 1 : 0;
		}

		return $flags;
	}

	/**
	 * Quick presets offered on the AI Bots tab.
	 *
	 * @since    1.3.0
	 * @return   array    Preset key => array( 'label', 'block' => category slugs ).
	 */
	public static function get_presets() {
		return array(
			'allow_all'      => array(
				'label' => __( 'Allow all AI crawlers', 'tbsh-ai-visibility-control' ),
				'block' => array(),
			),
			'block_training' => array(
				'label' => __( 'Block AI training, allow AI search', 'tbsh-ai-visibility-control' ),
				'block' => array( self::CATEGORY_TRAINING ),
			),
			'block_all'      => array(
				'label' => __( 'Block all AI crawlers', 'tbsh-ai-visibility-control' ),
				'block' => array( self::CATEGORY_TRAINING, self::CATEGORY_SEARCH, self::CATEGORY_USER ),
			),
		);
	}

	/**
	 * Identify a known AI crawler from a User-Agent string.
	 *
	 * @since    1.3.0
	 * @param    string    $user_agent    Raw User-Agent header.
	 * @return   string|false             Crawler slug, or false when not an AI crawler.
	 */
	public static function match_user_agent( $user_agent ) {
		if ( ! is_string( $user_agent ) || '' === $user_agent ) {
			return false;
		}

		if ( null === self::$user_agent_map ) {
			self::$user_agent_map = array();
			foreach ( self::get_definitions() as $slug => $bot ) {
				if ( '' !== $bot['ua'] ) {
					self::$user_agent_map[ $slug ] = $bot['ua'];
				}
			}
		}

		foreach ( self::$user_agent_map as $slug => $fragment ) {
			if ( false !== stripos( $user_agent, $fragment ) ) {
				return $slug;
			}
		}

		return false;
	}
}
