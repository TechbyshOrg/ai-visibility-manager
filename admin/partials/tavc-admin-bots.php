<?php
/**
 * View for AI Bots Settings tab
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Tavc
 * @subpackage Tavc/admin/partials
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

// Fetch currently blocked bots option
$tavc_blocked_bots = get_option( 'tavc_blocked_bots', array() );

// Define bots list and details
$tavc_bots_definition = array(
	'gptbot'            => array(
		'name' => 'GPTBot',
		'desc' => __( 'OpenAI\'s official web crawler used to index web pages for ChatGPT training and features.', 'tbsh-ai-visibility-control' ),
	),
	'claudebot'         => array(
		'name' => 'ClaudeBot',
		'desc' => __( 'Anthropic\'s crawler used to collect datasets for Claude LLMs and assistant systems.', 'tbsh-ai-visibility-control' ),
	),
	'perplexitybot'     => array(
		'name' => 'PerplexityBot',
		'desc' => __( 'Perplexity AI\'s crawler used to power conversational search queries with live site references.', 'tbsh-ai-visibility-control' ),
	),
	'google-extended'   => array(
		'name' => 'Google-Extended',
		'desc' => __( 'Google\'s crawler token enabling webmasters to opt-out of Gemini and Vertex AI training datasets.', 'tbsh-ai-visibility-control' ),
	),
	'ccbot'             => array(
		'name' => 'CCBot',
		'desc' => __( 'Common Crawl scraper used to compile massive open-source crawls used to train various LLMs.', 'tbsh-ai-visibility-control' ),
	),
	'amazonbot'         => array(
		'name' => 'Amazonbot',
		'desc' => __( 'Amazon\'s crawler used to fetch site information for training Alexa and other ML models.', 'tbsh-ai-visibility-control' ),
	),
	'applebot-extended' => array(
		'name' => 'Applebot-Extended',
		'desc' => __( 'Apple\'s extended crawler token to opt-out of Apple Intelligence model training datasets.', 'tbsh-ai-visibility-control' ),
	),
);
?>

<div class="tavc-card">
	<h3 class="tavc-card-title">
		<span class="dashicons dashicons-buddicons-buddypress-logo"></span>
		<?php esc_html_e( 'AI Crawler Control Panel', 'tbsh-ai-visibility-control' ); ?>
	</h3>
	
	<p style="margin-bottom: 20px; color: var(--tavc-text-muted); line-height: 1.5;">
		<?php esc_html_e( 'Use the options below to prevent popular AI models and crawler systems from scraping your content. When enabled, the corresponding disallow rules are dynamically added to your site\'s virtual robots.txt file.', 'tbsh-ai-visibility-control' ); ?>
	</p>
	
	<form method="post" action="options.php">
		<?php
		settings_fields( 'tavc_bots_settings_group' );
		?>
		
		<div class="tavc-bot-list">
			<?php foreach ( $tavc_bots_definition as $tavc_slug => $tavc_bot ) : ?>
				<div class="tavc-bot-row">
					<div class="tavc-bot-info">
						<div>
							<div class="tavc-bot-name"><?php echo esc_html( $tavc_bot['name'] ); ?></div>
							<div class="tavc-bot-desc"><?php echo esc_html( $tavc_bot['desc'] ); ?></div>
						</div>
					</div>
					<div class="tavc-bot-toggle">
						<!-- Store option value. Use hidden field to handle unchecked states cleanly -->
						<input type="hidden" name="tavc_blocked_bots[<?php echo esc_attr( $tavc_slug ); ?>]" value="0">
						<input type="checkbox" name="tavc_blocked_bots[<?php echo esc_attr( $tavc_slug ); ?>]" id="tavc_bot_<?php echo esc_attr( $tavc_slug ); ?>" value="1" <?php checked( isset( $tavc_blocked_bots[ $tavc_slug ] ) ? $tavc_blocked_bots[ $tavc_slug ] : 0, 1 ); ?>>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		
		<div style="margin-top: 25px; display: flex; justify-content: flex-start;">
			<?php submit_button( __( 'Save Crawler Settings', 'tbsh-ai-visibility-control' ), 'primary', 'submit', false ); ?>
		</div>
	</form>
</div>
