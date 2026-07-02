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
            __( '⚙️ Los valores por defecto se toman del <a href="%s" target="_blank">panel Sales Booster</a>. Aquí puedes sobreescribirlos solo para este widget.', 'dox-sales-booster' ),
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
    public function get_title()      { return '👁️ ' . __( 'Personas viendo (Sales Booster)', 'dox-sales-booster' ); }
    public function get_icon()       { return 'eicon-eye'; }
    public function get_keywords()   { return [ 'ventas', 'urgencia', 'viendo', 'sales booster', 'dox' ]; }
    public function get_categories() { return [ 'woocommerce-elements', 'general' ]; }

    // Elementor encola los assets cuando el widget está en la página,
    // aunque el encolado global del plugin no haya actuado.
    public function get_style_depends()  { return [ 'dsb-styles' ]; }
    public function get_script_depends() { return [ 'dsb-scripts' ]; }

    protected function register_controls() {
        $opts = dsb_get_settings();

        $this->start_controls_section( 'section_content', [ 'label' => __( 'Configuración', 'dox-sales-booster' ) ] );
        dsb_widget_note_control( $this );

        $this->add_control( 'min', [
            'label'   => __( 'Mínimo personas', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => $opts['viewing_min'],
            'min'     => 1, 'max' => 100,
        ] );
        $this->add_control( 'max', [
            'label'   => __( 'Máximo personas', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => $opts['viewing_max'],
            'min'     => 1, 'max' => 200,
        ] );
        $this->add_control( 'text', [
            'label'   => __( 'Texto', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => $opts['viewing_text'],
        ] );

        $this->end_controls_section();

        // Estilo
        $this->start_controls_section( 'section_style', [ 'label' => __( 'Estilo', 'dox-sales-booster' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ] );
        $this->add_control( 'text_color', [
            'label'     => __( 'Color del texto', 'dox-sales-booster' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .dsb-live-viewing' => 'color: {{VALUE}}' ],
        ] );
        $this->add_control( 'count_color', [
            'label'     => __( 'Color del número', 'dox-sales-booster' ),
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
            dsb_widget_placeholder( __( 'El contador "Personas viendo" está desactivado en los ajustes de Sales Booster.', 'dox-sales-booster' ) );
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
    public function get_title()      { return '🔥 ' . __( 'Ventas recientes (Sales Booster)', 'dox-sales-booster' ); }
    public function get_icon()       { return 'eicon-cart-medium'; }
    public function get_keywords()   { return [ 'ventas', 'urgencia', 'vendidos', 'sales booster', 'dox' ]; }
    public function get_categories() { return [ 'woocommerce-elements', 'general' ]; }

    public function get_style_depends() { return [ 'dsb-styles' ]; }

    protected function register_controls() {
        $opts = dsb_get_settings();

        $this->start_controls_section( 'section_content', [ 'label' => __( 'Configuración', 'dox-sales-booster' ) ] );
        dsb_widget_note_control( $this );

        $this->add_control( 'text', [
            'label'   => __( 'Texto', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => $opts['fakesales_text'],
        ] );
        $this->add_control( 'min', [
            'label'   => __( 'Mínimo ventas', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => $opts['fakesales_min'],
            'min' => 1, 'max' => 100,
        ] );
        $this->add_control( 'max', [
            'label'   => __( 'Máximo ventas', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => $opts['fakesales_max'],
            'min' => 1, 'max' => 200,
        ] );
        $this->add_control( 'timeframe', [
            'label'   => __( 'Cantidad de tiempo', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => $opts['fakesales_timeframe'],
        ] );
        $this->add_control( 'period', [
            'label'   => __( 'Período', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => $opts['fakesales_period'],
            'options' => [
                'minutos' => __( 'Minutos', 'dox-sales-booster' ),
                'horas'   => __( 'Horas', 'dox-sales-booster' ),
                'días'    => __( 'Días', 'dox-sales-booster' ),
                'semanas' => __( 'Semanas', 'dox-sales-booster' ),
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'section_style', [ 'label' => __( 'Estilo', 'dox-sales-booster' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ] );
        $this->add_control( 'text_color', [
            'label'     => __( 'Color del texto', 'dox-sales-booster' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .dsb-fake-sales' => 'color: {{VALUE}}' ],
        ] );
        $this->add_control( 'count_color', [
            'label'     => __( 'Color del número', 'dox-sales-booster' ),
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
            dsb_widget_placeholder( __( 'El texto de "Ventas recientes" está desactivado en los ajustes de Sales Booster.', 'dox-sales-booster' ) );
            return;
        }
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado en dsb_render_sales()
    }
}

/* ══════════════════════════════════════════════════════════════════════════
   Widget: Stock bajo (datos reales)
   ══════════════════════════════════════════════════════════════════════════ */
class DSB_Widget_Stock extends \Elementor\Widget_Base {

    public function get_name()       { return 'dsb_stock'; }
    public function get_title()      { return '⚡ ' . __( 'Stock bajo (Sales Booster)', 'dox-sales-booster' ); }
    public function get_icon()       { return 'eicon-alert'; }
    public function get_keywords()   { return [ 'stock', 'inventario', 'urgencia', 'sales booster', 'dox' ]; }
    public function get_categories() { return [ 'woocommerce-elements', 'general' ]; }

    public function get_style_depends() { return [ 'dsb-styles' ]; }

    protected function register_controls() {
        $opts = dsb_get_settings();

        $this->start_controls_section( 'section_content', [ 'label' => __( 'Configuración', 'dox-sales-booster' ) ] );
        dsb_widget_note_control( $this );

        $this->add_control( 'threshold', [
            'label'   => __( 'Umbral de unidades', 'dox-sales-booster' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => $opts['stock_threshold'],
            'min'     => 1, 'max' => 999,
            'description' => __( 'Solo se muestra si el stock real es menor o igual a este número.', 'dox-sales-booster' ),
        ] );
        $this->add_control( 'text', [
            'label'       => __( 'Texto', 'dox-sales-booster' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => $opts['stock_text'],
            'description' => __( 'Variable disponible: {stock}', 'dox-sales-booster' ),
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'section_style', [ 'label' => __( 'Estilo', 'dox-sales-booster' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ] );
        $this->add_control( 'text_color', [
            'label'     => __( 'Color del texto', 'dox-sales-booster' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .dsb-low-stock' => 'color: {{VALUE}}' ],
        ] );
        $this->add_control( 'count_color', [
            'label'     => __( 'Color del número', 'dox-sales-booster' ),
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
            dsb_widget_placeholder( __( 'Stock bajo: se muestra en páginas de producto cuando el inventario real está por debajo del umbral (nada que mostrar en este contexto).', 'dox-sales-booster' ) );
            return;
        }
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado en dsb_render_stock()
    }
}
