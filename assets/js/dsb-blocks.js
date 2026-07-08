/**
 * Dox Sales Booster — Bloques de Gutenberg (editor)
 * ES5 sin build step. La vista previa usa ServerSideRender, así que el markup
 * es idéntico al del frontend.
 */
(function (wp) {
    'use strict';

    if (!wp || !wp.blocks || !wp.element) return;

    var el                = wp.element.createElement;
    var Fragment          = wp.element.Fragment;
    var __                = wp.i18n.__;
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = (wp.blockEditor || wp.editor).InspectorControls;
    var PanelBody         = wp.components.PanelBody;
    var TextControl       = wp.components.TextControl;
    var SelectControl     = wp.components.SelectControl;
    var ServerSideRender  = wp.serverSideRender;

    function numField(key, label, value, onChange) {
        return el(TextControl, {
            key: key,
            label: label,
            type: 'number',
            value: (value === undefined || value === null) ? '' : value,
            placeholder: __('(usar valor global)', 'dox-sales-booster'),
            onChange: function (v) {
                var n = parseInt(v, 10);
                onChange(isNaN(n) ? undefined : n);
            }
        });
    }

    function textField(key, label, value, onChange) {
        return el(TextControl, {
            key: key,
            label: label,
            value: value || '',
            placeholder: __('(usar texto global)', 'dox-sales-booster'),
            onChange: function (v) {
                onChange(v === '' ? undefined : v);
            }
        });
    }

    function makeEdit(blockName, fieldsFn, note) {
        return function (props) {
            var children = [
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, { title: __('Ajustes', 'dox-sales-booster'), initialOpen: true },
                        fieldsFn(props),
                        el('p', { key: 'hint', style: { fontSize: '11px', color: '#888' } },
                            __('Los campos vacíos usan los valores del panel Sales Booster.', 'dox-sales-booster'))
                    )
                )
            ];
            if (note) {
                children.push(el('p', {
                    key: 'note',
                    style: { fontSize: '11px', color: '#888', fontStyle: 'italic', margin: '0 0 6px' }
                }, note));
            }
            if (ServerSideRender) {
                children.push(el(ServerSideRender, { key: 'ssr', block: blockName, attributes: props.attributes }));
            }
            return el(Fragment, {}, children);
        };
    }

    /* 👁️ Personas viendo */
    registerBlockType('dox-sales-booster/viewing', {
        title: '👁️ ' + __('Personas viendo (Sales Booster)', 'dox-sales-booster'),
        description: __('Contador de personas viendo este producto.', 'dox-sales-booster'),
        icon: 'visibility',
        category: 'widgets',
        keywords: [ 'dox', 'sales booster', 'viendo', 'urgencia' ],
        attributes: {
            min:  { type: 'number' },
            max:  { type: 'number' },
            text: { type: 'string' }
        },
        edit: makeEdit('dox-sales-booster/viewing', function (props) {
            var a = props.attributes, set = props.setAttributes;
            return [
                numField('min', __('Mínimo personas', 'dox-sales-booster'), a.min, function (v) { set({ min: v }); }),
                numField('max', __('Máximo personas', 'dox-sales-booster'), a.max, function (v) { set({ max: v }); }),
                textField('text', __('Texto', 'dox-sales-booster'), a.text, function (v) { set({ text: v }); })
            ];
        }),
        save: function () { return null; }
    });

    /* 🔥 Ventas recientes */
    registerBlockType('dox-sales-booster/sales', {
        title: '🔥 ' + __('Ventas recientes (Sales Booster)', 'dox-sales-booster'),
        description: __('Unidades vendidas en un período de tiempo.', 'dox-sales-booster'),
        icon: 'chart-line',
        category: 'widgets',
        keywords: [ 'dox', 'sales booster', 'ventas', 'vendidos' ],
        attributes: {
            min:       { type: 'number' },
            max:       { type: 'number' },
            text:      { type: 'string' },
            timeframe: { type: 'number' },
            period:    { type: 'string' }
        },
        edit: makeEdit('dox-sales-booster/sales', function (props) {
            var a = props.attributes, set = props.setAttributes;
            return [
                numField('min', __('Mínimo ventas', 'dox-sales-booster'), a.min, function (v) { set({ min: v }); }),
                numField('max', __('Máximo ventas', 'dox-sales-booster'), a.max, function (v) { set({ max: v }); }),
                textField('text', __('Texto', 'dox-sales-booster'), a.text, function (v) { set({ text: v }); }),
                numField('timeframe', __('Cantidad de tiempo', 'dox-sales-booster'), a.timeframe, function (v) { set({ timeframe: v }); }),
                el(SelectControl, {
                    key: 'period',
                    label: __('Período', 'dox-sales-booster'),
                    value: a.period || '',
                    options: [
                        { label: __('(usar valor global)', 'dox-sales-booster'), value: '' },
                        { label: __('Minutos', 'dox-sales-booster'), value: 'minutos' },
                        { label: __('Horas', 'dox-sales-booster'), value: 'horas' },
                        { label: __('Días', 'dox-sales-booster'), value: 'días' },
                        { label: __('Semanas', 'dox-sales-booster'), value: 'semanas' }
                    ],
                    onChange: function (v) { set({ period: v === '' ? undefined : v }); }
                })
            ];
        }),
        save: function () { return null; }
    });

    /* 🚚 Barra de envío gratis */
    registerBlockType('dox-sales-booster/shipbar', {
        title: '🚚 ' + __('Barra de envío gratis (Sales Booster)', 'dox-sales-booster'),
        description: __('Barra de progreso hacia el envío gratis según el total del carrito.', 'dox-sales-booster'),
        icon: 'car',
        category: 'widgets',
        keywords: [ 'dox', 'sales booster', 'envío', 'gratis', 'progreso' ],
        attributes: {
            threshold:    { type: 'number' },
            text:         { type: 'string' },
            success_text: { type: 'string' }
        },
        edit: makeEdit('dox-sales-booster/shipbar', function (props) {
            var a = props.attributes, set = props.setAttributes;
            return [
                numField('threshold', __('Monto para envío gratis (vacío = fuente del panel)', 'dox-sales-booster'), a.threshold, function (v) { set({ threshold: v }); }),
                textField('text', __('Texto de progreso (variable {precio})', 'dox-sales-booster'), a.text, function (v) { set({ text: v }); }),
                textField('success_text', __('Texto de éxito', 'dox-sales-booster'), a.success_text, function (v) { set({ success_text: v }); })
            ];
        }, __('La barra usa el carrito real del visitante; en el editor la vista previa puede mostrar el carrito vacío.', 'dox-sales-booster')),
        save: function () { return null; }
    });

    /* ⚡ Stock bajo (datos reales) */
    registerBlockType('dox-sales-booster/stock', {
        title: '⚡ ' + __('Stock bajo (Sales Booster)', 'dox-sales-booster'),
        description: __('Urgencia con el inventario REAL de WooCommerce: solo aparece si quedan pocas unidades.', 'dox-sales-booster'),
        icon: 'warning',
        category: 'widgets',
        keywords: [ 'dox', 'sales booster', 'stock', 'inventario', 'urgencia' ],
        attributes: {
            threshold:  { type: 'number' },
            text:       { type: 'string' },
            product_id: { type: 'number' }
        },
        edit: makeEdit('dox-sales-booster/stock', function (props) {
            var a = props.attributes, set = props.setAttributes;
            return [
                numField('threshold', __('Umbral de unidades', 'dox-sales-booster'), a.threshold, function (v) { set({ threshold: v }); }),
                textField('text', __('Texto (variable {stock})', 'dox-sales-booster'), a.text, function (v) { set({ text: v }); }),
                numField('product_id', __('ID de producto (vacío = producto actual)', 'dox-sales-booster'), a.product_id, function (v) { set({ product_id: v }); })
            ];
        }, __('Se muestra en páginas de producto cuando el stock real está por debajo del umbral. Fuera de una página de producto la vista previa puede quedar vacía.', 'dox-sales-booster')),
        save: function () { return null; }
    });

}(window.wp));
