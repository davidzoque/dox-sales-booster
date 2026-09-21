<?php
/**
 * Widgets de Elementor para Dox Sales Booster.
 * El registro lo hace dox-sales-booster.php dentro de elementor/widgets/register.
 * El render delega en includes/render.php, así que respeta los toggles globales.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Placeholder visible SOLO en el editor cuando el elemento no produce salida
// (función desactivada en el panel o sin datos en este contexto).
function dsb_widget_placeholder( $message ) {
    if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
        echo '<div class="dsb-widget-disabled">' . esc_html( $message ) . '</div>';
    }
}

// Nota común: los valores por defecto vienen del panel del plugin.
function dsb_widget_note_control( $widget ) {
    $widget->add_control( 'dsb_note', [
        'type' => \Elementor\Controls_Manager::RAW_HTML,
        'raw'  => sprintf(
            /* translators: %s: URL del panel de ajustes */
            __( '⚙️ Default values are taken from the <a href="%s" target="_blank">Sales Booster panel</a>. Here you can override them for this widget only.', 'dox-sales-booster' ),
            esc_url( admin_url( 'admin.php?page=dox-sales-booster' ) )
        ),
        'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
    ] );
}

/* ══════════════════════════════════════════════════════════════════════════
   Widget: Personas viendo
   ══════════════════════════════════════════════════════════════════════════ */
class DSB_Widget_Viewing extends \Elementor\Widget_Base {

    public function get_name()       { return 'dsb_viewing'; }
    public function get_title()      { return '👁️ ' . __( 'People viewing (Sales Booster)', 'dox-sales-booster' ); }
    public function get_icon()       { return 'eicon-eye'; }
    public function get_keywords()   { return [ 'ventas', 'urgencia', 'viendo', 'sales booster', 'dox' ]; }
    public function get_categories() { return [ 'woocommerce-elements', 'general' ]; }

    // Elementor encola los assets cuando el widget está en la página,
    // aunque el encolado global del plugin no haya actuado.
    public function get_style_depends()  { return [ 'dsb-styles' ]; }
    public function get_script_depends() { return [ 'dsb-scripts' ]; }

    protected function register_controls() {
        $opts = dsb_get_settings();

        $this->start_controls_section( 'section_content', [ 'label' => __( 'Settings', 'dox-sales-booster' ) ] );
        dsb_widget_note_control( $this );

        $this->add_control( 'min', [
            'label'   => __( 'Minimum people', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => $opts['viewing_min'],
            'min'     => 1, 'max' => 100,
        ] );
        $this->add_control( 'max', [
            'label'   => __( 'Maximum people', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => $opts['viewing_max'],
            'min'     => 1, 'max' => 200,
        ] );
        $this->add_control( 'text', [
            'label'   => __( 'Text', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => $opts['viewing_text'],
        ] );

        $this->end_controls_section();

        // Estilo
        $this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'dox-sales-booster' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ] );
        $this->add_control( 'text_color', [
            'label'     => __( 'Text color', 'dox-sales-booster' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .dsb-live-viewing' => 'color: {{VALUE}}' ],
        ] );
        $this->add_control( 'count_color', [
            'label'     => __( 'Number color', 'dox-sales-booster' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#e44c4c',
            'selectors' => [ '{{WRAPPER}} .dsb-viewing-count' => 'color: {{VALUE}}' ],
        ] );
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'typography',
            'selector' => '{{WRAPPER}} .dsb-live-viewing',
        ] );
        $this->end_controls_section();
    }

    protected function render() {
        $s    = $this->get_settings_for_display();
        $html = dsb_render_viewing( [
            'min'  => $s['min'] ?? '',
            'max'  => $s['max'] ?? '',
            'text' => $s['text'] ?? '',
        ] );

        if ( '' === $html ) {
            dsb_widget_placeholder( __( 'The "People viewing" counter is disabled in the Sales Booster settings.', 'dox-sales-booster' ) );
            return;
        }
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado en dsb_render_viewing()
    }
}

/* ══════════════════════════════════════════════════════════════════════════
   Widget: Ventas recientes
   ══════════════════════════════════════════════════════════════════════════ */
class DSB_Widget_Sales extends \Elementor\Widget_Base {

    public function get_name()       { return 'dsb_sales'; }
    public function get_title()      { return '🔥 ' . __( 'Recent sales (Sales Booster)', 'dox-sales-booster' ); }
    public function get_icon()       { return 'eicon-cart-medium'; }
    public function get_keywords()   { return [ 'ventas', 'urgencia', 'vendidos', 'sales booster', 'dox' ]; }
    public function get_categories() { return [ 'woocommerce-elements', 'general' ]; }

