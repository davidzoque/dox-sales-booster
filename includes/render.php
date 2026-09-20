<?php
/**
 * Núcleo compartido: ajustes por defecto, caché de productos, feed del popup
 * (simulado o con pedidos reales) y funciones de render reutilizadas por
 * shortcodes, widgets de Elementor y bloques de Gutenberg.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ── Valores por defecto ─────────────────────────────────────────────────── */

function dsb_default_locations() {
    // Lista de ejemplo, traducible: cada idioma trae las ciudades que tienen
    // sentido para su mercado (en español, las colombianas de siempre). El
    // dueño de la tienda las cambia desde el panel, una por línea.
    $cities = explode( "\n", __( "New York, NY 🇺🇸\nLos Angeles, CA 🇺🇸\nChicago, IL 🇺🇸\nHouston, TX 🇺🇸\nPhoenix, AZ 🇺🇸\nPhiladelphia, PA 🇺🇸\nSan Antonio, TX 🇺🇸\nSan Diego, CA 🇺🇸\nDallas, TX 🇺🇸\nJacksonville, FL 🇺🇸\nAustin, TX 🇺🇸\nFort Worth, TX 🇺🇸\nSan Jose, CA 🇺🇸\nColumbus, OH 🇺🇸\nCharlotte, NC 🇺🇸\nIndianapolis, IN 🇺🇸\nSan Francisco, CA 🇺🇸\nSeattle, WA 🇺🇸\nDenver, CO 🇺🇸\nOklahoma City, OK 🇺🇸\nNashville, TN 🇺🇸\nWashington, DC 🇺🇸\nEl Paso, TX 🇺🇸\nLas Vegas, NV 🇺🇸\nBoston, MA 🇺🇸\nDetroit, MI 🇺🇸\nPortland, OR 🇺🇸\nLouisville, KY 🇺🇸\nMemphis, TN 🇺🇸\nBaltimore, MD 🇺🇸\nMilwaukee, WI 🇺🇸\nAlbuquerque, NM 🇺🇸\nTucson, AZ 🇺🇸\nFresno, CA 🇺🇸\nSacramento, CA 🇺🇸\nMesa, AZ 🇺🇸\nAtlanta, GA 🇺🇸\nKansas City, MO 🇺🇸\nColorado Springs, CO 🇺🇸\nOmaha, NE 🇺🇸\nRaleigh, NC 🇺🇸\nMiami, FL 🇺🇸\nVirginia Beach, VA 🇺🇸\nLong Beach, CA 🇺🇸\nOakland, CA 🇺🇸\nMinneapolis, MN 🇺🇸\nBakersfield, CA 🇺🇸\nTulsa, OK 🇺🇸\nTampa, FL 🇺🇸\nArlington, TX 🇺🇸\nNew Orleans, LA 🇺🇸\nWichita, KS 🇺🇸\nCleveland, OH 🇺🇸\nOrlando, FL 🇺🇸\nSt. Louis, MO 🇺🇸\nPittsburgh, PA 🇺🇸\nCincinnati, OH 🇺🇸\nSalt Lake City, UT 🇺🇸\nBoise, ID 🇺🇸\nRichmond, VA 🇺🇸", 'dox-sales-booster' ) );
    $cities = array_values( array_filter( array_map( 'trim', $cities ) ) );

    return $cities ? $cities : [ 'New York, NY 🇺🇸' ];
}

