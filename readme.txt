=== AI Visibility Control – Dynamic llms.txt & robots.txt Crawler Control ===
Contributors: techbysh
Tags: crawler, llms.txt, robots.txt, privacy, seo
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage AI crawler access. Block GPTBot, Claude & AI bots in robots.txt, generate dynamic llms.txt files, and track referral visits locally.

== Description ==

Is your website ready for the AI search revolution? AI Visibility Control is the ultimate tool for webmasters to control how Large Language Models (LLMs), AI bots, and conversational search platforms index their content. 

This lightweight, highly optimized plugin allows you to block data-scraping AI crawlers while optimizing your content for friendly AI search engines, and tracking the exact traffic they refer back to your site.

### Why Users Need This Plugin
As AI search engines like ChatGPT, Claude, and Perplexity become mainstream, they crawl your website to train models and answer user queries. Traditional robots.txt files do not make it easy to manage these new crawlers. AI Visibility Control solves this by giving you a central control panel to configure crawler rules, output search-optimized summaries, and monitor referred traffic.

### What Makes This Plugin Different
Unlike standard robots.txt editors or massive security suites, AI Visibility Control is built specifically for the AI era. It is 100% self-contained, requires no external API keys, does not track your admin activity, and runs locally. It features:
1. **AI crawler settings**: Toggle switches to block specific bots (like GPTBot, ClaudeBot, PerplexityBot, etc.) dynamically, grouped by what they do: training, AI search, or fetching pages for a user.
2. **llms.txt standard**: Generates dynamic plain-text markdown cataloging your public pages to optimize AI ingestion, with an optional full-text llms-full.txt.
3. **Local AI traffic analytics**: Tracks incoming visits from AI platforms and AI crawler activity locally without bloating your database.

### Key Features
* **Block AI Training, Stay in AI Search**: One-click presets to block AI training crawlers while still allowing AI search crawlers such as OAI-SearchBot, Claude-SearchBot and PerplexityBot, so your site can keep appearing in AI answers.
* **19 AI Crawlers**: GPTBot, ClaudeBot, Google-Extended, Applebot-Extended, CCBot, Amazonbot, Bytespider, meta-externalagent, AI2Bot, OAI-SearchBot, Claude-SearchBot, PerplexityBot, DuckAssistBot, YouBot, ChatGPT-User, Claude-User, Perplexity-User, MistralAI-User and meta-externalfetcher.
* **Custom robots.txt Rules**: Block any other crawler, or block a crawler from part of your site only, with validated User-agent and Disallow lines.
* **Content Signals**: Publish a Content-Signal line (search, ai-input, ai-train) in robots.txt to state how your content may be used.
* **AI Crawler Activity Log**: See which AI crawlers visit your site, how often, when they were last seen, and which pages they read, including llms.txt.
* **Automatic llms.txt Generator**: Generates a standard `/llms.txt` file containing your site title, description, and a structured list of your public content.
* **llms.txt Content Controls**: Choose the content types to list, write your own About text, add short descriptions, group links by type, limit items per type, exclude single posts from the editor, and leave out content marked noindex in Yoast SEO, Rank Math, All in One SEO, SEOPress or The SEO Framework.
* **llms-full.txt (optional)**: Publishes the full text of your newest content as Markdown in one file.
* **Transient Caching**: Caches the llms.txt output to ensure instant page load times and automatically flushes the cache when content is saved or updated.
* **AI Referral Logger**: Detects visits from ChatGPT, Claude, Perplexity, Gemini, Copilot, Grok, Meta AI, DeepSeek, Mistral and more, by referrer or by UTM tag (for example utm_source=chatgpt.com), and logs them into a custom database table.
* **Cache-Compatible Tracking (optional)**: A tiny script records AI visits on pages served from a page cache or CDN.
* **Trends and Date Ranges**: View AI referrals for the last 7, 30 or 90 days with a daily chart and a comparison with the previous period.
* **WooCommerce AI Attribution**: See paid orders and revenue that came from AI assistants (uses WooCommerce Order Attribution; HPOS compatible).
* **Weekly Summary Email (optional)**: Get AI referral and crawler statistics in your inbox every Monday.
* **Automatic Log Clean-up**: Choose how long referral and crawler logs are kept.
* **GDPR-Compliant IP Hashing**: Hashes visitor IP addresses to protect user privacy and comply with GDPR regulations.
* **Referral Logger Debounce**: Uses a 5-minute transient debounce check to prevent duplicate entries and keep your database clean.
* **Automated Health Checks**: Runs diagnostics on pretty permalinks, loopback reachability, robots.txt, and search engine visibility, also shown in Tools > Site Health.
* **Diagnostic Tools**: Rebuild cache, clear log entries, export logs as a CSV file, and reset setting options with a single click.

