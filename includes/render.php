<?php
/**
 * Núcleo compartido: ajustes por defecto, caché de productos, feed del popup
 * (simulado o con pedidos reales) y funciones de render reutilizadas por
 * shortcodes, widgets de Elementor y bloques de Gutenberg.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ── Valores por defecto ─────────────────────────────────────────────────── */

function dsb_default_locations() {
    $cities = [
        'Bogotá, D.C.', 'Medellín, Antioquia', 'Cali, Valle del Cauca', 'Barranquilla, Atlántico',
        'Cartagena, Bolívar', 'Cúcuta, Norte de Santander', 'Bucaramanga, Santander', 'Pereira, Risaralda',
        'Manizales, Caldas', 'Santa Marta, Magdalena', 'Ibagué, Tolima', 'Pasto, Nariño',
        'Montería, Córdoba', 'Armenia, Quindío', 'Villavicencio, Meta', 'Neiva, Huila',
        'Popayán, Cauca', 'Valledupar, Cesar', 'Sincelejo, Sucre', 'Tunja, Boyacá',
        'Florencia, Caquetá', 'Quibdó, Chocó', 'Riohacha, La Guajira', 'Mocoa, Putumayo',
        'Leticia, Amazonas', 'San Andrés, San Andrés y Providencia', 'Yopal, Casanare', 'Arauca, Arauca',
        'San José del Guaviare, Guaviare', 'Inírida, Guainía', 'Puerto Carreño, Vichada', 'Mitú, Vaupés',
        'Envigado, Antioquia', 'Bello, Antioquia', 'Itagüí, Antioquia', 'Soledad, Atlántico',
        'Soacha, Cundinamarca', 'Palmira, Valle del Cauca', 'Buenaventura, Valle del Cauca', 'Floridablanca, Santander',
        'Girón, Santander', 'Dosquebradas, Risaralda', 'Tuluá, Valle del Cauca', 'Barrancabermeja, Santander',
        'Duitama, Boyacá', 'Sogamoso, Boyacá', 'Cartago, Valle del Cauca', 'Buga, Valle del Cauca',
        'Jamundí, Valle del Cauca', 'Yumbo, Valle del Cauca', 'Rionegro, Antioquia', 'Apartadó, Antioquia',
        'Turbo, Antioquia', 'Caucasia, Antioquia', 'Sabaneta, Antioquia', 'Copacabana, Antioquia',
        'La Estrella, Antioquia', 'Caldas, Antioquia', 'Zipaquirá, Cundinamarca', 'Chía, Cundinamarca',
        'Facatativá, Cundinamarca', 'Fusagasugá, Cundinamarca', 'Mosquera, Cundinamarca', 'Madrid, Cundinamarca',
        'Funza, Cundinamarca', 'Cajicá, Cundinamarca', 'Girardot, Cundinamarca', 'Malambo, Atlántico',
        'Sabanalarga, Atlántico', 'Baranoa, Atlántico', 'Magangué, Bolívar', 'Turbaco, Bolívar',
        'El Carmen de Bolívar, Bolívar', 'Ciénaga, Magdalena', 'Fundación, Magdalena', 'El Banco, Magdalena',
        'Ipiales, Nariño', 'Tumaco, Nariño', 'Ocaña, Norte de Santander', 'Villa del Rosario, Norte de Santander',
        'Los Patios, Norte de Santander', 'Pamplona, Norte de Santander', 'Piedecuesta, Santander', 'San Gil, Santander',
        'Socorro, Santander', 'Chiquinquirá, Boyacá', 'Paipa, Boyacá', 'Puerto Boyacá, Boyacá',
        'El Espinal, Tolima', 'Melgar, Tolima', 'Honda, Tolima', 'Pitalito, Huila',
        'Garzón, Huila', 'La Plata, Huila', 'Acacías, Meta', 'Granada, Meta',
        'Puerto López, Meta', 'Aguazul, Casanare', 'Paz de Ariporo, Casanare', 'Saravena, Arauca',
        'Tame, Arauca', 'Puerto Asís, Putumayo', 'Orito, Putumayo', 'San Juan del Cesar, La Guajira',
        'Maicao, La Guajira', 'Uribia, La Guajira', 'Aguachica, Cesar', 'Bosconia, Cesar',
        'Corozal, Sucre', 'San Marcos, Sucre', 'San Vicente del Caguán, Caquetá', 'Puerto Rico, Caquetá',
        'Santa Rosa de Cabal, Risaralda', 'Chinchiná, Caldas', 'La Dorada, Caldas', 'Riosucio, Caldas',
        'Calarcá, Quindío', 'Montenegro, Quindío', 'Quimbaya, Quindío', 'Cereté, Córdoba',
        'Sahagún, Córdoba', 'Lorica, Córdoba', 'Tierralta, Córdoba', 'Santander de Quilichao, Cauca',
        'El Tambo, Cauca', 'Puerto Tejada, Cauca', 'Istmina, Chocó', 'Tadó, Chocó',
    ];
    return array_map( function ( $c ) { return $c . ' 🇨🇴'; }, $cities );
}

