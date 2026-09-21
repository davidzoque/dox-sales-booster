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

// Método free_shipping de la zona que aplica al cliente actual, si depende de
// un pedido mínimo: [ 'min' => monto, 'ignore_discounts' => bool ]. Null si la
// zona no tiene envío gratis por monto mínimo.
function dsb_wc_free_shipping_method() {
    static $found = false; // false = todavía sin calcular
    if ( false !== $found ) return $found;

    // Sin carrito todavía no se puede saber la zona: no se guarda nada, para
    // que una llamada temprana no deje el resultado vacío el resto de la carga.
    if ( ! function_exists( 'WC' ) || null === WC()->cart || ! class_exists( 'WC_Shipping_Zones' ) ) return null;

    $found    = null;
    $packages = WC()->cart->get_shipping_packages();
    $package  = $packages ? reset( $packages ) : null;
    if ( ! $package ) return $found;

    $zone = WC_Shipping_Zones::get_zone_matching_package( $package );
    if ( ! $zone ) return $found;

    foreach ( $zone->get_shipping_methods( true ) as $method ) {
        if ( 'free_shipping' !== $method->id ) continue;
        // Solo cuenta si el envío gratis depende de un monto mínimo
        if ( ! in_array( $method->get_option( 'requires' ), [ 'min_amount', 'either', 'both' ], true ) ) continue;
        $amount = (float) $method->get_option( 'min_amount' );
        if ( $amount > 0 ) {
            $found = [
                'min'              => $amount,
                'ignore_discounts' => 'yes' === $method->get_option( 'ignore_discounts' ),
            ];
            break;
        }
    }
    return $found;
}

// Pedido mínimo de ese método. Devuelve 0 si la zona no lo tiene.
function dsb_wc_free_shipping_min() {
    $method = dsb_wc_free_shipping_method();
    return $method ? $method['min'] : 0;
}

// ¿El importe se cuenta antes de los cupones? Cuando el umbral sale del método
// de WooCommerce manda SU casilla ("aplicar el mínimo antes del cupón"), para
// que la barra y el checkout no puedan decir cosas distintas; con monto propio
// o con un umbral puesto a mano en el shortcode, manda la del panel.
function dsb_shipbar_ignores_coupons( $opts, $override = 0 ) {
    if ( (float) $override <= 0 && 'woocommerce' === ( $opts['shipbar_source'] ?? 'custom' ) ) {
        $method = dsb_wc_free_shipping_method();
        if ( $method ) return $method['ignore_discounts'];
    }
    return ! empty( $opts['shipbar_ignore_coupons'] );
}

// Importe del carrito que cuenta para el envío gratis. Es la misma cuenta que
// hace WC_Shipping_Free_Shipping::is_available(): el subtotal tal como se
// muestra (con o sin impuestos) y, si los cupones cuentan, menos el descuento
// con su impuesto. Hasta la 1.6.0 con los impuestos activos se partía del
// subtotal ANTES de cupones y encima se le sumaba el descuento, así que con un
// cupón puesto la barra felicitaba por un envío gratis que el checkout no daba.
function dsb_shipbar_cart_amount( $ignore_coupons ) {
    $cart   = WC()->cart;
    $amount = (float) $cart->get_displayed_subtotal();

    if ( ! $ignore_coupons ) {
        $amount -= (float) $cart->get_discount_total();
        if ( $cart->display_prices_including_tax() ) {
            $amount -= (float) $cart->get_discount_tax();
        }
    }
    return max( 0, round( $amount, wc_get_price_decimals() ) );
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

    $amount = dsb_shipbar_cart_amount( dsb_shipbar_ignores_coupons( $o, $args['threshold'] ) );
    $amount = (float) apply_filters( 'dsb_shipbar_amount', $amount );

    $done    = $amount >= $threshold;
    $percent = $done ? 100 : (int) floor( ( $amount / $threshold ) * 100 );
    $missing = max( 0, $threshold - $amount );

    if ( $done ) {
        $message = esc_html( $args['success_text'] );
    } else {
        $price_html = '<span class="dsb-shipbar-amount">' . wc_price( $missing ) . '</span>';
        $message    = str_replace( [ '{amount}', '{precio}', '{price}' ], $price_html, esc_html( $args['text'] ) );
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
    // 'dsb_envio_gratis' era el nombre hasta la 1.4.0 y se mantiene como alias
    // para no romper las páginas que ya lo tienen puesto.
    foreach ( [ 'dsb_free_shipping', 'dsb_envio_gratis' ] as $dsb_sc_tag ) {
        add_shortcode( $dsb_sc_tag, function ( $atts, $content = '', $tag = 'dsb_free_shipping' ) {
            $atts = shortcode_atts( [ 'threshold' => '', 'text' => '', 'success_text' => '' ], $atts, $tag );
            return dsb_render_shipping_bar( $atts );
        } );
    }

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

    // El cajón del mini carrito por bloques se abre en cualquier página, así que
    // el script tiene que estar cargado en todas. Va sin dependencias y arranca
    // cuando encuentra wp.data (que el propio bloque carga), para no obligar a
    // las tiendas con mini carrito clásico a descargar los paquetes del editor.
    $mini = ! empty( $o['shipbar_minicart'] );

    if ( ! $on_cart && ! $on_checkout && ! $mini ) return;

    wp_enqueue_script( 'dsb-shipbar-blocks', DSB_URL . 'assets/js/dsb-shipbar-blocks.js', [], DSB_VERSION, true );
    wp_localize_script( 'dsb-shipbar-blocks', 'dsbShipbar', [
        'threshold'     => dsb_shipbar_threshold( $o ),
        'text'          => $o['shipbar_text'],
        'successText'   => $o['shipbar_success_text'],
        'ignoreCoupons' => dsb_shipbar_ignores_coupons( $o ),
        'inclTax'       => ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->display_prices_including_tax() : false,
        'miniCart'      => $mini,
        'barColor'      => sanitize_hex_color( $o['shipbar_bar_color'] )   ?: '#4caf50',
        'trackColor'    => sanitize_hex_color( $o['shipbar_track_color'] ) ?: '#e9e9f0',
        'textColor'     => sanitize_hex_color( $o['shipbar_text_color'] )  ?: '#333333',
    ] );

    // La barra del cajón la crea el JS, así que los estilos del plugin pueden no
    // haberse encolado por ningún render.
    if ( $mini ) wp_enqueue_style( 'dsb-styles' );

    if ( $on_checkout ) {
        wp_add_inline_script(
            'wc-checkout',
            'jQuery(function($){$(document.body).on("applied_coupon_in_checkout removed_coupon_in_checkout",function(){$(document.body).trigger("wc_fragment_refresh");});});'
        );
    }
}, 20 );
