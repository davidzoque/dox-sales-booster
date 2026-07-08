=== Dox Sales Booster ===
Contributors: doxstudio
Tags: woocommerce, sales, urgency, popup, social proof, conversion
Requires at least: 5.9
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Boost WooCommerce conversions with a live viewing counter, recent sales counter, real low-stock urgency and animated purchase popup notifications.

== Description ==

**Dox Sales Booster** adds five conversion-focused elements to your WooCommerce store:

* **Live Viewing Counter** — Shows a dynamic number of people currently viewing the product. Fluctuates gradually at a configurable interval for a believable feel.
* **Recent Sales Counter** — Displays how many units have been sold in a given time period (minutes, hours, days, or weeks).
* **Low Stock Alert** — Real urgency based on the actual WooCommerce inventory: "Only {stock} left!" appears only when a product's real stock falls below your threshold.
* **Purchase Popup** — An animated notification popup showing a recent purchase: product image, name, price, location, and time. Can run on **simulated data** or on **real recent orders** (product, city and real time ago — never customer names).
* **Free Shipping Progress Bar** — "You're only {precio} away from free shipping!" with a progress bar based on the real cart total. Auto-inserted into the standard WooCommerce mini cart (including off-canvas carts like UICore Pro's), the cart page and the checkout, refreshing via cart fragments without page reloads. The threshold can be a custom amount or read from the WooCommerce Free Shipping method's minimum order amount for the customer's zone.

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
`[dsb_envio_gratis]` — Displays the free shipping progress bar (auto-inserted locations don't need it).

= Elementor Widgets =

* 👁️ Personas viendo (Sales Booster)
* 🔥 Ventas recientes (Sales Booster)
* ⚡ Stock bajo (Sales Booster)
* 🚚 Barra de envío gratis (Sales Booster)

= Gutenberg Blocks =

Search for "Sales Booster" in the block inserter: viewing counter, recent sales, low stock, and free shipping bar.

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

= 1.3.1 =
* Fixed: the free shipping bar did not appear on the Cart and Checkout pages when they are built with the WooCommerce **blocks** (woocommerce/cart, woocommerce/checkout) — those blocks don't fire the classic `woocommerce_before_cart` / `woocommerce_before_checkout_form` hooks. The bar is now injected above the block via `render_block` and kept in sync live by listening to the Store API (`wc/store/cart`), so it updates without a reload when quantities or coupons change. The classic (shortcode) cart/checkout keep working as before.
* New: animated progress bar — moving diagonal stripes, a periodic shimmer sweep, a smooth fill transition and a little "pop" when free shipping is reached. Fully disabled under `prefers-reduced-motion`. The admin preview now shows the movement too.

= 1.3.0 =
* New: **Free shipping progress bar** — shows how much is missing for free shipping, with progress bar, success message and configurable colors.
* New: Auto-inserted via standard WooCommerce hooks into the mini cart (works with off-canvas carts that use the native mini cart, e.g. UICore Pro), the cart page and the checkout — each location can be toggled independently.
* New: Threshold source options: a custom amount, or the minimum order amount of the WooCommerce "Free Shipping" method for the customer's shipping zone (falls back to the custom amount if the zone has no minimum configured).
* New: Live refresh without page reloads: the bar is registered as a cart fragment, so it updates on add-to-cart, quantity changes and coupons (including checkout coupons).
* New: "Ignore coupons" option to count the subtotal before discounts (like typical free-shipping thresholds).
* New: `[dsb_envio_gratis]` shortcode, 🚚 Elementor widget (with per-instance threshold, texts, colors and typography) and Gutenberg block.
* New: Admin tab "Envío gratis" with live preview and a progress simulator slider.

= 1.2.3 =
* New: independent font-size controls for each line of the purchase popup — title, price, meta (time · city) and link — instead of one size for everything. Defaults match the previous look, so the popup is unchanged until you adjust a size.
* Improved: the admin popup preview no longer needs horizontal scrolling — the preview column is wider and the preview box matches the popup's real width.

= 1.2.2 =
* Fixed: variable products showed a duplicated price in the purchase popup ("$ 110.000 - $ 120.000Rango de precios: desde...") — the screen-reader text from WooCommerce's price HTML leaked into the plain-text price. The popup price is now built with `wc_price()` directly; variable products display "Desde <minimum price>".
* Fixed: the popup product cache now invalidates automatically on every plugin update (the cache salt includes the plugin version), so price-format fixes apply immediately instead of after the 3-hour cache expires.

= 1.2.1 =
* Fixed: on stores whose currency symbol is an HTML entity (e.g. Colombian peso, COP = `&#36;`), the purchase popup showed the raw price entities (`&#36;&nbsp;148.000`) instead of the amount. The price is now decoded to plain text before it reaches the popup. Also fixes variable-product price ranges and sale prices.

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

= 1.3.1 =
Fixes the free shipping bar not showing on block-based Cart/Checkout pages, and adds an animated (moving) progress bar.

= 1.3.0 =
New free shipping progress bar for the mini cart, cart and checkout — enable it under Sales Booster → Envío gratis (off by default).

= 1.2.3 =
Per-element text sizes for the purchase popup (title, price, meta, link) and an admin preview that fits without horizontal scroll.

= 1.2.2 =
Fixes duplicated price text for variable products in the purchase popup (now "Desde <min>") and makes the product cache flush automatically on plugin updates.

= 1.2.1 =
Fixes the purchase popup showing raw price entities (e.g. &#36;) instead of the formatted amount on currencies like Colombian peso (COP).

= 1.2.0 =
Major update: GitHub auto-updates, real sales mode, low-stock element, Gutenberg blocks, popup position/price, checkout exclusion and many fixes. Review the new options under Sales Booster after updating.

= 1.1.0 =
Performance and design update: cached popup queries, jQuery-free frontend, redesigned popup.

= 1.0.0 =
Initial release.
