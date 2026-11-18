=== AI Visibility Control – Block AI Crawlers, llms.txt & AI Traffic ===
Contributors: techbysh
Tags: ai-crawlers, llms-txt, robots-txt, gptbot, ai-traffic
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Block AI training bots like GPTBot, stay visible in AI search, publish llms.txt, and see the visits and sales ChatGPT and other AI tools send you.

== Description ==

AI Visibility Control is a free WordPress plugin that lets you decide which AI crawlers can read your site, publishes an llms.txt file for AI tools, and shows the visits and WooCommerce sales that ChatGPT, Perplexity, Claude and other AI assistants send you.

Block AI training bots such as GPTBot and ClaudeBot in one click, while search crawlers like OAI-SearchBot and PerplexityBot can still find you, so your site can keep appearing in AI answers. Everything runs on your own site: no account, no API key, and no cookies.

### How it works in 3 steps

1. **Choose who can read your site.** On the AI Crawlers tab, pick a quick setup such as "Block AI training, allow AI search", or switch crawlers on and off one by one. The plugin adds the matching rules to your robots.txt file.
2. **Check your llms.txt file.** The plugin publishes `/llms.txt` automatically. Choose which content is listed and preview the file before AI tools read it.
3. **Watch your AI traffic.** See which AI assistants send you visitors, which pages they land on, which AI crawlers read your site, and, for WooCommerce stores, which orders came from AI.

### Control which AI crawlers can read your site

* **19 AI crawlers in three groups**: training crawlers, AI search crawlers, and fetchers that open a page when a person asks an AI assistant to. Each crawler shows who runs it and what it does.
* **One-click quick setup**: Allow all, Block AI training and allow AI search, Block all, or choose your own.
* **Clear status**: every crawler shows "Allowed" or "Blocked", and each group shows how many are blocked.
* **Custom robots.txt rules**: block any other crawler, or block a crawler from part of your site only, for example `/members/`. Invalid lines are rejected and shown to you.
* **Content Signals**: add a Content-Signal line (search, ai-input, ai-train) to robots.txt to state how your content may be used.
* **Live preview** of the exact rules the plugin adds to robots.txt.
* **AI crawler activity**: see which AI crawlers visit, how often, when they were last seen, and which pages they read, including your llms.txt file.

### Publish llms.txt and llms-full.txt

llms.txt is a plain-text file at the root of your site that gives AI tools a short, clean list of your content.

* **Automatic `/llms.txt`** with your site name, a short description, and links to your published content.
* **Choose what is listed**: content types (posts, pages, products and other public types), your own About text, short descriptions under each link, grouping by content type, and a limit per type.
* **Leave content out**: tick "Exclude from llms.txt" in the editor, or let the plugin skip content marked noindex in Yoast SEO, Rank Math, All in One SEO, SEOPress or The SEO Framework.
* **Optional `/llms-full.txt`** with the full text of your newest content (up to 500 items) converted to Markdown, so AI tools can read it in one request.
* **Always up to date**: the file is cached for speed and refreshed automatically when you publish, edit or delete content.

### See the traffic and sales AI sends you

* **AI traffic reports** for the last 7, 30 or 90 days, with a daily chart, a comparison with the previous period, top AI platforms and the most visited pages.
* **Detects visits from** ChatGPT, Perplexity, Claude, Gemini, Copilot, Grok, Meta AI, DeepSeek, Mistral, You.com, Phind, Poe and Duck.ai.
* **Catches hidden AI traffic**: many AI assistants hide the referrer, so the plugin also recognises tags such as `utm_source=chatgpt.com`. These visits often appear as "Direct" in other analytics tools.
* **WooCommerce sales from AI**: paid orders and revenue by AI source, plus the AI source on each order screen.
* **Works with page caching**: an optional small script (under 2 KB, no cookies) records AI visits on pages served from a cache or CDN.
* **Weekly summary email** (optional) every Monday morning.
* **CSV export** of all AI visits.

### Supported AI crawlers

* **AI training**: GPTBot (OpenAI), ClaudeBot (Anthropic), Google-Extended (Google), Applebot-Extended (Apple), CCBot (Common Crawl), Amazonbot (Amazon), Bytespider (ByteDance), meta-externalagent (Meta), AI2Bot (Allen Institute for AI).
* **AI search**: OAI-SearchBot (OpenAI), Claude-SearchBot (Anthropic), PerplexityBot (Perplexity), DuckAssistBot (DuckDuckGo), YouBot (You.com).
* **User-requested fetchers**: ChatGPT-User (OpenAI), Claude-User (Anthropic), Perplexity-User (Perplexity), MistralAI-User (Mistral AI), meta-externalfetcher (Meta).

