<?php
/**
 * Panel de administración — Dox Sales Booster
 * Los valores por defecto y helpers de datos viven en includes/render.php.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ── Menú ─────────────────────────────────────────────────────────────────── */
add_action( 'admin_menu', function () {
    add_menu_page(
        __( 'Dox Sales Booster', 'dox-sales-booster' ),
        __( 'Sales Booster', 'dox-sales-booster' ),
        'manage_options',
        'dox-sales-booster',
        'dsb_render_page',
        dsb_menu_icon(),
        58
    );
} );

/* ── Estilos/scripts del admin (handles propios, contenido inline) ────────── */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( 'toplevel_page_dox-sales-booster' !== $hook ) return;

    wp_register_style( 'dsb-admin', false, [], DSB_VERSION );
    wp_enqueue_style( 'dsb-admin' );
    wp_add_inline_style( 'dsb-admin', dsb_admin_css() );

    wp_register_script( 'dsb-admin', false, [ 'jquery' ], DSB_VERSION, true );
    wp_enqueue_script( 'dsb-admin' );
    wp_add_inline_script( 'dsb-admin', dsb_admin_js() );
} );

/* ── Guardar via AJAX ─────────────────────────────────────────────────────── */
add_action( 'wp_ajax_dsb_save_settings', function () {
    check_ajax_referer( 'dsb_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Sin permisos.', 'dox-sales-booster' ) ], 403 );
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- se sanea campo a campo en dsb_sanitize()
    $raw   = isset( $_POST['dsb'] ) ? wp_unslash( (array) $_POST['dsb'] ) : [];
    $clean = dsb_sanitize( $raw );
    update_option( 'dsb_settings', $clean );
    dsb_flush_popup_cache();

    // Recalcular el feed con los ajustes recién guardados (pre-calienta la caché
    // y alimenta el aviso de "fuente sin productos" del panel).
    $fresh      = wp_parse_args( $clean, dsb_defaults() );
    $feed_count = ! empty( $fresh['popup_enabled'] ) ? count( dsb_get_popup_feed( $fresh ) ) : -1;

    wp_send_json_success( [
        'message'    => __( '¡Configuración guardada!', 'dox-sales-booster' ),
        'feed_count' => $feed_count,
    ] );
} );

/* ── Restaurar valores por defecto via AJAX ───────────────────────────────── */
add_action( 'wp_ajax_dsb_reset_settings', function () {
    check_ajax_referer( 'dsb_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Sin permisos.', 'dox-sales-booster' ) ], 403 );
    }
    delete_option( 'dsb_settings' );
    dsb_flush_popup_cache();
    wp_send_json_success( [ 'message' => __( 'Valores por defecto restaurados.', 'dox-sales-booster' ) ] );
} );

/* ── Sanitización ─────────────────────────────────────────────────────────── */
function dsb_sanitize( $input ) {
    $d = dsb_defaults();
    $c = [];

    // Checkboxes
    foreach ( [ 'viewing_enabled', 'fakesales_enabled', 'stock_enabled', 'popup_enabled',
                'popup_show_mobile', 'popup_hide_outofstock', 'popup_exclude_checkout', 'popup_show_price',
                'shipbar_enabled', 'shipbar_minicart', 'shipbar_cart', 'shipbar_checkout', 'shipbar_ignore_coupons' ] as $k ) {
        $c[ $k ] = ! empty( $input[ $k ] ) ? 1 : 0;
    }

    // Numéricos: [min, max] permitidos por campo (mismos límites que el formulario)
    $ranges = [
        'viewing_min'           => [ 1, 50 ],
        'viewing_max'           => [ 1, 200 ],
        'viewing_interval'      => [ 1, 60 ],
        'fakesales_min'         => [ 1, 50 ],
        'fakesales_max'         => [ 1, 200 ],
        'fakesales_timeframe'   => [ 1, 999 ],
        'stock_threshold'       => [ 1, 999 ],
        'popup_interval'        => [ 10, 300 ],
        'popup_display_seconds' => [ 3, 30 ],
        'popup_first_delay_min' => [ 0, 120 ],
        'popup_first_delay_max' => [ 1, 180 ],
        'popup_ago_min'         => [ 1, 999 ],
        'popup_ago_max'         => [ 1, 999 ],
        'popup_close_silence'   => [ 0, 1440 ],
        'popup_max_per_page'    => [ 0, 50 ],
        'popup_font_title'      => [ 8, 30 ],
        'popup_font_price'      => [ 8, 28 ],
        'popup_font_meta'       => [ 8, 24 ],
        'popup_font_link'       => [ 8, 24 ],
        'popup_width'           => [ 200, 500 ],
        'popup_img_size'        => [ 40, 120 ],
        'popup_title_maxchars'  => [ 20, 100 ],
        'shipbar_threshold'     => [ 0, 999999999 ],
    ];
    foreach ( $ranges as $k => $r ) {
        $c[ $k ] = isset( $input[ $k ] ) ? min( $r[1], max( $r[0], absint( $input[ $k ] ) ) ) : $d[ $k ];
    }

    // Selects: solo valores de la whitelist
    $selects = [
        'fakesales_period'    => [ 'minutos', 'horas', 'días', 'semanas' ],
        'fakesales_data_mode' => [ 'simulated', 'real' ],
        'popup_animation'     => [ 'slide_up', 'slide_right' ],
        'popup_position'      => [ 'left', 'right' ],
        'popup_data_mode'     => [ 'simulated', 'real' ],
        'popup_products_type' => [ 'random', 'featured', 'sale', 'bestsellers' ],
        'shipbar_source'      => [ 'custom', 'woocommerce' ],
    ];
    foreach ( $selects as $k => $allowed ) {
        $v        = isset( $input[ $k ] ) ? sanitize_text_field( $input[ $k ] ) : '';
        $c[ $k ]  = in_array( $v, $allowed, true ) ? $v : $d[ $k ];
    }

    // Textos
    foreach ( [ 'viewing_text', 'fakesales_text', 'stock_text', 'popup_locations',
                'popup_prefix_text', 'popup_link_text', 'popup_names',
                'shipbar_text', 'shipbar_success_text' ] as $k ) {
        $c[ $k ] = isset( $input[ $k ] ) ? sanitize_textarea_field( $input[ $k ] ) : $d[ $k ];
    }

    // Colores (sanitize_hex_color devuelve ''/null si no es válido → default)
    foreach ( [ 'popup_bg_color', 'popup_title_color', 'popup_meta_color', 'popup_link_color',
                'shipbar_bar_color', 'shipbar_track_color', 'shipbar_text_color' ] as $k ) {
        $hex     = isset( $input[ $k ] ) ? sanitize_hex_color( $input[ $k ] ) : '';
        $c[ $k ] = $hex ?: $d[ $k ];
    }

    // Categorías (multiselect)
    foreach ( [ 'popup_cats_include', 'popup_cats_exclude' ] as $k ) {
        $c[ $k ] = isset( $input[ $k ] ) && is_array( $input[ $k ] )
            ? array_values( array_filter( array_map( 'absint', $input[ $k ] ) ) )
            : [];
    }

    // Coherencia entre campos
    if ( $c['viewing_min'] > $c['viewing_max'] )                     $c['viewing_max']           = $c['viewing_min'];
    if ( $c['fakesales_min'] > $c['fakesales_max'] )                 $c['fakesales_max']         = $c['fakesales_min'];
    if ( $c['popup_first_delay_min'] > $c['popup_first_delay_max'] ) $c['popup_first_delay_max'] = $c['popup_first_delay_min'];
    if ( $c['popup_ago_min'] > $c['popup_ago_max'] )                 $c['popup_ago_max']         = $c['popup_ago_min'];
    // La duración visible nunca puede alcanzar el intervalo entre popups
    if ( $c['popup_display_seconds'] >= $c['popup_interval'] ) {
        $c['popup_display_seconds'] = max( 3, $c['popup_interval'] - 1 );
    }

    return $c;
}