    public function get_style_depends() { return [ 'dsb-styles' ]; }

    protected function register_controls() {
        $opts = dsb_get_settings();

        $this->start_controls_section( 'section_content', [ 'label' => __( 'Settings', 'dox-sales-booster' ) ] );
        dsb_widget_note_control( $this );

        $this->add_control( 'text', [
            'label'   => __( 'Text', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => $opts['fakesales_text'],
        ] );
        $this->add_control( 'min', [
            'label'   => __( 'Minimum sales', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => $opts['fakesales_min'],
            'min' => 1, 'max' => 100,
        ] );
        $this->add_control( 'max', [
            'label'   => __( 'Maximum sales', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => $opts['fakesales_max'],
            'min' => 1, 'max' => 200,
        ] );
        $this->add_control( 'timeframe', [
            'label'   => __( 'Time quantity', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => $opts['fakesales_timeframe'],
        ] );
        $this->add_control( 'period', [
            'label'   => __( 'Period', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            // Claves en inglés, como en el panel y en el bloque. Hasta la 1.4.0
            // eran 'horas', 'días'...: un widget que guardó una de esas sigue
            // pintando bien (dsb_normalize_period() las entiende), solo que aquí
            // el desplegable sale sin marcar hasta que se vuelva a elegir. Con las
            // claves viejas pasaba lo contrario y peor: el valor por defecto ya
            // llega normalizado ('hours') y el desplegable salía vacío en TODOS
            // los widgets nuevos.
            'default' => dsb_normalize_period( $opts['fakesales_period'] ),
            'options' => [
                'minutes' => __( 'Minutes', 'dox-sales-booster' ),
                'hours'   => __( 'Hours', 'dox-sales-booster' ),
                'days'    => __( 'Days', 'dox-sales-booster' ),
                'weeks'   => __( 'Weeks', 'dox-sales-booster' ),
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'dox-sales-booster' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ] );
        $this->add_control( 'text_color', [
            'label'     => __( 'Text color', 'dox-sales-booster' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .dsb-fake-sales' => 'color: {{VALUE}}' ],
        ] );
        $this->add_control( 'count_color', [
            'label'     => __( 'Number color', 'dox-sales-booster' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#e44c4c',
            'selectors' => [ '{{WRAPPER}} .dsb-sales-count' => 'color: {{VALUE}}' ],
        ] );
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'typography',
            'selector' => '{{WRAPPER}} .dsb-fake-sales',
        ] );
        $this->end_controls_section();
    }

    protected function render() {
        $s    = $this->get_settings_for_display();
        $html = dsb_render_sales( [
            'min'       => $s['min'] ?? '',
            'max'       => $s['max'] ?? '',
            'text'      => $s['text'] ?? '',
            'timeframe' => $s['timeframe'] ?? '',
            'period'    => $s['period'] ?? '',
        ] );

        if ( '' === $html ) {
            dsb_widget_placeholder( __( 'The "Recent sales" text is disabled in the Sales Booster settings.', 'dox-sales-booster' ) );
            return;
        }
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado en dsb_render_sales()
    }
}

/* ══════════════════════════════════════════════════════════════════════════
   Widget: Barra de envío gratis
   ══════════════════════════════════════════════════════════════════════════ */
class DSB_Widget_Shipbar extends \Elementor\Widget_Base {

    public function get_name()       { return 'dsb_shipbar'; }
    public function get_title()      { return '🚚 ' . __( 'Free shipping bar (Sales Booster)', 'dox-sales-booster' ); }
    public function get_icon()       { return 'eicon-skill-bar'; }
    public function get_keywords()   { return [ 'envío', 'gratis', 'shipping', 'barra', 'progreso', 'sales booster', 'dox' ]; }
    public function get_categories() { return [ 'woocommerce-elements', 'general' ]; }

    public function get_style_depends() { return [ 'dsb-styles' ]; }

    protected function register_controls() {
        $opts = dsb_get_settings();

        $this->start_controls_section( 'section_content', [ 'label' => __( 'Settings', 'dox-sales-booster' ) ] );
        dsb_widget_note_control( $this );

        $this->add_control( 'threshold', [
            'label'       => __( 'Free shipping amount', 'dox-sales-booster' ),
            'type'        => \Elementor\Controls_Manager::NUMBER,
            'min'         => 0,
            'description' => __( 'Empty or 0 = use the source set in the panel (custom amount or WooCommerce method).', 'dox-sales-booster' ),
        ] );
        $this->add_control( 'text', [
            'label'       => __( 'Progress text', 'dox-sales-booster' ),
            'type'        => \Elementor\Controls_Manager::TEXTAREA,
            'default'     => $opts['shipbar_text'],
            'description' => __( 'Available variable: {amount} (how much is left to get free shipping).', 'dox-sales-booster' ),
        ] );
        $this->add_control( 'success_text', [
            'label'   => __( 'Success text', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::TEXTAREA,
            'default' => $opts['shipbar_success_text'],
        ] );

        $this->end_controls_section();

        // Estilo — los colores se pasan al render (la barra usa variables CSS
        // inline, así que un selector de Elementor no podría sobreescribirlas).
        $this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'dox-sales-booster' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ] );
        $this->add_control( 'bar_color', [
            'label'   => __( 'Bar color', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::COLOR,
            'default' => $opts['shipbar_bar_color'],
        ] );
        $this->add_control( 'track_color', [
            'label'   => __( 'Bar background color', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::COLOR,
            'default' => $opts['shipbar_track_color'],
        ] );
        $this->add_control( 'text_color', [
            'label'   => __( 'Text color', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::COLOR,
            'default' => $opts['shipbar_text_color'],
        ] );
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'typography',
            'selector' => '{{WRAPPER}} .dsb-shipbar-msg',
        ] );
        $this->end_controls_section();
    }

    protected function render() {
        $s    = $this->get_settings_for_display();
        $html = dsb_render_shipping_bar( [
            'threshold'    => $s['threshold'] ?? '',
            'text'         => $s['text'] ?? '',
            'success_text' => $s['success_text'] ?? '',
            'bar_color'    => $s['bar_color'] ?? '',
            'track_color'  => $s['track_color'] ?? '',
            'text_color'   => $s['text_color'] ?? '',
        ] );

        if ( '' === $html ) {
            dsb_widget_placeholder( __( 'The free shipping bar is disabled in the Sales Booster settings, or no free shipping amount is configured.', 'dox-sales-booster' ) );
            return;
        }
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado en dsb_render_shipping_bar()
    }
}

/* ══════════════════════════════════════════════════════════════════════════
   Widget: Stock bajo (datos reales)
   ══════════════════════════════════════════════════════════════════════════ */
class DSB_Widget_Stock extends \Elementor\Widget_Base {

    public function get_name()       { return 'dsb_stock'; }
    public function get_title()      { return '⚡ ' . __( 'Low stock (Sales Booster)', 'dox-sales-booster' ); }
    public function get_icon()       { return 'eicon-alert'; }
    public function get_keywords()   { return [ 'stock', 'inventario', 'urgencia', 'sales booster', 'dox' ]; }
    public function get_categories() { return [ 'woocommerce-elements', 'general' ]; }

    public function get_style_depends() { return [ 'dsb-styles' ]; }

    protected function register_controls() {
        $opts = dsb_get_settings();

        $this->start_controls_section( 'section_content', [ 'label' => __( 'Settings', 'dox-sales-booster' ) ] );
        dsb_widget_note_control( $this );

        $this->add_control( 'threshold', [
            'label'   => __( 'Units threshold', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => $opts['stock_threshold'],
            'min'     => 1, 'max' => 999,
            'description' => __( 'Only shown if the real stock is less than or equal to this number.', 'dox-sales-booster' ),
        ] );
        $this->add_control( 'text', [
            'label'       => __( 'Text', 'dox-sales-booster' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => $opts['stock_text'],
            'description' => __( 'Available variable: {stock}', 'dox-sales-booster' ),
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'dox-sales-booster' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ] );
        $this->add_control( 'text_color', [
            'label'     => __( 'Text color', 'dox-sales-booster' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .dsb-low-stock' => 'color: {{VALUE}}' ],
        ] );
        $this->add_control( 'count_color', [
            'label'     => __( 'Number color', 'dox-sales-booster' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .dsb-stock-count' => 'color: {{VALUE}}' ],
        ] );
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'typography',
            'selector' => '{{WRAPPER}} .dsb-low-stock',
        ] );
        $this->end_controls_section();
    }

    protected function render() {
        $s    = $this->get_settings_for_display();
        $html = dsb_render_stock( [
            'threshold' => $s['threshold'] ?? '',
            'text'      => $s['text'] ?? '',
        ] );

        if ( '' === $html ) {
            dsb_widget_placeholder( __( 'Low stock: shown on product pages when the real inventory is below the threshold (nothing to show in this context).', 'dox-sales-booster' ) );
            return;
        }
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado en dsb_render_stock()
    }
}
