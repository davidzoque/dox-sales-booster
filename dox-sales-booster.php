<?php
/**
 * Plugin Name:       Dox Sales Booster
 * Plugin URI:        https://doxstudio.com
 * Description:       Adds purchase notifications, live viewing counter, recent sales counter and real low-stock urgency to WooCommerce to boost conversions with social proof.
 * Version:           1.2.2
 * Author:            Dox Studio
 * Author URI:        https://doxstudio.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dox-sales-booster
 * Domain Path:       /languages
 * Requires at least: 5.9
 * Tested up to:      7.0
 * Requires PHP:      7.4
 * WC requires at least: 6.0
 * WC tested up to:   10.9
 * Update URI:        https://github.com/davidzoque/dox-sales-booster
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DSB_VERSION', '1.2.2' );
define( 'DSB_PATH', plugin_dir_path( __FILE__ ) );
define( 'DSB_URL',  plugin_dir_url( __FILE__ ) );

// ─── Compatibilidad con HPOS (High Performance Order Storage) de WooCommerce ──
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

// ─── Traducciones (Loco Translate / WPML / Polylang) ──────────────────────────
// Prioridad 1: antes de que dsb_defaults() se use en init (bloques/frontend).
add_action( 'init', function () {
    load_plugin_textdomain( 'dox-sales-booster', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}, 1 );

// ─── Cargar archivos ───────────────────────────────────────────────────────────
require_once DSB_PATH . 'includes/render.php';
require_once DSB_PATH . 'includes/blocks.php';
require_once DSB_PATH . 'includes/frontend.php';
if ( is_admin() ) {
    require_once DSB_PATH . 'admin/settings.php';
}

// ─── Aviso si WooCommerce no está activo ──────────────────────────────────────
add_action( 'admin_notices', function () {
    if ( class_exists( 'WooCommerce' ) ) return;
    if ( ! current_user_can( 'activate_plugins' ) ) return;
    echo '<div class="notice notice-warning"><p><strong>Dox Sales Booster:</strong> '
        . esc_html__( 'WooCommerce no está activo. El popup de compras y el aviso de stock necesitan WooCommerce; los contadores de "personas viendo" y "ventas recientes" siguen funcionando.', 'dox-sales-booster' )
        . '</p></div>';
} );

// ─── Widgets de Elementor ──────────────────────────────────────────────────────
// elementor/widgets/register dispara DESPUÉS de que todas las clases de
// Elementor (incluida Widget_Base) están cargadas — es el hook correcto.
add_action( 'elementor/widgets/register', function ( $manager ) {
    require_once DSB_PATH . 'elementor/widgets.php';
    $manager->register( new DSB_Widget_Viewing() );
    $manager->register( new DSB_Widget_Sales() );
    $manager->register( new DSB_Widget_Stock() );
} );

// ─── Auto-actualizaciones desde GitHub (Plugin Update Checker) ────────────────
// El plugin se actualiza desde las releases del repo de GitHub, no desde
// WordPress.org. Para repos privados, define el token en wp-config.php:
//     define( 'DSB_GITHUB_TOKEN', 'github_pat_xxxxxxxx' );
// (fine-grained PAT con permiso de solo lectura de "Contents" sobre el repo)
$dsb_puc = DSB_PATH . 'vendor/plugin-update-checker/plugin-update-checker.php';
if ( file_exists( $dsb_puc ) ) {
    require_once $dsb_puc;

    $dsb_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/davidzoque/dox-sales-booster/', // ← ajusta usuario/organización si tu repo vive en otra cuenta
        __FILE__,
        'dox-sales-booster'
    );
    $dsb_update_checker->setBranch( 'main' );
    // Usa el ZIP limpio que el workflow de GitHub Actions adjunta a cada release
    $dsb_update_checker->getVcsApi()->enableReleaseAssets();
    if ( defined( 'DSB_GITHUB_TOKEN' ) && DSB_GITHUB_TOKEN ) {
        $dsb_update_checker->setAuthentication( DSB_GITHUB_TOKEN );
    }
}
