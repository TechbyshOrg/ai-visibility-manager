=== AI Visibility Control – Block AI Crawlers, llms.txt & AI Traffic ===
Contributors: techbysh
Tags: ai-crawlers, llms-txt, robots-txt, gptbot, ai-traffic
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.4.0
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

* **26 AI crawlers in three groups**: training crawlers, AI search crawlers, and fetchers that open a page when a person asks an AI assistant to. Each crawler shows who runs it and what it does.
* **One-click quick setup**: Allow all, Block AI training and allow AI search, Block all, or choose your own.
* **Clear status**: every crawler shows "Allowed" or "Blocked", and each group shows how many are blocked.
* **Custom robots.txt rules**: block any other crawler, or block a crawler from part of your site only, for example `/members/`. Invalid lines are rejected and shown to you.
* **Content Signals**: add a Content-Signal line (search, ai-input, ai-train) to robots.txt to state how your content may be used.
* **Live preview** of the exact rules the plugin adds to robots.txt.
* **Turn blocked crawlers away** (optional): robots.txt only asks, so the plugin can also answer blocked crawlers with "403 Forbidden" instead of your pages. They can still read robots.txt and llms.txt.
* **Crawl rate limit** (optional): slow down AI crawlers that request too many pages per minute with a "429 Too Many Requests" response. People asking an AI assistant about a page are never limited.
* **AI crawler activity**: see which AI crawlers visit, how often, when they were last seen, which pages they read (including your llms.txt file) and how many requests were refused.

### Publish llms.txt and llms-full.txt

llms.txt is a plain-text file at the root of your site that gives AI tools a short, clean list of your content.

* **Automatic `/llms.txt`** with your site name, a short description, and links to your published content.
* **Choose what is listed**: content types (posts, pages, products and other public types), your own About text, short descriptions under each link, grouping by content type, and a limit per type.
* **Leave content out**: tick "Exclude from llms.txt" in the editor, or let the plugin skip content marked noindex in Yoast SEO, Rank Math, All in One SEO, SEOPress or The SEO Framework.
* **Optional `/llms-full.txt`** with the full text of your newest content (up to 500 items) converted to Markdown, so AI tools can read it in one request.
* **Markdown version of every page** (optional): add `.md` to the address of any listed page, for example `/about.md`, to get a clean Markdown copy, as the llms.txt proposal recommends. llms.txt can link to these copies, and each page points to its copy in its HTML head.
* **Always up to date**: the file is cached for speed and refreshed automatically when you publish, edit or delete content.

### See the traffic and sales AI sends you

* **AI traffic reports** for the last 7, 30 or 90 days, with a daily chart, a comparison with the previous period, top AI platforms and the most visited pages.
* **Detects visits from** ChatGPT, Perplexity, Claude, Gemini, Copilot, Grok, Meta AI, DeepSeek, Mistral, You.com, Phind, Poe and Duck.ai.
* **Catches hidden AI traffic**: many AI assistants hide the referrer, so the plugin also recognises tags such as `utm_source=chatgpt.com`. These visits often appear as "Direct" in other analytics tools.
* **WooCommerce sales from AI**: paid orders, revenue and average order value by AI source, the products AI visitors buy, the pages where they arrived, and the AI source on each order screen.
* **Works with page caching**: an optional small script (under 2 KB, no cookies) records AI visits on pages served from a cache or CDN.
* **Summary email** (optional), weekly or monthly.
* **CSV export** of all AI visits and of AI crawler visits.

### Supported AI crawlers

* **AI training**: GPTBot (OpenAI), ClaudeBot (Anthropic), Google-Extended (Google), Applebot-Extended (Apple), CCBot (Common Crawl), Amazonbot (Amazon), Bytespider (ByteDance), meta-externalagent (Meta), FacebookBot (Meta), AI2Bot (Allen Institute for AI), omgili (Webz.io).
* **AI search**: OAI-SearchBot (OpenAI), Claude-SearchBot (Anthropic), PerplexityBot (Perplexity), DuckAssistBot (DuckDuckGo), YouBot (You.com).
* **User-requested fetchers**: ChatGPT-User (OpenAI), Claude-User (Anthropic), Perplexity-User (Perplexity), MistralAI-User (Mistral AI), meta-externalfetcher (Meta).

You can block any other crawler with your own robots.txt rules.

### Perfect for