/* ── SVG Logo Dox Studio ──────────────────────────────────────────────────── */
function dsb_logo_svg() {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 949.82 161.7" height="34" aria-label="Dox Studio">
  <defs><style>.dl1{fill:#141313}.dl2{fill:#ff8d27}</style></defs>
  <g>
    <path class="dl1" d="M298.6,22.18v117.35h-21.79v-11.16c-6.85,9.63-18.05,15.54-32.99,12.83-22-4-39.13-22.4-39.86-44.75-.87-26.61,20.53-48.55,46.95-48.55,11.43,0,20.18,5.45,25.9,13.49V22.18h21.79ZM274.94,94.88c0-14.47-10-26.08-23.76-26.08s-24.92,11.7-24.92,26.08,11.34,25.99,24.92,25.99,23.76-11.61,23.76-25.99Z"/>
    <path class="dl1" d="M307.97,94.88c0-26.08,20.01-47.16,44.65-47.16s44.65,21.08,44.65,47.16-20.01,46.98-44.65,46.98-44.65-21.08-44.65-46.98ZM375.48,94.88c0-13.84-10.27-25.36-22.86-25.36s-22.86,11.79-22.86,25.36,10.45,25.18,22.86,25.18,22.86-11.61,22.86-25.18Z"/>
    <path class="dl1" d="M427.63,94.7l-32.42-44.57h26.88l18.93,25.99,19.02-25.99h26.88l-32.42,44.57,32.69,44.83h-26.88l-19.29-26.44-19.2,26.44h-26.88l32.69-44.83Z"/>
    <path class="dl2" d="M493.44,122.03l12.86-5.54c2.68,6.16,12.77,11.61,22.06,11.61s18.93-4.82,18.93-13.22c0-8.93-10.36-11.16-19.65-13.84-18.49-4.64-31.97-11.16-31.97-26.79,0-16.61,15.54-27.06,32.78-27.06,14.38,0,27.24,6.7,33.05,17.24l-12.06,7.06c-3.04-6.43-11.7-10.9-19.92-11.07-10.63-.27-19.74,4.47-19.74,13.58s9.65,10.09,20.72,13.66c16.16,5.18,31.08,11.97,30.81,27.06,0,16.34-16.7,27.33-34.39,26.53-14.47-.54-28.85-8.13-33.49-19.2Z"/>
    <path class="dl2" d="M597.29,50.22l-.08-28.05h-15.01l.08,28.05h-15.35v15.01h25.31l-9.98,9.98-.07,64.33h15.01l.08-74.31h20.27v-15.01h-20.27Z"/>
    <path class="dl2" d="M624.61,50.22h15.01v51.23c0,12.78,9.49,23.98,22.22,25.12,14.5,1.29,26.63-10.05,26.63-24.28v-52.07h15l.09,89.31h-15.01l-.09-11.34c-7.67,10.04-20.69,15.78-34.81,12.94-17.11-3.44-29.05-19.07-29.05-36.52v-54.38Z"/>
    <path class="dl2" d="M802.06,22.18v117.35h-15v-15.18c-6.14,10.57-16.67,17.67-30.75,17.5-24.08-.28-44.46-19.15-46.35-43.16-2.19-27.71,19.67-50.88,46.83-50.88,13.84,0,24.2,7.06,30.28,17.5V22.18h15ZM785.81,94.88c0-18.22-11.43-32.51-28.85-32.51s-31.8,14.56-31.8,32.51,14.56,32.42,31.8,32.42,28.85-14.56,28.85-32.42Z"/>
    <path class="dl2" d="M817.45,28.43c-.49-5.08,3.23-9.02,8.26-9.02,4.56,0,8.22,3.48,8.22,8.13,0,5.01-4,8.52-9.04,8.09-3.84-.33-7.07-3.36-7.44-7.2ZM833.13,50.22v89.31h-15V50.22h15Z"/>
    <path class="dl2" d="M842.68,94.88c0-26.08,20.01-47.16,44.66-47.16s44.65,21.08,44.65,47.16-20.01,46.98-44.65,46.98-44.66-21.08-44.66-46.98ZM916.99,94.88c0-17.42-13.13-32.15-29.65-32.15s-29.65,14.83-29.65,32.15,13.48,31.97,29.65,31.97,29.65-14.82,29.65-31.97Z"/>
    <polygon class="dl1" points="93.37 0 23.34 40.41 93.37 80.85 93.37 161.7 163.38 121.29 163.38 40.42 93.37 0"/>
    <path class="dl2" d="M17.51,144.87h0c23.34,13.47,52.52-3.38,52.52-30.33h0c0-12.51-6.67-24.07-17.51-30.32h0C29.18,70.73,0,87.58,0,114.54h0c0,12.51,6.68,24.07,17.51,30.33Z"/>
    <path class="dl2" d="M935.17,48.88h-3.18v-1.16h7.57v1.16h-3.2v6.43h-1.18v-6.43ZM942.06,55.31h-1.18v-7.59h1.08l3.47,3.98,3.33-3.98h1.06v7.59h-1.2v-6.07l-3.2,3.86h-.05l-3.3-3.86v6.07Z"/>
  </g>
</svg>';
}

function dsb_menu_icon() {
    return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>');
}

function dsb_icon_eye()  { return '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'; }
function dsb_icon_fire() { return '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2c0 0-3 3-3 7 0 2 1 3 1 3s-2-1-2-4c-2 2-3 5-3 7a7 7 0 0014 0c0-5-4-8-7-13z"/></svg>'; }
function dsb_icon_bag()  { return '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>'; }
function dsb_icon_code() { return '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>'; }
function dsb_icon_bolt() { return '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>'; }
function dsb_icon_truck() { return '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="1"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>'; }

/* ── Render página ─────────────────────────────────────────────────────────── */
function dsb_render_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    $o     = dsb_get_settings();
    $nonce = wp_create_nonce( 'dsb_nonce' );
    $font_title = (int) $o['popup_font_title'];
    $font_price = (int) $o['popup_font_price'];
    $font_meta  = (int) $o['popup_font_meta'];
    $font_link  = (int) $o['popup_font_link'];

    // Ubicaciones: mostrar el formato legado {{{...}}} como una por línea
    $loc_display = $o['popup_locations'];
    if ( false !== strpos( $loc_display, '{{{' ) ) {
        $loc_display = implode( "\n", dsb_parse_locations( $loc_display ) );
    }

    // Categorías de producto para los filtros del popup
    $cats = [];
    if ( taxonomy_exists( 'product_cat' ) ) {
        $terms = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false ] );
        if ( ! is_wp_error( $terms ) ) $cats = $terms;
    }
    $cats_incl = array_map( 'absint', (array) $o['popup_cats_include'] );
    $cats_excl = array_map( 'absint', (array) $o['popup_cats_exclude'] );

    // ¿La fuente actual del popup tiene productos? (cacheado)
    $feed_count = ! empty( $o['popup_enabled'] ) ? count( dsb_get_popup_feed( $o ) ) : -1;

    $disp_secs = (int) $o['popup_display_seconds'];
    $maxchars  = (int) $o['popup_title_maxchars'];
    ?>
    <div class="dsb-wrap">

        <div class="dsb-header">
            <div class="dsb-header-left">
                <?php echo dsb_logo_svg(); ?>
                <span class="dsb-header-badge">Sales Booster</span>
                <span class="dsb-header-version">v<?php echo esc_html( DSB_VERSION ); ?></span>
            </div>
            <div class="dsb-header-actions">
                <button class="dsb-btn-reset" id="dsb-reset-btn" type="button"><?php esc_html_e( 'Restaurar valores por defecto', 'dox-sales-booster' ); ?></button>
                <button class="dsb-btn-save" id="dsb-save-btn" type="button"><?php esc_html_e( 'Guardar cambios', 'dox-sales-booster' ); ?></button>
            </div>
        </div>

        <div class="dsb-tabs">
            <button type="button" class="dsb-tab active" data-tab="viewing"><?php echo dsb_icon_eye(); ?> <?php esc_html_e( 'Personas viendo', 'dox-sales-booster' ); ?></button>
            <button type="button" class="dsb-tab" data-tab="sales"><?php echo dsb_icon_fire(); ?> <?php esc_html_e( 'Ventas recientes', 'dox-sales-booster' ); ?></button>
            <button type="button" class="dsb-tab" data-tab="stock"><?php echo dsb_icon_bolt(); ?> <?php esc_html_e( 'Stock bajo', 'dox-sales-booster' ); ?></button>
            <button type="button" class="dsb-tab" data-tab="shipbar"><?php echo dsb_icon_truck(); ?> <?php esc_html_e( 'Envío gratis', 'dox-sales-booster' ); ?></button>
            <button type="button" class="dsb-tab" data-tab="popup"><?php echo dsb_icon_bag(); ?> <?php esc_html_e( 'Popup de compra', 'dox-sales-booster' ); ?></button>
            <button type="button" class="dsb-tab" data-tab="shortcodes"><?php echo dsb_icon_code(); ?> <?php esc_html_e( 'Shortcodes', 'dox-sales-booster' ); ?></button>
        </div>

        <form id="dsb-form">
        <input type="hidden" name="dsb[_nonce]" value="<?php echo esc_attr( $nonce ); ?>">

        <!-- PERSONAS VIENDO -->
        <div class="dsb-panel active" id="dsb-tab-viewing">
            <div class="dsb-panel-grid">
                <div class="dsb-card">
                    <div class="dsb-card-header">
                        <h2><?php echo dsb_icon_eye(); ?> <?php esc_html_e( 'Contador de personas viendo', 'dox-sales-booster' ); ?></h2>
                        <label class="dsb-toggle"><input type="checkbox" name="dsb[viewing_enabled]" value="1" <?php checked( $o['viewing_enabled'], 1 ); ?>><span class="dsb-toggle-slider"></span></label>
                    </div>
                    <p class="dsb-card-desc"><?php esc_html_e( 'Número aleatorio de personas viendo el producto, con variación gradual. Agrégalo con el widget de Elementor, el bloque de Gutenberg o el shortcode [dsb_viewing].', 'dox-sales-booster' ); ?></p>
                    <div class="dsb-field">
                        <label><?php esc_html_e( 'Texto', 'dox-sales-booster' ); ?></label>
                        <input type="text" name="dsb[viewing_text]" value="<?php echo esc_attr( $o['viewing_text'] ); ?>">
                    </div>
                    <div class="dsb-field-row">
                        <div class="dsb-field"><label><?php esc_html_e( 'Mínimo', 'dox-sales-booster' ); ?></label><input type="number" name="dsb[viewing_min]" value="<?php echo esc_attr( $o['viewing_min'] ); ?>" min="1" max="50"><span class="dsb-hint"><?php esc_html_e( 'personas', 'dox-sales-booster' ); ?></span></div>
                        <div class="dsb-field"><label><?php esc_html_e( 'Máximo', 'dox-sales-booster' ); ?></label><input type="number" name="dsb[viewing_max]" value="<?php echo esc_attr( $o['viewing_max'] ); ?>" min="1" max="200"><span class="dsb-hint"><?php esc_html_e( 'personas', 'dox-sales-booster' ); ?></span></div>
                        <div class="dsb-field"><label><?php esc_html_e( 'Refrescar cada', 'dox-sales-booster' ); ?></label><input type="number" name="dsb[viewing_interval]" value="<?php echo esc_attr( $o['viewing_interval'] ); ?>" min="1" max="60"><span class="dsb-hint"><?php esc_html_e( 'minutos', 'dox-sales-booster' ); ?></span></div>
                    </div>
                </div>
                <div class="dsb-card dsb-preview-card">
                    <h3><?php esc_html_e( 'Vista previa', 'dox-sales-booster' ); ?></h3>
                    <div class="dsb-preview-box">
                        <div class="dsb-preview-product">
                            <div class="dsb-preview-img"></div>
                            <div class="dsb-preview-info">
                                <div class="dsb-preview-title-bar"></div>
                                <div class="dsb-preview-price-bar"></div>
                                <div class="dsb-preview-viewing">👁️ <strong style="color:#ff8d27"><?php echo wp_rand( (int) $o['viewing_min'], max( (int) $o['viewing_min'], (int) $o['viewing_max'] ) ); ?></strong> <?php echo esc_html( $o['viewing_text'] ); ?></div>
                            </div>
                        </div>
                    </div>
                    <p class="dsb-shortcode-box"><?php esc_html_e( 'Shortcode', 'dox-sales-booster' ); ?>: <code>[dsb_viewing]</code></p>
                </div>
            </div>
        </div>

        <!-- VENTAS RECIENTES -->
        <div class="dsb-panel" id="dsb-tab-sales">
            <div class="dsb-panel-grid">
                <div class="dsb-card">
                    <div class="dsb-card-header">
                        <h2><?php echo dsb_icon_fire(); ?> <?php esc_html_e( 'Texto de ventas recientes', 'dox-sales-booster' ); ?></h2>
                        <label class="dsb-toggle"><input type="checkbox" name="dsb[fakesales_enabled]" value="1" <?php checked( $o['fakesales_enabled'], 1 ); ?>><span class="dsb-toggle-slider"></span></label>
                    </div>
                    <p class="dsb-card-desc"><?php esc_html_e( 'Muestra las unidades vendidas en un período. Agrégalo con el widget de Elementor, el bloque de Gutenberg o el shortcode [dsb_sales].', 'dox-sales-booster' ); ?></p>
                    <div class="dsb-field">
                        <label><?php esc_html_e( 'Texto', 'dox-sales-booster' ); ?></label>
                        <input type="text" name="dsb[fakesales_text]" value="<?php echo esc_attr( $o['fakesales_text'] ); ?>">
                        <span class="dsb-hint"><?php esc_html_e( 'Variables:', 'dox-sales-booster' ); ?> <code>{count}</code> <code>{timeframe}</code> <code>{period}</code></span>
                    </div>
                    <div class="dsb-field">
                        <label><?php esc_html_e( 'Modo de datos', 'dox-sales-booster' ); ?></label>
                        <select name="dsb[fakesales_data_mode]">
                            <option value="simulated" <?php selected( $o['fakesales_data_mode'], 'simulated' ); ?>><?php esc_html_e( 'Simulado (número entre el mínimo y el máximo)', 'dox-sales-booster' ); ?></option>
                            <option value="real" <?php selected( $o['fakesales_data_mode'], 'real' ); ?>><?php esc_html_e( 'Real (unidades vendidas de verdad)', 'dox-sales-booster' ); ?></option>
                        </select>
                        <span class="dsb-hint"><?php esc_html_e( 'Simulado: el número se mantiene fijo durante todo el período (ya no cambia al recargar la página) y es el mismo para todos los visitantes. Real: cuenta las unidades vendidas del producto en pedidos completados o en proceso dentro del período; si no hubo ninguna venta, el texto no se muestra.', 'dox-sales-booster' ); ?></span>
                    </div>

                    <div class="dsb-sales-sim-only">
                        <div class="dsb-field-row">
                            <div class="dsb-field"><label><?php esc_html_e( 'Mínimo', 'dox-sales-booster' ); ?></label><input type="number" name="dsb[fakesales_min]" value="<?php echo esc_attr( $o['fakesales_min'] ); ?>" min="1" max="50"></div>
                            <div class="dsb-field"><label><?php esc_html_e( 'Máximo', 'dox-sales-booster' ); ?></label><input type="number" name="dsb[fakesales_max]" value="<?php echo esc_attr( $o['fakesales_max'] ); ?>" min="1" max="200"></div>
                        </div>
                    </div>
                    <div class="dsb-field-row">
                        <div class="dsb-field"><label><?php esc_html_e( 'Cantidad', 'dox-sales-booster' ); ?></label><input type="number" name="dsb[fakesales_timeframe]" value="<?php echo esc_attr( $o['fakesales_timeframe'] ); ?>" min="1" max="999"></div>
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Período', 'dox-sales-booster' ); ?></label>
                            <select name="dsb[fakesales_period]">
                                <?php foreach ( [ 'minutos' => __( 'Minutos', 'dox-sales-booster' ), 'horas' => __( 'Horas', 'dox-sales-booster' ), 'días' => __( 'Días', 'dox-sales-booster' ), 'semanas' => __( 'Semanas', 'dox-sales-booster' ) ] as $v => $l ) : ?>
                                <option value="<?php echo esc_attr( $v ); ?>" <?php selected( $o['fakesales_period'], $v ); ?>><?php echo esc_html( $l ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="dsb-info-box"><?php esc_html_e( 'El color del número {count} se controla desde Elementor → widget "🔥 Ventas recientes" → pestaña Estilo.', 'dox-sales-booster' ); ?></div>
                </div>
                <div class="dsb-card dsb-preview-card">
                    <h3><?php esc_html_e( 'Vista previa', 'dox-sales-booster' ); ?></h3>
                    <div class="dsb-preview-box">
                        <div class="dsb-preview-product">
                            <div class="dsb-preview-img"></div>
                            <div class="dsb-preview-info">
                                <div class="dsb-preview-title-bar"></div>
                                <div class="dsb-preview-price-bar"></div>
                                <div class="dsb-preview-sales"><?php
                                    $cnt          = wp_rand( (int) $o['fakesales_min'], max( (int) $o['fakesales_min'], (int) $o['fakesales_max'] ) );
                                    $preview_text = esc_html( $o['fakesales_text'] );
                                    $preview_text = str_replace(
                                        [ '{count}', '{timeframe}', '{period}' ],
                                        [ '<strong class="dsb-sales-count">' . $cnt . '</strong>', esc_html( $o['fakesales_timeframe'] ), esc_html( $o['fakesales_period'] ) ],
                                        $preview_text
                                    );
                                    echo wp_kses( $preview_text, [ 'strong' => [ 'class' => [] ] ] );
                                ?></div>
                            </div>
                        </div>
                    </div>
                    <p class="dsb-shortcode-box"><?php esc_html_e( 'Shortcode', 'dox-sales-booster' ); ?>: <code>[dsb_sales]</code></p>
                </div>
            </div>
        </div>

        <!-- STOCK BAJO -->
        <div class="dsb-panel" id="dsb-tab-stock">
            <div class="dsb-panel-grid">
                <div class="dsb-card">
                    <div class="dsb-card-header">
                        <h2><?php echo dsb_icon_bolt(); ?> <?php esc_html_e( 'Aviso de stock bajo (datos reales)', 'dox-sales-booster' ); ?></h2>
                        <label class="dsb-toggle"><input type="checkbox" name="dsb[stock_enabled]" value="1" <?php checked( $o['stock_enabled'], 1 ); ?>><span class="dsb-toggle-slider"></span></label>
                    </div>
                    <p class="dsb-card-desc"><?php esc_html_e( 'Urgencia con el inventario REAL de WooCommerce: solo aparece si el producto gestiona stock y quedan pocas unidades. Agrégalo con el widget de Elementor, el bloque de Gutenberg o el shortcode [dsb_stock].', 'dox-sales-booster' ); ?></p>
                    <div class="dsb-field">
                        <label><?php esc_html_e( 'Texto', 'dox-sales-booster' ); ?></label>
                        <input type="text" name="dsb[stock_text]" value="<?php echo esc_attr( $o['stock_text'] ); ?>">
                        <span class="dsb-hint"><?php esc_html_e( 'Variable:', 'dox-sales-booster' ); ?> <code>{stock}</code></span>
                    </div>
                    <div class="dsb-field">
                        <label><?php esc_html_e( 'Umbral de unidades', 'dox-sales-booster' ); ?></label>
                        <input type="number" name="dsb[stock_threshold]" value="<?php echo esc_attr( $o['stock_threshold'] ); ?>" min="1" max="999">
                        <span class="dsb-hint"><?php esc_html_e( 'Se muestra cuando el stock real es menor o igual a este número.', 'dox-sales-booster' ); ?></span>
                    </div>
                    <div class="dsb-info-box"><?php esc_html_e( 'A diferencia de los otros elementos, este usa datos 100% reales: si el producto no gestiona inventario o tiene stock de sobra, no se muestra nada.', 'dox-sales-booster' ); ?></div>
                </div>
                <div class="dsb-card dsb-preview-card">
                    <h3><?php esc_html_e( 'Vista previa', 'dox-sales-booster' ); ?></h3>
                    <div class="dsb-preview-box">
                        <div class="dsb-preview-product">
                            <div class="dsb-preview-img"></div>
                            <div class="dsb-preview-info">
                                <div class="dsb-preview-title-bar"></div>
                                <div class="dsb-preview-price-bar"></div>
                                <div class="dsb-preview-sales" id="dsb-stock-preview-text"><?php
                                    $stock_prev = esc_html( $o['stock_text'] );
                                    $stock_prev = str_replace( '{stock}', '<strong class="dsb-stock-count" style="color:#b3261e">3</strong>', $stock_prev );
                                    echo wp_kses( $stock_prev, [ 'strong' => [ 'class' => [], 'style' => [] ] ] );
                                ?></div>
                            </div>
                        </div>
                    </div>
                    <p class="dsb-shortcode-box"><?php esc_html_e( 'Shortcode', 'dox-sales-booster' ); ?>: <code>[dsb_stock]</code></p>
                </div>
            </div>
        </div>

        <!-- BARRA DE ENVÍO GRATIS -->
        <div class="dsb-panel" id="dsb-tab-shipbar">
            <div class="dsb-panel-grid">
                <div class="dsb-card">
                    <div class="dsb-card-header">
                        <h2><?php echo dsb_icon_truck(); ?> <?php esc_html_e( 'Barra de envío gratis', 'dox-sales-booster' ); ?></h2>
                        <label class="dsb-toggle"><input type="checkbox" name="dsb[shipbar_enabled]" value="1" <?php checked( $o['shipbar_enabled'], 1 ); ?>><span class="dsb-toggle-slider"></span></label>
                    </div>
                    <p class="dsb-card-desc"><?php esc_html_e( 'Barra de progreso con lo que le falta al cliente para obtener envío gratis. Usa el carrito real y se actualiza sola (sin recargar) al agregar o quitar productos. Compatible con el mini carrito estándar de WooCommerce, incluido el offcanvas de UICore Pro.', 'dox-sales-booster' ); ?></p>

                    <h4 class="dsb-subsection"><?php esc_html_e( 'Dónde se muestra', 'dox-sales-booster' ); ?></h4>

                    <div class="dsb-switches-row">
                        <label class="dsb-switch-label">
                            <span class="dsb-toggle dsb-toggle-sm"><input type="checkbox" name="dsb[shipbar_minicart]" value="1" <?php checked( $o['shipbar_minicart'], 1 ); ?>><span class="dsb-toggle-slider"></span></span>
                            <?php esc_html_e( 'Mini carrito (offcanvas / widget)', 'dox-sales-booster' ); ?>
                        </label>
                        <label class="dsb-switch-label">
                            <span class="dsb-toggle dsb-toggle-sm"><input type="checkbox" name="dsb[shipbar_cart]" value="1" <?php checked( $o['shipbar_cart'], 1 ); ?>><span class="dsb-toggle-slider"></span></span>
                            <?php esc_html_e( 'Página del carrito', 'dox-sales-booster' ); ?>
                        </label>
                        <label class="dsb-switch-label">
                            <span class="dsb-toggle dsb-toggle-sm"><input type="checkbox" name="dsb[shipbar_checkout]" value="1" <?php checked( $o['shipbar_checkout'], 1 ); ?>><span class="dsb-toggle-slider"></span></span>
                            <?php esc_html_e( 'Página de pago (checkout)', 'dox-sales-booster' ); ?>
                        </label>
                    </div>

                    <h4 class="dsb-subsection"><?php esc_html_e( 'Monto para envío gratis', 'dox-sales-booster' ); ?></h4>

                    <div class="dsb-field">
                        <label><?php esc_html_e( 'Fuente del monto', 'dox-sales-booster' ); ?></label>
                        <select name="dsb[shipbar_source]">
                            <option value="custom" <?php selected( $o['shipbar_source'], 'custom' ); ?>><?php esc_html_e( 'Monto propio (configurado aquí)', 'dox-sales-booster' ); ?></option>
                            <option value="woocommerce" <?php selected( $o['shipbar_source'], 'woocommerce' ); ?>><?php esc_html_e( 'Método "Envío gratuito" de WooCommerce (pedido mínimo)', 'dox-sales-booster' ); ?></option>
                        </select>
                        <span class="dsb-hint"><?php esc_html_e( 'Con la fuente WooCommerce, el monto se lee del pedido mínimo del método "Envío gratuito" de la zona de envío del cliente. Si esa zona no tiene monto mínimo configurado, se usa el monto propio como respaldo.', 'dox-sales-booster' ); ?></span>
                    </div>

                    <div class="dsb-shipbar-custom-only">
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Monto propio', 'dox-sales-booster' ); ?></label>
                            <input type="number" name="dsb[shipbar_threshold]" value="<?php echo esc_attr( $o['shipbar_threshold'] ); ?>" min="0" max="999999999">
                            <span class="dsb-hint"><?php esc_html_e( 'Solo números, sin símbolo de moneda. Ej: 349000', 'dox-sales-booster' ); ?></span>
                        </div>
                    </div>

                    <div class="dsb-switches-row">
                        <label class="dsb-switch-label">
                            <span class="dsb-toggle dsb-toggle-sm"><input type="checkbox" name="dsb[shipbar_ignore_coupons]" value="1" <?php checked( $o['shipbar_ignore_coupons'], 1 ); ?>><span class="dsb-toggle-slider"></span></span>
                            <?php esc_html_e( 'Ignorar cupones (contar el subtotal sin descuentos)', 'dox-sales-booster' ); ?>
                        </label>
                    </div>

                    <h4 class="dsb-subsection"><?php esc_html_e( 'Textos', 'dox-sales-booster' ); ?></h4>

                    <div class="dsb-field">
                        <label><?php esc_html_e( 'Texto de progreso', 'dox-sales-booster' ); ?></label>
                        <textarea name="dsb[shipbar_text]" rows="2"><?php echo esc_textarea( $o['shipbar_text'] ); ?></textarea>
                        <span class="dsb-hint"><?php esc_html_e( 'Variable:', 'dox-sales-booster' ); ?> <code>{precio}</code> — <?php esc_html_e( 'lo que falta para el envío gratis.', 'dox-sales-booster' ); ?></span>
                    </div>

                    <div class="dsb-field">
                        <label><?php esc_html_e( 'Texto de éxito', 'dox-sales-booster' ); ?></label>
                        <textarea name="dsb[shipbar_success_text]" rows="2"><?php echo esc_textarea( $o['shipbar_success_text'] ); ?></textarea>
                        <span class="dsb-hint"><?php esc_html_e( 'Se muestra cuando el carrito ya alcanzó el monto.', 'dox-sales-booster' ); ?></span>
                    </div>

                    <h4 class="dsb-subsection"><?php esc_html_e( 'Colores', 'dox-sales-booster' ); ?></h4>

                    <div class="dsb-field-row">
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Barra de progreso', 'dox-sales-booster' ); ?></label>
                            <input type="color" name="dsb[shipbar_bar_color]" value="<?php echo esc_attr( $o['shipbar_bar_color'] ); ?>" class="dsb-color-input">
                        </div>
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Fondo de la barra', 'dox-sales-booster' ); ?></label>
                            <input type="color" name="dsb[shipbar_track_color]" value="<?php echo esc_attr( $o['shipbar_track_color'] ); ?>" class="dsb-color-input">
                        </div>
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Texto', 'dox-sales-booster' ); ?></label>
                            <input type="color" name="dsb[shipbar_text_color]" value="<?php echo esc_attr( $o['shipbar_text_color'] ); ?>" class="dsb-color-input">
                        </div>
                    </div>
                </div>

                <div class="dsb-card dsb-preview-card">
                    <h3><?php esc_html_e( 'Vista previa', 'dox-sales-booster' ); ?></h3>
                    <div class="dsb-preview-box">
                        <div id="dsb-shipbar-preview" style="--dsb-prev-fill:<?php echo esc_attr( $o['shipbar_bar_color'] ); ?>;--dsb-prev-track:<?php echo esc_attr( $o['shipbar_track_color'] ); ?>;--dsb-prev-text:<?php echo esc_attr( $o['shipbar_text_color'] ); ?>;text-align:center;">
                            <p id="dsb-shipbar-preview-msg" style="font-size:13px;margin:0 0 8px;color:var(--dsb-prev-text);"></p>
                            <div style="height:10px;border-radius:99px;background:var(--dsb-prev-track);overflow:hidden;">
                                <div id="dsb-shipbar-preview-fill" style="height:100%;border-radius:99px;width:65%;background-color:var(--dsb-prev-fill);transition:width .4s ease;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="dsb-field">
                        <label><?php esc_html_e( 'Simular progreso del carrito', 'dox-sales-booster' ); ?> — <strong><span id="dsb-shipbar-demo-val">65</span>%</strong></label>
                        <input type="range" id="dsb-shipbar-demo" value="65" min="0" max="100" step="1" class="dsb-slider">
                        <span class="dsb-hint"><?php esc_html_e( 'Solo para la vista previa: al llegar a 100% se muestra el texto de éxito.', 'dox-sales-booster' ); ?></span>
                    </div>
                    <p class="dsb-shortcode-box"><?php esc_html_e( 'Shortcode', 'dox-sales-booster' ); ?>: <code>[dsb_envio_gratis]</code></p>
                    <div class="dsb-info-box"><?php esc_html_e( 'Con las ubicaciones activadas arriba, la barra se inserta sola: no necesitas shortcode ni widget. El shortcode y el widget de Elementor son para colocarla en lugares adicionales.', 'dox-sales-booster' ); ?></div>
                </div>
            </div>
        </div>

        <!-- POPUP -->
        <div class="dsb-panel" id="dsb-tab-popup">
            <div class="dsb-panel-grid">
                <div class="dsb-card">
                    <div class="dsb-card-header">
                        <h2><?php echo dsb_icon_bag(); ?> <?php esc_html_e( 'Popup de compra reciente', 'dox-sales-booster' ); ?></h2>
                        <label class="dsb-toggle"><input type="checkbox" name="dsb[popup_enabled]" value="1" <?php checked( $o['popup_enabled'], 1 ); ?>><span class="dsb-toggle-slider"></span></label>
                    </div>
                    <p class="dsb-card-desc"><?php esc_html_e( 'Aparece automáticamente en todo el sitio. No necesita shortcode.', 'dox-sales-booster' ); ?></p>

                    <h4 class="dsb-subsection"><?php esc_html_e( 'Comportamiento', 'dox-sales-booster' ); ?></h4>

                    <div class="dsb-field-row">
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Mostrar cada', 'dox-sales-booster' ); ?></label>
                            <input type="number" name="dsb[popup_interval]" value="<?php echo esc_attr( $o['popup_interval'] ); ?>" min="10" max="300">
                            <span class="dsb-hint"><?php esc_html_e( 'segundos', 'dox-sales-booster' ); ?></span>
                        </div>
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Animación', 'dox-sales-booster' ); ?></label>
                            <select name="dsb[popup_animation]">
                                <option value="slide_up" <?php selected( $o['popup_animation'], 'slide_up' ); ?>><?php esc_html_e( 'Deslizar arriba', 'dox-sales-booster' ); ?></option>
                                <option value="slide_right" <?php selected( $o['popup_animation'], 'slide_right' ); ?>><?php esc_html_e( 'Deslizar lateral', 'dox-sales-booster' ); ?></option>
                            </select>
                        </div>
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Posición', 'dox-sales-booster' ); ?></label>
                            <select name="dsb[popup_position]">
                                <option value="left" <?php selected( $o['popup_position'], 'left' ); ?>><?php esc_html_e( 'Abajo izquierda', 'dox-sales-booster' ); ?></option>
                                <option value="right" <?php selected( $o['popup_position'], 'right' ); ?>><?php esc_html_e( 'Abajo derecha', 'dox-sales-booster' ); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="dsb-field">
                        <label><?php esc_html_e( 'Duración del popup', 'dox-sales-booster' ); ?> — <strong><span id="dsb-display-secs-val"><?php echo $disp_secs; ?></span> seg</strong></label>
                        <input type="range" id="dsb-display-secs-slider" name="dsb[popup_display_seconds]"
                               value="<?php echo esc_attr( $disp_secs ); ?>" min="3" max="30" step="1" class="dsb-slider">
                        <span class="dsb-hint"><?php esc_html_e( 'Cuántos segundos permanece visible antes de desaparecer. Si supera el intervalo, se ajusta automáticamente.', 'dox-sales-booster' ); ?></span>
                    </div>

                    <div class="dsb-field-row">
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Primer popup: espera mínima', 'dox-sales-booster' ); ?></label>
                            <input type="number" name="dsb[popup_first_delay_min]" value="<?php echo esc_attr( $o['popup_first_delay_min'] ); ?>" min="0" max="120">
                            <span class="dsb-hint"><?php esc_html_e( 'segundos', 'dox-sales-booster' ); ?></span>
                        </div>
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Primer popup: espera máxima', 'dox-sales-booster' ); ?></label>
                            <input type="number" name="dsb[popup_first_delay_max]" value="<?php echo esc_attr( $o['popup_first_delay_max'] ); ?>" min="1" max="180">
                            <span class="dsb-hint"><?php esc_html_e( 'segundos', 'dox-sales-booster' ); ?></span>
                        </div>
                    </div>

                    <div class="dsb-field-row">
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Silencio tras cerrar', 'dox-sales-booster' ); ?></label>
                            <input type="number" name="dsb[popup_close_silence]" value="<?php echo esc_attr( $o['popup_close_silence'] ); ?>" min="0" max="1440">
                            <span class="dsb-hint"><?php esc_html_e( 'minutos sin popups si el visitante lo cierra (0 = desactivado)', 'dox-sales-booster' ); ?></span>
                        </div>
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Máximo por página', 'dox-sales-booster' ); ?></label>
                            <input type="number" name="dsb[popup_max_per_page]" value="<?php echo esc_attr( $o['popup_max_per_page'] ); ?>" min="0" max="50">
                            <span class="dsb-hint"><?php esc_html_e( 'popups por carga de página (0 = sin límite)', 'dox-sales-booster' ); ?></span>
                        </div>
                    </div>

                    <div class="dsb-switches-row">
                        <label class="dsb-switch-label">
                            <span class="dsb-toggle dsb-toggle-sm"><input type="checkbox" name="dsb[popup_show_mobile]" value="1" <?php checked( $o['popup_show_mobile'], 1 ); ?>><span class="dsb-toggle-slider"></span></span>
                            <?php esc_html_e( 'Mostrar en móvil', 'dox-sales-booster' ); ?>
                        </label>
                        <label class="dsb-switch-label">
                            <span class="dsb-toggle dsb-toggle-sm"><input type="checkbox" name="dsb[popup_exclude_checkout]" value="1" <?php checked( $o['popup_exclude_checkout'], 1 ); ?>><span class="dsb-toggle-slider"></span></span>
                            <?php esc_html_e( 'Ocultar en carrito y checkout (recomendado: no distrae durante el pago)', 'dox-sales-booster' ); ?>
                        </label>
                    </div>

                    <h4 class="dsb-subsection"><?php esc_html_e( 'Datos', 'dox-sales-booster' ); ?></h4>

                    <div class="dsb-field">
                        <label><?php esc_html_e( 'Modo de datos', 'dox-sales-booster' ); ?></label>
                        <select name="dsb[popup_data_mode]">
                            <option value="simulated" <?php selected( $o['popup_data_mode'], 'simulated' ); ?>><?php esc_html_e( 'Simulado (productos del catálogo)', 'dox-sales-booster' ); ?></option>
                            <option value="real" <?php selected( $o['popup_data_mode'], 'real' ); ?>><?php esc_html_e( 'Real (pedidos de los últimos 30 días)', 'dox-sales-booster' ); ?></option>
                        </select>
                        <span class="dsb-hint"><?php esc_html_e( 'Modo real: producto, ciudad y tiempo salen de pedidos reales (completados/en proceso). Nunca se muestran nombres ni datos del cliente. Si no hay pedidos recientes, cae al modo simulado.', 'dox-sales-booster' ); ?></span>
                    </div>

                    <div class="dsb-sim-only">
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Fuente de productos', 'dox-sales-booster' ); ?></label>
                            <select name="dsb[popup_products_type]">
                                <option value="random"      <?php selected( $o['popup_products_type'], 'random' ); ?>><?php esc_html_e( 'Aleatorio', 'dox-sales-booster' ); ?></option>
                                <option value="featured"    <?php selected( $o['popup_products_type'], 'featured' ); ?>><?php esc_html_e( 'Destacados', 'dox-sales-booster' ); ?></option>
                                <option value="sale"        <?php selected( $o['popup_products_type'], 'sale' ); ?>><?php esc_html_e( 'En oferta', 'dox-sales-booster' ); ?></option>
                                <option value="bestsellers" <?php selected( $o['popup_products_type'], 'bestsellers' ); ?>><?php esc_html_e( 'Más vendidos', 'dox-sales-booster' ); ?></option>
                            </select>
                        </div>

                        <?php if ( $cats ) : ?>
                        <div class="dsb-field-row">
                            <div class="dsb-field">
                                <label><?php esc_html_e( 'Incluir solo estas categorías', 'dox-sales-booster' ); ?></label>
                                <select name="dsb[popup_cats_include][]" multiple size="5" class="dsb-multiselect">
                                    <?php foreach ( $cats as $t ) : ?>
                                    <option value="<?php echo (int) $t->term_id; ?>" <?php selected( in_array( (int) $t->term_id, $cats_incl, true ) ); ?>><?php echo esc_html( $t->name ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="dsb-hint"><?php esc_html_e( 'Vacío = todas. Ctrl/Cmd + clic para varias.', 'dox-sales-booster' ); ?></span>
                            </div>
                            <div class="dsb-field">
                                <label><?php esc_html_e( 'Excluir estas categorías', 'dox-sales-booster' ); ?></label>
                                <select name="dsb[popup_cats_exclude][]" multiple size="5" class="dsb-multiselect">
                                    <?php foreach ( $cats as $t ) : ?>
                                    <option value="<?php echo (int) $t->term_id; ?>" <?php selected( in_array( (int) $t->term_id, $cats_excl, true ) ); ?>><?php echo esc_html( $t->name ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="dsb-hint"><?php esc_html_e( 'Ctrl/Cmd + clic para quitar la selección.', 'dox-sales-booster' ); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="dsb-field-row">
                            <div class="dsb-field">
                                <label><?php esc_html_e( '"Hace X minutos": mínimo', 'dox-sales-booster' ); ?></label>
                                <input type="number" name="dsb[popup_ago_min]" value="<?php echo esc_attr( $o['popup_ago_min'] ); ?>" min="1" max="999">
                            </div>
                            <div class="dsb-field">
                                <label><?php esc_html_e( '"Hace X minutos": máximo', 'dox-sales-booster' ); ?></label>
                                <input type="number" name="dsb[popup_ago_max]" value="<?php echo esc_attr( $o['popup_ago_max'] ); ?>" min="1" max="999">
                            </div>
                        </div>

                        <div class="dsb-switches-row">
                            <label class="dsb-switch-label">
                                <span class="dsb-toggle dsb-toggle-sm"><input type="checkbox" name="dsb[popup_hide_outofstock]" value="1" <?php checked( $o['popup_hide_outofstock'], 1 ); ?>><span class="dsb-toggle-slider"></span></span>
                                <?php esc_html_e( 'Ocultar productos sin stock', 'dox-sales-booster' ); ?>
                            </label>
                        </div>
                    </div><!-- /.dsb-sim-only -->

                    <div class="dsb-switches-row">
                        <label class="dsb-switch-label">
                            <span class="dsb-toggle dsb-toggle-sm"><input type="checkbox" name="dsb[popup_show_price]" value="1" <?php checked( $o['popup_show_price'], 1 ); ?>><span class="dsb-toggle-slider"></span></span>
                            <?php esc_html_e( 'Mostrar el precio del producto', 'dox-sales-booster' ); ?>
                        </label>
                    </div>

                    <div class="dsb-warn-box" id="dsb-source-warning" <?php echo 0 === $feed_count ? '' : 'style="display:none"'; ?>>
                        ⚠️ <?php esc_html_e( 'La fuente seleccionada no tiene productos ahora mismo: el popup no se mostrará. Revisa la fuente, las categorías o el modo de datos.', 'dox-sales-booster' ); ?>
                    </div>

                    <h4 class="dsb-subsection"><?php esc_html_e( 'Contenido', 'dox-sales-booster' ); ?></h4>

                    <div class="dsb-field">
                        <label><?php esc_html_e( 'Texto prefijo del título', 'dox-sales-booster' ); ?></label>
                        <input type="text" name="dsb[popup_prefix_text]" value="<?php echo esc_attr( $o['popup_prefix_text'] ); ?>" placeholder="🛍️ Alguien ha comprado">
                        <span class="dsb-hint"><?php esc_html_e( 'Acepta la variable {name}: se sustituye por un nombre aleatorio de la lista de abajo (o "Alguien" si está vacía). Ej: "🛍️ {name} ha comprado"', 'dox-sales-booster' ); ?></span>
                    </div>

                    <div class="dsb-field">
                        <label><?php esc_html_e( 'Nombres de compradores (opcional)', 'dox-sales-booster' ); ?></label>
                        <textarea name="dsb[popup_names]" rows="3" placeholder="María&#10;Camila&#10;Andrés&#10;Juan Pablo"><?php echo esc_textarea( $o['popup_names'] ); ?></textarea>
                        <span class="dsb-hint"><?php esc_html_e( 'Uno por línea. Solo se usan si el prefijo contiene {name}.', 'dox-sales-booster' ); ?></span>
                    </div>

                    <div class="dsb-field">
                        <label><?php esc_html_e( 'Texto del enlace', 'dox-sales-booster' ); ?></label>
                        <input type="text" name="dsb[popup_link_text]" value="<?php echo esc_attr( $o['popup_link_text'] ); ?>" placeholder="Ver producto">
                    </div>

                    <div class="dsb-field">
                        <label><?php esc_html_e( 'Caracteres máximos del título', 'dox-sales-booster' ); ?> — <strong><span id="dsb-maxchars-val"><?php echo $maxchars; ?></span></strong></label>
                        <input type="range" id="dsb-maxchars-slider" name="dsb[popup_title_maxchars]"
                               value="<?php echo esc_attr( $maxchars ); ?>" min="20" max="100" step="1" class="dsb-slider">
                        <span class="dsb-hint"><?php esc_html_e( 'Si el título supera este número de caracteres, se corta con "…"', 'dox-sales-booster' ); ?></span>
                    </div>

                    <h4 class="dsb-subsection"><?php esc_html_e( 'Apariencia', 'dox-sales-booster' ); ?></h4>

                    <div class="dsb-field-row">
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Ancho del popup', 'dox-sales-booster' ); ?></label>
                            <input type="number" name="dsb[popup_width]" value="<?php echo esc_attr( $o['popup_width'] ); ?>" min="200" max="500">
                            <span class="dsb-hint">px</span>
                        </div>
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Tamaño de imagen', 'dox-sales-booster' ); ?></label>
                            <input type="number" name="dsb[popup_img_size]" value="<?php echo esc_attr( $o['popup_img_size'] ); ?>" min="40" max="120">
                            <span class="dsb-hint">px</span>
                        </div>
                    </div>

                    <h4 class="dsb-subsection"><?php esc_html_e( 'Tamaño del texto', 'dox-sales-booster' ); ?></h4>
                    <div class="dsb-field-row">
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Título', 'dox-sales-booster' ); ?></label>
                            <input type="number" name="dsb[popup_font_title]" value="<?php echo esc_attr( $font_title ); ?>" min="8" max="30">
                            <span class="dsb-hint">px</span>
                        </div>
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Precio', 'dox-sales-booster' ); ?></label>
                            <input type="number" name="dsb[popup_font_price]" value="<?php echo esc_attr( $font_price ); ?>" min="8" max="28">
                            <span class="dsb-hint">px</span>
                        </div>
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Meta (tiempo · ciudad)', 'dox-sales-booster' ); ?></label>
                            <input type="number" name="dsb[popup_font_meta]" value="<?php echo esc_attr( $font_meta ); ?>" min="8" max="24">
                            <span class="dsb-hint">px</span>
                        </div>
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Enlace', 'dox-sales-booster' ); ?></label>
                            <input type="number" name="dsb[popup_font_link]" value="<?php echo esc_attr( $font_link ); ?>" min="8" max="24">
                            <span class="dsb-hint">px</span>
                        </div>
                    </div>

                    <div class="dsb-field-row">
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Color de fondo', 'dox-sales-booster' ); ?></label>
                            <input type="color" name="dsb[popup_bg_color]" value="<?php echo esc_attr( $o['popup_bg_color'] ); ?>" class="dsb-color-input">
                        </div>
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Color del título', 'dox-sales-booster' ); ?></label>
                            <input type="color" name="dsb[popup_title_color]" value="<?php echo esc_attr( $o['popup_title_color'] ); ?>" class="dsb-color-input">
                        </div>
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Color del meta', 'dox-sales-booster' ); ?></label>
                            <input type="color" name="dsb[popup_meta_color]" value="<?php echo esc_attr( $o['popup_meta_color'] ); ?>" class="dsb-color-input">
                        </div>
                        <div class="dsb-field">
                            <label><?php esc_html_e( 'Color del enlace', 'dox-sales-booster' ); ?></label>
                            <input type="color" name="dsb[popup_link_color]" value="<?php echo esc_attr( $o['popup_link_color'] ); ?>" class="dsb-color-input">
                        </div>
                    </div>

                    <h4 class="dsb-subsection"><?php esc_html_e( 'Ubicaciones', 'dox-sales-booster' ); ?></h4>

                    <div class="dsb-field">
                        <label><?php esc_html_e( 'Ciudades', 'dox-sales-booster' ); ?></label>
                        <textarea name="dsb[popup_locations]" rows="8"><?php echo esc_textarea( $loc_display ); ?></textarea>
                        <span class="dsb-hint"><?php esc_html_e( 'Una por línea. En modo simulado se usan siempre; en modo real, solo cuando el pedido no tiene ciudad.', 'dox-sales-booster' ); ?></span>
                    </div>
                </div>

                <div class="dsb-card dsb-preview-card">
                    <h3><?php esc_html_e( 'Vista previa del popup', 'dox-sales-booster' ); ?></h3>
                    <p class="dsb-preview-popup-scale-note" id="dsb-popup-width-note"><?php printf( esc_html__( 'Ancho: %dpx', 'dox-sales-booster' ), (int) $o['popup_width'] ); ?></p>
                    <div class="dsb-preview-popup-wrap">
                        <div class="dsb-preview-popup" id="dsb-popup-preview" style="width:<?php echo (int) $o['popup_width']; ?>px;background:<?php echo esc_attr( $o['popup_bg_color'] ); ?>;">
                            <button type="button" class="dsb-preview-popup-close" onclick="return false;">✕</button>
                            <div style="display:flex;align-items:center;gap:15px;">
                                <div class="dsb-preview-popup-img" id="dsb-prev-img" style="width:<?php echo (int) $o['popup_img_size']; ?>px;height:<?php echo (int) $o['popup_img_size']; ?>px;flex-shrink:0;"></div>
                                <div style="min-width:0;flex:1;overflow:hidden;">
                                    <p class="dsb-prev-title" style="font-weight:500;margin:0 0 4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:<?php echo $font_title; ?>px;color:<?php echo esc_attr( $o['popup_title_color'] ); ?>;"><?php echo esc_html( $o['popup_prefix_text'] ); ?> <span style="font-weight:600;text-decoration:underline;"><?php esc_html_e( 'Suéter tejido lila', 'dox-sales-booster' ); ?></span></p>
                                    <p class="dsb-prev-price" id="dsb-prev-price" style="margin:0 0 2px;font-weight:600;font-size:<?php echo $font_price; ?>px;color:<?php echo esc_attr( $o['popup_title_color'] ); ?>;<?php echo empty( $o['popup_show_price'] ) ? 'display:none;' : ''; ?>">$ 89.900</p>
                                    <p class="dsb-prev-meta" style="margin:0 0 6px;font-size:<?php echo $font_meta; ?>px;color:<?php echo esc_attr( $o['popup_meta_color'] ); ?>;"><?php esc_html_e( 'hace 5 minutos · Bogotá, D.C. 🇨🇴', 'dox-sales-booster' ); ?></p>
                                    <a href="#" class="dsb-prev-link" onclick="return false;" style="font-size:<?php echo $font_link; ?>px;font-weight:500;color:<?php echo esc_attr( $o['popup_link_color'] ); ?>;text-decoration:none;"><?php echo esc_html( $o['popup_link_text'] ); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="dsb-info-box"><?php esc_html_e( 'Los cambios actualizan la vista previa en tiempo real.', 'dox-sales-booster' ); ?></div>
                </div>
            </div>
        </div>

        <!-- SHORTCODES -->
        <div class="dsb-panel" id="dsb-tab-shortcodes">
            <div class="dsb-sc-grid">
                <div class="dsb-card">
                    <h2><?php echo dsb_icon_eye(); ?> <code>[dsb_viewing]</code></h2>
                    <p><?php esc_html_e( 'Muestra el contador de personas viendo. Colócalo donde quieras en la página de producto.', 'dox-sales-booster' ); ?></p>
                    <div class="dsb-sc-examples">
                        <p><strong><?php esc_html_e( 'Básico:', 'dox-sales-booster' ); ?></strong></p>
                        <code>[dsb_viewing]</code>
                        <p><strong><?php esc_html_e( 'Personalizado:', 'dox-sales-booster' ); ?></strong></p>
                        <code>[dsb_viewing min="5" max="20" text="personas mirando esto."]</code>
                    </div>
                    <div class="dsb-info-box"><?php esc_html_e( 'También disponible como widget de Elementor y bloque de Gutenberg.', 'dox-sales-booster' ); ?></div>
                </div>
                <div class="dsb-card">
                    <h2><?php echo dsb_icon_fire(); ?> <code>[dsb_sales]</code></h2>
                    <p><?php esc_html_e( 'Muestra el texto de ventas recientes. Colócalo donde quieras en la página de producto.', 'dox-sales-booster' ); ?></p>
                    <div class="dsb-sc-examples">
                        <p><strong><?php esc_html_e( 'Básico:', 'dox-sales-booster' ); ?></strong></p>
                        <code>[dsb_sales]</code>
                        <p><strong><?php esc_html_e( 'Personalizado:', 'dox-sales-booster' ); ?></strong></p>
                        <code>[dsb_sales min="4" max="18" timeframe="24" period="horas"]</code>
                    </div>
                    <div class="dsb-info-box"><?php esc_html_e( 'También disponible como widget de Elementor y bloque de Gutenberg.', 'dox-sales-booster' ); ?></div>
                </div>
                <div class="dsb-card">
                    <h2><?php echo dsb_icon_bolt(); ?> <code>[dsb_stock]</code></h2>
                    <p><?php esc_html_e( 'Aviso de stock bajo con inventario real. Solo se muestra si quedan pocas unidades.', 'dox-sales-booster' ); ?></p>
                    <div class="dsb-sc-examples">
                        <p><strong><?php esc_html_e( 'Básico (producto actual):', 'dox-sales-booster' ); ?></strong></p>
                        <code>[dsb_stock]</code>
                        <p><strong><?php esc_html_e( 'Personalizado:', 'dox-sales-booster' ); ?></strong></p>
                        <code>[dsb_stock threshold="5" text="⚡ ¡Últimas {stock} unidades!" product_id="123"]</code>
                    </div>
                    <div class="dsb-info-box"><?php esc_html_e( 'También disponible como widget de Elementor y bloque de Gutenberg.', 'dox-sales-booster' ); ?></div>
                </div>
                <div class="dsb-card">
                    <h2><?php echo dsb_icon_truck(); ?> <code>[dsb_envio_gratis]</code></h2>
                    <p><?php esc_html_e( 'Barra de progreso de envío gratis. Con las ubicaciones del panel activadas se inserta sola en el mini carrito, el carrito y el checkout; usa el shortcode solo para lugares adicionales.', 'dox-sales-booster' ); ?></p>
                    <div class="dsb-sc-examples">
                        <p><strong><?php esc_html_e( 'Básico:', 'dox-sales-booster' ); ?></strong></p>
                        <code>[dsb_envio_gratis]</code>
                        <p><strong><?php esc_html_e( 'Personalizado:', 'dox-sales-booster' ); ?></strong></p>
                        <code>[dsb_envio_gratis threshold="349000" text="¡Te faltan {precio} para el envío gratis!"]</code>
                    </div>
                    <div class="dsb-info-box"><?php esc_html_e( 'También disponible como widget de Elementor y bloque de Gutenberg.', 'dox-sales-booster' ); ?></div>
                </div>
                <div class="dsb-card dsb-card-full">
                    <h2><?php echo dsb_icon_bag(); ?> <?php esc_html_e( 'Popup de compra', 'dox-sales-booster' ); ?></h2>
                    <p><?php esc_html_e( 'El popup no necesita shortcode: se activa automáticamente cuando está habilitado en la pestaña "Popup de compra". En Gutenberg busca los bloques "Sales Booster"; en Elementor, los widgets con el mismo nombre.', 'dox-sales-booster' ); ?></p>
                </div>
            </div>
        </div>

        </form>
        <div id="dsb-toast" class="dsb-toast">✓ <?php esc_html_e( '¡Configuración guardada!', 'dox-sales-booster' ); ?></div>
    </div>
    <?php
}

/* ══ CSS ═════════════════════════════════════════════════════════════════════ */
function dsb_admin_css() { return '
.dsb-wrap *{box-sizing:border-box}
.dsb-wrap{max-width:1100px;margin:20px 20px 60px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
.dsb-header{display:flex;align-items:center;justify-content:space-between;background:#fff;border-radius:12px;padding:16px 24px;margin-bottom:4px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.dsb-header-left{display:flex;align-items:center;gap:14px}
.dsb-header svg{max-height:34px;width:auto}
.dsb-header-badge{background:#fff4e8;color:#ff8d27;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;padding:4px 10px;border-radius:20px;border:1px solid #ffd4a3}
.dsb-header-version{font-size:11px;color:#aaa;font-weight:600}
.dsb-header-actions{display:flex;align-items:center;gap:10px}
.dsb-btn-reset{background:none;color:#888;border:1.5px solid #e5e7eb;border-radius:8px;padding:9px 16px;font-size:13px;font-weight:500;cursor:pointer;transition:.2s}
.dsb-btn-reset:hover{border-color:#e44c4c;color:#e44c4c}
.dsb-btn-reset.loading{opacity:.7;pointer-events:none}
.dsb-btn-save{background:#ff8d27;color:#fff;border:none;border-radius:8px;padding:10px 22px;font-size:14px;font-weight:600;cursor:pointer;transition:.2s;box-shadow:0 2px 8px rgba(255,141,39,.3)}
.dsb-btn-save:hover{background:#e07a1a}
.dsb-btn-save.loading{opacity:.7;pointer-events:none}
.dsb-tabs{display:flex;gap:4px;background:#fff;border-radius:12px;padding:6px;margin-bottom:16px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.dsb-tab{display:flex;align-items:center;gap:7px;background:none;border:none;border-radius:8px;padding:10px 18px;font-size:13px;font-weight:500;color:#666;cursor:pointer;transition:.15s}
.dsb-tab:hover{background:#fff4e8;color:#ff8d27}
.dsb-tab.active{background:#ff8d27;color:#fff;box-shadow:0 2px 8px rgba(255,141,39,.3)}
.dsb-tab.active svg{stroke:#fff}
.dsb-panel{display:none}
.dsb-panel.active{display:block}
.dsb-panel-grid{display:grid;grid-template-columns:1fr 560px;gap:16px;align-items:start}
.dsb-sc-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.dsb-card{background:#fff;border-radius:12px;padding:24px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.dsb-card-full{grid-column:1/-1}
.dsb-card h2{font-size:15px;margin:0 0 6px;display:flex;align-items:center;gap:8px;color:#1a1a2e}
.dsb-card h2 svg{stroke:#ff8d27}
.dsb-card h3{font-size:11px;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:.6px;margin:0 0 14px}
.dsb-subsection{font-size:11px;font-weight:700;color:#ff8d27;text-transform:uppercase;letter-spacing:.8px;margin:26px 0 14px;padding-top:16px;border-top:1px solid #f3f4f6}
.dsb-card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
.dsb-card-desc{font-size:13px;color:#777;margin:0 0 20px}
.dsb-field{margin-bottom:16px}
.dsb-field label{display:block;font-size:11px;font-weight:700;color:#555;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px}
.dsb-field input[type=text],.dsb-field input[type=number],.dsb-field select,.dsb-field textarea{width:100%;padding:8px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#333;transition:.15s;background:#fafafa}
.dsb-field input:focus,.dsb-field select:focus,.dsb-field textarea:focus{outline:none;border-color:#ff8d27;background:#fff;box-shadow:0 0 0 3px rgba(255,141,39,.12)}
.dsb-field select.dsb-multiselect{height:auto}
.dsb-field-row{display:flex;gap:12px}
.dsb-field-row .dsb-field{flex:1}
.dsb-hint{font-size:11px;color:#aaa;display:block;margin-top:4px}
.dsb-color-input{width:100%;height:36px;padding:2px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;background:#fafafa}
.dsb-color-input:focus{outline:none;border-color:#ff8d27}
.dsb-slider{width:100%;-webkit-appearance:none;height:6px;border-radius:6px;background:#ffd4a3;outline:none;margin-top:8px;cursor:pointer}
.dsb-slider::-webkit-slider-thumb{-webkit-appearance:none;width:20px;height:20px;border-radius:50%;background:#ff8d27;cursor:pointer;box-shadow:0 2px 6px rgba(255,141,39,.4)}
.dsb-slider::-moz-range-thumb{width:20px;height:20px;border-radius:50%;background:#ff8d27;cursor:pointer;border:none}
.dsb-toggle{position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0}
.dsb-toggle input{opacity:0;width:0;height:0}
.dsb-toggle-slider{position:absolute;inset:0;background:#d1d5db;border-radius:24px;cursor:pointer;transition:.25s}
.dsb-toggle-slider:before{content:"";position:absolute;width:18px;height:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.25s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.dsb-toggle input:checked+.dsb-toggle-slider{background:#ff8d27}
.dsb-toggle input:checked+.dsb-toggle-slider:before{transform:translateX(20px)}
.dsb-toggle-sm{width:36px;height:20px}
.dsb-toggle-sm .dsb-toggle-slider:before{width:14px;height:14px}
.dsb-toggle-sm input:checked+.dsb-toggle-slider:before{transform:translateX(16px)}
.dsb-switches-row{display:flex;flex-direction:column;gap:10px;margin-bottom:16px}
.dsb-switch-label{display:flex;align-items:center;gap:10px;font-size:13px;color:#444;cursor:pointer}
.dsb-sim-only,.dsb-shipbar-custom-only,.dsb-sales-sim-only{transition:opacity .2s}
.dsb-sim-only.dsb-dim,.dsb-shipbar-custom-only.dsb-dim,.dsb-sales-sim-only.dsb-dim{opacity:.45;pointer-events:none}
.dsb-preview-card{background:#f9f9fc;border:1.5px dashed #ffd4a3;position:sticky;top:46px}
.dsb-preview-box{background:#fff;border-radius:10px;padding:16px;margin-bottom:16px;border:1px solid #eee}
.dsb-preview-product{display:flex;gap:12px;align-items:center}
.dsb-preview-img{width:70px;height:70px;background:linear-gradient(135deg,#ffe8cc,#ffd4a3);border-radius:8px;flex-shrink:0}
.dsb-preview-info{flex:1}
.dsb-preview-title-bar{height:10px;background:#e9e9f0;border-radius:4px;margin-bottom:6px;width:80%}
.dsb-preview-price-bar{height:10px;background:#e9e9f0;border-radius:4px;width:40%;margin-bottom:10px}
.dsb-preview-viewing,.dsb-preview-sales{font-size:13px;color:#555}
.dsb-preview-popup-wrap{overflow-x:auto;overflow-y:visible;margin-bottom:12px;padding:6px 2px 10px;background:#f0f0f5;border-radius:10px}
.dsb-preview-popup{background:#fff;border-radius:10px;box-shadow:0 0 7px 0 rgba(0,0,0,.1);padding:20px 38px 20px 20px;position:relative;display:inline-block;box-sizing:border-box;min-width:200px}
.dsb-preview-popup-close{position:absolute;top:6px;right:8px;background:none;border:none;cursor:default;font-size:11px;color:#bbb}
.dsb-preview-popup-img{background:linear-gradient(135deg,#ffe8cc,#ffd4a3);border-radius:6px;flex-shrink:0}
.dsb-preview-popup-scale-note{font-size:11px;color:#888;text-align:center;margin-bottom:8px;font-style:italic}
.dsb-sc-examples{background:#fff4e8;border-radius:8px;padding:14px 16px;margin-bottom:16px}
.dsb-sc-examples p{margin:10px 0 4px;font-size:12px;font-weight:700;color:#666}
.dsb-sc-examples p:first-child{margin-top:0}
.dsb-sc-examples code{display:block;background:#fff;border:1px solid #ffd4a3;border-radius:6px;padding:8px 10px;font-size:12px;color:#e07a1a}
.dsb-shortcode-box{background:#fff4e8;border-radius:8px;padding:10px 14px;font-size:12px;margin:0}
.dsb-shortcode-box code{color:#ff8d27;font-weight:700}
.dsb-info-box{background:#fff4e8;border-left:3px solid #ff8d27;border-radius:6px;padding:10px 14px;font-size:12px;color:#7a4a00;margin-top:8px}
.dsb-warn-box{background:#fdeaea;border-left:3px solid #e44c4c;border-radius:6px;padding:10px 14px;font-size:12px;color:#8a1f1f;margin:8px 0 16px}
.dsb-toast{position:fixed;bottom:30px;right:30px;background:#ff8d27;color:#fff;padding:12px 22px;border-radius:10px;font-size:14px;font-weight:600;box-shadow:0 4px 14px rgba(255,141,39,.4);opacity:0;transform:translateY(10px);transition:.3s;pointer-events:none;z-index:9999}
.dsb-toast.show{opacity:1;transform:translateY(0)}
.dsb-toast.dsb-toast-error{background:#e44c4c;box-shadow:0 4px 14px rgba(228,76,76,.4)}
#dsb-shipbar-preview-fill{background-image:linear-gradient(45deg,rgba(255,255,255,.28) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.28) 50%,rgba(255,255,255,.28) 75%,transparent 75%,transparent);background-size:20px 20px;animation:dsb-prev-stripes 1s linear infinite}
@keyframes dsb-prev-stripes{from{background-position:20px 0}to{background-position:0 0}}
@media(prefers-reduced-motion:reduce){#dsb-shipbar-preview-fill{animation:none}}
@media(max-width:1024px){.dsb-panel-grid,.dsb-sc-grid{grid-template-columns:1fr}.dsb-tabs{flex-wrap:wrap}.dsb-preview-card{position:static}}
'; }

/* ══ JS ══════════════════════════════════════════════════════════════════════ */
function dsb_admin_js() {
    $i18n = wp_json_encode( [
        'saving'        => __( 'Guardando...', 'dox-sales-booster' ),
        'saveLabel'     => __( 'Guardar cambios', 'dox-sales-booster' ),
        'resetting'     => __( 'Restaurando...', 'dox-sales-booster' ),
        'resetLabel'    => __( 'Restaurar valores por defecto', 'dox-sales-booster' ),
        'confirmReset'  => __( '¿Restaurar toda la configuración a los valores por defecto? Esta acción no se puede deshacer.', 'dox-sales-booster' ),
        'saveError'     => __( 'No se pudo guardar. Revisa tu conexión e inténtalo de nuevo.', 'dox-sales-booster' ),
        /* translators: %d: ancho del popup en píxeles */
        'widthLabel'    => __( 'Ancho: %dpx', 'dox-sales-booster' ),
        'demoProduct'   => __( 'Suéter tejido lila', 'dox-sales-booster' ),
        'defaultPrefix' => __( '🛍️ Alguien ha comprado', 'dox-sales-booster' ),
        'defaultLink'   => __( 'Ver producto', 'dox-sales-booster' ),
    ] );

    return 'window.dsbAdminI18n = ' . $i18n . ';' . <<<'JS'

jQuery(function($){
    var I     = window.dsbAdminI18n || {};
    var DEMO  = I.demoProduct || 'Suéter tejido lila';
    var dirty = false;

    /* Tabs (con deep-link por hash) */
    function activateTab(id){
        if(!$('#dsb-tab-'+id).length) return;
        $('.dsb-tab').removeClass('active');
        $('.dsb-tab[data-tab="'+id+'"]').addClass('active');
        $('.dsb-panel').removeClass('active');
        $('#dsb-tab-'+id).addClass('active');
    }
    $('.dsb-tab').on('click',function(){
        var id=$(this).data('tab');
        activateTab(id);
        if(history.replaceState) history.replaceState(null,'','#'+id);
    });
    if(location.hash) activateTab(location.hash.substring(1));

    /* Aviso de cambios sin guardar */
    $('#dsb-form').on('input change','input, select, textarea',function(){ dirty=true; });
    window.addEventListener('beforeunload',function(e){
        if(dirty){ e.preventDefault(); e.returnValue=''; }
    });

    /* Toast (éxito / error) */
    var toastTimer=null;
    function toast(msg,isError){
        var $t=$('#dsb-toast');
        $t.toggleClass('dsb-toast-error',!!isError).text((isError?'✕ ':'✓ ')+msg).addClass('show');
        clearTimeout(toastTimer);
        toastTimer=setTimeout(function(){ $t.removeClass('show'); },3200);
    }

    /* Vista previa: título con prefijo + truncado */
    function previewTitle(){
        var prefix=$('input[name="dsb[popup_prefix_text]"]').val()||I.defaultPrefix||'';
        var n=parseInt($('#dsb-maxchars-slider').val(),10)||0;
        var sep=prefix+' ';
        var full=sep+DEMO;
        var truncated=(n>0&&full.length>n)?full.substring(0,n)+'…':full;
        var isPrefixed=truncated.indexOf(sep)===0;
        var namePart=isPrefixed?truncated.slice(sep.length):truncated;
        var $title=$('#dsb-popup-preview .dsb-prev-title');
        $title.empty();
        if(isPrefixed){ $title.append(document.createTextNode(prefix+' ')); }
        $title.append($('<span/>').css({fontWeight:600,textDecoration:'underline'}).text(namePart));
    }
    $('input[name="dsb[popup_prefix_text]"]').on('input',previewTitle);
    $('#dsb-maxchars-slider').on('input',function(){
        $('#dsb-maxchars-val').text($(this).val());
        previewTitle();
    });

    /* Vista previa: tamaños de texto por elemento */
    $('input[name="dsb[popup_font_title]"]').on('input',function(){
        $('#dsb-popup-preview .dsb-prev-title').css('font-size',(parseInt(this.value,10)||14)+'px');
    });
    $('input[name="dsb[popup_font_price]"]').on('input',function(){
        $('#dsb-prev-price').css('font-size',(parseInt(this.value,10)||13)+'px');
    });
    $('input[name="dsb[popup_font_meta]"]').on('input',function(){
        $('#dsb-popup-preview .dsb-prev-meta').css('font-size',(parseInt(this.value,10)||12)+'px');
    });
    $('input[name="dsb[popup_font_link]"]').on('input',function(){
        $('#dsb-popup-preview .dsb-prev-link').css('font-size',(parseInt(this.value,10)||12)+'px');
    });
    $('#dsb-display-secs-slider').on('input',function(){
        $('#dsb-display-secs-val').text($(this).val());
    });
    $('input[name="dsb[popup_bg_color]"]').on('input',function(){
        $('#dsb-popup-preview').css('background',$(this).val());
    });
    $('input[name="dsb[popup_title_color]"]').on('input',function(){
        $('#dsb-popup-preview .dsb-prev-title').css('color',$(this).val());
        $('#dsb-prev-price').css('color',$(this).val());
    });
    $('input[name="dsb[popup_meta_color]"]').on('input',function(){
        $('#dsb-popup-preview .dsb-prev-meta').css('color',$(this).val());
    });
    $('input[name="dsb[popup_link_color]"]').on('input',function(){
        $('#dsb-popup-preview .dsb-prev-link').css('color',$(this).val());
    });
    $('input[name="dsb[popup_width]"]').on('input',function(){
        var w=parseInt($(this).val(),10)||320;
        $('#dsb-popup-preview').css('width',w+'px');
        $('#dsb-popup-width-note').text((I.widthLabel||'Ancho: %dpx').replace('%d',w));
    });
    $('input[name="dsb[popup_img_size]"]').on('input',function(){
        var s=parseInt($(this).val(),10)||60;
        $('#dsb-prev-img').css({width:s+'px',height:s+'px'});
    });
    $('input[name="dsb[popup_link_text]"]').on('input',function(){
        $('#dsb-popup-preview .dsb-prev-link').text($(this).val()||I.defaultLink||'Ver producto');
    });
    $('input[name="dsb[popup_show_price]"]').on('change',function(){
        $('#dsb-prev-price').toggle(this.checked);
    });

    /* Vista previa: texto de stock */
    $('input[name="dsb[stock_text]"]').on('input',function(){
        var esc=$('<div/>').text($(this).val()||'').html();
        $('#dsb-stock-preview-text').html(esc.replace('{stock}','<strong class="dsb-stock-count" style="color:#b3261e">3</strong>'));
    });

    /* Barra de envío gratis: vista previa en vivo */
    function shipbarPreview(){
        var pct=parseInt($('#dsb-shipbar-demo').val(),10)||0;
        var $msg=$('#dsb-shipbar-preview-msg');
        if(pct>=100){
            $msg.text($('textarea[name="dsb[shipbar_success_text]"]').val()||'');
        }else{
            var txt=$('textarea[name="dsb[shipbar_text]"]').val()||'';
            var esc=$('<div/>').text(txt).html();
            $msg.html(esc.replace(/\{(precio|price)\}/g,'<strong>$ 58.000</strong>'));
        }
        $('#dsb-shipbar-preview-fill').css('width',pct+'%').css('animation',pct>=100?'none':'');
        $('#dsb-shipbar-demo-val').text(pct);
    }
    $('#dsb-shipbar-demo').on('input',shipbarPreview);
    $('textarea[name="dsb[shipbar_text]"], textarea[name="dsb[shipbar_success_text]"]').on('input',shipbarPreview);
    $('input[name="dsb[shipbar_bar_color]"]').on('input',function(){
        $('#dsb-shipbar-preview').css('--dsb-prev-fill',$(this).val());
    });
    $('input[name="dsb[shipbar_track_color]"]').on('input',function(){
        $('#dsb-shipbar-preview').css('--dsb-prev-track',$(this).val());
    });
    $('input[name="dsb[shipbar_text_color]"]').on('input',function(){
        $('#dsb-shipbar-preview').css('--dsb-prev-text',$(this).val());
    });
    shipbarPreview();

    /* Fuente del monto: atenuar el monto propio cuando se usa WooCommerce */
    function syncShipbarSource(){
        var wc=$('select[name="dsb[shipbar_source]"]').val()==='woocommerce';
        $('.dsb-shipbar-custom-only').toggleClass('dsb-dim',wc);
    }
    $('select[name="dsb[shipbar_source]"]').on('change',syncShipbarSource);
    syncShipbarSource();

    /* Modo de datos: atenuar campos que solo aplican al modo simulado */
    function syncDataMode(){
        var real=$('select[name="dsb[popup_data_mode]"]').val()==='real';
        $('.dsb-sim-only').toggleClass('dsb-dim',real);
    }
    $('select[name="dsb[popup_data_mode]"]').on('change',syncDataMode);
    syncDataMode();

    /* Ventas recientes: en modo real el mínimo/máximo no se usan */
    function syncSalesMode(){
        var real=$('select[name="dsb[fakesales_data_mode]"]').val()==='real';
        $('.dsb-sales-sim-only').toggleClass('dsb-dim',real);
    }
    $('select[name="dsb[fakesales_data_mode]"]').on('change',syncSalesMode);
    syncSalesMode();

    /* Restaurar valores por defecto */
    $('#dsb-reset-btn').on('click',function(e){
        e.preventDefault();
        if(!window.confirm(I.confirmReset||'¿Restaurar los valores por defecto?')) return;
        var $btn=$(this);
        $btn.addClass('loading').text(I.resetting||'Restaurando...');
        $.post(ajaxurl,{action:'dsb_reset_settings',nonce:$('input[name="dsb[_nonce]"]').val()})
            .done(function(res){
                if(res && res.success){ dirty=false; location.reload(); }
                else{
                    $btn.removeClass('loading').text(I.resetLabel||'Restaurar valores por defecto');
                    toast((res&&res.data&&res.data.message)||I.saveError||'Error',true);
                }
            })
            .fail(function(){
                $btn.removeClass('loading').text(I.resetLabel||'Restaurar valores por defecto');
                toast(I.saveError||'Error',true);
            });
    });

    /* Guardar */
    $('#dsb-save-btn').on('click',function(e){
        e.preventDefault();
        var $btn=$(this);
        $btn.addClass('loading').text(I.saving||'Guardando...');
        var formData=$('#dsb-form').serializeArray();
        formData.push({name:'action',value:'dsb_save_settings'});
        formData.push({name:'nonce',value:$('input[name="dsb[_nonce]"]').val()});
        $.post(ajaxurl,formData)
            .done(function(res){
                $btn.removeClass('loading').text(I.saveLabel||'Guardar cambios');
                if(res && res.success){
                    dirty=false;
                    toast(res.data && res.data.message ? res.data.message : 'OK');
                    if(res.data && typeof res.data.feed_count!=='undefined'){
                        $('#dsb-source-warning').toggle(res.data.feed_count===0);
                    }
                }else{
                    toast((res&&res.data&&res.data.message)||I.saveError||'Error',true);
                }
            })
            .fail(function(){
                $btn.removeClass('loading').text(I.saveLabel||'Guardar cambios');
                toast(I.saveError||'Error',true);
            });
    });
});
JS;
}
