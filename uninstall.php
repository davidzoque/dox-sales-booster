<?php
/**
 * Limpieza al desinstalar Dox Sales Booster: opciones y transients propios.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

delete_option( 'dsb_settings' );
delete_option( 'dsb_cache_ver' );

global $wpdb;
// Transients propios: dsb_pp_* (caché de productos), dsb_stock_flush_lock
// y los legados dsb_popup_products_* de la 1.x.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '\_transient\_dsb\_%'
        OR option_name LIKE '\_transient\_timeout\_dsb\_%'"
);
