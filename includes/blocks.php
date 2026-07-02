<?php
/**
 * Bloques de Gutenberg (dinámicos, sin build step).
 * Reutilizan las funciones de render de includes/render.php: los atributos
 * vacíos heredan los valores globales del panel Sales Booster.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'dsb_register_blocks' );

function dsb_register_blocks() {
    if ( ! function_exists( 'register_block_type' ) ) return;

    // Los handles se registran aquí (init) para que existan tanto en el editor
    // como en el frontend. El registro duplicado en DSB_Frontend es inocuo.
    wp_register_style( 'dsb-styles', DSB_URL . 'assets/css/dsb.css', [], DSB_VERSION );
    wp_register_script( 'dsb-scripts', DSB_URL . 'assets/js/dsb.js', [], DSB_VERSION, true );

    wp_register_script(
        'dsb-blocks',
        DSB_URL . 'assets/js/dsb-blocks.js',
        [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ],
        DSB_VERSION,
        true
    );
    if ( function_exists( 'wp_set_script_translations' ) ) {
        wp_set_script_translations( 'dsb-blocks', 'dox-sales-booster', DSB_PATH . 'languages' );
    }

    register_block_type( 'dox-sales-booster/viewing', [
        'api_version'     => 2,
        'editor_script'   => 'dsb-blocks',
        'style'           => 'dsb-styles',
        'view_script'     => 'dsb-scripts', // refresco periódico del contador
        'attributes'      => [
            'min'  => [ 'type' => 'number' ],
            'max'  => [ 'type' => 'number' ],
            'text' => [ 'type' => 'string' ],
        ],
        'render_callback' => function ( $attrs ) {
            return dsb_render_viewing( $attrs );
        },
    ] );

    register_block_type( 'dox-sales-booster/sales', [
        'api_version'     => 2,
        'editor_script'   => 'dsb-blocks',
        'style'           => 'dsb-styles',
        'attributes'      => [
            'min'       => [ 'type' => 'number' ],
            'max'       => [ 'type' => 'number' ],
            'text'      => [ 'type' => 'string' ],
            'timeframe' => [ 'type' => 'number' ],
            'period'    => [ 'type' => 'string' ],
        ],
        'render_callback' => function ( $attrs ) {
            return dsb_render_sales( $attrs );
        },
    ] );

    register_block_type( 'dox-sales-booster/stock', [
        'api_version'     => 2,
        'editor_script'   => 'dsb-blocks',
        'style'           => 'dsb-styles',
        'attributes'      => [
            'threshold'  => [ 'type' => 'number' ],
            'text'       => [ 'type' => 'string' ],
            'product_id' => [ 'type' => 'number' ],
        ],
        'render_callback' => function ( $attrs ) {
            return dsb_render_stock( $attrs );
        },
    ] );
}