### Source Code
The unminified/transpiled source code for the frontend assets in this plugin is publicly available on GitHub at:
https://github.com/TechbyshOrg/ai-visibility-manager

To build the assets from source:
1. Clone the repository
2. Run `npm install`
3. Run `npm run build`

### External services

This plugin does not need any external service to work. It connects to one external service, only in the case below:

1. **Google Apps Script** (https://script.google.com) — Optional deactivation feedback, sent to the plugin author (Techbysh) and stored in a Google Sheet. It is sent **only** when an administrator deactivates the plugin and clicks **Submit & Deactivate** in the feedback popup; **Skip & Deactivate** and closing the popup send nothing. It contains the chosen reason, any text typed, the plugin, WordPress, and PHP versions, the site language, whether the site is multisite, and the active theme name. The site URL and admin email are included only if the administrator ticks the opt-in checkbox. Privacy: https://policies.google.com/privacy Terms: https://policies.google.com/terms

== Installation ==

1. Upload the `tbsh-ai-visibility-control` folder to the `/wp-content/plugins/` directory, or install directly through the WordPress admin panel.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings > AI Visibility Control** to configure crawler access and view logs.

== Frequently Asked Questions ==

= Does this plugin require any external API keys or accounts? =
No. The plugin is 100% self-contained, runs entirely on your local server, and requires no external APIs or accounts. The only outgoing request is the optional feedback you can choose to send when deactivating the plugin (see External services).

= What is the llms.txt file and why does my site need it? =
The `llms.txt` file is an emerging web standard that provides LLM crawlers with a lightweight, clean, markdown-formatted directory of your site's content. This ensures AI search platforms read and attribute your content accurately.

= Which AI search bots can I block using this plugin? =
19 crawlers in three groups. Training: GPTBot (OpenAI), ClaudeBot (Anthropic), Google-Extended, Applebot-Extended, CCBot (Common Crawl), Amazonbot, Bytespider, meta-externalagent and AI2Bot. AI search: OAI-SearchBot, Claude-SearchBot, PerplexityBot, DuckAssistBot and YouBot. User-requested fetchers: ChatGPT-User, Claude-User, Perplexity-User, MistralAI-User and meta-externalfetcher. You can block any other crawler with Custom robots.txt Rules. Developers can add crawlers with the `tavc_bot_definitions` filter.

= Can I stop AI training but still appear in AI search answers? =
Yes. On the **AI Crawlers** tab, choose "Block AI training, allow AI search" under Quick setup and click "Save Crawler Settings". Training crawlers such as GPTBot and ClaudeBot are blocked, while search crawlers such as OAI-SearchBot, Claude-SearchBot and PerplexityBot stay allowed.

= Do all AI crawlers obey robots.txt? =
No. robots.txt is a request, not a lock. Some providers state that crawlers acting for a user (such as ChatGPT-User or Perplexity-User) may not follow it. Use server-level protection for content that must stay private.

= What are Content Signals? =
A newer, voluntary robots.txt line (`Content-Signal: search=yes, ai-train=no`) that states how your content may be used: for search, as input for AI answers, or for AI training. It does not block anything on its own, and not every crawler reads it.

= How do I leave a page out of llms.txt? =
Open it in the editor and tick "Exclude from llms.txt" in the AI Visibility box. Content marked noindex in Yoast SEO, Rank Math, All in One SEO, SEOPress or The SEO Framework is left out automatically (you can turn this off on the llms.txt tab).

= What is llms-full.txt? =
An optional companion file at `/llms-full.txt` that contains the full text of your newest content as Markdown, so AI tools can read it in one request. It is off by default. If you use a membership or paywall plugin, check the file before turning it on, because WordPress reports members-only content as public.

= Which AI crawlers visit my site? =
See the **AI Crawler Activity** card on the **AI Crawlers** tab. It shows requests per crawler, when each was last seen, and the most crawled pages. Visits served from a page cache or CDN do not reach WordPress and are not counted.

= Does this plugin override my existing robots.txt rules? =
No. The plugin hooks into the native WordPress `robots_txt` filter and only appends crawler rules at the bottom of the output, leaving existing rules generated by other plugins completely intact.

= How does the referral logging system work? =
When a visitor arrives at your site, the plugin checks the HTTP Referrer header. If the visitor was referred by an AI platform (like ChatGPT, Perplexity, Claude, Gemini, Copilot, or Bard), it logs the timestamp, referrer source, and the requested page URL. Many AI assistants hide the referrer but tag their links (for example `utm_source=chatgpt.com`), so the plugin also recognises these tags. You can turn this off under **Settings & Tools > Tracking & Data Settings**.

= My site uses a page cache. Are AI visits still counted? =
Pages served from a full-page cache or CDN never reach WordPress, so server-side logging cannot see them. Turn on "Cache-compatible referral tracking" under **Settings & Tools**. It adds a small script (under 1 KB, no cookies) that reports only visits from AI platforms.

= Can I see sales from AI assistants in WooCommerce? =
Yes. When WooCommerce Order Attribution is on (WooCommerce 8.5 or later), orders whose visit came from an AI assistant are tagged, and the **AI Traffic** tab shows paid orders and revenue by AI source. The source is also shown on the order screen. Orders placed before you updated to 1.3.0 are not included.

= Does the referral tracking comply with GDPR/privacy laws? =
Yes. The plugin does not store raw IP addresses or personal user data in the database. IP addresses are hashed using MD5 during the transient check to prevent duplicate entries and then discarded.

= How does the 5-minute debounce check work? =
To prevent database bloat, when a visitor from an AI source clicks multiple links or refreshes a page within 5 minutes, the plugin only records the first visit. This is handled using transient cache keys.

= Will this plugin slow down my website? =
No. The plugin is lightweight, loads admin assets only on its settings page, utilizes transient caching for database queries, and has no frontend CSS or JavaScript files unless you turn on cache-compatible referral tracking. AI crawler visits are stored as one counter row per crawler, page and day.

= How long are logs kept? =
Referral logs are kept until you delete them, as in earlier versions. Crawler logs are kept for 90 days. You can change both under **Settings & Tools > Tracking & Data Settings**; older entries are deleted automatically once a day.

= Do I need custom permalinks enabled to use this plugin? =
Yes. Pretty permalinks (e.g. Post Name) must be active under Settings > Permalinks so the custom virtual routing for `/llms.txt` works correctly.

= What happens if my llms.txt cache is not rebuilt? =
The `llms.txt` cache is managed automatically. It is cleared and rebuilt whenever you save, edit, publish, or trash a post or page, ensuring it is always up to date.

= How do I manually rebuild the llms.txt cache? =
Click "Rebuild llms.txt" on either the **llms.txt** tab or the **Settings & Tools** tab.

= Can I export the logged AI referral traffic details? =
Yes. On the **Settings & Tools** tab, click "Download CSV" under Maintenance to download a spreadsheet of all logged AI visits.

= What happens to my data when I delete the plugin? =
By default, database logs are preserved. However, you can toggle the "Completely Remove Data on Deletion" setting on the **Settings & Tools** tab so all data is wiped when uninstalling.

= Is this plugin compatible with WooCommerce and SEO plugins? =
Yes. The plugin is fully compatible with WooCommerce, Yoast SEO, Rank Math, AIOSEO, and page builders like Elementor or Divi.

= Is there a Pro version available, and what does it add? =
This is the free version. It contains all the core tools to block bots and log traffic. Future premium updates will offer advanced crawl firewalls, bot rate limiting, and full markdown page exports.

If you are interested in trying these premium features early, we are currently running a Pro Beta program. Contact us at support@techbysh.com to request access.

== Screenshots ==

1. **Overview Dashboard** – The main control panel showing the AI Visibility Health Check, Content Discoverability Report (posts and pages indexed for llms.txt), total AI referral count, and quick-glance status badges for llms.txt and robots.txt.
2. **AI Bots Control Panel** – Toggle switches to individually allow or block popular AI crawlers including GPTBot (OpenAI), ClaudeBot (Anthropic), PerplexityBot, Google-Extended, CCBot, Amazonbot, and Applebot-Extended directly from your WordPress dashboard.
3. **llms.txt Generator & Settings** – The llms.txt configuration tab displaying the endpoint URL, live cache status, file size, and a live markdown preview of the generated file that AI search engines will consume.
4. **AI Search Referrals Analytics** – The referral tracking screen that logs and displays incoming visits from AI platforms such as ChatGPT, Claude, Perplexity, Gemini, Copilot, and Bard when they refer users to your site.
5. **Plugin Maintenance Tools (top)** – The Tools tab showing maintenance actions: Rebuild llms.txt Cache, Export Referrals as CSV, Clear Referrals Logs, and Reset Plugin Settings back to defaults.
6. **Plugin Maintenance Tools (bottom) & Uninstall Preferences** – The lower portion of the Tools tab revealing the Uninstall Preferences section with a "Completely Remove Data on Deletion" option to wipe all logs and settings when the plugin is removed.
7. **System Status & Diagnostics** – A full environment report listing AI Visibility Control version, WordPress version, PHP version, MySQL/MariaDB version, memory limit, max execution time, multisite status, and permalink structure for quick troubleshooting.

== Changelog ==

= 1.3.0 =
* New: 12 more AI crawlers (19 in total), grouped into training, AI search and user-requested fetchers.
* New: Quick presets, including "Block AI training, allow AI search".
* New: AI crawler activity log with requests, last visit and most crawled pages, including llms.txt fetches.
* New: Custom robots.txt rules for any crawler and path.
* New: Content Signals (search, ai-input, ai-train) in robots.txt.
* New: llms.txt settings: content types, About text, short descriptions, grouping, item limit, per-post exclusion and automatic exclusion of noindex content.
* New: Optional /llms-full.txt with the full text of your newest content as Markdown.
* New: AI referrals are also detected from UTM tags such as utm_source=chatgpt.com, and from DeepSeek, Mistral, You.com, Phind, Poe, Duck.ai and copilot.com.
* New: Optional cache-compatible referral tracking for sites behind a page cache or CDN.
* New: 7, 30 and 90-day referral reports with a daily chart and comparison with the previous period.
* New: WooCommerce paid orders and revenue by AI source (HPOS compatible).
* New: Optional weekly summary email.
* New: Automatic log retention settings.
* New: Plugin checks in Tools > Site Health, and suggested privacy policy text.
* Improved: Referral logs ignore AI crawlers and no longer split the same page by UTM parameters. The CSV export has a "Detected Via" column.
* Improved: llms.txt titles and site name no longer contain HTML entities such as &amp;#8217;.
* Fixed: Updating the plugin no longer flushes rewrite rules before other plugins have registered theirs.
* Improved: Settings screen redesign. A "Get started" checklist on the Overview, settings grouped where you expect them (AI Crawlers, AI Traffic, Settings & Tools, Status), quick setup for crawlers with live "Allowed / Blocked" labels, a warning before unsaved changes are lost, and a separate Danger Zone for actions that delete data.
* Improved: Accessibility. Readable status colors and switches (WCAG 2.2 AA contrast), correct heading structure, screen-reader labels for tabs, health checks and new-tab links, and larger switch targets.
* Improved: The settings page opens immediately; reachability checks load in the background.
* Improved: Save confirmations now appear below the header instead of inside it.
* Improved: No external font is loaded in the admin (privacy), and the layout adapts to narrow screens and right-to-left languages.

= 1.2.0 =
* Prevented crawler and uninstall settings forms from overwriting each other.
* Flushed rewrite rules on cache rebuild and plugin updates so /llms.txt stays reachable.
* Restored the active settings tab after Tools actions.
* Added a path fallback for /llms.txt when rewrite rules have not flushed yet.

= 1.1.0 =
* Excluded frontend source code from release package.
* Added Pro Beta contact support program details.
* Standardized internal versioning references.

= 1.0.2 =
* Updated plugin tags in readme.txt for better discoverability on WordPress.org.

= 1.0.1 =
* Updated readme.txt with accurate screenshot descriptions for all seven plugin screens.

= 1.0.0 =
* Initial release.
* Automated llms.txt markdown generation with transients caching.
* AI bot control settings using robots_txt filters.
* Non-blocking database logging with GDPR-compliant IP hashing and 5-minute debounce checks.
* Diagnostics health checking and maintenance tools.
