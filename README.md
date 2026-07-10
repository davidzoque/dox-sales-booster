# Dox Sales Booster

Plugin de WooCommerce (Dox Studio) que añade prueba social para mejorar la conversión:

- **👁️ Personas viendo** — contador con fluctuación gradual (shortcode `[dsb_viewing]`, widget de Elementor, bloque de Gutenberg).
- **🔥 Ventas recientes** — unidades vendidas en un período (`[dsb_sales]`, widget, bloque). Modo **simulado** (número estable dentro del rango, no cambia al recargar) o **real** (ventas reales del producto; si no hubo, no se muestra).
- **⚡ Stock bajo** — urgencia con inventario **real** de WooCommerce (`[dsb_stock]`, widget, bloque).
- **🛍️ Popup de compra** — notificación animada con producto, precio, ciudad y tiempo. Modo **simulado** (catálogo) o **real** (pedidos recientes, siempre anónimo: solo ciudad y tiempo).
- **🚚 Barra de envío gratis** — "¡Te faltan {precio} para el envío gratis!" con barra de progreso según el carrito real (`[dsb_envio_gratis]`, widget, bloque). Se inserta sola en el mini carrito estándar de WooCommerce (incluido el offcanvas de UICore Pro), el carrito y el checkout, y se refresca por cart fragments sin recargar. El monto puede ser propio o leerse del método "Envío gratuito" de WooCommerce de la zona del cliente.

Todo se configura en **wp-admin → Sales Booster**.

> ℹ️ Distribución propia de Dox Studio — este plugin no se publica en WordPress.org (la prueba social simulada incumple su guideline 9). Se instala subiendo el ZIP de la última release y **se actualiza solo** desde este repositorio.

## Requisitos

- WordPress 5.9+ (probado hasta 7.0)
- WooCommerce 6.0+ (probado hasta 10.9) — solo para popup y stock; los contadores funcionan sin Woo
- PHP 7.4+

## Instalación

1. Descarga el ZIP de la [última release](https://github.com/davidzoque/dox-sales-booster/releases/latest) (`dox-sales-booster.zip`).
2. En WordPress: **Plugins → Añadir nuevo → Subir plugin**, elige el ZIP y actívalo.
3. Configúralo en **wp-admin → Sales Booster**.

## Actualizaciones automáticas

El plugin incluye [Plugin Update Checker v5.7](https://github.com/YahnisElsts/plugin-update-checker) apuntando a las **releases** de este repositorio (público), así que **no requiere ninguna configuración ni token**: las actualizaciones aparecen en la pantalla normal de plugins de WordPress, igual que cualquier otro. Puedes forzar la comprobación con **"Ver detalles / Buscar actualizaciones"** en la fila del plugin.

## Publicar una nueva versión (mantenedor)

1. Sube el número de versión en **dos** sitios: header `Version:` de `dox-sales-booster.php` (y la constante `DSB_VERSION`) y `Stable tag:` de `readme.txt`. Añade la entrada al changelog.
2. Commit + tag + push:
   ```bash
   git commit -am "v1.2.1"
   git tag v1.2.1
   git push && git push --tags
   ```
3. El workflow de GitHub Actions (`.github/workflows/release.yml`) crea la **release** con un ZIP limpio (`dox-sales-booster.zip`) adjunto.
4. Los sitios detectan la actualización en unas horas (WordPress consulta ~2 veces al día), o al instante con el enlace **Buscar actualizaciones**.

Notas:
- El tag debe empezar por `v` (p. ej. `v1.2.1`) y **coincidir** con la versión del header (el workflow lo valida).
- PUC usa el asset ZIP de la release (`enableReleaseAssets`), así que el paquete no arrastra `.github/` ni archivos de desarrollo.

## Estructura

```
dox-sales-booster.php      Bootstrap: constantes, requires, HPOS, Elementor, PUC
uninstall.php              Limpieza de opciones y transients al desinstalar
includes/render.php        Núcleo compartido: defaults, caché, feed (simulado/real), renders
includes/frontend.php      Encolado de assets, popup en el footer, shortcodes
includes/blocks.php        Bloques de Gutenberg (dinámicos, sin build step)
includes/shipping-bar.php  Barra de envío gratis: umbral, render, hooks y fragments
admin/settings.php         Panel de administración (tabs, preview en vivo, AJAX)
elementor/widgets.php      Widgets de Elementor (viewing, sales, stock, shipbar)
assets/css/dsb.css         Estilos del frontend
assets/js/dsb.js           JS del frontend (popup, contador) — vanilla, sin jQuery
assets/js/dsb-blocks.js    JS del editor de bloques (ES5, sin build)
vendor/plugin-update-checker/  Librería PUC v5.7 (MIT)
languages/                 Traducciones (.po/.mo) — text domain: dox-sales-booster
```

## Desarrollo

- Validar sintaxis: `php -l <archivo>` sobre cada PHP y `node --check` sobre los JS.
- La caché de productos del popup usa transients con un salt versionado (`dsb_cache_ver`); se invalida al guardar ajustes, al editar/borrar productos y (con candado de 5 min) en cambios de stock.
- Los textos visibles de JS llegan por `dsbConfig.i18n` / `dsbAdminI18n` — mantenerlos traducibles.
