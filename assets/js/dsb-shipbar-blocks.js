/**
 * Dox Sales Booster — barra de envío gratis en carrito/checkout por BLOQUES.
 *
 * Los bloques de WooCommerce (woocommerce/cart, woocommerce/checkout) usan el
 * Store API (React) en vez de los cart fragments clásicos, así que la barra que
 * inyectamos server-side no se refrescaría sola al cambiar cantidades o cupones.
 * Este script escucha el store `wc/store/cart` y recalcula el progreso in-place.
 *
 * Config vía window.dsbShipbar: { threshold, text, successText, ignoreCoupons }.
 */
(function () {
    'use strict';

    var cfg = window.dsbShipbar || {};
    var threshold = parseFloat(cfg.threshold) || 0;
    if (!window.wp || !window.wp.data || threshold <= 0) return;

    // Formatea un importe con los datos de moneda que expone el Store API, para
    // que coincida con el formato de wc_price() del render server-side.
    function formatPrice(amount, t) {
        var minor = (t && t.currency_minor_unit != null) ? t.currency_minor_unit : 2;
        var parts = amount.toFixed(minor).split('.');
        var thou  = (t && t.currency_thousand_separator != null) ? t.currency_thousand_separator : ',';
        var dec   = (t && t.currency_decimal_separator != null) ? t.currency_decimal_separator : '.';
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thou);
        var num = parts.length > 1 && parts[1] !== '' ? parts[0] + dec + parts[1] : parts[0];
        return ((t && t.currency_prefix) || '') + num + ((t && t.currency_suffix) || '');
    }

    // Escribe el mensaje sin innerHTML: el importe va en su propio <span> como
    // texto, evitando cualquier inyección desde la plantilla del panel.
    function setMessage(msgEl, done, priceText) {
        if (done) { msgEl.textContent = cfg.successText || ''; return; }
        var tmpl = cfg.text || '';
        var m = tmpl.match(/\{(?:precio|price)\}/);
        if (!m) { msgEl.textContent = tmpl; return; }
        while (msgEl.firstChild) msgEl.removeChild(msgEl.firstChild);
        msgEl.appendChild(document.createTextNode(tmpl.slice(0, m.index)));
        var span = document.createElement('span');
        span.className = 'dsb-shipbar-amount';
        span.textContent = priceText;
        msgEl.appendChild(span);
        msgEl.appendChild(document.createTextNode(tmpl.slice(m.index + m[0].length)));
    }

    function paint(amount, t) {
        var done = amount >= threshold;
        var pct  = done ? 100 : Math.floor((amount / threshold) * 100);
        pct = Math.max(0, Math.min(100, pct));
        var priceText = formatPrice(Math.max(0, threshold - amount), t);

        var wraps = document.querySelectorAll('.dsb-shipbar-wrap.dsb-shipbar-auto');
        for (var i = 0; i < wraps.length; i++) {
            var fill  = wraps[i].querySelector('.dsb-shipbar-fill');
            var track = wraps[i].querySelector('.dsb-shipbar-track');
            var bar   = wraps[i].querySelector('.dsb-shipbar');
            var msg   = wraps[i].querySelector('.dsb-shipbar-msg');
            if (fill)  fill.style.width = pct + '%';
            if (track) track.setAttribute('aria-valuenow', pct);
            if (bar)   bar.classList.toggle('dsb-shipbar-done', done);
            if (msg)   setMessage(msg, done, priceText);
        }
    }

    var last = null;
    function update() {
        var store = window.wp.data.select('wc/store/cart');
        if (!store || typeof store.getCartTotals !== 'function') return;
        var t = store.getCartTotals();
        if (!t) return;
        var minor  = Math.pow(10, (t.currency_minor_unit != null ? t.currency_minor_unit : 2));
        var amount = parseInt(t.total_items || 0, 10) / minor;
        if (cfg.ignoreCoupons) amount += parseInt(t.total_discount || 0, 10) / minor;
        if (amount === last) return; // sin cambios: no repintar
        last = amount;
        paint(amount, t);
    }

    window.wp.data.subscribe(update);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', update);
    } else {
        update();
    }
})();