* **Bloggers and publishers** who do not want their articles used to train AI models, but still want to be cited in AI answers.
* **Online stores** that want to know whether ChatGPT and Perplexity bring visitors and sales.
* **Agencies and site managers** who need a simple, consistent AI crawler policy on client sites.
* **Anyone curious** about which AI bots read their site and how often.

### What this plugin does not do

* **It cannot stop every bot.** robots.txt is a request that some crawlers ignore. The optional 403 response stops crawlers that identify themselves honestly, but a bot can pretend to be a normal browser, and pages served from a page cache or CDN never reach WordPress. For complete blocking, also add rules in your CDN or web server.
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

### Support

* [Ask a question in the support forum](https://wordpress.org/support/plugin/tbsh-ai-visibility-control/)
* [Source code on GitHub](https://github.com/TechbyshOrg/ai-visibility-manager)

### For developers

Filters let you extend the plugin: `tavc_bot_definitions` (add crawlers), `tavc_ai_referrer_domains` and `tavc_ai_utm_sources` (add AI platforms), `tavc_llms_txt_post_types`, `tavc_llms_txt_include_post`, `tavc_llms_txt_output`, `tavc_llms_full_txt_output`, `tavc_llms_full_txt_max_bytes` and `tavc_markdown_page_output` (llms.txt and Markdown pages), `tavc_custom_robots_rules` (robots.txt), `tavc_refuse_blocked_crawler`, `tavc_enforcement_exempt_paths` and `tavc_rate_limit_per_minute` (turning crawlers away), `tavc_log_crawler_visit`, `tavc_crawler_paths_per_day`, `tavc_beacon_hourly_limit`, `tavc_client_ip` (visitor address behind a proxy), `tavc_referral_allowed_query_args`, `tavc_digest_email` and `tavc_woocommerce_order_ai_source`. The `tavc_crawler_request_refused` action fires when a crawler request is refused.

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

26 crawlers in three groups. Training: GPTBot, ClaudeBot, Google-Extended, Applebot-Extended, CCBot, Amazonbot, Bytespider, meta-externalagent, FacebookBot, AI2Bot, omgili, cohere-ai, Diffbot, PanguBot, Timpibot and ImagesiftBot. AI search: OAI-SearchBot, Claude-SearchBot, PerplexityBot, DuckAssistBot and YouBot. User-requested fetchers: ChatGPT-User, Claude-User, Perplexity-User, MistralAI-User and meta-externalfetcher. You can block any other crawler with custom robots.txt rules.

= Do AI crawlers have to obey robots.txt? =

No. robots.txt is a request, not a lock. Major AI companies say their crawlers follow it, but some state that fetchers acting for a user (such as ChatGPT-User or Perplexity-User) may not. Protect private content with a login or server rules, not robots.txt alone.

= Can the plugin block crawlers that ignore robots.txt? =

Yes, for crawlers that identify themselves. Turn on "Refuse requests from blocked crawlers" on the AI Crawlers tab, and blocked crawlers get a short "403 Forbidden" response instead of your pages. robots.txt and llms.txt stay readable so every crawler can see your rules. Crawlers are recognised by their User-Agent, which a bot can fake, and pages served from a page cache or CDN never reach WordPress. For complete blocking, the AI Crawlers tab shows ready-to-copy Apache and nginx rules for the crawlers you block; the plugin never edits server files itself.

= An AI crawler is slowing down my site. What can I do? =

Turn on the crawl rate limit on the AI Crawlers tab and choose how many requests per minute each AI crawler may make (10 to 300). Extra requests get a "429 Too Many Requests" response, which well-behaved crawlers treat as a sign to slow down and retry later. User-requested fetchers such as ChatGPT-User are never limited, because a person is waiting for the answer.

= What is llms.txt, and does it guarantee AI visibility? =

llms.txt is a plain-text file at yoursite.com/llms.txt that gives AI tools a short, clean list of your content. AI Visibility Control creates and updates it for you. It makes your content easier for AI tools to read, but it does not guarantee that ChatGPT, Perplexity or any other assistant will show or cite your site.

= How do I keep a page out of llms.txt? =

Open the page in the editor and tick "Exclude from llms.txt" in the AI Visibility box. Content marked noindex in Yoast SEO, Rank Math, All in One SEO, SEOPress or The SEO Framework is left out automatically; you can turn this off on the llms.txt tab.

= What is llms-full.txt? =

llms-full.txt is an optional second file at yoursite.com/llms-full.txt that contains the full text of your newest content as Markdown, so AI tools can read it in one request. It is off by default. If you use a membership or paywall plugin, check the file before turning it on, because WordPress reports members-only content as public.

= What are the .md versions of my pages? =

When you turn on "Serve a Markdown version of each listed page" on the llms.txt tab, every page listed in llms.txt is also available as clean Markdown at its address plus ".md", for example yoursite.com/about.md. The llms.txt proposal recommends this so AI tools can read your content without menus, scripts and layout. The same rules as llms.txt apply, so excluded, noindex and password-protected content has no Markdown version. It is off by default.

= Can I track ChatGPT traffic in WordPress? =

Yes. AI Visibility Control logs visits from ChatGPT, Perplexity, Claude, Gemini, Copilot, Grok, Meta AI, DeepSeek, Mistral and other AI assistants. It reads the referrer and also recognises tags such as utm_source=chatgpt.com, which catches ChatGPT visits that other analytics tools often report as "Direct". See them on the AI Traffic tab.

= My site uses a page cache. Are AI visits still counted? =

Visits served from a full-page cache or CDN never reach WordPress, so they are not counted by default. Turn on "Cache-compatible referral tracking" under Settings & Tools. It adds a script under 2 KB, with no cookies, that reports only visits that come from AI platforms.

= Can I see WooCommerce sales from ChatGPT and other AI assistants? =

Yes, with WooCommerce 8.5 or later and WooCommerce Order Attribution turned on. Orders whose visit came from an AI assistant are tagged with the source, and the AI Traffic tab shows paid orders, revenue and average order value by AI platform, the products AI visitors buy most and the pages where they arrived. Only orders placed after you update to version 1.3.0 or later are included.

= Does it store personal data, use cookies or need an account? =

No account, no API key and no cookies. AI Visibility Control does not store IP addresses: a one-way hash is kept for at most one day to avoid counting the same visit twice and to apply rate limits. Logs stay in your own WordPress database and contain the date, the AI platform and the page visited, without search terms or other query parameters. New installs keep AI traffic logs for one year; you can change this under Settings & Tools.

= Will it slow down my site, and does it change my existing robots.txt? =

No to both. The plugin loads nothing on your public pages unless you turn on cache-compatible tracking, serves llms.txt from a saved copy, and stores crawler visits as one small counter per crawler, page and day. Turning crawlers away happens early, before your theme loads, so refused requests cost less than a normal page view. It only adds rules at the end of the robots.txt that WordPress creates, so rules from your SEO plugin stay in place. A physical robots.txt file on your server takes priority over WordPress and must be removed for the rules to appear. If your site is installed in a sub-folder (for example example.com/blog/), crawlers only read robots.txt at the domain root, so copy the rules shown on the AI Crawlers tab into that file; the health check tells you when this applies.

= Does it work on multisite, and is it translation-ready? =

Yes. On multisite, each site has its own settings, logs and llms.txt. All text in the plugin is translatable, and the admin screens support right-to-left languages.

= What happens to my data if I delete the plugin? =

By default, your logs and settings are kept when you delete the plugin. To remove everything, tick "Completely Remove Data on Deletion" under Settings & Tools first, then delete the plugin from the Plugins screen.

== Screenshots ==

1. Overview tab: a "Get started" checklist, AI visits in the last 30 days with the change from the previous 30 days, the last AI visit, llms.txt status, how many of the 26 AI crawlers are blocked, and a health check that confirms llms.txt and robots.txt are working.
2. AI Crawlers tab: one-click "Block AI training, allow AI search" setup, an Allowed or Blocked switch for each of the 26 crawlers in three groups (training, AI search, user-requested), options to refuse blocked crawlers with a 403 and to rate-limit busy crawlers, crawler activity with refused requests and the most crawled pages, custom robots.txt rules, Content Signals and a preview of the rules added to robots.txt.
3. llms.txt tab: the file address, size and AI crawler fetches, a live preview of llms.txt linking to the Markdown version of each page, and settings for content types, About text, grouping, item limits, noindex content, llms-full.txt and Markdown versions of pages.
4. AI Traffic tab: AI visits for the last 30 days with a daily chart, top AI platforms such as ChatGPT and Perplexity, the most visited pages, the latest visits with how each was detected, and WooCommerce orders from AI with revenue, average order value, top products and the pages where buyers arrived.
5. Settings & Tools tab: UTM detection, cache-compatible tracking, crawler logging, log retention and a weekly or monthly summary email, plus maintenance tools (rebuild llms.txt, CSV exports of AI visits and crawler visits, test email), a separate Danger Zone and the data removal choice.
6. Status tab: the AI visibility health check, system details for troubleshooting and links to the WordPress.org support forum and email support.

== Changelog ==

= 1.4.0 =
* New: Optionally turn blocked crawlers away with a "403 Forbidden" response, for crawlers that ignore robots.txt. robots.txt and llms.txt stay readable.
* New: Optional crawl rate limit per AI crawler (10 to 300 requests per minute) with a "429 Too Many Requests" response. User-requested fetchers are never limited.
* New: Refused requests are counted in the AI crawler activity, the CSV export and the summary email.
* New: Optional Markdown version of every page listed in llms.txt, at the page address plus ".md", with a link in the page head and an option to link to these copies from llms.txt.
* New: WooCommerce report shows the average order value, the products AI visitors buy most and the pages where they arrived.
* New: CSV export of AI crawler visits.
* New: The summary email can be sent weekly or monthly.
* New: FacebookBot (Meta), omgili (Webz.io), cohere-ai, Diffbot, PanguBot (Huawei), Timpibot and ImagesiftBot training crawlers (26 in total).
* New: Option to stop publishing llms.txt and llms-full.txt, with a reminder when every AI crawler is blocked.
* New: Copy-ready Apache and nginx rules that block the selected crawlers before WordPress or a page cache runs.
* New: The Status health check warns when a page cache stops blocked crawlers from being turned away.
* Improvement: The Status tab links to the WordPress.org support forum for help.
* Fix: The selected period (7, 30 or 90 days) is highlighted again on the AI Traffic tab.
* Improvement: llms.txt is built in small batches by one request at a time, and the previous copy is served while it is rebuilt. It is rebuilt only when published content of the listed types changes, not on every order or menu save. New installs list at most 1,000 items per content type.
* Improvement: The crawl rate limit is counted per crawler and network, so a crawler faking another's name cannot use up its limit. Rate limits and duplicate-visit checks no longer write to the options table on every request.
* Improvement: Requests for missing pages, and more than 5,000 different pages per crawler per day, are grouped into one row, so fake crawler traffic cannot fill the database.
* Improvement: The settings page loads only the open tab, AI Traffic opens on the last 30 days, and CSV exports are written in batches.
* Improvement: Site Health checks run in the background and confirm that robots.txt really contains the plugin's rules.
* Improvement: "Get started" progress is saved for each user instead of in the browser.
* Privacy: Logged AI visit addresses no longer keep search terms or other query parameters (only content identifiers such as ?p=), and new installs keep AI traffic logs for one year.
* Fix: Blocked crawlers can no longer read pages or the REST API by adding ?robots= to an address, and blocked crawlers are no longer served llms-full.txt.
* Fix: Page addresses in non-Latin scripts (for example Cyrillic or Arabic) are logged correctly.
* Fix: llms.txt and .md pages return status 200 when served before the rewrite rules are refreshed.
* Fix: llms-full.txt and .md pages render blocks (so membership plugins can hide restricted content) and leave out text wrapped in shortcodes of inactive plugins. Relative links are resolved correctly.
* Fix: After saving a form you stay on the same tab, and one-time messages are not shown again.
* Fix: On multisite, uninstalling removes the data of every site that opted in, and network deactivation cleans up every site.
* Fix: Sites installed in a sub-folder get a clear robots.txt health message (crawlers only read robots.txt at the domain root) instead of advice to delete a physical file, and the AI Crawlers tab links to the generated rules instead of a missing page.

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
* Improvement: Internal version numbers are consistent.

= 1.0.2 =
* Improvement: Updated plugin tags for better discoverability on WordPress.org.

= 1.0.1 =
* Improvement: Accurate screenshot descriptions for all seven screens.

= 1.0.0 =
* Initial release: llms.txt generation with caching, AI crawler blocking in robots.txt, AI visit logging without storing IP addresses, health checks and maintenance tools.

== Upgrade Notice ==

= 1.4.0 =
Big update from 1.2.0: block AI training while staying in AI search, 26 AI crawlers, optional 403 and rate limits for crawlers, llms.txt controls and .md pages, AI traffic and WooCommerce sales reports. Your settings and logs are kept; new options stay off until you turn them on.

= 1.3.0 =
Block AI training and stay in AI search with one click, 19 AI crawlers, llms.txt controls, AI traffic reports and WooCommerce sales from AI. Your settings and logs are kept.