function dsb_defaults() {
    return [
        // Personas viendo
        'viewing_enabled'        => 1,
        'viewing_min'            => 3,
        'viewing_max'            => 12,
        'viewing_text'           => __( 'people are viewing this product right now.', 'dox-sales-booster' ),
        'viewing_interval'       => 2,
        'viewing_auto'           => 1,
        'viewing_position'       => 'after_price',
        'viewing_text_color'     => '#555555',
        'viewing_count_color'    => '#e44c4c',

        // Ventas recientes
        'fakesales_enabled'      => 1,
        'fakesales_min'          => 3,
        'fakesales_max'          => 15,
        'fakesales_text'         => __( '🔥 {count} sold in the last {timeframe} {period}', 'dox-sales-booster' ),
        'fakesales_timeframe'    => 24,
        'fakesales_period'       => 'hours',
        'fakesales_data_mode'    => 'simulated', // simulated | real
        'fakesales_auto'         => 1,
        'fakesales_position'     => 'after_price',
        'fakesales_text_color'   => '#555555',
        'fakesales_count_color'  => '#e44c4c',

        // Stock bajo (datos reales de WooCommerce)
        'stock_enabled'          => 1,
        'stock_threshold'        => 10,
        'stock_text'             => __( '⚡ Only {stock} units left!', 'dox-sales-booster' ),
        'stock_auto'             => 1,
        'stock_position'         => 'before_add_to_cart',
        'stock_text_color'       => '#b3261e',
        'stock_count_color'      => '#b3261e',

        // Barra de envío gratis
        'shipbar_enabled'        => 0,
        'shipbar_minicart'       => 1,
        'shipbar_cart'           => 1,
        'shipbar_checkout'       => 1,
        'shipbar_source'         => 'custom', // custom | woocommerce
        'shipbar_threshold'      => 100,
        'shipbar_ignore_coupons' => 1,
        'shipbar_text'           => __( '🚚 Add {amount} more to get free shipping!', 'dox-sales-booster' ),
        'shipbar_success_text'   => __( '🎉 Congratulations! You get free shipping.', 'dox-sales-booster' ),
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
        'popup_prefix_text'      => __( '🛍️ Someone purchased', 'dox-sales-booster' ),
        'popup_link_text'        => __( 'View product', 'dox-sales-booster' ),
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
        // Hasta la 1.4.0 el período se guardaba en español; se normaliza al leer
        // para que los sitios que actualizan sigan funcionando sin tocar nada.
        $opts['fakesales_period'] = dsb_normalize_period( $opts['fakesales_period'] );
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

    // Campo vacío: se cae a la lista por defecto, que es traducible y trae las
    // ciudades del idioma del sitio.
    return dsb_default_locations();
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
            ? sprintf( __( 'From %s', 'dox-sales-booster' ), wc_price( $min ) )
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

/* ── Contador de ventas: número estable y ventas reales ──────────────────── */

// Período guardado, normalizado. Hasta la 1.4.0 se guardaba en español
// ("horas", "días"): los valores viejos se siguen entendiendo.
function dsb_normalize_period( $period ) {
    $legacy = [ 'minutos' => 'minutes', 'horas' => 'hours', 'días' => 'days', 'dias' => 'days', 'semanas' => 'weeks' ];
    $period = $legacy[ (string) $period ] ?? (string) $period;
    return in_array( $period, [ 'minutes', 'hours', 'days', 'weeks' ], true ) ? $period : 'hours';
}

// Etiqueta del período tal como la lee el visitante dentro de {period}.
function dsb_period_label( $period ) {
    switch ( dsb_normalize_period( $period ) ) {
        case 'minutes': return _x( 'minutes', 'sales period', 'dox-sales-booster' );
        case 'days':    return _x( 'days', 'sales period', 'dox-sales-booster' );
        case 'weeks':   return _x( 'weeks', 'sales period', 'dox-sales-booster' );
        default:        return _x( 'hours', 'sales period', 'dox-sales-booster' );
    }
}

// Segundos que representa el período configurado ("hours", "days"...).
function dsb_period_seconds( $period ) {
    switch ( dsb_normalize_period( $period ) ) {
        case 'minutes': return MINUTE_IN_SECONDS;
        case 'days':    return DAY_IN_SECONDS;
        case 'weeks':   return WEEK_IN_SECONDS;
        default:        return HOUR_IN_SECONDS;
    }
}

// Producto del contexto actual (o el pasado explícitamente por atributo).
function dsb_context_product_id( $explicit = 0 ) {
    $explicit = absint( $explicit );
    if ( $explicit ) return $explicit;
    if ( ! function_exists( 'wc_get_product' ) ) return 0;

    global $product;
    if ( $product instanceof WC_Product ) return (int) $product->get_id();
    if ( get_the_ID() && 'product' === get_post_type() ) return (int) get_the_ID();
    return 0;
}

// Número pseudoaleatorio DETERMINISTA: la misma semilla devuelve siempre el
// mismo número. Con wp_rand() el contador cambiaba en cada carga de página (se
// notaba falso al recargar); así se mantiene fijo durante toda la ventana de
// tiempo, es idéntico para todos los visitantes y sobrevive a la caché de página.
function dsb_stable_count( $seed, $min, $max ) {
    if ( $max <= $min ) return $min;
    // 7 dígitos hex = 28 bits: siempre positivo y cabe en un int de 32 bits.
    $hash = hexdec( substr( md5( (string) $seed ), 0, 7 ) );
    return $min + (int) ( $hash % ( $max - $min + 1 ) );
}

// Unidades REALES vendidas de un producto dentro de la ventana de tiempo.
// Cacheado 15 min porque recorre pedidos.
function dsb_real_sales_count( $product_id, $window_seconds ) {
    $product_id = (int) $product_id;
    if ( ! $product_id || ! function_exists( 'wc_get_orders' ) ) return 0;

    $cache_key = 'dsb_rs_' . md5( dsb_cache_salt() . '|' . $product_id . '|' . (int) $window_seconds );
    $cached    = get_transient( $cache_key );
    if ( false !== $cached ) return (int) $cached;

    $count  = 0;
    $orders = wc_get_orders( [
        'limit'        => 100,
        'status'       => [ 'completed', 'processing' ],
        'date_created' => '>' . ( time() - (int) $window_seconds ),
    ] );

    foreach ( (array) $orders as $order ) {
        if ( ! $order instanceof WC_Order ) continue;
        foreach ( $order->get_items() as $item ) {
            // get_product_id() devuelve el padre en variaciones: cuenta todas
            // las variantes bajo el mismo producto, que es lo que se muestra.
            if ( (int) $item->get_product_id() === $product_id ) {
                $count += (int) $item->get_quantity();
            }
        }
    }

    set_transient( $cache_key, $count, 15 * MINUTE_IN_SECONDS );
    return $count;
}

/* ── Render compartido (shortcodes / Elementor / Gutenberg) ───────────────── */

// Los args vacíos o null caen al valor global del panel.
// Estilo en línea de un elemento: escribe solo las variables de color que
// traiga esa instancia (bloque o widget). Lo que no venga lo sigue poniendo el
// color global del panel, que se imprime con los estilos del plugin.
function dsb_inline_colors( $args, $map ) {
    $style = '';
    foreach ( $map as $key => $var ) {
        if ( empty( $args[ $key ] ) ) continue;
        $hex = sanitize_hex_color( $args[ $key ] );
        if ( $hex ) $style .= $var . ':' . $hex . ';';
    }
    return $style ? ' style="' . esc_attr( $style ) . '"' : '';
}

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
        'min'        => $o['viewing_min'],
        'max'        => $o['viewing_max'],
        'text'        => $o['viewing_text'],
        'product_id'  => 0,
        'text_color'  => '',
        'count_color' => '',
    ] );

    $min = max( 1, (int) $args['min'] );
    $max = max( $min, (int) $args['max'] );

    // Clave de persistencia: el JS guarda el número por producto en
    // sessionStorage, para que al recargar continúe donde iba en vez de saltar
    // a otro valor aleatorio. El número impreso aquí es solo el respaldo
    // (visitantes sin JS); el JS lo sustituye por el suyo al cargar.
    $key = dsb_context_product_id( $args['product_id'] );
    if ( ! $key ) $key = 'page';

    dsb_ensure_assets( true );

    $colors = dsb_inline_colors( $args, [ 'text_color' => '--dsb-viewing-text', 'count_color' => '--dsb-viewing-count' ] );

    return '<p class="dsb-live-viewing" data-min="' . esc_attr( $min ) . '" data-max="' . esc_attr( $max ) . '" data-key="' . esc_attr( $key ) . '"' . $colors . '>'
        . '<span class="dsb-eye-icon">&#128065;</span> '
        . '<span class="dsb-viewing-count">' . wp_rand( $min, $max ) . '</span> '
        . esc_html( $args['text'] )
        . '</p>';
}

