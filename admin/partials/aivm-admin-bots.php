<?php
/**
 * View for AI Bots Settings tab
 *
 * @link       https://techbysh.com
 * @since      1.0.0
 *
 * @package    Aivm
 * @subpackage Aivm/admin/partials
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

// Fetch currently blocked bots option
$blocked_bots = get_option( 'aivm_blocked_bots', array() );

// Define bots list and details
$bots_definition = array(
	'gptbot'            => array(
		'name' => 'GPTBot',
		'desc' => __( 'OpenAI\'s official web crawler used to index web pages for ChatGPT training and features.', 'ai-visibility-manager' ),
	),
	'claudebot'         => array(
		'name' => 'ClaudeBot',
		'desc' => __( 'Anthropic\'s crawler used to collect datasets for Claude LLMs and assistant systems.', 'ai-visibility-manager' ),
	),
	'perplexitybot'     => array(
		'name' => 'PerplexityBot',
		'desc' => __( 'Perplexity AI\'s crawler used to power conversational search queries with live site references.', 'ai-visibility-manager' ),
	),
	'google-extended'   => array(
		'name' => 'Google-Extended',
		'desc' => __( 'Google\'s crawler token enabling webmasters to opt-out of Gemini and Vertex AI training datasets.', 'ai-visibility-manager' ),
	),
	'ccbot'             => array(
		'name' => 'CCBot',
		'desc' => __( 'Common Crawl scraper used to compile massive open-source crawls used to train various LLMs.', 'ai-visibility-manager' ),
	),
	'amazonbot'         => array(
		'name' => 'Amazonbot',
		'desc' => __( 'Amazon\'s crawler used to fetch site information for training Alexa and other ML models.', 'ai-visibility-manager' ),
	),
	'applebot-extended' => array(
		'name' => 'Applebot-Extended',
		'desc' => __( 'Apple\'s extended crawler token to opt-out of Apple Intelligence model training datasets.', 'ai-visibility-manager' ),
	),
);
?>

<div class="aivm-card">
	<h3 class="aivm-card-title">
		<span class="dashicons dashicons-buddicons-buddypress-logo"></span>
		<?php esc_html_e( 'AI Crawler Control Panel', 'ai-visibility-manager' ); ?>
	</h3>
	
	<p style="margin-bottom: 20px; color: var(--aivm-text-muted); line-height: 1.5;">
		<?php esc_html_e( 'Use the options below to prevent popular AI models and crawler systems from scraping your content. When enabled, the corresponding disallow rules are dynamically added to your site\'s virtual robots.txt file.', 'ai-visibility-manager' ); ?>
	</p>
	
	<form method="post" action="options.php">
		<?php
		settings_fields( 'aivm_settings_group' );
		?>
		
		<div class="aivm-bot-list">
			<?php foreach ( $bots_definition as $slug => $bot ) : ?>
				<div class="aivm-bot-row">
					<div class="aivm-bot-info">
						<div>
							<div class="aivm-bot-name"><?php echo esc_html( $bot['name'] ); ?></div>
							<div class="aivm-bot-desc"><?php echo esc_html( $bot['desc'] ); ?></div>
						</div>
					</div>
					<div class="aivm-bot-toggle">
						<!-- Store option value. Use hidden field to handle unchecked states cleanly -->
						<input type="hidden" name="aivm_blocked_bots[<?php echo esc_attr( $slug ); ?>]" value="0">
						<input type="checkbox" name="aivm_blocked_bots[<?php echo esc_attr( $slug ); ?>]" id="aivm_bot_<?php echo esc_attr( $slug ); ?>" value="1" <?php checked( isset( $blocked_bots[ $slug ] ) ? $blocked_bots[ $slug ] : 0, 1 ); ?>>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		
		<div style="margin-top: 25px; display: flex; justify-content: flex-start;">
			<?php submit_button( __( 'Save Crawler Settings', 'ai-visibility-manager' ), 'primary', 'submit', false ); ?>
		</div>
	</form>
</div>
