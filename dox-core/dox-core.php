<?php
/**
 * Dox Core — el menú común de los plugins de Dox Studio.
 *
 * Junta todos los plugins Dox bajo un solo menú "Dox Plugins" en vez de que
 * cada uno cuelgue su propia entrada del menú principal de WordPress. Cada
 * plugin sigue siendo independiente: lleva su copia de esta carpeta y funciona
 * igual esté solo o acompañado (ver loader.php).
 *
 * El menú se llama "Dox Plugins" y no "Dox Studio" a propósito: el tema de Dox
 * Studio (UiCore con marca blanca) ya crea un menú "Dox Studio" con el slug
 * `uicore`, y dos entradas con el mismo nombre en la misma barra confunden.
 *
 * Cómo se apunta un plugin, desde su propio archivo:
 *
 *     add_action( 'dox_core_register', function ( $core ) {
 *         $core->register_plugin( [
 *             'slug'    => 'dox-sales-booster',
 *             'name'    => 'Sales Booster',
 *             'version' => DSB_VERSION,
 *             'summary' => 'Social proof for WooCommerce.',
 *             'page'    => [
 *                 'menu_title' => 'Sales Booster',
 *                 'page_title' => 'Dox Sales Booster',
 *                 'callback'   => 'dsb_render_page',
 *             ],
 *         ] );
 *     } );
 *
 * El submenú lo crea el core, no el plugin, para que el orden y las
 * capacidades sean iguales en todos.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// La declaración va dentro de un if a propósito: PHP registra las clases de
// primer nivel al compilar el archivo, antes de ejecutar su primera línea, así
// que un class_exists() suelto aquí arriba daría true en esta misma copia.
if ( ! class_exists( 'Dox_Core' ) ) :

class Dox_Core {

	const VERSION   = '1.0.0';
	const MENU_SLUG = 'dox-plugins';

	private static $instance = null;

	/** Plugins registrados, por slug. */
	private $plugins = [];

	/** Hook de pantalla de cada subpágina creada, por slug. */
	private $hooks = [];

	/** Carpeta de la copia que ha ganado, para las vistas y las traducciones. */
	private $path;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->path = trailingslashit( __DIR__ );

		add_action( 'init', [ $this, 'load_textdomain' ], 1 );
		// Prioridad 9: antes de que los plugins registren nada suyo en el menú.
		add_action( 'admin_menu', [ $this, 'register_menu' ], 9 );
	}

	public function path() {
		return $this->path;
	}

	public function load_textdomain() {
		$mofile = $this->path . 'languages/dox-core-' . determine_locale() . '.mo';
		if ( file_exists( $mofile ) ) {
			load_textdomain( 'dox-core', $mofile );
		}
	}

	/**
	 * Apunta un plugin en el menú común.
	 *
	 * @param array $args slug, name, version, summary, url y (opcional) page:
	 *                    menu_title, page_title, capability, menu_slug, callback.
	 */
	public function register_plugin( array $args ) {

		if ( empty( $args['slug'] ) || empty( $args['name'] ) ) return;

		$args = wp_parse_args( $args, [
			'slug'    => '',
			'name'    => '',
			'version' => '',
			'summary' => '',
			'url'     => 'https://doxstudio.com/plugins/',
			'page'    => [],
		] );

		if ( ! empty( $args['page'] ) ) {
			$args['page'] = wp_parse_args( $args['page'], [
				'menu_title' => $args['name'],
				'page_title' => $args['name'],
				'capability' => 'manage_options',
				'menu_slug'  => $args['slug'],
				'callback'   => '',
			] );
		}

		$this->plugins[ $args['slug'] ] = $args;
	}

	public function get_plugins() {
		return $this->plugins;
	}

	/**
	 * Hook de pantalla de la subpágina de un plugin, para que pueda encolar sus
	 * estilos solo ahí. Al pasar de menú principal a submenú, el hook deja de
	 * ser `toplevel_page_x` y pasa a ser `dox-plugins_page_x`.
	 */
	public function page_hook( $slug ) {
		return isset( $this->hooks[ $slug ] ) ? $this->hooks[ $slug ] : '';
	}

	public function register_menu() {

		do_action( 'dox_core_register', $this );

		if ( ! $this->plugins ) return; // nada que enseñar

		add_menu_page(
			__( 'Dox Plugins', 'dox-core' ),
			__( 'Dox Plugins', 'dox-core' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_dashboard' ],
			$this->menu_icon(),
			'58.9'
		);

		// Sin esto, WordPress repite el nombre del menú como primer submenú.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Dox Plugins', 'dox-core' ),
			__( 'Installed', 'dox-core' ),
			'manage_options',
			self::MENU_SLUG
		);

		foreach ( $this->plugins as $slug => $plugin ) {
			if ( empty( $plugin['page'] ) || empty( $plugin['page']['callback'] ) ) continue;

			$hook = add_submenu_page(
				self::MENU_SLUG,
				$plugin['page']['page_title'],
				$plugin['page']['menu_title'],
				$plugin['page']['capability'],
				$plugin['page']['menu_slug'],
				$plugin['page']['callback']
			);

			if ( $hook ) $this->hooks[ $slug ] = $hook;
		}
	}

	public function render_dashboard() {
		$view = $this->path . 'views/dashboard.php';
		if ( file_exists( $view ) ) {
			include $view;
		}
	}

	/**
	 * Catálogo para la portada: lo que hay, esté instalado o no.
	 */
	public function catalog() {
		return [
			'dox-sales-booster' => [
				'name'    => 'Dox Sales Booster',
				'summary' => __( 'Purchase notifications, live viewing counter, low stock urgency and a free shipping bar for WooCommerce.', 'dox-core' ),
				'url'     => 'https://doxstudio.com/plugins/',
			],
			'dox-pos' => [
				'name'    => 'Dox POS',
				'summary' => __( 'A point of sale for WooCommerce: sell in person with the same catalogue and stock.', 'dox-core' ),
				'url'     => 'https://doxstudio.com/dox-pos/',
			],
			'dox-feedback' => [
				'name'    => 'Dox Feedback',
				'summary' => __( 'Collect reviews and feedback from your customers.', 'dox-core' ),
				'url'     => 'https://doxstudio.com/plugins/',
			],
			'dox-functions' => [
				'name'    => 'Dox Functions',
				'summary' => __( 'Code snippets with no risk of breaking the site editing functions.php.', 'dox-core' ),
				'url'     => 'https://doxstudio.com/plugins/',
			],
		];
	}

	private function menu_icon() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
			. '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>'
			. '<rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>';

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}
}

endif;

/**
 * Acceso corto, como wc() o WC().
 */
if ( ! function_exists( 'dox_core' ) ) {
	function dox_core() {
		return Dox_Core::instance();
	}
}

dox_core();