You can block any other crawler with your own robots.txt rules.

### Perfect for

* **Bloggers and publishers** who do not want their articles used to train AI models, but still want to be cited in AI answers.
* **Online stores** that want to know whether ChatGPT and Perplexity bring visitors and sales.
* **Agencies and site managers** who need a simple, consistent AI crawler policy on client sites.
* **Anyone curious** about which AI bots read their site and how often.

### What this plugin does not do

* **It does not force bots to obey.** robots.txt is a request. Well-behaved crawlers follow it; others may not. Some providers state that fetchers acting for a user may ignore it.
* **It does not guarantee AI visibility.** llms.txt and allowing AI search crawlers make your content easier to find, but AI companies decide what they show.
* **It does not see cached visits by default.** Visits served from a page cache or CDN do not reach WordPress. Turn on cache-compatible tracking to count AI visits on cached pages.
* **It does not change your Google ranking.** Google Search uses Googlebot, which this plugin never blocks. Google-Extended only controls Gemini training and grounding.

### Compatibility

* WordPress 6.2 or later and PHP 7.4 or later.
* WooCommerce 8.5 or later for AI sales reports (uses WooCommerce Order Attribution). Compatible with High-Performance Order Storage and the block checkout.
* Reads noindex settings from Yoast SEO, Rank Math, All in One SEO, SEOPress and The SEO Framework.
* Adds rules to the robots.txt that WordPress creates, so rules from your SEO plugin stay in place.
* Works on multisite: each site has its own settings, logs and llms.txt.
* Translation-ready, including right-to-left languages.

### Privacy

* This plugin sets no cookies and does not store IP addresses. To avoid counting the same visit twice, a one-way hash is kept for a few minutes and then discarded.
* AI visit logs contain only the date, the AI platform, the page visited and how the visit was detected.
* Logs stay in your WordPress database. You choose how long they are kept, and you can export or delete them at any time.
* Suggested text for your privacy policy is added under Settings > Privacy.

### Free and Pro

Everything described on this page is free. A Pro version is in beta and is planned to add a real-time AI crawl firewall, bot rate limiting, full Markdown page export and entity graph generation. To try it early, email support@techbysh.com.

### Documentation and support