function dsb_defaults() {
    return [
        // Personas viendo
        'viewing_enabled'        => 1,
        'viewing_min'            => 3,
        'viewing_max'            => 12,
        'viewing_text'           => __( 'personas están viendo este producto ahora.', 'dox-sales-booster' ),
        'viewing_interval'       => 2,

        // Ventas recientes
        'fakesales_enabled'      => 1,
        'fakesales_min'          => 3,
        'fakesales_max'          => 15,
        'fakesales_text'         => __( '🔥 {count} vendidos en las últimas {timeframe} {period}', 'dox-sales-booster' ),
        'fakesales_timeframe'    => 24,
        'fakesales_period'       => 'horas',

        // Stock bajo (datos reales de WooCommerce)
        'stock_enabled'          => 1,
        'stock_threshold'        => 10,
        'stock_text'             => __( '⚡ ¡Solo quedan {stock} unidades!', 'dox-sales-booster' ),

        // Barra de envío gratis
        'shipbar_enabled'        => 0,
        'shipbar_minicart'       => 1,
        'shipbar_cart'           => 1,
        'shipbar_checkout'       => 1,
        'shipbar_source'         => 'custom', // custom | woocommerce
        'shipbar_threshold'      => 150000,
        'shipbar_ignore_coupons' => 1,
        'shipbar_text'           => __( '🚚 ¡Te faltan {precio} para el envío gratis!', 'dox-sales-booster' ),
        'shipbar_success_text'   => __( '🎉 ¡Felicidades! Tienes envío gratis.', 'dox-sales-booster' ),
        'shipbar_bar_color'      => '#4caf50',
        'shipbar_track_color'    => '#e9e9f0',
        'shipbar_text_color'     => '#333333',

        // Popup — comportamiento
        'popup_enabled'          => 1,
        'popup_interval'         => 25,
        'popup_display_seconds'  => 7,
        'popup_animation'        => 'slide_up',
        'popup_position'         => 'left',
        'popup_show_mobile'      => 1,
        'popup_exclude_checkout' => 1,
        'popup_close_silence'    => 30,
        'popup_max_per_page'     => 0,
        'popup_first_delay_min'  => 3,
        'popup_first_delay_max'  => 6,

        // Popup — datos
        'popup_data_mode'        => 'simulated',
        'popup_products_type'    => 'random',
        'popup_hide_outofstock'  => 0,
        'popup_cats_include'     => [],
        'popup_cats_exclude'     => [],
        'popup_show_price'       => 1,
        'popup_ago_min'          => 1,
        'popup_ago_max'          => 59,
        'popup_names'            => '',

        // Popup — apariencia
        'popup_font_title'       => 14,
        'popup_font_price'       => 13,
        'popup_font_meta'        => 12,
        'popup_font_link'        => 12,
        'popup_prefix_text'      => __( '🛍️ Alguien ha comprado', 'dox-sales-booster' ),
        'popup_link_text'        => __( 'Ver producto', 'dox-sales-booster' ),
        'popup_width'            => 400,
        'popup_img_size'         => 75,
        'popup_title_maxchars'   => 45,
        'popup_bg_color'         => '#ffffff',
        'popup_title_color'      => '#1a1a1a',
        'popup_meta_color'       => '#777777',
        'popup_link_color'       => '#555555',

        // Ubicaciones (una por línea)
        'popup_locations'        => implode( "\n", dsb_default_locations() ),
    ];
}

function dsb_get_settings( $fresh = false ) {
    static $opts = null;
    if ( $fresh || null === $opts ) {
        $opts = wp_parse_args( get_option( 'dsb_settings', [] ), dsb_defaults() );
    }
    return $opts;
}

/* ── Caché de productos del popup ─────────────────────────────────────────── */
// Las claves de transient incluyen un "salt" versionado; invalidar = rotar el
// salt (los transients viejos expiran solos por TTL).

