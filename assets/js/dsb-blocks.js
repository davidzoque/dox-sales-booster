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
            placeholder: __('(use global value)', 'dox-sales-booster'),
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
            placeholder: __('(use global text)', 'dox-sales-booster'),
            onChange: function (v) {
                onChange(v === '' ? undefined : v);
            }
        });
    }

    function makeEdit(blockName, fieldsFn, note) {
        return function (props) {
            var children = [
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, { title: __('Settings', 'dox-sales-booster'), initialOpen: true },
                        fieldsFn(props),
                        el('p', { key: 'hint', style: { fontSize: '11px', color: '#888' } },
                            __('Empty fields use the values from the Sales Booster panel.', 'dox-sales-booster'))
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
        title: '👁️ ' + __('People viewing (Sales Booster)', 'dox-sales-booster'),
        description: __('Counter of people viewing this product.', 'dox-sales-booster'),
        icon: 'visibility',
        category: 'widgets',
        keywords: [ 'dox', 'sales booster', __('viewing', 'dox-sales-booster'), __('urgency', 'dox-sales-booster') ],
        attributes: {
            min:        { type: 'number' },
            max:        { type: 'number' },
            text:       { type: 'string' },
            product_id: { type: 'number' }
        },
        edit: makeEdit('dox-sales-booster/viewing', function (props) {
            var a = props.attributes, set = props.setAttributes;
            return [
                numField('min', __('Minimum people', 'dox-sales-booster'), a.min, function (v) { set({ min: v }); }),
                numField('max', __('Maximum people', 'dox-sales-booster'), a.max, function (v) { set({ max: v }); }),
                textField('text', __('Text', 'dox-sales-booster'), a.text, function (v) { set({ text: v }); }),
                numField('product_id', __('Product ID (empty = current product)', 'dox-sales-booster'), a.product_id, function (v) { set({ product_id: v }); })
            ];
        }),
        save: function () { return null; }
    });

    /* 🔥 Ventas recientes */
    registerBlockType('dox-sales-booster/sales', {
        title: '🔥 ' + __('Recent sales (Sales Booster)', 'dox-sales-booster'),
        description: __('Units sold within a period of time.', 'dox-sales-booster'),
        icon: 'chart-line',
        category: 'widgets',
        keywords: [ 'dox', 'sales booster', __('sales', 'dox-sales-booster'), __('sold', 'dox-sales-booster') ],
        attributes: {
            min:        { type: 'number' },
            max:        { type: 'number' },
            text:       { type: 'string' },
            timeframe:  { type: 'number' },
            period:     { type: 'string' },
            product_id: { type: 'number' }
        },
        edit: makeEdit('dox-sales-booster/sales', function (props) {
            var a = props.attributes, set = props.setAttributes;
            return [
                numField('min', __('Minimum sales', 'dox-sales-booster'), a.min, function (v) { set({ min: v }); }),
                numField('max', __('Maximum sales', 'dox-sales-booster'), a.max, function (v) { set({ max: v }); }),
                textField('text', __('Text', 'dox-sales-booster'), a.text, function (v) { set({ text: v }); }),
                numField('timeframe', __('Time quantity', 'dox-sales-booster'), a.timeframe, function (v) { set({ timeframe: v }); }),
                el(SelectControl, {
                    key: 'period',
                    label: __('Period', 'dox-sales-booster'),
                    value: a.period || '',
                    options: [
                        { label: __('(use global value)', 'dox-sales-booster'), value: '' },
                        { label: __('Minutes', 'dox-sales-booster'), value: 'minutes' },
                        { label: __('Hours', 'dox-sales-booster'), value: 'hours' },
                        { label: __('Days', 'dox-sales-booster'), value: 'days' },
                        { label: __('Weeks', 'dox-sales-booster'), value: 'weeks' }
                    ],
                    onChange: function (v) { set({ period: v === '' ? undefined : v }); }
                }),
                numField('product_id', __('Product ID (empty = current product)', 'dox-sales-booster'), a.product_id, function (v) { set({ product_id: v }); })
            ];
        }),
        save: function () { return null; }
    });

    /* 🚚 Barra de envío gratis */
    registerBlockType('dox-sales-booster/shipbar', {
        title: '🚚 ' + __('Free shipping bar (Sales Booster)', 'dox-sales-booster'),
        description: __('Progress bar toward free shipping based on the cart total.', 'dox-sales-booster'),
        icon: 'car',
        category: 'widgets',
        keywords: [ 'dox', 'sales booster', __('shipping', 'dox-sales-booster'), __('free', 'dox-sales-booster'), __('progress', 'dox-sales-booster') ],
        attributes: {
            threshold:    { type: 'number' },
            text:         { type: 'string' },
            success_text: { type: 'string' }
        },
        edit: makeEdit('dox-sales-booster/shipbar', function (props) {
            var a = props.attributes, set = props.setAttributes;
            return [
                numField('threshold', __('Free shipping amount (empty = panel source)', 'dox-sales-booster'), a.threshold, function (v) { set({ threshold: v }); }),
                textField('text', __('Progress text (variable {amount})', 'dox-sales-booster'), a.text, function (v) { set({ text: v }); }),
                textField('success_text', __('Success text', 'dox-sales-booster'), a.success_text, function (v) { set({ success_text: v }); })
            ];
        }, __('The bar uses the real cart of the visitor; in the editor the preview may show an empty cart.', 'dox-sales-booster')),
        save: function () { return null; }
    });

    /* ⚡ Stock bajo (datos reales) */
    registerBlockType('dox-sales-booster/stock', {
        title: '⚡ ' + __('Low stock (Sales Booster)', 'dox-sales-booster'),
        description: __('Urgency based on the REAL WooCommerce inventory: it only appears when few units are left.', 'dox-sales-booster'),
        icon: 'warning',
        category: 'widgets',
        keywords: [ 'dox', 'sales booster', 'stock', __('inventory', 'dox-sales-booster'), __('urgency', 'dox-sales-booster') ],
        attributes: {
            threshold:  { type: 'number' },
            text:       { type: 'string' },
            product_id: { type: 'number' }
        },
        edit: makeEdit('dox-sales-booster/stock', function (props) {
            var a = props.attributes, set = props.setAttributes;
            return [
                numField('threshold', __('Units threshold', 'dox-sales-booster'), a.threshold, function (v) { set({ threshold: v }); }),
                textField('text', __('Text (variable {stock})', 'dox-sales-booster'), a.text, function (v) { set({ text: v }); }),
                numField('product_id', __('Product ID (empty = current product)', 'dox-sales-booster'), a.product_id, function (v) { set({ product_id: v }); })
            ];
        }, __('Shown on product pages when the real stock is below the threshold. Outside a product page the preview may be empty.', 'dox-sales-booster')),
        save: function () { return null; }
    });

}(window.wp));
