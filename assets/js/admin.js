/* =====================================================================
   SH SERVICIOS · JavaScript del panel administrativo
   ===================================================================== */

(function () {
    'use strict';

    const SHS = window.SHS || {};
    const $   = (sel, ctx = document) => ctx.querySelector(sel);
    const $$  = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

    const money = (value) => '$' + Number(value || 0).toLocaleString('es-AR', {
        minimumFractionDigits: 2, maximumFractionDigits: 2
    });

    const toNumber = (value) => {
        if (typeof value === 'number') { return value; }
        const raw = String(value || '').replace(/\s|\$/g, '');
        // Acepta 1.234.567,89 y 1234567.89
        if (raw.lastIndexOf(',') > raw.lastIndexOf('.')) {
            return parseFloat(raw.replace(/\./g, '').replace(',', '.')) || 0;
        }
        return parseFloat(raw.replace(/,/g, '')) || 0;
    };

    /* -----------------------------------------------------------------
       Barra lateral
       ----------------------------------------------------------------- */
    (function sidebar() {
        const bar      = $('#adminSidebar');
        const toggle   = $('#sidebarToggle');
        const close    = $('#sidebarClose');
        const backdrop = $('#adminBackdrop');
        if (!bar || !toggle) { return; }

        const open  = () => { bar.classList.add('is-open'); backdrop?.classList.add('is-visible'); };
        const shut  = () => { bar.classList.remove('is-open'); backdrop?.classList.remove('is-visible'); };

        toggle.addEventListener('click', open);
        close?.addEventListener('click', shut);
        backdrop?.addEventListener('click', shut);
        document.addEventListener('keydown', e => { if (e.key === 'Escape') { shut(); } });
    })();

    /* -----------------------------------------------------------------
       Formulario de producto con pestañas (maquinaria / repuestos)
       ----------------------------------------------------------------- */
    (function productFormTabs() {
        const wrap = $('.pform');
        if (!wrap) { return; }

        const tabs   = $$('.form-tab', wrap);
        const panels = $$('.form-tabpanel', wrap);
        if (!tabs.length) { return; }

        const storeKey = 'pform-tab:' + location.pathname;

        function show(name, save) {
            tabs.forEach(t => {
                const on = t.dataset.tab === name;
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            panels.forEach(p => { p.hidden = p.dataset.panel !== name; });
            if (save) {
                try { sessionStorage.setItem(storeKey, name); } catch (e) { /* private mode */ }
                if (history.replaceState) { history.replaceState(null, '', '#' + name); }
            }
        }

        tabs.forEach(t => t.addEventListener('click', () => show(t.dataset.tab, true)));

        // Si al guardar hay un campo obligatorio vacío en una pestaña oculta,
        // se abre esa pestaña para que el navegador pueda señalarlo.
        const form = wrap.closest('form') || $('#' + (wrap.dataset.form || ''));
        form?.addEventListener('invalid', e => {
            const panel = e.target.closest('.form-tabpanel');
            if (panel && panel.hidden) { show(panel.dataset.panel, true); }
        }, true);

        // Pestaña inicial: #hash  >  última usada  >  primera
        const fromHash = location.hash.replace('#', '');
        let initial = tabs[0].dataset.tab;
        if (tabs.some(t => t.dataset.tab === fromHash)) {
            initial = fromHash;
        } else {
            try {
                const saved = sessionStorage.getItem(storeKey);
                if (saved && tabs.some(t => t.dataset.tab === saved)) { initial = saved; }
            } catch (e) { /* private mode */ }
        }
        show(initial, false);
    })();

    /* -----------------------------------------------------------------
       Textarea que crece hacia abajo (como un documento)
       ----------------------------------------------------------------- */
    (function autoGrow() {
        const fields = $$('textarea[data-autogrow]');
        if (!fields.length) { return; }

        const fit = (el) => {
            el.style.height = 'auto';
            el.style.height = (el.scrollHeight + 2) + 'px';
        };

        fields.forEach(el => {
            fit(el);
            el.addEventListener('input', () => fit(el));
        });
        // Al abrir una pestaña que estaba oculta, recalcular (scrollHeight = 0 si estaba display:none)
        window.addEventListener('resize', () => fields.forEach(fit));
        document.querySelectorAll('.form-tab').forEach(t =>
            t.addEventListener('click', () => setTimeout(() => fields.forEach(fit), 0))
        );
    })();

    /* -----------------------------------------------------------------
       Cálculo de precio en vivo (costo → ganancia → precio final)
       ----------------------------------------------------------------- */
    (function priceCalculator() {
        const box = $('#priceCalc');
        if (!box) { return; }

        const cost   = $('#cost_price');
        const profit = $('#profit_percent');
        const final  = $('#final_price');

        const outProfitAmount = $('#outProfitAmount');
        const outFinal        = $('#outFinalPrice');
        const outMargin       = $('#outMargin');

        let lastEdited = 'profit';

        function recalc(initial) {
            const c = toNumber(cost?.value);

            // Al abrir el formulario: si el producto ya tiene un precio final
            // cargado, se respeta (se deduce la ganancia hacia atrás) y NUNCA
            // se pisa con 0 aunque el costo sea 0.
            if (initial && toNumber(final?.value) > 0) {
                lastEdited = 'final';
            }

            if (lastEdited === 'final') {
                const f = toNumber(final?.value);
                const amount = f - c;
                if (profit && c > 0) { profit.value = ((amount / c) * 100).toFixed(2); }
                paint(c, amount, f);
                return;
            }

            const p      = toNumber(profit?.value);
            const amount = c * (p / 100);
            const f      = c + amount;

            if (final && !initial) { final.value = f.toFixed(2); }
            paint(c, amount, f);
        }

        function paint(cost, amount, finalPrice) {
            if (outProfitAmount) { outProfitAmount.textContent = money(amount); }
            if (outFinal)        { outFinal.textContent = money(finalPrice); }
            if (outMargin) {
                outMargin.textContent = finalPrice > 0
                    ? (((finalPrice - cost) / finalPrice) * 100).toFixed(1) + '%'
                    : '—';
            }
        }

        cost?.addEventListener('input', () => { recalc(); });
        profit?.addEventListener('input', () => { lastEdited = 'profit'; recalc(); });
        final?.addEventListener('input', () => { lastEdited = 'final'; recalc(); });

        recalc(true);
    })();

    /* -----------------------------------------------------------------
       Edición rápida de precios en la tabla
       ----------------------------------------------------------------- */
    $$('[data-price-form]').forEach(form => {
        form.addEventListener('submit', async e => {
            e.preventDefault();

            const btn = form.querySelector('[type="submit"]');
            btn.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': SHS.csrf || '' },
                    body: new FormData(form)
                });
                const data = await response.json();

                window.shsToast(data.message, data.ok ? 'success' : 'danger');

                if (data.ok && data.prices) {
                    // El <form> vive fuera de la tabla (los inputs se asocian con form="").
                    const anchor = document.querySelector('[form="' + form.id + '"]');
                    const row    = anchor ? anchor.closest('tr') : form.closest('tr');

                    row?.querySelectorAll('[data-price-out]').forEach(cell => {
                        const key = cell.dataset.priceOut;
                        if (data.prices[key]) { cell.textContent = data.prices[key]; }
                    });
                    row?.classList.add('table-flash');
                    setTimeout(() => row?.classList.remove('table-flash'), 1200);
                }
            } catch (err) {
                window.shsToast('No se pudo actualizar el precio.', 'danger');
            } finally {
                btn.disabled = false;
            }
        });
    });

    /* -----------------------------------------------------------------
       Cotización: ítems dinámicos
       ----------------------------------------------------------------- */
    (function quoteBuilder() {
        const table = $('#quoteItems');
        if (!table) { return; }

        const template = $('#quoteItemTemplate');
        const addBtn   = $('#quoteAddItem');
        const catalog  = window.QUOTE_CATALOG || [];

        function rowIndex() {
            return Date.now() + Math.floor(Math.random() * 1000);
        }

        function addRow(product) {
            const index = rowIndex();
            const html  = template.innerHTML.replace(/__INDEX__/g, index);

            const wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            const row = wrapper.firstElementChild;

            table.appendChild(row);

            if (product) {
                row.querySelector('[name$="[product_id]"]').value  = product.id;
                row.querySelector('[name$="[description]"]').value = product.name;
                row.querySelector('[name$="[code]"]').value        = product.code;
                row.querySelector('[name$="[unit_price]"]').value  = product.price;
                row.querySelector('[name$="[item_type]"]').value   = product.type;
            }

            recalcAll();
            return row;
        }

        function recalcRow(row) {
            const qty      = toNumber(row.querySelector('[name$="[quantity]"]')?.value) || 0;
            const price    = toNumber(row.querySelector('[name$="[unit_price]"]')?.value) || 0;
            const discount = toNumber(row.querySelector('[name$="[discount_percent]"]')?.value) || 0;
            const total    = qty * price * (1 - discount / 100);

            const out = row.querySelector('[data-line-total]');
            if (out) {
                out.textContent = money(total);
                out.dataset.value = total;
            }

            return total;
        }

        function recalcAll() {
            let subtotal = 0;
            $$('.quote-line', table).forEach(row => { subtotal += recalcRow(row); });

            const discountPercent = toNumber($('#discount_percent')?.value);
            const discountAmount  = toNumber($('#discount_amount')?.value) ||
                                    (subtotal * discountPercent / 100);
            const shipping = toNumber($('#shipping_cost')?.value);
            const other    = toNumber($('#other_costs')?.value);

            const total = Math.max(0, subtotal - Math.min(discountAmount, subtotal) + shipping + other);

            const set = (id, value) => { const el = $(id); if (el) { el.textContent = money(value); } };
            set('#sumSubtotal', subtotal);
            set('#sumDiscount', Math.min(discountAmount, subtotal));
            set('#sumShipping', shipping);
            set('#sumOther', other);
            set('#sumTotal', total);

            // Vista previa de la financiación seleccionada
            const select = $('#financing_option_id');
            const box    = $('#financePreview');

            if (select && box) {
                const option = select.selectedOptions[0];
                const down   = Number(option?.dataset.down || 0);
                const fees   = Number(option?.dataset.installments || 0);
                const rate   = Number(option?.dataset.interest || 0);

                if (!option || !option.value || fees < 1) {
                    box.innerHTML = '<span class="text-muted-2">Sin plan de financiación.</span>';
                } else {
                    const downAmount = total * (down / 100);
                    const balance    = total - downAmount;
                    const interest   = balance * (rate / 100);
                    const financed   = balance + interest;
                    const fee        = financed / fees;

                    box.innerHTML =
                        '<div class="d-flex flex-wrap gap-3">' +
                        '<div><span class="text-muted-2 d-block small">Anticipo</span><strong>' + money(downAmount) + '</strong></div>' +
                        '<div><span class="text-muted-2 d-block small">Interés</span><strong>' + money(interest) + '</strong></div>' +
                        '<div><span class="text-muted-2 d-block small">' + fees + ' cuotas de</span><strong>' + money(fee) + '</strong></div>' +
                        '<div><span class="text-muted-2 d-block small">Total financiado</span><strong>' + money(downAmount + financed) + '</strong></div>' +
                        '</div>';
                }
            }
        }

        table.addEventListener('input', e => {
            if (e.target.closest('.quote-line')) { recalcAll(); }
        });

        table.addEventListener('click', e => {
            const remove = e.target.closest('[data-remove-line]');
            if (remove) {
                remove.closest('.quote-line')?.remove();
                recalcAll();
            }
        });

        ['#discount_percent', '#discount_amount', '#shipping_cost', '#other_costs', '#financing_option_id']
            .forEach(sel => $(sel)?.addEventListener('input', recalcAll));
        $('#financing_option_id')?.addEventListener('change', recalcAll);

        addBtn?.addEventListener('click', () => addRow(null));

        // Buscador del catálogo para agregar productos
        const picker = $('#quotePicker');
        const search = $('#quotePickerSearch');

        if (picker && search) {
            const render = (term) => {
                const query = term.trim().toLowerCase();
                const list  = query.length < 2
                    ? catalog.slice(0, 12)
                    : catalog.filter(p =>
                        p.name.toLowerCase().includes(query) || p.code.toLowerCase().includes(query)
                    ).slice(0, 30);

                picker.innerHTML = list.map(p =>
                    '<button type="button" class="picker__item w-100 text-start" data-add-product=\'' +
                    JSON.stringify(p).replace(/'/g, '&#39;') + '\'>' +
                    '<i class="bi ' + (p.type === 'machine' ? 'bi-truck-front-fill' : 'bi-nut-fill') + '"></i>' +
                    '<span class="flex-grow-1">' + p.name + '<br><code>' + p.code + '</code></span>' +
                    '<strong>' + money(p.price) + '</strong></button>'
                ).join('') || '<div class="p-3 text-muted-2">Sin resultados.</div>';
            };

            search.addEventListener('input', () => render(search.value));
            render('');

            picker.addEventListener('click', e => {
                const btn = e.target.closest('[data-add-product]');
                if (!btn) { return; }
                addRow(JSON.parse(btn.dataset.addProduct));
                window.shsToast('Ítem agregado a la cotización.', 'success', 2000);
            });
        }

        recalcAll();
    })();

    /* -----------------------------------------------------------------
       Selector con búsqueda (repuestos compatibles, etc.)
       ----------------------------------------------------------------- */
    $$('[data-picker-search]').forEach(input => {
        input.addEventListener('input', () => {
            const target = $(input.dataset.pickerSearch);
            if (!target) { return; }

            const term = input.value.trim().toLowerCase();

            $$('.picker__item', target).forEach(item => {
                item.classList.toggle('is-hidden', term !== '' && !item.textContent.toLowerCase().includes(term));
            });
        });
    });

    /* -----------------------------------------------------------------
       Dropzone de imágenes
       ----------------------------------------------------------------- */
    $$('.dropzone').forEach(zone => {
        const input = $('#' + zone.dataset.input);
        if (!input) { return; }

        zone.addEventListener('click', () => input.click());

        ['dragenter', 'dragover'].forEach(evt =>
            zone.addEventListener(evt, e => { e.preventDefault(); zone.classList.add('is-dragover'); })
        );
        ['dragleave', 'drop'].forEach(evt =>
            zone.addEventListener(evt, e => { e.preventDefault(); zone.classList.remove('is-dragover'); })
        );

        zone.addEventListener('drop', e => {
            input.files = e.dataTransfer.files;
            updateLabel();
        });

        input.addEventListener('change', updateLabel);

        function updateLabel() {
            const label = zone.querySelector('[data-file-label]');
            if (!label) { return; }
            label.textContent = input.files.length
                ? input.files.length + ' archivo(s) seleccionado(s)'
                : label.dataset.default || 'Arrastrá las imágenes o hacé clic para elegirlas';
        }
    });

    /* -----------------------------------------------------------------
       Pestañas de configuración
       ----------------------------------------------------------------- */
    (function settingsTabs() {
        const nav = $('.settings-nav');
        if (!nav) { return; }

        const activate = (group) => {
            $$('.settings-nav button').forEach(b => b.classList.toggle('is-active', b.dataset.group === group));
            $$('.settings-panel').forEach(p => p.classList.toggle('is-active', p.dataset.group === group));
            try { localStorage.setItem('shs_settings_tab', group); } catch (e) {}
        };

        nav.addEventListener('click', e => {
            const btn = e.target.closest('button[data-group]');
            if (btn) { activate(btn.dataset.group); }
        });

        let saved = null;
        try { saved = localStorage.getItem('shs_settings_tab'); } catch (e) {}

        const first = $('.settings-nav button')?.dataset.group;
        activate(saved && $('[data-group="' + saved + '"]') ? saved : first);
    })();

    /* -----------------------------------------------------------------
       Gráficos (Chart.js)
       ----------------------------------------------------------------- */
    (function charts() {
        if (typeof Chart === 'undefined') { return; }

        Chart.defaults.font.family = '"Segoe UI", Roboto, Arial, sans-serif';
        Chart.defaults.color = '#6B6B6B';

        const palette = ['#F5C400', '#111111', '#6B6B6B', '#2A6FB5', '#2E9E5B', '#C6402F', '#E08A00', '#8E7CC3'];

        $$('[data-chart]').forEach(canvas => {
            let config;
            try { config = JSON.parse(canvas.dataset.chart); } catch (e) { return; }

            const type = config.type || 'line';

            const datasets = (config.datasets || [{ data: config.values, label: config.label || '' }])
                .map((ds, i) => Object.assign({
                    backgroundColor: type === 'line'
                        ? 'rgba(245,196,0,.16)'
                        : (config.multicolor ? palette : palette[i % palette.length]),
                    borderColor: type === 'line' ? '#F5C400' : palette[i % palette.length],
                    borderWidth: type === 'line' ? 3 : 0,
                    borderRadius: type === 'bar' ? 5 : 0,
                    pointBackgroundColor: '#111',
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    tension: .34,
                    fill: type === 'line'
                }, ds));

            new Chart(canvas, {
                type,
                data: { labels: config.labels || [], datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            display: config.legend !== false && (type === 'doughnut' || type === 'pie'),
                            position: 'bottom',
                            labels: { boxWidth: 12, padding: 14 }
                        },
                        tooltip: {
                            backgroundColor: '#111',
                            padding: 11,
                            titleColor: '#F5C400',
                            cornerRadius: 6,
                            displayColors: false
                        }
                    },
                    scales: (type === 'doughnut' || type === 'pie') ? {} : {
                        x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 16 } },
                        y: { beginAtZero: true, grid: { color: '#EFF1F4' }, ticks: { precision: 0 } }
                    }
                }
            });
        });
    })();

    /* -----------------------------------------------------------------
       Movimientos de stock desde la tabla
       ----------------------------------------------------------------- */
    (function stockModal() {
        const modal = $('#stockModal');
        if (!modal) { return; }

        document.addEventListener('click', e => {
            const btn = e.target.closest('[data-stock-move]');
            if (!btn) { return; }

            $('#stockProductId', modal).value    = btn.dataset.stockMove;
            $('#stockProductName', modal).textContent = btn.dataset.productName || '';
            $('#stockCurrent', modal).textContent = btn.dataset.currentStock || '0';

            const type = $('#stockType', modal);
            if (type && btn.dataset.defaultType) { type.value = btn.dataset.defaultType; }
        });
    })();

    /* -----------------------------------------------------------------
       Selección múltiple en tablas
       ----------------------------------------------------------------- */
    $$('[data-check-all]').forEach(master => {
        master.addEventListener('change', () => {
            $$(master.dataset.checkAll).forEach(cb => { cb.checked = master.checked; });
        });
    });

    /* -----------------------------------------------------------------
       Autogeneración de slug/meta en formularios
       ----------------------------------------------------------------- */
    $$('[data-mirror]').forEach(source => {
        source.addEventListener('input', () => {
            const target = $(source.dataset.mirror);
            if (target && !target.dataset.touched) { target.value = source.value; }
        });
    });

    $$('[data-mirror-target]').forEach(target => {
        target.addEventListener('input', () => { target.dataset.touched = '1'; });
    });

    /* -----------------------------------------------------------------
       Contadores de caracteres
       ----------------------------------------------------------------- */
    $$('[maxlength]').forEach(field => {
        const counter = field.parentElement.querySelector('[data-counter]');
        if (!counter) { return; }

        const paint = () => {
            counter.textContent = field.value.length + ' / ' + field.getAttribute('maxlength');
        };

        field.addEventListener('input', paint);
        paint();
    });

})();
