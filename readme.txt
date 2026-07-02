=== Dox Sales Booster ===
Contributors: doxstudio
Tags: woocommerce, sales, urgency, popup, social proof, conversion
Requires at least: 5.9
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Boost WooCommerce conversions with a live viewing counter, recent sales counter, real low-stock urgency and animated purchase popup notifications.

== Description ==

**Dox Sales Booster** adds four conversion-focused social proof elements to your WooCommerce store:

* **Live Viewing Counter** — Shows a dynamic number of people currently viewing the product. Fluctuates gradually at a configurable interval for a believable feel.
* **Recent Sales Counter** — Displays how many units have been sold in a given time period (minutes, hours, days, or weeks).
* **Low Stock Alert** — Real urgency based on the actual WooCommerce inventory: "Only {stock} left!" appears only when a product's real stock falls below your threshold.
* **Purchase Popup** — An animated notification popup showing a recent purchase: product image, name, price, location, and time. Can run on **simulated data** or on **real recent orders** (product, city and real time ago — never customer names).

All elements are controlled from a dedicated admin panel under **Sales Booster** in the WordPress menu, and can be placed via **shortcodes**, **Elementor widgets**, or **Gutenberg blocks**.

= Features =

* Purchase popup with entrance/exit animations, position (bottom-left / bottom-right) and price display
* Real data mode: feeds the popup with actual recent orders (anonymous — city and time only)
* Popup hidden on cart/checkout by default (configurable) so it never distracts from payment
* Visitors who close the popup silence it for a configurable time (per session)
* Configurable display duration, interval, first-popup delay, "time ago" range and max popups per page
* Optional buyer names via the `{name}` placeholder in the popup title
* Category include/exclude filters for popup products
* Low-stock element driven by real inventory (simple and variable products)
* Elementor widgets and Gutenberg blocks with style controls
* Translation-ready (compatible with Loco Translate, WPML, Polylang) — including all frontend JS strings
* Automatic updates served from GitHub Releases (Plugin Update Checker)
* Lightweight — vanilla JS, no external libraries, no remote requests
* Mobile-friendly, `prefers-reduced-motion` support, Escape closes the popup

= Shortcodes =

`[dsb_viewing]` — Displays the live viewing counter.
`[dsb_sales]` — Displays the recent sales counter.
`[dsb_stock]` — Displays the real low-stock alert (product pages).

= Elementor Widgets =

* 👁️ Personas viendo (Sales Booster)
* 🔥 Ventas recientes (Sales Booster)
* ⚡ Stock bajo (Sales Booster)

= Gutenberg Blocks =

Search for "Sales Booster" in the block inserter: viewing counter, recent sales, and low stock.

= Requirements =

* WordPress 5.9 or higher
* WooCommerce 6.0 or higher (for the popup and stock elements)
* PHP 7.4 or higher

== Installation ==

1. Upload the `dox-sales-booster` folder to the `/wp-content/plugins/` directory, or install the ZIP via the WordPress plugin uploader.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Sales Booster** in the admin menu to configure the plugin.
4. (Optional) Place the shortcodes, Elementor widgets or Gutenberg blocks on your product pages.
5. Updates are automatic from this plugin's public GitHub releases — nothing to configure.

The purchase popup is enabled automatically on all pages (except cart/checkout by default) — no shortcode needed.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

The purchase popup and the low-stock alert require WooCommerce. The viewing and recent-sales counters work without it.

= Are the sales and viewing numbers real? =

The viewing and recent-sales counters are simulated within the min/max range you configure. The purchase popup can run in **simulated** mode (catalog products) or in **real** mode (actual recent orders). The low-stock alert always uses real inventory data.

= What customer data does the real mode expose? =

Only the product, the billing **city**, and how long ago the order was placed. Customer names or any identifying data are never shown.

= How do updates work without WordPress.org? =

The plugin checks this project's public GitHub releases (via Plugin Update Checker) and shows updates in the normal WordPress updates screen — no configuration or token needed.

= Can I change the popup locations? =

Yes. In the admin panel under "Popup de compra" there is a Locations field — one city per line. The legacy `{{{City}}};` format from 1.x is still understood.