* [Read the documentation](https://www.techbysh.com/docs/tbsh-ai-visibility-control.html)
* [Ask a question in the support forum](https://wordpress.org/support/plugin/tbsh-ai-visibility-control/)
* [Source code on GitHub](https://github.com/TechbyshOrg/ai-visibility-manager)

### For developers

Filters let you extend the plugin: `tavc_bot_definitions` (add crawlers), `tavc_ai_referrer_domains` and `tavc_ai_utm_sources` (add AI platforms), `tavc_llms_txt_post_types`, `tavc_llms_txt_include_post`, `tavc_llms_txt_output`, `tavc_llms_full_txt_output` and `tavc_llms_full_txt_max_bytes` (llms.txt), `tavc_custom_robots_rules` (robots.txt), `tavc_log_crawler_visit`, `tavc_beacon_hourly_limit`, `tavc_digest_email` and `tavc_woocommerce_order_ai_source`.

### External services

This plugin does not need any external service to work. It connects to one external service, only in the case below:

1. **Google Apps Script** (https://script.google.com) — Optional deactivation feedback, sent to the plugin author (Techbysh) and stored in a Google Sheet. It is sent **only** when an administrator deactivates the plugin and clicks **Submit & Deactivate** in the feedback popup; **Skip & Deactivate** and closing the popup send nothing. It contains the chosen reason, any text typed, the plugin, WordPress, and PHP versions, the site language, whether the site is multisite, and the active theme name. The site URL and admin email are included only if the administrator ticks the opt-in checkbox. Privacy: https://policies.google.com/privacy Terms: https://policies.google.com/terms

== Installation ==

1. In WordPress, go to **Plugins > Add New Plugin**, search for "AI Visibility Control", then click **Install Now** and **Activate**. Or upload the plugin zip under **Plugins > Add New Plugin > Upload Plugin**.
2. Click **AI Visibility** in the admin menu, or click **Settings** under the plugin name on the Plugins screen.
3. Follow the **Get started** checklist on the Overview tab.
4. On the **AI Crawlers** tab, choose a quick setup (for example "Block AI training, allow AI search") and click **Save Crawler Settings**.
5. On the **llms.txt** tab, check the preview and choose which content is listed. Your file is at `yoursite.com/llms.txt`.

Pretty permalinks (for example "Post name" under Settings > Permalinks) are recommended so `/llms.txt` loads correctly on every server.

== Frequently Asked Questions ==

= What does AI Visibility Control do? =

AI Visibility Control is a free WordPress plugin with three jobs. It controls which AI crawlers may read your site (through robots.txt), it publishes an llms.txt file that lists your content for AI tools, and it reports the visits and WooCommerce sales that AI assistants such as ChatGPT and Perplexity send to your site. It needs no account or API key.

= Can I block AI training but still appear in ChatGPT and other AI search answers? =

Yes. On the AI Crawlers tab, choose "Block AI training, allow AI search" and click Save Crawler Settings. This blocks training crawlers such as GPTBot, ClaudeBot, Google-Extended and CCBot, and keeps search crawlers such as OAI-SearchBot, Claude-SearchBot and PerplexityBot allowed, so AI assistants can still find and cite your pages.

= Will blocking AI crawlers hurt my Google rankings? =

No. Google Search uses Googlebot, which AI Visibility Control never blocks. Google-Extended is a separate setting that only tells Google not to use your content for Gemini training and grounding; Google states it does not affect Search.

= Which AI crawlers can I block? =

19 crawlers in three groups. Training: GPTBot, ClaudeBot, Google-Extended, Applebot-Extended, CCBot, Amazonbot, Bytespider, meta-externalagent and AI2Bot. AI search: OAI-SearchBot, Claude-SearchBot, PerplexityBot, DuckAssistBot and YouBot. User-requested fetchers: ChatGPT-User, Claude-User, Perplexity-User, MistralAI-User and meta-externalfetcher. You can block any other crawler with custom robots.txt rules.

= Do AI crawlers have to obey robots.txt? =

No. robots.txt is a request, not a lock. Major AI companies say their crawlers follow it, but some state that fetchers acting for a user (such as ChatGPT-User or Perplexity-User) may not. Protect private content with a login or server rules, not robots.txt alone.

= What is llms.txt, and does it guarantee AI visibility? =

llms.txt is a plain-text file at yoursite.com/llms.txt that gives AI tools a short, clean list of your content. AI Visibility Control creates and updates it for you. It makes your content easier for AI tools to read, but it does not guarantee that ChatGPT, Perplexity or any other assistant will show or cite your site.

= How do I keep a page out of llms.txt? =

Open the page in the editor and tick "Exclude from llms.txt" in the AI Visibility box. Content marked noindex in Yoast SEO, Rank Math, All in One SEO, SEOPress or The SEO Framework is left out automatically; you can turn this off on the llms.txt tab.

= What is llms-full.txt? =

llms-full.txt is an optional second file at yoursite.com/llms-full.txt that contains the full text of your newest content as Markdown, so AI tools can read it in one request. It is off by default. If you use a membership or paywall plugin, check the file before turning it on, because WordPress reports members-only content as public.

= Can I track ChatGPT traffic in WordPress? =

Yes. AI Visibility Control logs visits from ChatGPT, Perplexity, Claude, Gemini, Copilot, Grok, Meta AI, DeepSeek, Mistral and other AI assistants. It reads the referrer and also recognises tags such as utm_source=chatgpt.com, which catches ChatGPT visits that other analytics tools often report as "Direct". See them on the AI Traffic tab.

= My site uses a page cache. Are AI visits still counted? =

Visits served from a full-page cache or CDN never reach WordPress, so they are not counted by default. Turn on "Cache-compatible referral tracking" under Settings & Tools. It adds a script under 2 KB, with no cookies, that reports only visits that come from AI platforms.

= Can I see WooCommerce sales from ChatGPT and other AI assistants? =

Yes, with WooCommerce 8.5 or later and WooCommerce Order Attribution turned on. Orders whose visit came from an AI assistant are tagged with the source, and the AI Traffic tab shows paid orders and revenue by AI platform. Only orders placed after you install version 1.3.0 are included.

= Does it store personal data, use cookies or need an account? =

No account, no API key and no cookies. AI Visibility Control does not store IP addresses: a one-way hash is kept for a few minutes to avoid counting the same visit twice. Logs stay in your own WordPress database and contain the date, the AI platform and the page visited.

= Will it slow down my site, and does it change my existing robots.txt? =

No to both. The plugin loads nothing on your public pages unless you turn on cache-compatible tracking, serves llms.txt from a saved copy, and stores crawler visits as one small counter per crawler, page and day. It only adds rules at the end of the robots.txt that WordPress creates, so rules from your SEO plugin stay in place. A physical robots.txt file on your server takes priority over WordPress and must be removed for the rules to appear.

= Does it work on multisite, and is it translation-ready? =

Yes. On multisite, each site has its own settings, logs and llms.txt. All text in the plugin is translatable, and the admin screens support right-to-left languages.

= What happens to my data if I delete the plugin, and is there a Pro version? =

By default, your logs and settings are kept when you delete the plugin. To remove everything, tick "Completely Remove Data on Deletion" under Settings & Tools first. Everything described here is free; a Pro version is in beta and is planned to add a real-time crawl firewall, bot rate limiting, full Markdown page export and entity graph generation. Email support@techbysh.com to join the beta.

== Screenshots ==

1. Overview: a "Get started" checklist, AI visits in the last 30 days, llms.txt status, and a health check that shows how to fix each problem.
2. AI Crawlers: quick setup to block AI training and allow AI search, with an "Allowed" or "Blocked" label for each of the 19 crawlers.
3. AI crawler activity: which AI bots read your site, how often, when they were last seen, and the most crawled pages.
4. llms.txt: the file address, a live preview, and settings for content types, descriptions, exclusions and llms-full.txt.
5. AI Traffic: AI visits per day for the last 7, 30 or 90 days, top AI platforms such as ChatGPT and Perplexity, and the most visited pages.
6. WooCommerce sales from AI: paid orders and revenue by AI platform.
7. Settings & Tools: tracking options, log retention, weekly summary email, maintenance tools and a separate Danger Zone.

== Changelog ==

= 1.3.0 =
* New: 12 more AI crawlers (19 in total), grouped into training, AI search and user-requested fetchers.
* New: Quick setup, including "Block AI training, allow AI search".
* New: AI crawler activity log with requests, last visit and most crawled pages, including llms.txt fetches.
* New: Custom robots.txt rules for any crawler and path.
* New: Content Signals (search, ai-input, ai-train) in robots.txt.
* New: llms.txt settings: content types, About text, short descriptions, grouping, item limit, per-post exclusion and automatic exclusion of noindex content.
* New: Optional /llms-full.txt with the full text of your newest content as Markdown.
* New: AI visits are also detected from UTM tags such as utm_source=chatgpt.com, and from DeepSeek, Mistral, You.com, Phind, Poe, Duck.ai and copilot.com.
* New: Optional cache-compatible tracking for sites behind a page cache or CDN.
* New: 7, 30 and 90-day AI traffic reports with a daily chart and a comparison with the previous period.
* New: WooCommerce paid orders and revenue by AI source (HPOS compatible).
* New: Optional weekly summary email.
* New: Choose how long logs are kept.
* New: Plugin checks in Tools > Site Health, and suggested privacy policy text.
* New: Optional deactivation feedback form. Nothing is sent unless you click "Submit & Deactivate".
* Improvement: Redesigned settings screens: a "Get started" checklist, clearer tabs (AI Crawlers, AI Traffic, Settings & Tools, Status), "Allowed / Blocked" labels, a warning before unsaved changes are lost, and a separate Danger Zone.
* Improvement: Accessibility: readable status colors and switches (WCAG 2.2 AA contrast), correct heading structure, screen-reader labels and larger switch targets.
* Improvement: The settings page opens immediately; reachability checks load in the background.
* Improvement: Save confirmations appear below the header instead of inside it.
* Improvement: No external font is loaded in the admin, and the layout adapts to narrow screens and right-to-left languages.
* Improvement: AI visit logs ignore AI crawlers and no longer split the same page by UTM tags. The CSV export has a "Detected Via" column.
* Improvement: llms.txt titles and the site name no longer contain HTML codes such as &amp;#8217;.
* Fix: /llms.txt is no longer redirected to /llms.txt/ on some permalink settings.
* Fix: Updating the plugin no longer resets other plugins' URL rules.
* Fix: Health checks no longer fail on local or staging sites with self-signed certificates.

= 1.2.0 =
* Fix: Saving the crawler settings no longer resets the uninstall setting, and the other way round.
* Fix: /llms.txt stays reachable after rebuilding it and after plugin updates.
* Fix: The settings page returns to the tab you were on after using a tool.
* Fix: /llms.txt also loads on servers where the URL rules have not been refreshed yet.

= 1.1.0 =
* Improvement: Smaller download; development source files are no longer included.
* New: Pro beta contact details.
* Improvement: Internal version numbers are consistent.

= 1.0.2 =
* Improvement: Updated plugin tags for better discoverability on WordPress.org.

= 1.0.1 =
* Improvement: Accurate screenshot descriptions for all seven screens.

= 1.0.0 =
* Initial release: llms.txt generation with caching, AI crawler blocking in robots.txt, AI visit logging without storing IP addresses, health checks and maintenance tools.

== Upgrade Notice ==

= 1.3.0 =
Block AI training and stay in AI search with one click, 19 AI crawlers, llms.txt controls, AI traffic reports and WooCommerce sales from AI. Your settings and logs are kept.
