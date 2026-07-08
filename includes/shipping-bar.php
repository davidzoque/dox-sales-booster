<?php
/**
 * Barra de progreso de envío gratis.
 * Se inserta tanto en el mini carrito y el carrito/checkout CLÁSICOS (hooks
 * estándar de WooCommerce) como en el carrito/checkout por BLOQUES de Gutenberg
 * (vía render_block). En el mini cart se refresca con los cart fragments; en los
 * bloques, con un JS que escucha el Store API (assets/js/dsb-shipbar-blocks.js).
 * Compatible con UICore Pro, Elementor, Storefront, etc. El umbral puede ser un
 * monto propio o leerse del método "Envío gratuito" de la zona del cliente.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ── Umbral ───────────────────────────────────────────────────────────────── */

// Pedido mínimo del método free_shipping de la zona que aplica al cliente
// actual. Devuelve 0 si la zona no tiene envío gratis por monto mínimo.
function dsb_wc_free_shipping_min() {
    static $min = null;
    if ( null !== $min ) return $min;
    $min = 0;

    if ( ! function_exists( 'WC' ) || null === WC()->cart || ! class_exists( 'WC_Shipping_Zones' ) ) return $min;

    $packages = WC()->cart->get_shipping_packages();
    $package  = $packages ? reset( $packages ) : null;
    if ( ! $package ) return $min;

    $zone = WC_Shipping_Zones::get_zone_matching_package( $package );
    if ( ! $zone ) return $min;

    foreach ( $zone->get_shipping_methods( true ) as $method ) {
        if ( 'free_shipping' !== $method->id ) continue;
        // Solo cuenta si el envío gratis depende de un monto mínimo
        if ( ! in_array( $method->get_option( 'requires' ), [ 'min_amount', 'either', 'both' ], true ) ) continue;
        $amount = (float) $method->get_option( 'min_amount' );
        if ( $amount > 0 ) {
            $min = $amount;
            break;
        }
    }
    return $min;
}

// Resuelve el umbral efectivo: override del shortcode/widget > fuente
// WooCommerce (si está configurada y tiene monto) > monto propio del panel.
function dsb_shipbar_threshold( $opts, $override = 0 ) {
    $override = (float) $override;
    if ( $override > 0 ) return $override;

    if ( 'woocommerce' === ( $opts['shipbar_source'] ?? 'custom' ) ) {
        $wc_min = dsb_wc_free_shipping_min();
        if ( $wc_min > 0 ) return $wc_min;
        // Sin mínimo configurado en WooCommerce → cae al monto propio.
    }
    return (float) $opts['shipbar_threshold'];
}

/* ── Render ───────────────────────────────────────────────────────────────── */

/**
 * @param array $args  Overrides: threshold, text, success_text, bar_color,
 *                     track_color, text_color. Vacíos = valores del panel.
 * @param bool  $auto  true en las inserciones automáticas (hooks + fragments):
 *                     añade la clase que usan los cart fragments para
 *                     refrescar la barra sin recargar la página.
 */
function dsb_render_shipping_bar( $args = [], $auto = false ) {
    $o = dsb_get_settings();
    if ( empty( $o['shipbar_enabled'] ) ) return '';
    if ( ! function_exists( 'WC' ) || null === WC()->cart ) return '';

    $args = wp_parse_args( dsb_filter_args( $args ), [
        'threshold'    => 0, // 0 = usar la fuente configurada en el panel
        'text'         => $o['shipbar_text'],
        'success_text' => $o['shipbar_success_text'],
        'bar_color'    => $o['shipbar_bar_color'],
        'track_color'  => $o['shipbar_track_color'],
        'text_color'   => $o['shipbar_text_color'],
    ] );

    $threshold = dsb_shipbar_threshold( $o, $args['threshold'] );
    if ( $threshold <= 0 ) return '';

    // Mismo cálculo que usa WooCommerce para el pedido mínimo: subtotal
    // mostrado (con/sin impuestos según la tienda), opcionalmente sin cupones.
    $amount = wc_tax_enabled()
        ? (float) WC()->cart->get_displayed_subtotal()
        : (float) WC()->cart->cart_contents_total;
    if ( ! empty( $o['shipbar_ignore_coupons'] ) ) {
        $amount += (float) WC()->cart->get_discount_total();
    }
    $amount = (float) apply_filters( 'dsb_shipbar_amount', $amount );

    $done    = $amount >= $threshold;
    $percent = $done ? 100 : (int) floor( ( $amount / $threshold ) * 100 );
    $missing = max( 0, $threshold - $amount );

    if ( $done ) {
        $message = esc_html( $args['success_text'] );
    } else {
        $price_html = '<span class="dsb-shipbar-amount">' . wc_price( $missing ) . '</span>';
        $message    = str_replace( [ '{precio}', '{price}' ], $price_html, esc_html( $args['text'] ) );
    }

    // wc_price() genera spans con clase y <bdi> — permitirlos y nada más.
    $message = wp_kses( $message, [
        'span' => [ 'class' => [] ],
        'bdi'  => [],
    ] );

    $style = sprintf(
        '--dsb-shipbar-fill:%s;--dsb-shipbar-track:%s;--dsb-shipbar-text:%s;',
        sanitize_hex_color( $args['bar_color'] ) ?: '#4caf50',
        sanitize_hex_color( $args['track_color'] ) ?: '#e9e9f0',
        sanitize_hex_color( $args['text_color'] ) ?: '#333333'
    );

    dsb_ensure_assets();

    $wrap_class = 'dsb-shipbar-wrap' . ( $auto ? ' dsb-shipbar-auto' : '' );

    return '<div class="' . esc_attr( $wrap_class ) . '">'
        . '<div class="dsb-shipbar' . ( $done ? ' dsb-shipbar-done' : '' ) . '" style="' . esc_attr( $style ) . '">'
        . '<p class="dsb-shipbar-msg">' . $message . '</p>'
        . '<div class="dsb-shipbar-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr( $percent ) . '">'
        . '<div class="dsb-shipbar-fill" style="width:' . esc_attr( $percent ) . '%"></div>'
        . '</div></div></div>';
}