= Is the popup shown on mobile? =

Yes, and it adapts to the screen width automatically. You can disable it on mobile from the admin panel.

= Is it compatible with caching plugins? =

Yes. All dynamic numbers are generated client-side via JavaScript, so they work correctly even with full-page caching.

== Screenshots ==

1. Admin panel — Popup settings tab with live preview
2. Admin panel — Viewing counter settings
3. Admin panel — Low stock settings
4. Frontend — Purchase popup notification
5. Elementor widgets panel

== Changelog ==

= 1.2.0 =
* New: Automatic updates from GitHub Releases (Plugin Update Checker) — no WordPress.org needed.
* New: Real sales mode — the purchase popup can use actual recent orders (product, city, real "time ago"; buyer always anonymous). Falls back to simulated mode if there are no recent orders.
* New: Low-stock urgency element `[dsb_stock]` driven by real inventory, with Elementor widget and Gutenberg block.
* New: Gutenberg blocks for the viewing counter, recent sales counter and low stock.
* New: Popup position (bottom-left / bottom-right) and optional product price in the popup.
* New: Popup hidden on cart/checkout pages by default (configurable).
* New: Closing the popup (X or Escape) silences it for a configurable number of minutes per session.
* New: Optional buyer names via the `{name}` placeholder; category include/exclude filters; configurable first-popup delay, "time ago" range and max popups per page.
* Improved: viewing counter fluctuates gradually instead of jumping randomly; popup pauses while the tab is hidden; `prefers-reduced-motion` support; no more repeated screen-reader announcements.
* Improved: locations are now one per line in the admin (legacy `{{{...}}}` format still parsed).
* Improved: admin panel — unsaved-changes warning, error toasts, deep-linkable tabs, plugin version badge, "source has no products" warning, WooCommerce dependency notice, live stock/price previews.
* Fixed: overlapping popup timers — a long display duration could cut the next popup short; duration is now clamped server- and client-side.
* Fixed: Elementor widgets now respect the global on/off toggles and declare their CSS/JS dependencies (they styled incorrectly when all features were off).
* Fixed: product cache now refreshes on stock changes (throttled to once per 5 minutes) and uses a short negative cache when the source is empty.
* Fixed: AJAX failures no longer leave the Save button stuck; quotes in text settings are no longer backslash-escaped; invalid color values fall back to defaults; select fields validated against whitelists.
* Dev: shared render layer for shortcodes/widgets/blocks, `uninstall.php` cleanup, `Update URI` header, full i18n coverage (frontend and admin JS strings), dead code and dead JS payload removed.

= 1.1.0 =
* New: Redesigned purchase popup with cleaner, more polished look (larger image, refined typography and shadow).
* New: "Restore defaults" button in the admin panel.
* New: Popup product list is now cached (3-hour transient) — no more database query on every page load. Cache invalidates automatically on settings save and product changes.
* New: Default locations list expanded to 130+ Colombian cities with departments.
* Improved: Frontend JavaScript rewritten in vanilla JS — jQuery dependency removed.
* Improved: Assets are no longer loaded when all features are disabled.
* Improved: Popup image now uses lazy loading and explicit dimensions (better CLS).
* Improved: Elementor widget strings are now translatable.
* Security: Server-side min/max validation for all numeric settings; hardened output escaping; wp_rand() instead of rand().
* Fixed: Duplicate inner wrapper in popup markup that doubled the padding.

= 1.0.0 =
* Initial release.
* Live viewing counter widget, shortcode, and Elementor widget.
* Recent sales counter widget, shortcode, and Elementor widget.
* Animated purchase popup with full customization.
* Admin panel with tabbed interface and real-time preview.
* Translation support (Loco Translate compatible).
* Mobile-responsive popup with configurable display duration.

== Upgrade Notice ==

= 1.2.0 =
Major update: GitHub auto-updates, real sales mode, low-stock element, Gutenberg blocks, popup position/price, checkout exclusion and many fixes. Review the new options under Sales Booster after updating.

= 1.1.0 =
Performance and design update: cached popup queries, jQuery-free frontend, redesigned popup.

= 1.0.0 =
Initial release.
