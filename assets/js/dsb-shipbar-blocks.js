/**
 * Dox Sales Booster — barra de envío gratis en el carrito, el checkout y el
 * mini carrito construidos con BLOQUES.
 *
 * Los bloques de WooCommerce (woocommerce/cart, woocommerce/checkout,
 * woocommerce/mini-cart) usan el Store API (React) en vez de los cart fragments
 * clásicos, así que la barra que inyectamos server-side no se refrescaría sola
 * al cambiar cantidades o cupones. Este script escucha el store `wc/store/cart`
 * y recalcula el progreso in-place.
 *
 * El cajón del mini carrito, además, no existe en el HTML hasta que el visitante
 * lo abre: no hay hook de PHP donde insertar nada, así que la barra se construye
 * aquí con el mismo marcado que genera dsb_render_shipping_bar().
 *
 * Config vía window.dsbShipbar: { threshold, text, successText, ignoreCoupons,
 * miniCart, barColor, trackColor, textColor }.
 */
(function () {
    'use strict';

    var cfg = window.dsbShipbar || {};
    var threshold = parseFloat(cfg.threshold) || 0;
    if (threshold <= 0) return;

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
        var m = tmpl.match(/\{(?:amount|precio|price)\}/);
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

    /* ── Mini carrito por bloques ─────────────────────────────────────────── */

    // Mismo marcado que dsb_render_shipping_bar(), construido nodo a nodo.
    function buildBar() {
        var wrap = document.createElement('div');
        wrap.className = 'dsb-shipbar-wrap dsb-shipbar-auto';

        var bar = document.createElement('div');
        bar.className = 'dsb-shipbar';
        bar.style.setProperty('--dsb-shipbar-fill',  cfg.barColor   || '#4caf50');
        bar.style.setProperty('--dsb-shipbar-track', cfg.trackColor || '#e9e9f0');
        bar.style.setProperty('--dsb-shipbar-text',  cfg.textColor  || '#333333');

        var msg = document.createElement('p');
        msg.className = 'dsb-shipbar-msg';

        var track = document.createElement('div');
        track.className = 'dsb-shipbar-track';
        track.setAttribute('role', 'progressbar');
        track.setAttribute('aria-valuemin', '0');
        track.setAttribute('aria-valuemax', '100');
        track.setAttribute('aria-valuenow', '0');

        var fill = document.createElement('div');
        fill.className = 'dsb-shipbar-fill';
        fill.style.width = '0%';

        track.appendChild(fill);
        bar.appendChild(msg);
        bar.appendChild(track);
        wrap.appendChild(bar);
        return wrap;
    }

    // El cajón se monta y se desmonta con React; si se lleva la barra por
    // delante, el observador la vuelve a poner en el siguiente ciclo.
    function injectMiniCart() {
        if (!cfg.miniCart) return false;
        var footers = document.querySelectorAll(
            '.wc-block-mini-cart__footer, .wp-block-woocommerce-mini-cart-footer-block'
        );
        var added = false;
        for (var i = 0; i < footers.length; i++) {
            if (footers[i].querySelector('.dsb-shipbar-wrap')) continue;
            footers[i].insertBefore(buildBar(), footers[i].firstChild);
            added = true;
        }
        return added;
    }

    /* ── Lectura del carrito ──────────────────────────────────────────────── */

    var last = null;
    function readCart() {
        var store = window.wp && window.wp.data && window.wp.data.select('wc/store/cart');
        if (!store || typeof store.getCartTotals !== 'function') return null;
        var t = store.getCartTotals();
        if (!t) return null;
        var minor  = Math.pow(10, (t.currency_minor_unit != null ? t.currency_minor_unit : 2));
        var amount = parseInt(t.total_items || 0, 10) / minor;
        if (cfg.ignoreCoupons) amount += parseInt(t.total_discount || 0, 10) / minor;
        return { amount: amount, totals: t };
    }

    function update(force) {
        var cart = readCart();
        if (!cart) return;
        if (!force && cart.amount === last) return; // sin cambios: no repintar
        last = cart.amount;
        paint(cart.amount, cart.totals);
    }

    /* ── Arranque ─────────────────────────────────────────────────────────── */

    var scheduled = false;
    function onDomChange() {
        if (scheduled) return;
        scheduled = true;
        window.requestAnimationFrame(function () {
            scheduled = false;
            if (injectMiniCart()) update(true); // barra recién puesta: pintarla ya
        });
    }

    var subscribed = false;
    function subscribe() {
        if (subscribed || !window.wp || !window.wp.data) return false;
        subscribed = true;
        window.wp.data.subscribe(function () { update(false); });
        update(true);
        return true;
    }

    function init() {
        injectMiniCart();
        // El cajón del mini carrito carga sus scripts al abrirse, así que
        // wp.data puede no existir todavía al cargar la página.
        if (!subscribe()) {
            var tries = 0;
            var iv = setInterval(function () {
                if (subscribe() || ++tries > 60) clearInterval(iv);
            }, 250);
        }
        if (cfg.miniCart && window.MutationObserver) {
            new MutationObserver(onDomChange).observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