function dsb_cache_salt() {
    $v = get_option( 'dsb_cache_ver' );
    if ( ! $v ) {
        $v = (string) time();
        add_option( 'dsb_cache_ver', $v, '', false );
    }
    // La versión del plugin forma parte del salt: cada actualización invalida
    // la caché automáticamente (los items cacheados pueden cambiar de formato).
    return DSB_VERSION . '|' . $v;
}

function dsb_flush_popup_cache() {
    update_option( 'dsb_cache_ver', (string) microtime( true ), false );
}

// Cambios de stock: solo interesa si el popup oculta productos agotados, y con
// un candado de 5 min para no invalidar en cascada en tiendas con muchas ventas.
function dsb_maybe_flush_stock_cache() {
    $o = dsb_get_settings();
    if ( empty( $o['popup_enabled'] ) || empty( $o['popup_hide_outofstock'] ) ) return;
    if ( get_transient( 'dsb_stock_flush_lock' ) ) return;
    set_transient( 'dsb_stock_flush_lock', 1, 5 * MINUTE_IN_SECONDS );
    dsb_flush_popup_cache();
}

add_action( 'save_post_product', 'dsb_flush_popup_cache' );
add_action( 'before_delete_post', function ( $post_id ) {
    if ( get_post_type( $post_id ) === 'product' ) dsb_flush_popup_cache();
} );
add_action( 'woocommerce_product_set_stock', 'dsb_maybe_flush_stock_cache' );
add_action( 'woocommerce_variation_set_stock', 'dsb_maybe_flush_stock_cache' );
add_action( 'woocommerce_product_stock_status_changed', 'dsb_maybe_flush_stock_cache' );

/* ── Parsers de listas ────────────────────────────────────────────────────── */

function dsb_parse_list_lines( $raw ) {
    if ( ! is_string( $raw ) || '' === trim( $raw ) ) return [];
    $parts = array_map( 'trim', preg_split( '/[\r\n;]+/', $raw ) );
    return array_values( array_filter( $parts, 'strlen' ) );
}

function dsb_parse_locations( $raw ) {
    // Formato legado 1.x: {{{Ciudad}}}; {{{Ciudad}}}
    if ( is_string( $raw ) && false !== strpos( $raw, '{{{' ) ) {
        preg_match_all( '/\{\{\{([^}]+)\}\}\}/', $raw, $m );
        if ( ! empty( $m[1] ) ) return array_map( 'trim', $m[1] );
    }
    $lines = dsb_parse_list_lines( $raw );
    if ( $lines ) return $lines;
    return [
        'Bogotá, D.C. 🇨🇴', 'Medellín, Antioquia 🇨🇴', 'Cali, Valle del Cauca 🇨🇴',
        'Barranquilla, Atlántico 🇨🇴', 'Cartagena, Bolívar 🇨🇴',
    ];
}

/* ── Feed de productos del popup ──────────────────────────────────────────── */

// Precio en texto plano para el popup. Se construye con wc_price() en vez de
// limpiar get_price_html(): ese HTML incluye texto para lectores de pantalla
// ("Rango de precios: desde...", "El precio original era...") que al quitar las
// etiquetas quedaba duplicado. Los variables muestran "Desde <mínimo>".
function dsb_price_text( $product ) {
    if ( $product->is_type( 'variable' ) ) {
        $min = $product->get_variation_price( 'min', true );
        $max = $product->get_variation_price( 'max', true );
        if ( '' === $min ) return '';
        $price = ( $min < $max )
            /* translators: %s: minimum price of a variable product. */
            ? sprintf( __( 'Desde %s', 'dox-sales-booster' ), wc_price( $min ) )
            : wc_price( $min );
    } else {
        if ( '' === $product->get_price() ) return '';
        $price = wc_price( wc_get_price_to_display( $product ) );
    }
    return trim( html_entity_decode( wp_strip_all_tags( $price ), ENT_QUOTES, 'UTF-8' ) );
}

function dsb_product_to_item( $product ) {
    if ( ! $product instanceof WC_Product ) return null;
    $image_id  = $product->get_image_id();
    $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : wc_placeholder_img_src( 'thumbnail' );
    return [
        'title' => $product->get_name(),
        'url'   => get_permalink( $product->get_id() ),
        'image' => esc_url( $image_url ),
        'price' => dsb_price_text( $product ),
    ];
}

