/**
 * Dox Sales Booster — Frontend JS
 * Popup de compras y contador de "personas viendo".
 * Sin dependencias (vanilla JS). Textos visibles vía dsbConfig.i18n.
 */
(function () {
    'use strict';

    var cfg  = window.dsbConfig || {};
    var I18N = cfg.i18n || {};

    /* ── Helpers ─────────────────────────────────────────────────────────── */

    function randInt(min, max) {
        min = parseInt(min, 10); max = parseInt(max, 10);
        if (isNaN(min)) min = 0;
        if (isNaN(max) || max < min) max = min;
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    function randItem(arr) {
        return arr[Math.floor(Math.random() * arr.length)];
    }

    function clamp(n, min, max) {
        return Math.min(max, Math.max(min, n));
    }

    // Escapa caracteres HTML para evitar XSS al construir HTML por string
    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function t(key, fallback) {
        return I18N[key] || fallback;
    }

    function minutesText(mins) {
        return mins < 2
            ? t('just_now', 'hace un momento')
            : t('mins_ago', 'hace %d minutos').replace('%d', mins);
    }

    // "hace X" real a partir del timestamp del pedido (modo de datos reales)
    function timeAgoReal(ts) {
        var secs = Math.max(0, Math.floor(Date.now() / 1000 - ts));
        var mins = Math.floor(secs / 60);
        if (mins < 60) return minutesText(mins);
        var hours = Math.floor(mins / 60);
        if (hours < 24) return t('hours_ago', 'hace %d horas').replace('%d', hours);
        return t('days_ago', 'hace %d días').replace('%d', Math.floor(hours / 24));
    }

    function timeAgoSimulated() {
        return minutesText(randInt(cfg.popup_ago_min || 1, cfg.popup_ago_max || 59));
    }

    /* ── 1. Personas viendo: fluctuación suave cada N minutos ───────────── */

    function initLiveViewing() {
        var els = document.querySelectorAll('.dsb-live-viewing');
        if (!els.length) return;

        var interval = (parseInt(cfg.viewing_interval, 10) || 2) * 60 * 1000;

        setInterval(function () {
            els.forEach(function (el) {
                var min  = parseInt(el.getAttribute('data-min'), 10) || 3;
                var max  = parseInt(el.getAttribute('data-max'), 10) || 12;
                var span = el.querySelector('.dsb-viewing-count');
                if (!span) return;
                var cur = parseInt(span.textContent, 10);
                if (isNaN(cur)) cur = randInt(min, max);
                // Variación gradual (±1-2): más creíble que un salto aleatorio
                var delta = (Math.random() < 0.5 ? -1 : 1) * randInt(1, 2);
                var next  = clamp(cur + delta, min, max);
                if (next === cur) next = clamp(cur - delta, min, max);
                span.textContent = next;
            });
        }, interval);
    }

    /* ── 2. Popup de compra reciente ─────────────────────────────────────── */

    var SILENCE_KEY = 'dsbSilenceUntil';

    function silenceActive() {
        try {
            var until = parseInt(sessionStorage.getItem(SILENCE_KEY) || '0', 10);
            return until > 0 && Date.now() < until;
        } catch (e) { return false; }
    }

    function setSilence(minutes) {
        if (!minutes || minutes <= 0) return;
        try {
            sessionStorage.setItem(SILENCE_KEY, String(Date.now() + minutes * 60000));
        } catch (e) { /* sessionStorage no disponible */ }
    }

    function truncate(str, maxLen) {
        if (!maxLen || maxLen <= 0) return str;
        return str.length > maxLen ? str.substring(0, maxLen) + '…' : str;
    }

    function buildPopupHTML(product, locations, names) {
        var prefix   = cfg.popup_prefix || '🛍️ Alguien ha comprado';
        var linkText = cfg.popup_link_text || 'Ver producto';
        var maxChars = cfg.popup_title_maxchars || 0;
        var imgSize  = parseInt(cfg.popup_img_size, 10) || 75;

        // {name} → nombre aleatorio de la lista (o "Alguien" si está vacía)
        if (prefix.indexOf('{name}') !== -1) {
            var name = (names && names.length) ? randItem(names) : t('someone', 'Alguien');
            prefix = prefix.split('{name}').join(name);
        }

        // Truncar el texto completo (prefijo + nombre) y extraer la parte del
        // nombre para envolverla en el enlace
        var sep           = prefix + ' ';
        var truncatedFull = truncate(sep + product.title, maxChars);
        var nameDisplay   = truncatedFull.indexOf(sep) === 0
            ? truncatedFull.slice(sep.length)
            : truncatedFull;

        // Meta: tiempo real (pedidos reales) o simulado + ciudad
        var ago  = product.ts ? timeAgoReal(product.ts) : timeAgoSimulated();
        var city = product.city || (locations.length ? randItem(locations) : '');
        var meta = ago + (city ? ' · ' + city : '');

        var safeImg = escHtml(product.image);
        var safeUrl = escHtml(product.url);
        var safeAlt = escHtml(product.title);

        var priceHtml = (cfg.popup_show_price && product.price)
            ? '<span class="dsb-popup-price">' + escHtml(product.price) + '</span>'
            : '';

        return (
            '<a class="dsb-popup-img-link" href="' + safeUrl + '">' +
                '<img class="dsb-popup-img" src="' + safeImg + '" alt="' + safeAlt + '"' +
                    ' width="' + imgSize + '" height="' + imgSize + '" loading="lazy" />' +
            '</a>' +
            '<div class="dsb-popup-body">' +
                '<span class="dsb-popup-title">' +
                    escHtml(prefix) + ' <a href="' + safeUrl + '">' + escHtml(nameDisplay) + '</a>' +
                '</span>' +
                priceHtml +
                '<span class="dsb-popup-meta">' + escHtml(meta) + '</span>' +
                '<a class="dsb-popup-link" href="' + safeUrl + '">' + escHtml(linkText) + '</a>' +
            '</div>'
        );
    }

    function hidePopup(popup) {
        if (popup.style.display === 'none') return;
        popup.classList.add('dsb-animate-out');
        setTimeout(function () {
            popup.classList.remove('dsb-animate-out');
            popup.style.display = 'none';
        }, 380);
    }

    function initPopup() {
        if (!cfg.popup_enabled) return;

        var products = cfg.popup_products || [];
        if (!products.length) return;

        var popup = document.getElementById('dsb-popup');
        if (!popup) return;

        // El visitante cerró el popup hace poco → respetar el silencio
        if (silenceActive()) return;

        var locations  = cfg.popup_locations || [];
        var names      = cfg.popup_names || [];
        var intervalMs = (parseInt(cfg.popup_interval, 10) || 25) * 1000;
        var displayMs  = (parseInt(cfg.popup_display_seconds, 10) || 7) * 1000;
        // Un popup nunca vive más que el intervalo: evita que el timer de
        // ocultado de un popup recorte la vida del siguiente
        displayMs = Math.min(displayMs, Math.max(1000, intervalMs - 400));

        var maxPerPage = parseInt(cfg.popup_max_per_page, 10) || 0;
        var inner      = popup.querySelector('.dsb-popup-inner');

        var st = { hideTimer: null, nextTimer: null, shown: 0, lastIdx: -1, stopped: false };

        // Evita repetir el mismo producto dos veces seguidas
        function pickIdx() {
            if (products.length < 2) return 0;
            var i;
            do { i = Math.floor(Math.random() * products.length); } while (i === st.lastIdx);
            return i;
        }

        function cycle() {
            if (st.stopped) return;
            if (maxPerPage > 0 && st.shown >= maxPerPage) return;

            // Pestaña en segundo plano: no mostrar nada, reintentar en el
            // siguiente ciclo
            if (!document.hidden) {
                st.lastIdx = pickIdx();
                inner.innerHTML = buildPopupHTML(products[st.lastIdx], locations, names);

                popup.classList.remove('dsb-animate', 'dsb-animate-out');
                void popup.offsetWidth; // fuerza reflow para reiniciar la animación
                popup.style.display = 'block';
                popup.classList.add('dsb-animate');
                st.shown++;

                clearTimeout(st.hideTimer);
                st.hideTimer = setTimeout(function () { hidePopup(popup); }, displayMs);
            }

            st.nextTimer = setTimeout(cycle, intervalMs);
        }

        // Cierre manual (X o Escape): detiene el ciclo y silencia la sesión
        function stop() {
            st.stopped = true;
            clearTimeout(st.hideTimer);
            clearTimeout(st.nextTimer);
            hidePopup(popup);
            setSilence(parseInt(cfg.popup_close_silence, 10) || 0);
        }

        var closeBtn = popup.querySelector('.dsb-popup-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                stop();
            });
        }
        document.addEventListener('keydown', function (e) {
            if ((e.key === 'Escape' || e.key === 'Esc') && popup.style.display !== 'none') {
                stop();
            }
        });

        var dMin = parseInt(cfg.popup_first_delay_min, 10); if (isNaN(dMin) || dMin < 0) dMin = 3;
        var dMax = parseInt(cfg.popup_first_delay_max, 10); if (isNaN(dMax) || dMax < dMin) dMax = dMin;
        setTimeout(cycle, randInt(dMin * 1000, dMax * 1000));
    }

    /* ── Init ────────────────────────────────────────────────────────────── */

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initLiveViewing();
            initPopup();
        });
    } else {
        initLiveViewing();
        initPopup();
    }

}());
