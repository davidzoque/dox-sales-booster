<?php
/**
 * Plugin Name:       Dox Sales Booster
 * Plugin URI:        https://doxstudio.com
 * Description:       Adds purchase notifications, live viewing counter, recent sales counter, real low-stock urgency and a free-shipping progress bar to WooCommerce to boost conversions with social proof.
 * Version:           1.7.1
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
 * WC tested up to:   11.1
 * Update URI:        https://github.com/davidzoque/dox-sales-booster
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DSB_VERSION', '1.7.1' );
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

// El plugin trae un solo español (es_ES) y WordPress no pasa de es_CO, es_MX o
// es_AR a es_ES por su cuenta. Hasta la 1.4.0 el plugin estaba escrito en
// español y se veía así en cualquier sitio; desde que el código va en inglés,
// una tienda colombiana se lo encontraría en inglés sin esto. Solo actúa cuando
// WordPress no encuentra el archivo de la variante: una traducción propia (Loco
// Translate, un paquete en wp-content/languages) sigue mandando.
add_filter( 'load_textdomain_mofile', function ( $mofile, $domain ) {
    if ( 'dox-sales-booster' !== $domain ) return $mofile;
    if ( ! preg_match( '/dox-sales-booster-es(_[A-Za-z]+)?\.mo$/', (string) $mofile ) ) return $mofile;
    if ( is_readable( $mofile ) || is_readable( substr( $mofile, 0, -3 ) . '.l10n.php' ) ) return $mofile;

    $es = DSB_PATH . 'languages/dox-sales-booster-es_ES.mo';
    return is_readable( $es ) ? $es : $mofile;
}, 10, 2 );

// Lo mismo para las cadenas de los bloques de Gutenberg, que viajan en un JSON
// con el locale en el nombre.
add_filter( 'load_script_translation_file', function ( $file, $handle, $domain ) {
    if ( 'dox-sales-booster' !== $domain || ! $file || is_readable( $file ) ) return $file;

    $name = preg_replace( '/^dox-sales-booster-es(_[A-Za-z]+)?-/', 'dox-sales-booster-es_ES-', basename( $file ), 1, $hits );
    if ( ! $hits ) return $file;

    $es = DSB_PATH . 'languages/' . $name;
    return is_readable( $es ) ? $es : $file;
}, 10, 3 );

// ─── Menú común de los plugins de Dox Studio ──────────────────────────────────
// Cada plugin Dox lleva su copia de dox-core y se carga solo la más nueva de
// todas las instaladas, así que esto no pisa nada si hay más plugins Dox.
// Con file_exists: si la carpeta llegara a medias (una subida cortada), el plugin
// sigue en pie en vez de tumbar el sitio con un error fatal. Se apunta igual en
// Dox Plugins si otro plugin Dox trae el core y, si no, usa su propio menú (el
// respaldo de admin/settings.php).
if ( file_exists( DSB_PATH . 'dox-core/loader.php' ) && file_exists( DSB_PATH . 'dox-core/version.php' ) ) {
    require_once DSB_PATH . 'dox-core/loader.php';
    if ( class_exists( 'Dox_Core_Loader' ) ) { // Un loader.php vacío (la subida se cortó ahí) existe pero no define nada.
        Dox_Core_Loader::register( require DSB_PATH . 'dox-core/version.php', DSB_PATH . 'dox-core/dox-core.php' );
    }
}

// ─── Cargar archivos ───────────────────────────────────────────────────────────
require_once DSB_PATH . 'includes/cities.php';
require_once DSB_PATH . 'includes/render.php';
require_once DSB_PATH . 'includes/blocks.php';
require_once DSB_PATH . 'includes/frontend.php';
require_once DSB_PATH . 'includes/shipping-bar.php';
require_once DSB_PATH . 'includes/auto-insert.php';
if ( is_admin() ) {
    require_once DSB_PATH . 'admin/settings.php';
}

// ─── Aviso si WooCommerce no está activo ──────────────────────────────────────
add_action( 'admin_notices', function () {
    if ( class_exists( 'WooCommerce' ) ) return;
    if ( ! current_user_can( 'activate_plugins' ) ) return;
    echo '<div class="notice notice-warning"><p><strong>Dox Sales Booster:</strong> '
        . esc_html__( 'WooCommerce is not active. The purchase popup and the low stock notice need WooCommerce; the "people viewing" and "recent sales" counters keep working.', 'dox-sales-booster' )
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
    $manager->register( new DSB_Widget_Shipbar() );
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