// Modo simulado: catálogo según la fuente configurada. Cacheado 3 h.
function dsb_get_popup_products( $opts ) {
    if ( ! function_exists( 'wc_get_product' ) ) return [];

    $type    = $opts['popup_products_type'] ?? 'random';
    $instock = ! empty( $opts['popup_hide_outofstock'] );
    $incl    = array_map( 'absint', (array) ( $opts['popup_cats_include'] ?? [] ) );
    $excl    = array_map( 'absint', (array) ( $opts['popup_cats_exclude'] ?? [] ) );

    $cache_key = 'dsb_pp_' . md5( implode( '|', [ dsb_cache_salt(), 'sim', $type, (int) $instock, implode( ',', $incl ), implode( ',', $excl ) ] ) );
    $cached    = get_transient( $cache_key );
    if ( 'none' === $cached ) return [];
    if ( is_array( $cached ) && $cached ) return $cached;

    $args = [
        'post_type'           => 'product',
        'post_status'         => 'publish',
        'posts_per_page'      => 20,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ];

    $tax_query = [];
    switch ( $type ) {
        case 'featured':
            $tax_query[] = [ 'taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => 'featured' ];
            break;
        case 'sale':
            $args['post__in'] = array_merge( [ 0 ], wc_get_product_ids_on_sale() );
            break;
        case 'bestsellers':
            $args['meta_key'] = 'total_sales';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = 'DESC';
            break;
        default:
            $args['orderby'] = 'rand';
    }

    if ( $incl ) $tax_query[] = [ 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $incl ];
    if ( $excl ) $tax_query[] = [ 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $excl, 'operator' => 'NOT IN' ];
    if ( $tax_query ) {
        if ( count( $tax_query ) > 1 ) $tax_query['relation'] = 'AND';
        $args['tax_query'] = $tax_query;
    }

    if ( $instock ) {
        $args['meta_query'] = [ [ 'key' => '_stock_status', 'value' => 'instock' ] ];
    }

    $query    = new WP_Query( $args );
    $products = [];
    foreach ( $query->posts as $post ) {
        $item = dsb_product_to_item( wc_get_product( $post->ID ) );
        if ( $item ) $products[] = $item;
    }

    // Cache negativa corta para no repetir la consulta cuando no hay resultados.
    set_transient( $cache_key, $products ?: 'none', $products ? 3 * HOUR_IN_SECONDS : 10 * MINUTE_IN_SECONDS );
    return $products;
}

// Modo real: pedidos recientes de WooCommerce (últimos 30 días). Solo se expone
// producto, ciudad de facturación y fecha — nunca datos identificables del cliente.
function dsb_get_real_sales( $opts ) {
    if ( ! function_exists( 'wc_get_orders' ) ) return [];

    $cache_key = 'dsb_pp_' . md5( dsb_cache_salt() . '|real' );
    $cached    = get_transient( $cache_key );
    if ( 'none' === $cached ) return [];
    if ( is_array( $cached ) && $cached ) return $cached;

    $orders = wc_get_orders( [
        'limit'        => 30,
        'status'       => [ 'completed', 'processing' ],
        'orderby'      => 'date',
        'order'        => 'DESC',
        'date_created' => '>' . ( time() - 30 * DAY_IN_SECONDS ),
    ] );

    $items = [];
    foreach ( (array) $orders as $order ) {
        if ( ! $order instanceof WC_Order ) continue;
        $line_items = $order->get_items();
        if ( empty( $line_items ) ) continue;
        $line    = reset( $line_items );
        $product = wc_get_product( $line->get_product_id() ); // producto padre: nombre genérico, sin variación
        if ( ! $product || ! $product->is_visible() ) continue;
        $item = dsb_product_to_item( $product );
        if ( ! $item ) continue;
        $city = trim( (string) $order->get_billing_city() );
        if ( $city ) $item['city'] = $city;
        $created = $order->get_date_created();
        if ( $created ) $item['ts'] = $created->getTimestamp();
        $items[] = $item;
    }

    set_transient( $cache_key, $items ?: 'none', $items ? 30 * MINUTE_IN_SECONDS : 10 * MINUTE_IN_SECONDS );
    return $items;
}

function dsb_get_popup_feed( $opts ) {
    if ( empty( $opts['popup_enabled'] ) ) return [];
    if ( 'real' === ( $opts['popup_data_mode'] ?? 'simulated' ) ) {
        $real = dsb_get_real_sales( $opts );
        if ( $real ) return $real;
        // Sin pedidos recientes → fallback al modo simulado.
    }
    return dsb_get_popup_products( $opts );
}

/* ── Render compartido (shortcodes / Elementor / Gutenberg) ───────────────── */

// Los args vacíos o null caen al valor global del panel.
function dsb_filter_args( $args ) {
    return array_filter( (array) $args, function ( $v ) {
        return null !== $v && '' !== $v;
    } );
}

// Red de seguridad: si un elemento se renderiza en una página donde los assets
// no se encolaron (p. ej. shortcode con las funciones globales apagadas),
// se encolan aquí y WordPress los imprime en el footer.
function dsb_ensure_assets( $with_js = false ) {
    if ( is_admin() || ! wp_style_is( 'dsb-styles', 'registered' ) ) return;
    wp_enqueue_style( 'dsb-styles' );
    if ( $with_js && wp_script_is( 'dsb-scripts', 'registered' ) ) {
        wp_enqueue_script( 'dsb-scripts' );
    }
}

function dsb_render_viewing( $args = [] ) {
    $o = dsb_get_settings();
    if ( empty( $o['viewing_enabled'] ) ) return '';

    $args = wp_parse_args( dsb_filter_args( $args ), [
        'min'  => $o['viewing_min'],
        'max'  => $o['viewing_max'],
        'text' => $o['viewing_text'],
    ] );

    $min = max( 1, (int) $args['min'] );
    $max = max( $min, (int) $args['max'] );

    dsb_ensure_assets( true );

    return '<p class="dsb-live-viewing" data-min="' . esc_attr( $min ) . '" data-max="' . esc_attr( $max ) . '">'
        . '<span class="dsb-eye-icon">&#128065;</span> '
        . '<span class="dsb-viewing-count">' . wp_rand( $min, $max ) . '</span> '
        . esc_html( $args['text'] )
        . '</p>';
}

function dsb_render_sales( $args = [] ) {
    $o = dsb_get_settings();
    if ( empty( $o['fakesales_enabled'] ) ) return '';

    $args = wp_parse_args( dsb_filter_args( $args ), [
        'min'       => $o['fakesales_min'],
        'max'       => $o['fakesales_max'],
        'text'      => $o['fakesales_text'],
        'timeframe' => $o['fakesales_timeframe'],
        'period'    => $o['fakesales_period'],
    ] );

    $min  = max( 1, (int) $args['min'] );
    $max  = max( $min, (int) $args['max'] );
    $text = str_replace(
        [ '{count}', '{timeframe}', '{period}' ],
        [ '<strong class="dsb-sales-count">' . wp_rand( $min, $max ) . '</strong>', esc_html( $args['timeframe'] ), esc_html( $args['period'] ) ],
        esc_html( $args['text'] )
    );

    dsb_ensure_assets();

    return '<p class="dsb-fake-sales">' . wp_kses( $text, [ 'strong' => [ 'class' => [] ] ] ) . '</p>';
}

// Urgencia con stock REAL de WooCommerce. Solo renderiza si el producto
// gestiona inventario y le quedan entre 1 y {threshold} unidades.
function dsb_render_stock( $args = [] ) {
    $o = dsb_get_settings();
    if ( empty( $o['stock_enabled'] ) || ! function_exists( 'wc_get_product' ) ) return '';

    $args = wp_parse_args( dsb_filter_args( $args ), [
        'product_id' => 0,
        'threshold'  => $o['stock_threshold'],
        'text'       => $o['stock_text'],
    ] );

    $product_obj = null;
    $pid         = absint( $args['product_id'] );
    if ( $pid ) {
        $product_obj = wc_get_product( $pid );
    } else {
        global $product;
        if ( $product instanceof WC_Product ) {
            $product_obj = $product;
        } elseif ( get_the_ID() && 'product' === get_post_type() ) {
            $product_obj = wc_get_product( get_the_ID() );
        }
    }
    if ( ! $product_obj ) return '';

    $stock = $product_obj->get_stock_quantity();
    if ( null === $stock && $product_obj->is_type( 'variable' ) ) {
        // Variable sin stock propio: suma del stock gestionado de sus variaciones.
        $sum = null;
        foreach ( $product_obj->get_children() as $vid ) {
            $variation = wc_get_product( $vid );
            if ( ! $variation ) continue;
            $q = $variation->get_stock_quantity();
            if ( null !== $q ) $sum = (int) $sum + max( 0, (int) $q );
        }
        $stock = $sum;
    }

    $threshold = max( 1, (int) $args['threshold'] );
    if ( null === $stock || $stock <= 0 || $stock > $threshold ) return '';

    $text = str_replace(
        '{stock}',
        '<strong class="dsb-stock-count">' . (int) $stock . '</strong>',
        esc_html( $args['text'] )
    );

    dsb_ensure_assets();

    return '<p class="dsb-low-stock">' . wp_kses( $text, [ 'strong' => [ 'class' => [] ] ] ) . '</p>';
}