/* ── Inserción automática ─────────────────────────────────────────────────── */

// Render con candado por contexto: evita duplicar la barra si en la misma página
// se dispararan tanto el hook clásico como el filtro de bloques (una página usa
// uno u otro, pero así queda a prueba de temas que mezclen ambos sistemas).
function dsb_shipbar_render_once( $context ) {
    static $done = [];
    if ( ! empty( $done[ $context ] ) ) return '';
    $done[ $context ] = true;
    return dsb_render_shipping_bar( [], true );
}

add_action( 'init', function () {
    if ( ! class_exists( 'WooCommerce' ) ) return;

    $o = dsb_get_settings();

    // Shortcode siempre disponible (devuelve '' si la barra está desactivada).
    add_shortcode( 'dsb_envio_gratis', function ( $atts ) {
        $atts = shortcode_atts( [ 'threshold' => '', 'text' => '', 'success_text' => '' ], $atts, 'dsb_envio_gratis' );
        return dsb_render_shipping_bar( $atts );
    } );

    if ( empty( $o['shipbar_enabled'] ) ) return; // sin inserción automática

    // Mini carrito (offcanvas de UICore, widget de WooCommerce, etc.): entre el
    // subtotal y los botones. Vive dentro de .widget_shopping_cart_content, así
    // que se refresca solo con los cart fragments estándar.
    if ( ! empty( $o['shipbar_minicart'] ) ) {
        add_action( 'woocommerce_widget_shopping_cart_before_buttons', function () {
            echo dsb_render_shipping_bar( [], true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado en dsb_render_shipping_bar()
        }, 5 );
    }

    // Carrito / checkout CLÁSICOS (shortcode [woocommerce_cart] / [woocommerce_checkout]).
    if ( ! empty( $o['shipbar_cart'] ) ) {
        add_action( 'woocommerce_before_cart', function () {
            echo dsb_shipbar_render_once( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado en dsb_render_shipping_bar()
        }, 5 );
    }
    if ( ! empty( $o['shipbar_checkout'] ) ) {
        add_action( 'woocommerce_before_checkout_form', function () {
            echo dsb_shipbar_render_once( 'checkout' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado en dsb_render_shipping_bar()
        }, 5 );
    }

    // Carrito / checkout por BLOQUES (Gutenberg woocommerce/cart y woocommerce/
    // checkout). Estos NO disparan los hooks clásicos, así que anteponemos la
    // barra al bloque contenedor cuando se renderiza.
    if ( ! empty( $o['shipbar_cart'] ) || ! empty( $o['shipbar_checkout'] ) ) {
        add_filter( 'render_block', function ( $content, $block ) use ( $o ) {
            $name = $block['blockName'] ?? '';
            if ( 'woocommerce/cart' === $name && ! empty( $o['shipbar_cart'] ) ) {
                return dsb_shipbar_render_once( 'cart' ) . $content;
            }
            if ( 'woocommerce/checkout' === $name && ! empty( $o['shipbar_checkout'] ) ) {
                return dsb_shipbar_render_once( 'checkout' ) . $content;
            }
            return $content;
        }, 10, 2 );
    }
} );

/* ── Refresco en vivo ─────────────────────────────────────────────────────── */

// Mini carrito clásico: cada instancia automática se registra como cart fragment,
// así WooCommerce la reemplaza vía AJAX en added_to_cart / wc_fragment_refresh.
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
    $html = dsb_render_shipping_bar( [], true );
    if ( '' !== $html ) {
        $fragments['.dsb-shipbar-wrap.dsb-shipbar-auto'] = $html;
    }
    return $fragments;
} );

// Carrito / checkout por bloques: usan el Store API, no los cart fragments. Este
// script escucha wc/store/cart y recalcula la barra in-place. En el checkout
// clásico, además refresca los fragments al aplicar/quitar cupones.
add_action( 'wp_enqueue_scripts', function () {
    if ( ! function_exists( 'is_cart' ) ) return;
    $o = dsb_get_settings();
    if ( empty( $o['shipbar_enabled'] ) ) return;

    $on_cart     = is_cart() && ! empty( $o['shipbar_cart'] );
    $on_checkout = is_checkout() && ! empty( $o['shipbar_checkout'] );
    if ( ! $on_cart && ! $on_checkout ) return;

    wp_enqueue_script( 'dsb-shipbar-blocks', DSB_URL . 'assets/js/dsb-shipbar-blocks.js', [ 'wp-data' ], DSB_VERSION, true );
    wp_localize_script( 'dsb-shipbar-blocks', 'dsbShipbar', [
        'threshold'     => dsb_shipbar_threshold( $o ),
        'text'          => $o['shipbar_text'],
        'successText'   => $o['shipbar_success_text'],
        'ignoreCoupons' => ! empty( $o['shipbar_ignore_coupons'] ),
    ] );

    if ( $on_checkout ) {
        wp_add_inline_script(
            'wc-checkout',
            'jQuery(function($){$(document.body).on("applied_coupon_in_checkout removed_coupon_in_checkout",function(){$(document.body).trigger("wc_fragment_refresh");});});'
        );
    }
}, 20 );
