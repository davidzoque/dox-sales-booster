<?php
/**
 * Frontend: registro/encolado de assets, popup automático en el footer
 * y shortcodes. El render real vive en includes/render.php.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class DSB_Frontend {

    private $opts;

    public function __construct() {
        $this->opts = dsb_get_settings();

        add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ], 5 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
        add_action( 'wp_footer',          [ $this, 'render_popup' ] );

        // Shortcodes — registrados siempre (también en admin/AJAX, para builders
        // que renderizan contenido vía admin-ajax.php).
        add_shortcode( 'dsb_viewing', [ $this, 'shortcode_viewing' ] );
        add_shortcode( 'dsb_sales',   [ $this, 'shortcode_sales' ] );
        add_shortcode( 'dsb_stock',   [ $this, 'shortcode_stock' ] );
    }

    // ── ¿El popup debe actuar en esta página? ────────────────────────────────
    private function popup_active() {
        if ( empty( $this->opts['popup_enabled'] ) ) return false;
        if ( ! class_exists( 'WooCommerce' ) ) return false;
        if ( ! empty( $this->opts['popup_exclude_checkout'] ) && ( is_cart() || is_checkout() ) ) return false;
        return true;
    }

    // ── Registro (siempre): Elementor/Gutenberg los encolan bajo demanda ─────
    public function register_assets() {
        wp_register_style( 'dsb-styles', DSB_URL . 'assets/css/dsb.css', [], DSB_VERSION );
        wp_register_script( 'dsb-scripts', DSB_URL . 'assets/js/dsb.js', [], DSB_VERSION, true );
        wp_add_inline_style( 'dsb-styles', $this->dynamic_css() );
    }

    private function dynamic_css() {
        $font_size   = (int) $this->opts['popup_font_size'];
        $popup_width = (int) $this->opts['popup_width'];
        $img_size    = (int) $this->opts['popup_img_size'];
        $bg_color    = $this->opts['popup_bg_color'] ?: '#ffffff';
        $title_color = $this->opts['popup_title_color'] ?: '#1a1a1a';
        $meta_color  = $this->opts['popup_meta_color'] ?: '#777777';
        $link_color  = $this->opts['popup_link_color'] ?: '#555555';
        $mobile_img  = max( 44, (int) round( $img_size * 0.75 ) );

        return "
            #dsb-popup {
                --dsb-popup-font: {$font_size}px;
                max-width: {$popup_width}px;
                background: {$bg_color};
            }
            .dsb-popup-img   { width: {$img_size}px; height: {$img_size}px; }
            .dsb-popup-title { font-size: var(--dsb-popup-font, 14px); color: {$title_color}; }
            .dsb-popup-price { font-size: calc(var(--dsb-popup-font, 14px) - 1px); color: {$title_color}; }
            .dsb-popup-meta  { font-size: calc(var(--dsb-popup-font, 14px) - 2px); color: {$meta_color}; }
            .dsb-popup-link  { font-size: calc(var(--dsb-popup-font, 14px) - 2px); color: {$link_color}; }
            @media (max-width: 600px) {
                #dsb-popup {
                    max-width: calc(100vw - 20px) !important;
                    width: calc(100vw - 20px);
                    left: 10px;
                    right: 10px;
                    bottom: 10px;
                }
                .dsb-popup-img { width: {$mobile_img}px !important; height: {$mobile_img}px !important; }
                .dsb-popup-inner { padding: 12px 34px 12px 12px; gap: 12px; }
                .dsb-popup-title { white-space: normal; -webkit-line-clamp: 2; display: -webkit-box; -webkit-box-orient: vertical; overflow: hidden; }
                .dsb-popup-link  { display: none !important; }
            }
        ";
    }

    // ── Encolar CSS/JS y configuración del frontend ──────────────────────────
    public function enqueue() {
        $popup_on = $this->popup_active();
        $any      = $popup_on
            || ! empty( $this->opts['viewing_enabled'] )
            || ! empty( $this->opts['fakesales_enabled'] );

        $config = [
            'viewing_interval' => (int) $this->opts['viewing_interval'],
            'popup_enabled'    => $popup_on,
            'i18n'             => [
                'just_now'  => __( 'hace un momento', 'dox-sales-booster' ),
                'mins_ago'  => __( 'hace %d minutos', 'dox-sales-booster' ),
                'hours_ago' => __( 'hace %d horas', 'dox-sales-booster' ),
                'days_ago'  => __( 'hace %d días', 'dox-sales-booster' ),
                'someone'   => __( 'Alguien', 'dox-sales-booster' ),
            ],
        ];

        // La configuración (y la consulta) del popup solo cuando hace falta.
        if ( $popup_on ) {
            $config += [
                'popup_interval'        => (int) $this->opts['popup_interval'],
                'popup_display_seconds' => (int) $this->opts['popup_display_seconds'],
                'popup_products'        => dsb_get_popup_feed( $this->opts ),
                'popup_locations'       => dsb_parse_locations( $this->opts['popup_locations'] ),
                'popup_names'           => dsb_parse_list_lines( $this->opts['popup_names'] ),
                'popup_prefix'          => $this->opts['popup_prefix_text'],
                'popup_link_text'       => $this->opts['popup_link_text'],
                'popup_show_price'      => ! empty( $this->opts['popup_show_price'] ),
                'popup_img_size'        => (int) $this->opts['popup_img_size'],
                'popup_title_maxchars'  => (int) $this->opts['popup_title_maxchars'],
                'popup_first_delay_min' => (int) $this->opts['popup_first_delay_min'],
                'popup_first_delay_max' => (int) $this->opts['popup_first_delay_max'],
                'popup_ago_min'         => (int) $this->opts['popup_ago_min'],
                'popup_ago_max'         => (int) $this->opts['popup_ago_max'],
                'popup_close_silence'   => (int) $this->opts['popup_close_silence'],
                'popup_max_per_page'    => (int) $this->opts['popup_max_per_page'],
            ];
        }

        wp_localize_script( 'dsb-scripts', 'dsbConfig', $config );

        if ( $any ) {
            wp_enqueue_style( 'dsb-styles' );
            wp_enqueue_script( 'dsb-scripts' );
        }
    }

    // ── Popup de compra reciente (automático en footer) ──────────────────────
    public function render_popup() {
        if ( ! $this->popup_active() ) return;

        $animation = in_array( $this->opts['popup_animation'], [ 'slide_up', 'slide_right' ], true )
            ? $this->opts['popup_animation'] : 'slide_up';
        $position  = 'right' === ( $this->opts['popup_position'] ?? 'left' ) ? 'right' : 'left';

        $classes = [ 'dsb-popup', 'animation-' . $animation, 'dsb-pos-' . $position ];
        if ( empty( $this->opts['popup_show_mobile'] ) ) $classes[] = 'dsb-hide-mobile';
        ?>
        <div id="dsb-popup" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" style="display:none;">
            <button type="button" class="dsb-popup-close" aria-label="<?php esc_attr_e( 'Cerrar', 'dox-sales-booster' ); ?>">&#10005;</button>
            <div class="dsb-popup-inner"></div>
        </div>
        <?php
    }

    // ── Shortcodes (los atributos vacíos heredan del panel) ──────────────────
    public function shortcode_viewing( $atts ) {
        $atts = shortcode_atts( [ 'min' => '', 'max' => '', 'text' => '' ], $atts, 'dsb_viewing' );
        return dsb_render_viewing( $atts );
    }

    public function shortcode_sales( $atts ) {
        $atts = shortcode_atts( [ 'min' => '', 'max' => '', 'text' => '', 'timeframe' => '', 'period' => '' ], $atts, 'dsb_sales' );
        return dsb_render_sales( $atts );
    }

    public function shortcode_stock( $atts ) {
        $atts = shortcode_atts( [ 'product_id' => '', 'threshold' => '', 'text' => '' ], $atts, 'dsb_stock' );
        return dsb_render_stock( $atts );
    }
}

add_action( 'init', function () {
    new DSB_Frontend();
} );
