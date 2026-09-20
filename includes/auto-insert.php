<?php
/**
 * Inserción automática en la ficha de producto.
 *
 * El contador de personas viendo, el de ventas recientes y el aviso de stock
 * se podían colocar de tres formas: shortcode, widget de Elementor o bloque de
 * Gutenberg. En una tienda cuya ficha de producto usa la plantilla nativa de
 * WooCommerce (sin Elementor y sin editar la ficha con bloques) no había dónde
 * ponerlos sin escribir PHP. Desde aquí se insertan solos en la posición que
 * elija el panel, como ya hacía la barra de envío gratis con sus ubicaciones.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Posiciones que ofrece el panel.
 *
 * Las prioridades son las de woocommerce_single_product_summary, donde
 * WooCommerce pone el título en 5, la valoración y el precio en 10, la
 * descripción corta en 20, el formulario de compra en 30, los datos del
 * producto en 40 y los botones de compartir en 50.
 */
function dsb_product_positions() {
    return [
        'after_price'        => [
            'label'    => __( 'After the price', 'dox-sales-booster' ),
            'hook'     => 'woocommerce_single_product_summary',
            'priority' => 11,
        ],
        'after_excerpt'      => [
            'label'    => __( 'After the short description', 'dox-sales-booster' ),
            'hook'     => 'woocommerce_single_product_summary',
            'priority' => 21,
        ],
        'before_add_to_cart' => [
            'label'    => __( 'Before the add to cart form', 'dox-sales-booster' ),
            'hook'     => 'woocommerce_single_product_summary',
            'priority' => 29,
        ],
        'before_button'      => [
            'label'    => __( 'Just above the add to cart button', 'dox-sales-booster' ),
            'hook'     => 'woocommerce_before_add_to_cart_button',
            'priority' => 10,
        ],
        'after_button'       => [
            'label'    => __( 'Just below the add to cart button', 'dox-sales-booster' ),
            'hook'     => 'woocommerce_after_add_to_cart_button',
            'priority' => 10,
        ],
        'after_add_to_cart'  => [
            'label'    => __( 'After the add to cart form', 'dox-sales-booster' ),
            'hook'     => 'woocommerce_single_product_summary',
            'priority' => 31,
        ],
        'after_meta'         => [
            'label'    => __( 'After the product meta (SKU, categories)', 'dox-sales-booster' ),
            'hook'     => 'woocommerce_single_product_summary',
            'priority' => 41,
        ],
    ];
}

// Elemento -> prefijo de sus opciones y función que lo pinta.
function dsb_auto_elements() {
    return [
        'viewing' => [ 'prefix' => 'viewing',   'render' => 'dsb_render_viewing' ],
        'sales'   => [ 'prefix' => 'fakesales', 'render' => 'dsb_render_sales' ],
        'stock'   => [ 'prefix' => 'stock',     'render' => 'dsb_render_stock' ],
    ];
}

add_action( 'init', function () {
    if ( ! class_exists( 'WooCommerce' ) ) return;

    $o         = dsb_get_settings();
    $positions = dsb_product_positions();

    foreach ( dsb_auto_elements() as $el ) {
        $prefix = $el['prefix'];
        if ( empty( $o[ $prefix . '_enabled' ] ) || empty( $o[ $prefix . '_auto' ] ) ) continue;

        $where = isset( $positions[ $o[ $prefix . '_position' ] ] )
            ? $positions[ $o[ $prefix . '_position' ] ]
            : $positions['after_price'];

        $render = $el['render'];
        add_action( $where['hook'], function () use ( $render ) {
            // Solo en la ficha: algunos temas reutilizan estos hooks en vistas
            // rápidas o en el bucle de la tienda, donde no pintan nada.
            if ( ! is_product() ) return;
            echo call_user_func( $render ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado en el propio render
        }, $where['priority'] );
    }
} );

/**
 * Migración de versión.
 *
 * La inserción automática nace encendida, que es lo que espera quien instala el
 * plugin hoy. Pero una tienda que ya lo tenía puesto ha colocado los elementos
 * a mano (con un widget, un bloque o un shortcode) y encontrárselos otra vez
 * al actualizar sería un duplicado: por eso, al venir de una versión anterior,
 * se apaga. El interruptor queda en el panel para quien lo quiera.
 */
add_action( 'init', function () {
    if ( get_option( 'dsb_version' ) === DSB_VERSION ) return;

    $stored   = get_option( 'dsb_version' );
    $settings = get_option( 'dsb_settings' );

    // Sin dsb_version (no existía antes de la 1.6.0) pero con ajustes guardados
    // = viene de la 1.5.0 o anterior.
    if ( ! $stored && is_array( $settings ) ) {
        $settings['viewing_auto']   = 0;
        $settings['fakesales_auto'] = 0;
        $settings['stock_auto']     = 0;
        update_option( 'dsb_settings', $settings );
        dsb_get_settings( true ); // refrescar la copia en memoria
    }

    update_option( 'dsb_version', DSB_VERSION, false );
}, 2 );
