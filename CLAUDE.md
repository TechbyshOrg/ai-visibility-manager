# AI Visibility Control (WordPress plugin)

WordPress.org slug / text domain: `tbsh-ai-visibility-control`. Class/option/hook prefix: `TAVC` / `tavc_`.
Plain PHP (7.4+, WP 6.2+), no Composer, npm, build step or tests. Version lives in 3 places that must match:
`TAVC_VERSION` + header `Version:` in `tbsh-ai-visibility-control.php`, and `Stable tag:` in `readme.txt`.
Bumping the version makes `TAVC::maybe_upgrade()` re-run the activator (dbDelta, cron, defaults) on every site.

A separate standalone product, `ai-visibility-manager-pro` (prefix `aivmp`), lives next to this plugin; it does not hook into it.

## Architecture

`tbsh-ai-visibility-control.php` → activation/deactivation hooks, WooCommerce HPOS declaration → `TAVC::get_instance()`
(`includes/class-tavc.php`) requires every class and registers **all** hooks in `define_admin_hooks()` / `define_public_hooks()`.

| File | Role |
|---|---|
| `includes/class-tavc-bots.php` | Crawler registry (single source of truth): slug → name/vendor/category (training, search, user)/desc/UA fragment. Presets, defaults, `match_user_agent()`. Filter `tavc_bot_definitions`. |
| `includes/class-tavc-ai-sources.php` | AI referrer domains + utm_source values → display name. Filters `tavc_ai_referrer_domains`, `tavc_ai_utm_sources`. |
| `includes/class-tavc-settings.php` | Defaults, getters and sanitizers for every 1.3.0 option (always read options through it). Custom robots rules parser. |
| `includes/class-tavc-bot-manager.php` | `robots_txt` filter (prio 100): blocked crawlers (+ legacy filter `tavc_custom_robots_rules`), custom rule groups, Content-Signal line. `build_rules()` also feeds the admin preview. |
| `includes/class-tavc-llms-txt.php` | `/llms.txt` and `/llms-full.txt` (rewrite `tavc_llms_txt=1|full`, also `index.php/llms.txt` for PATHINFO permalinks, + path fallback, `status_header(200)` + nosniff), settings-driven post selection built in batches of 200 (`each_post()`; only lines are kept in memory), per-post exclusion meta `_tavc_llms_exclude`, SEO-plugin noindex exclusion (AIOSEO table checked once, only when `aioseo()` exists). Output is stored in non-autoloaded options `tavc_llms_store_{index|full}`; `TAVC_LLMS_Txt::clear_cache()` bumps `tavc_llms_generation`, and `get_output()` rebuilds under the lock option `tavc_llms_lock_{type}` while other requests get the stale copy. Cleared only by `transition_post_status` / `deleted_post` for selected, published types (plus settings changes and the meta box). Setting `enabled` (since 1.4.0) turns both files off (404). Since 1.4.0 also opt-in Markdown pages: `/slug.md` is routed by the `request` filter (url_to_postid) to the post + query var `tavc_markdown`, served at `template_redirect` prio 5; `/index.md` = static front page; plain permalinks use `?tavc_markdown=1`. Same eligibility rules as llms.txt (context `markdown` in `tavc_llms_txt_include_post`); content is rendered with `do_blocks()` and unknown enclosing shortcodes are dropped with their content. |
| `includes/class-tavc-markdown.php` | Small DOM-based HTML → Markdown converter for llms-full.txt and `.md` pages; `from_html( $html, $base_url )` resolves relative links against the post permalink. |
| `includes/class-tavc-referral-logger.php` | Referral detection (referrer, then utm_source), 5-min debounce (`TAVC_DB::bump_counter()`), URL normalisation: target URL built from `home_url()` + raw path (never the Host header), query string reduced to an allow-list (filter `tavc_referral_allowed_query_args`). Opt-in beacon (`public/js/tavc-tracker.js` + REST `POST tavc/v1/referral`): per-client limit → 429, site-wide hourly cap (`tavc_beacon_hourly_limit`, 2000) → silent 200. When the beacon is on, server-side logging is skipped to avoid double counts. |
| `includes/class-tavc-crawler-logger.php` | `template_redirect` prio 1: counts AI crawler requests per bot/day/path (upsert). 404s are stored as `TAVC_DB::NOT_FOUND_PATH`; more than `tavc_crawler_paths_per_day` (5000) new paths per bot/day go to `TAVC_DB::OTHER_PATHS`. |
| `includes/class-tavc-enforcer.php` | Opt-in (option `tavc_enforcement_settings`), `init` prio 0: 403 for crawlers blocked on the AI Crawlers tab, and a requests/minute limit (429 + Retry-After) counted per crawler **and client network** (/24, /48) for the others (user-category fetchers never limited). Exempt: `/robots.txt`, `/llms.txt`, and `?robots=1` / `?tavc_llms_txt=1` only on the home path without `rest_route` (llms-full.txt is not exempt). `get_client_ip()` (filter `tavc_client_ip`) is shared with the referral logger. Refusals are logged in the crawler table (`blocked` / `limited` columns) with the raw request path. Filters `tavc_refuse_blocked_crawler`, `tavc_enforcement_exempt_paths`, `tavc_rate_limit_per_minute`; action `tavc_crawler_request_refused`. |
| `includes/class-tavc-db.php` | All SQL for `{prefix}tavc_referrals` (+ `method` column), `{prefix}tavc_crawler_hits` and `{prefix}tavc_counters`. `SCHEMA_VERSION` + option `tavc_db_version` (set by the activator only when every table exists) skip the SHOW TABLES/COLUMNS checks; bump it when a table changes. `bump_counter( $key, $window )`: fixed-window counter in the object cache, else one atomic upsert (returns 0 = unavailable, callers allow). CSV exports read keyset batches (`get_*_csv_batch()`). |
| `includes/class-tavc-activator.php` | `activate( $flush_rewrite = true )`: requires its own dependencies, creates/updates the three tables with `dbDelta()` (table schema lives here) and sets `tavc_db_version`, adds default options (autoloaded; new installs get llms.txt max 1000 per type and 1-year referral retention, upgrades keep 0 = unlimited / forever), registers the llms.txt rewrite rules, schedules cron, writes `tavc_plugin_version`. Also called by `maybe_upgrade()` (lock option `tavc_upgrade_lock`) with `false` (flush deferred). |
| `includes/class-tavc-deactivator.php` | `deactivate( $network_wide )`, per site (network: every site); deletes the `rewrite_rules` option instead of flushing (a flush during deactivation would keep the llms.txt rules): deletes caches (legacy llms/referral/beacon transients, health results, llms store + locks, `tavc_wc_report_*` / `tavc_rate_limit_*` by prefix), truncates `tavc_counters`, clears both cron hooks. Keeps options and logs. |
| `uninstall.php` | Per site (every site on multisite), only where `tavc_delete_on_uninstall` is on: every option, transient (incl. prefixes), cron hook, `_tavc_llms_exclude` post meta, `_tavc_ai_source` order meta (postmeta + HPOS `wc_orders_meta`), all three tables. User meta `tavc_get_started` is removed once if any site opted in. |
| `includes/class-tavc-cron.php` / `class-tavc-digest.php` | Daily retention prune, summary email (weekly = recurring event; monthly = single event on the 1st, rescheduled after each run; hook name stays `tavc_weekly_digest`). |
| `includes/class-tavc-woocommerce.php` | `woocommerce_order_save_attribution_data` → order meta `_tavc_ai_source`; cached report (`tavc_wc_report_*`, 15 min): revenue + average per source/currency, top products, top entry pages (from WC's `_wc_order_attribution_session_entry`). |
| `admin/class-tavc-admin.php` | Top-level "AI Visibility" admin page (`admin.php?page=tbsh-ai-visibility-control`, screen `toplevel_page_tbsh-ai-visibility-control`; build links with `TAVC_Admin::get_page_url()`; the pre-1.3.0 `options-general.php?page=…` URL redirects there). Prints `settings_errors()` itself because top-level pages do not get `options-head.php`. Also `register_setting` per form group, tools (`admin-post.php?action=tavc_tool_action&tool=…`), notices, meta box, privacy text, health checks. |
| `admin/class-tavc-site-health.php` | Site Health async tests (`health-check-tavc-llms-txt` / `-robots-txt` AJAX actions, `async_direct_test`) + Info section. The robots test checks that the first rule of `build_rules()` is in the served file. |
| `includes/class-tavc-deactivation-feedback.php` + `admin/js/deactivation-feedback.js` + `admin/css/deactivation-feedback.css` | Optional deactivation survey (Plugins screen only, loaded under `is_admin()` from the main file). Ported 1:1 from the `wacdmg-ai-content-assistant` plugin and posts to the **same** Google Apps Script/Sheet: keep ENDPOINT_URL, SECRET_TOKEN, POST field names/order and reason keys identical to that plugin. The Apps Script lives only in that plugin's `docs/feedback-apps-script.gs`; its `ALLOWED_PLUGINS` must list this plugin's folder names. Overrides: `TAVC_FEEDBACK_URL` / `TAVC_FEEDBACK_TOKEN`, filters `tavc_deactivation_feedback_url` / `_token` / `_payload`. Disclosed under "External services" in readme.txt. |
| `admin/partials/*.php` | One partial per tab; `tavc-admin-display.php` renders tab links as real `?tab=` URLs and includes **only the active tab's** partial (in TAVC_Admin context, `$this`), so each partial must be self-contained. Tab labels: Overview, AI Crawlers (`bots`), llms.txt, AI Traffic (`referrals`, default range 30 days), Settings & Tools (`tools`), Status. Slugs are in saved URLs: never rename them. `tavc-admin-help.php` is a shared card. Tool links: `TAVC_Admin::get_tool_url( $tool )` (nonce `tavc_tool_{tool}`). "Get started" progress: user meta `tavc_get_started` (opened tabs + `hidden`). AI Crawlers also shows copy-only Apache/nginx rules from `TAVC_Bot_Manager::get_server_rules()`. |
| Health checks UI | `TAVC_Admin::render_health_section()` / `render_llms_tile()` render cached results, or a placeholder filled by `wp_ajax_tavc_health_check` (nonce + `manage_options`) when the loopback checks are not cached. |
| `admin/js/tavc-admin.js` | Redirects old `#tab` links to `?tab=`, opens `<details>` around a `#target`, strips `tavc_msg`/`settings-updated` from `_wp_http_referer`, quick-setup radios, live group counts, unsaved-changes guard for every `options.php` form, `data-tavc-confirm` danger links, background health fetch. Tab list and strings come from `tavcAdmin` (`wp_localize_script`). |

## Data

- Options: `tavc_blocked_bots`, `tavc_delete_on_uninstall`, `tavc_llms_txt_last_rebuild`, `tavc_plugin_version`, `tavc_db_version`,
  `tavc_llms_txt_settings`, `tavc_tracking_settings`, `tavc_content_signals`, `tavc_custom_robots_rules`, `tavc_enforcement_settings`,
  `tavc_llms_generation`, `tavc_llms_store_index|full` (not autoloaded), locks `tavc_llms_lock_index|full`, `tavc_upgrade_lock`.
- Tables: `tavc_referrals`, `tavc_crawler_hits`, `tavc_counters` (bucket = md5 of the counter key; pruned daily by expiry). User meta: `tavc_get_started`.
- `tavc_crawler_hits` has `blocked` and `limited` counters (1.4.0); `hits` includes them. Readers fall back when the columns are missing.
- Each admin form has its own settings group so saving one never resets another.
- Timestamps in both tables are site-local time (`current_time( 'mysql' )`).

## Gotchas when changing things

- Tabs are hard-coded in two places: `TAVC_Admin::get_allowed_settings_tabs()` (also passed to the JS) and the `$tavc_tabs` list in the display partial.
- Add crawlers only in `TAVC_Bots::get_definitions()`; keep existing slugs stable (they are option keys).
- New options/transients/tables/meta must be added to `uninstall.php`; transients, cache options and cron hooks also to the deactivator. Both run per site on multisite. A table change needs `TAVC_DB::SCHEMA_VERSION` bumped.
- Never flush rewrite rules at `init` priority < 10; `maybe_upgrade()` defers the flush to `wp_loaded`.
- WordPress may run a register_setting sanitizer twice on a new option: sanitizers must accept their own output.
- A physical `robots.txt` in the WP root bypasses the `robots_txt` filter.
- Admin UI: no inline `style=""` (use the classes and `--tavc-*` tokens in `tavc-admin.css`; text colors use the `*-text` tokens, which meet WCAG AA), logical CSS properties for RTL, wrap tables in `.tavc-table-scroll`, card titles are `h2`, panel titles `h3`, decorative dashicons get `aria-hidden="true"`. Keep `<hr class="wp-header-end">` after the header so WordPress places notices below it.

## Release

Push a tag: `.github/workflows/create-release.yml` builds a zip for any tag (files from `.distignore`); `deploy-to-wp.yml` deploys `X.Y.Z` tags to WordPress.org SVN (10up action) after the lint job passes. `ci.yml` runs `php -l` on PHP 7.4–8.4 and WordPress Plugin Check on pushes and pull requests. `.distignore` excludes `docs/`, `.wordpress-org/`, `.github/`, etc. from the shipped plugin.

## Open items (owner will fix later)

- **Documentation page not published.** `https://www.techbysh.com/docs/tbsh-ai-visibility-control.html` returns 404. Its source is `docs/support.html` (not shipped; its canonical and og:url point to that address). Until it is live, help links point to the WordPress.org support forum instead: the Status tab card (`admin/partials/tavc-admin-help.php`) and the readme "Support" section. When the page is published, add a "Read the documentation" link back to both.