function dsb_render_sales( $args = [] ) {
    $o = dsb_get_settings();
    if ( empty( $o['fakesales_enabled'] ) ) return '';

    $args = wp_parse_args( dsb_filter_args( $args ), [
        'min'        => $o['fakesales_min'],
        'max'        => $o['fakesales_max'],
        'text'       => $o['fakesales_text'],
        'timeframe'  => $o['fakesales_timeframe'],
        'period'     => $o['fakesales_period'],
        'product_id' => 0,
    ] );

    $min       = max( 1, (int) $args['min'] );
    $max       = max( $min, (int) $args['max'] );
    $timeframe = max( 1, (int) $args['timeframe'] );
    $window    = $timeframe * dsb_period_seconds( $args['period'] );
    $pid       = dsb_context_product_id( $args['product_id'] );

    if ( 'real' === ( $o['fakesales_data_mode'] ?? 'simulated' ) ) {
        // Ventas REALES del producto en la ventana. Si no hubo ninguna no se
        // inventa un número: el elemento simplemente no se muestra.
        $count = dsb_real_sales_count( $pid, $window );
        if ( $count < 1 ) return '';
    } else {
        // Simulado, pero ESTABLE: el mismo número durante toda la ventana de
        // tiempo, en vez de uno nuevo en cada carga de página.
        $bucket = (int) floor( time() / max( MINUTE_IN_SECONDS, $window ) );
        $count  = dsb_stable_count( $pid . '|' . $bucket . '|' . $min . '|' . $max, $min, $max );
    }

    $text = str_replace(
        [ '{count}', '{timeframe}', '{period}' ],
        [ '<strong class="dsb-sales-count">' . (int) $count . '</strong>', esc_html( $args['timeframe'] ), esc_html( dsb_period_label( $args['period'] ) ) ],
        esc_html( $args['text'] )
    );

    dsb_ensure_assets();

    $colors = dsb_inline_colors( $args, [ 'text_color' => '--dsb-sales-text', 'count_color' => '--dsb-sales-count' ] );

    return '<p class="dsb-fake-sales"' . $colors . '>' . wp_kses( $text, [ 'strong' => [ 'class' => [] ] ] ) . '</p>';
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

    $colors = dsb_inline_colors( $args, [ 'text_color' => '--dsb-stock-text', 'count_color' => '--dsb-stock-count' ] );

    return '<p class="dsb-low-stock"' . $colors . '>' . wp_kses( $text, [ 'strong' => [ 'class' => [] ] ] ) . '</p>';
}
