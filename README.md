# Dox Sales Booster

Plugin premium de WooCommerce (Dox Studio) que añade prueba social para mejorar la conversión:

- **👁️ Personas viendo** — contador con fluctuación gradual (shortcode `[dsb_viewing]`, widget de Elementor, bloque de Gutenberg).
- **🔥 Ventas recientes** — unidades vendidas en un período (`[dsb_sales]`, widget, bloque).
- **⚡ Stock bajo** — urgencia con inventario **real** de WooCommerce (`[dsb_stock]`, widget, bloque).
- **🛍️ Popup de compra** — notificación animada con producto, precio, ciudad y tiempo. Modo **simulado** (catálogo) o **real** (pedidos de los últimos 30 días, siempre anónimo: solo ciudad y tiempo).

Todo se configura en **wp-admin → Sales Booster**.

> ⚠️ Distribución privada de Dox Studio. Este plugin **no** se publica en WordPress.org (la prueba social simulada incumple su guideline 9); se distribuye e instala manualmente o vía este repo.

## Requisitos

- WordPress 5.9+ (probado hasta 7.0)
- WooCommerce 6.0+ (probado hasta 10.9) — solo para popup y stock; los contadores funcionan sin Woo
- PHP 7.4+

## Actualizaciones automáticas desde GitHub

El plugin incluye [Plugin Update Checker v5.7](https://github.com/YahnisElsts/plugin-update-checker) apuntando a las **releases** de este repositorio. Los sitios con el plugin instalado ven las actualizaciones en la pantalla normal de plugins de WordPress (con enlace "Check for updates").

### Configuración inicial (una sola vez)

1. **Crear el repo** (privado) en GitHub, p. ej. `davidzoque/dox-sales-booster`.
   - Si el usuario/organización u el nombre difieren, ajusta la URL en `dox-sales-booster.php` (bloque *Auto-actualizaciones desde GitHub*) y en el header `Update URI`.
2. **Subir el código** (la raíz del repo es la raíz del plugin, este mismo directorio):
   ```bash
   git init -b main
   git add .
   git commit -m "Dox Sales Booster 1.2.0"
   git remote add origin git@github.com:davidzoque/dox-sales-booster.git
   git push -u origin main
   ```
3. **Token de lectura** (solo repos privados): crea un *fine-grained personal access token* en GitHub → Settings → Developer settings → Personal access tokens, con acceso **solo a este repo** y permiso **Contents: Read-only**. En cada sitio WordPress cliente, añade a `wp-config.php`:
   ```php
   define( 'DSB_GITHUB_TOKEN', 'github_pat_xxxxxxxx' );
   ```
   El token **nunca** va dentro del código del plugin ni del repo.

### Publicar una nueva versión

1. Sube el número de versión en **dos** sitios: header `Version:` de `dox-sales-booster.php` (y la constante `DSB_VERSION`) y `Stable tag:` de `readme.txt`. Añade la entrada al changelog.
2. Commit + tag + push:
   ```bash
   git commit -am "v1.2.1"
   git tag v1.2.1
   git push && git push --tags
   ```
3. El workflow de GitHub Actions (`.github/workflows/release.yml`) crea la **release** con un ZIP limpio (`dox-sales-booster.zip`) adjunto.
4. Los sitios detectan la actualización en unas horas (WordPress consulta ~2 veces al día), o al instante con el enlace **Check for updates** en la fila del plugin.

Notas:
- El tag debe empezar por `v` (p. ej. `v1.2.1`) y coincidir con la versión del header.
- PUC usa el asset ZIP de la release (`enableReleaseAssets`), así que el paquete no arrastra `.github/` ni archivos de desarrollo.

## Estructura

```
dox-sales-booster.php      Bootstrap: constantes, requires, HPOS, Elementor, PUC
uninstall.php              Limpieza de opciones y transients al desinstalar
includes/render.php        Núcleo compartido: defaults, caché, feed (simulado/real), renders
includes/frontend.php      Encolado de assets, popup en el footer, shortcodes
includes/blocks.php        Bloques de Gutenberg (dinámicos, sin build step)
admin/settings.php         Panel de administración (tabs, preview en vivo, AJAX)
elementor/widgets.php      Widgets de Elementor (viewing, sales, stock)
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
