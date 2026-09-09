/* =====================================================================
   SH SERVICIOS · JavaScript del sitio público
   Sin dependencias más allá de Bootstrap (bundle) para modales/dropdowns.
   Recordá: acá se valida por comodidad del usuario, la seguridad real
   está en el servidor.
   ===================================================================== */

(function () {
    'use strict';

    const SHS = window.SHS || {};
    const $   = (sel, ctx = document) => ctx.querySelector(sel);
    const $$  = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

    /* -----------------------------------------------------------------
       Utilidades
       ----------------------------------------------------------------- */

    const money = (value, currency = 'ARS') => {
        const symbol = currency === 'USD' ? 'USD ' : '$';
        return symbol + Number(value || 0).toLocaleString('es-AR', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    };

    const debounce = (fn, wait = 260) => {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(null, args), wait);
        };
    };

    async function request(url, options = {}) {
        const config = Object.assign({
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': SHS.csrf || ''
            }
        }, options);

        const response = await fetch(url, config);
        const text     = await response.text();

        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Respuesta inesperada del servidor.');
        }
    }

    function postForm(url, data) {
        const body = new FormData();
        Object.entries(data).forEach(([key, value]) => body.append(key, value));
        body.append('_token', SHS.csrf || '');
        return request(url, { method: 'POST', body });
    }

    /* -----------------------------------------------------------------
       Notificaciones (toasts)
       ----------------------------------------------------------------- */
    const icons = {
        success: 'bi-check-circle-fill',
        danger:  'bi-exclamation-octagon-fill',
        warning: 'bi-exclamation-triangle-fill',
        info:    'bi-info-circle-fill'
    };

    function toast(message, type = 'info', timeout = 4600) {
        const stack = $('#toastStack');
        if (!stack) { return; }

        const el = document.createElement('div');
        el.className = 'toast-msg toast-msg--' + type;
        el.setAttribute('role', 'status');
        el.innerHTML =
            '<i class="bi ' + (icons[type] || icons.info) + ' toast-msg__icon"></i>' +
            '<div class="toast-msg__text"></div>' +
            '<button type="button" class="toast-msg__close" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>';

        el.querySelector('.toast-msg__text').textContent = message;
        stack.appendChild(el);

        const dismiss = () => {
            el.classList.add('is-leaving');
            setTimeout(() => el.remove(), 240);
        };

        el.querySelector('.toast-msg__close').addEventListener('click', dismiss);
        if (timeout) { setTimeout(dismiss, timeout); }
    }

    window.shsToast = toast;

    (SHS.flash || []).forEach(f => toast(f.message, f.type === 'danger' ? 'danger' : f.type));

    /* -----------------------------------------------------------------
       Navegación móvil
       ----------------------------------------------------------------- */
    (function navigation() {
        const toggle = $('#navToggle');
        const menu   = $('#mainMenu');
        if (!toggle || !menu) { return; }

        const backdrop = document.createElement('div');
        backdrop.className = 'overlay-backdrop overlay-backdrop--nav';
        document.body.appendChild(backdrop);

        const close = () => {
            menu.classList.remove('is-open');
            backdrop.classList.remove('is-visible');
            toggle.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        };

        toggle.addEventListener('click', () => {
            const open = menu.classList.toggle('is-open');
            backdrop.classList.toggle('is-visible', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.body.style.overflow = open ? 'hidden' : '';
        });

        backdrop.addEventListener('click', close);
        document.addEventListener('keydown', e => { if (e.key === 'Escape') { close(); } });
    })();

    /* -----------------------------------------------------------------
       Buscador colapsable de la cabecera
       ----------------------------------------------------------------- */
    (function searchToggle() {
        const box    = $('#searchBox');
        const toggle = $('#searchToggle');
        if (!box || !toggle) { return; }

        const input = $('#globalSearch', box);

        const open = () => {
            box.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
            if (input) { setTimeout(() => input.focus(), 30); }
        };
        const close = () => {
            box.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        };

        toggle.addEventListener('click', () => {
            box.classList.contains('is-open') ? close() : open();
        });

        document.addEventListener('click', e => {
            if (box.classList.contains('is-open') && !box.contains(e.target)) { close(); }
        });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') { close(); } });
    })();

    /* -----------------------------------------------------------------
       Buscador con sugerencias
       ----------------------------------------------------------------- */
    (function search() {
        const input = $('#globalSearch');
        const box   = $('#searchResults');
        if (!input || !box) { return; }

        let items = [];
        let index = -1;

        const hide = () => { box.hidden = true; index = -1; };

        const render = (data) => {
            if (!data.suggestions || data.suggestions.length === 0) {
                box.innerHTML = '<div class="suggest-empty">' +
                    '<i class="bi bi-search"></i><br>Sin resultados para <strong>' +
                    escapeHtml(data.term) + '</strong><br>' +
                    '<small>Probá con el código interno, el código OEM o el modelo de la máquina.</small></div>';
                box.hidden = false;
                items = [];
                return;
            }

            box.innerHTML = data.suggestions.map(s =>
                '<a class="suggest-item" href="' + s.url + '">' +
                (s.image
                    ? '<img class="suggest-item__thumb" src="' + s.image + '" alt="" loading="lazy">'
                    : '<span class="suggest-item__thumb"><i class="bi ' + s.icon + '"></i></span>') +
                '<span class="suggest-item__body">' +
                '<span class="suggest-item__label">' + escapeHtml(s.label) + '</span>' +
                '<span class="suggest-item__sub">' + escapeHtml(s.sub) + '</span>' +
                '</span>' +
                '<span class="suggest-item__tag">' + escapeHtml(s.type) + '</span>' +
                '</a>'
            ).join('') +
            '<a class="suggest-item" href="' + data.search_url + '">' +
            '<span class="suggest-item__thumb"><i class="bi bi-arrow-right"></i></span>' +
            '<span class="suggest-item__body"><span class="suggest-item__label">Ver todos los resultados</span></span></a>';

            items = $$('.suggest-item', box);
            box.hidden = false;
        };

        const lookup = debounce(async () => {
            const term = input.value.trim();
            if (term.length < 2) { hide(); return; }

            try {
                const data = await request(SHS.baseUrl + '/api/buscar?q=' + encodeURIComponent(term));
                render(data);
            } catch (e) { hide(); }
        }, 240);

        input.addEventListener('input', lookup);
        input.addEventListener('focus', () => { if (input.value.trim().length >= 2) { lookup(); } });

        input.addEventListener('keydown', e => {
            if (box.hidden || items.length === 0) { return; }

            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                items.forEach(i => i.classList.remove('is-active'));
                index = e.key === 'ArrowDown'
                    ? (index + 1) % items.length
                    : (index - 1 + items.length) % items.length;
                items[index].classList.add('is-active');
                items[index].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter' && index >= 0) {
                e.preventDefault();
                window.location.href = items[index].href;
            } else if (e.key === 'Escape') {
                hide();
            }
        });

        document.addEventListener('click', e => {
            if (!e.target.closest('.searchbox')) { hide(); }
        });
    })();

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    /* Favoritos: función retirada (se quitó el botón "guardar en favoritos"). */

    /* -----------------------------------------------------------------
       Comparador
       ----------------------------------------------------------------- */
    const Compare = {
        key: 'shs_compare',

        all() {
            try {
                return JSON.parse(localStorage.getItem(this.key) || '[]').map(Number);
            } catch (e) { return []; }
        },

        toggle(id) {
            id = Number(id);
            let list = this.all();
            const exists = list.includes(id);

            if (!exists && list.length >= (SHS.compareMax || 3)) {
                toast('Podés comparar hasta ' + (SHS.compareMax || 3) + ' máquinas. Quitá una para agregar otra.', 'warning');
                return false;
            }

            list = exists ? list.filter(i => i !== id) : list.concat(id);
            try { localStorage.setItem(this.key, JSON.stringify(list)); } catch (e) {}

            this.paint();
            return !exists;
        },

        clear() {
            try { localStorage.removeItem(this.key); } catch (e) {}
            this.paint();
        },

        async paint() {
            const list = this.all();
            const bar  = $('#compareBar');

            $$('[data-compare-toggle]').forEach(btn => {
                const active = list.includes(Number(btn.dataset.compareToggle));
                btn.classList.toggle('is-active', active);
                btn.title = active ? 'Quitar del comparador' : 'Agregar al comparador';
            });

            const count = $('#compareCount');
            if (count) { count.textContent = list.length; }

            const go = $('#compareGo');
            if (go) { go.href = SHS.baseUrl + '/comparar?ids=' + list.join(','); }

            if (!bar) { return; }

            if (list.length === 0) {
                bar.hidden = true;
                document.body.classList.remove('has-compare-bar');
                return;
            }

            bar.hidden = false;
            document.body.classList.add('has-compare-bar');

            try {
                const data = await request(SHS.baseUrl + '/api/comparar?ids=' + list.join(','));
                const box  = $('#compareItems');
                if (!box) { return; }

                box.innerHTML = data.products.map(p =>
                    '<div class="compare-chip">' +
                    '<img src="' + p.image + '" alt="" loading="lazy">' +
                    '<span>' + escapeHtml(p.name) + '</span>' +
                    '<button type="button" data-compare-remove="' + p.id + '" aria-label="Quitar">' +
                    '<i class="bi bi-x-lg"></i></button></div>'
                ).join('');
            } catch (e) { /* la barra sigue funcionando igual */ }
        }
    };

    window.shsCompare = Compare;

    document.addEventListener('click', e => {
        const toggle = e.target.closest('[data-compare-toggle]');
        if (toggle) {
            e.preventDefault();
            const added = Compare.toggle(toggle.dataset.compareToggle);
            if (added === true)  { toast('Agregado al comparador.', 'success', 2400); }
            if (added === false) { toast('Quitado del comparador.', 'info', 2200); }
            return;
        }

        const remove = e.target.closest('[data-compare-remove]');
        if (remove) {
            Compare.toggle(remove.dataset.compareRemove);
            return;
        }

        if (e.target.closest('#compareClear')) {
            Compare.clear();
            toast('Comparador vacío.', 'info', 2200);
        }
    });

    Compare.paint();

    /* -----------------------------------------------------------------
       Cotizador: agregar/quitar ítems
       ----------------------------------------------------------------- */
    /* Selector de cantidad (− / +) reutilizable: cualquier bloque
       [data-qty] con un [data-qty-input] entre dos botones. */
    const clampQty = (n) => Math.max(1, Math.min(999, Math.round(Number(n) || 1)));

    document.addEventListener('click', e => {
        const step = e.target.closest('[data-qty-minus], [data-qty-plus]');
        if (!step) { return; }

        const box   = step.closest('[data-qty]');
        const input = box && box.querySelector('[data-qty-input]');
        if (!input || input.disabled) { return; }

        const delta = step.hasAttribute('data-qty-plus') ? 1 : -1;
        input.value = clampQty(Number(input.value) + delta);
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    document.addEventListener('change', e => {
        const input = e.target.closest('[data-qty-input]');
        if (input) { input.value = clampQty(input.value); }
    });

    document.addEventListener('click', async e => {
        const btn = e.target.closest('[data-quote-add]');
        if (!btn) { return; }

        e.preventDefault();
        btn.disabled = true;

        const qtyInput = btn.closest('.product-actions, [data-quote-wrap]')?.querySelector('[data-qty-input]');
        const quantity = qtyInput ? clampQty(qtyInput.value) : (btn.dataset.quoteQty || 1);

        try {
            const data = await postForm(SHS.baseUrl + '/api/cotizador/agregar', {
                product_id: btn.dataset.quoteAdd,
                quantity: quantity
            });

            toast(data.message, data.ok ? 'success' : 'warning');
            $$('[data-quote-count]').forEach(el => { el.textContent = data.count; });

            // Desde la ficha del producto: llevar directo a la cotización.
            if (data.ok && btn.hasAttribute('data-quote-go')) {
                window.location.href = SHS.baseUrl + '/cotizador';
                return;
            }
        } catch (err) {
            toast('No se pudo agregar el producto.', 'danger');
        } finally {
            btn.disabled = false;
        }
    });

    /* Cambiar la cantidad de una línea ya agregada al carrito. */
    document.addEventListener('change', async e => {
        const input = e.target.closest('[data-qline-input]');
        if (!input) { return; }

        const productId = input.dataset.qlineInput;
        input.disabled = true;

        try {
            const data = await postForm(SHS.baseUrl + '/api/cotizador/cantidad', {
                product_id: productId,
                quantity: clampQty(input.value)
            });

            if (data.ok && data.line) {
                // El servidor manda la cantidad ya normalizada: así el campo
                // se autocorrige si hubo clics muy rápidos.
                input.value = clampQty(data.line.quantity);

                const total = document.querySelector('#sumTotal');
                const sub   = document.querySelector('#sumSubtotal');
                if (data.totals && total) { total.textContent = data.totals.total_fmt; }
                if (data.totals && sub)   { sub.textContent   = data.totals.subtotal_fmt; }

                const row = input.closest('.quote-item');
                const cell = row && row.querySelector('[data-line-total]');
                if (cell && data.line.line_total_fmt) { cell.textContent = data.line.line_total_fmt; }
            } else {
                location.reload();
            }
        } catch (err) {
            toast('No se pudo actualizar la cantidad.', 'danger');
            location.reload();
        } finally {
            input.disabled = false;
        }
    });

    /* Vaciar la cotización (antes era un form que dejaba ver el JSON crudo). */
    document.addEventListener('click', async e => {
        const btn = e.target.closest('[data-quote-clear]');
        if (!btn) { return; }

        e.preventDefault();
        if (!window.confirm(btn.dataset.quoteClear || '¿Vaciar la cotización?')) { return; }

        btn.disabled = true;
        try {
            await postForm(SHS.baseUrl + '/api/cotizador/vaciar', {});
            location.reload();
        } catch (err) {
            toast('No se pudo vaciar la cotización.', 'danger');
            btn.disabled = false;
        }
    });

    document.addEventListener('click', async e => {
        const btn = e.target.closest('[data-quote-remove]');
        if (!btn) { return; }

        e.preventDefault();

        try {
            const data = await postForm(SHS.baseUrl + '/api/cotizador/quitar', { product_id: btn.dataset.quoteRemove });
            $$('[data-quote-count]').forEach(el => { el.textContent = data.count; });

            const row = btn.closest('.quote-item');
            if (row) {
                row.style.transition = 'opacity .2s, transform .2s';
                row.style.opacity = '0';
                row.style.transform = 'translateX(-14px)';
                setTimeout(() => location.reload(), 220);
            } else {
                location.reload();
            }
        } catch (err) {
            toast('No se pudo quitar el ítem.', 'danger');
        }
    });

    /* -----------------------------------------------------------------
       Galería de producto
       ----------------------------------------------------------------- */
    (function gallery() {
        const main = $('#galleryMain');
        if (!main) { return; }

        const image  = main.querySelector('img');
        const thumbs = $$('.gallery__thumb');

        // Cada miniatura es una "diapositiva": foto o video.
        const slides = thumbs.map(t => {
            if (t.classList.contains('gallery__thumb--video')) {
                return { video: true, provider: t.dataset.videoProvider, ref: t.dataset.videoRef };
            }
            return { video: false, src: t.dataset.full || (t.querySelector('img') || {}).src };
        });

        let current = 0;

        const clearVideo = () => {
            const v = main.querySelector('.gallery__video');
            if (v) { v.remove(); }
            main.classList.remove('is-video');
        };

        const buildVideo = (slide) => {
            const box = document.createElement('div');
            box.className = 'gallery__video';

            if (slide.provider === 'file') {
                const vid = document.createElement('video');
                vid.src = slide.ref;
                vid.controls = true;
                vid.autoplay = true;
                vid.setAttribute('playsinline', '');
                box.appendChild(vid);
            } else {
                const src = slide.provider === 'vimeo'
                    ? 'https://player.vimeo.com/video/' + encodeURIComponent(slide.ref) + '?autoplay=1'
                    : 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(slide.ref) + '?autoplay=1&rel=0';
                const frame = document.createElement('iframe');
                frame.src = src;
                frame.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture');
                frame.setAttribute('allowfullscreen', '');
                frame.setAttribute('loading', 'lazy');
                box.appendChild(frame);
            }
            main.appendChild(box);
        };

        const show = (i) => {
            current = (i + slides.length) % slides.length;
            const slide = slides[current];

            clearVideo();

            if (slide.video) {
                image.hidden = true;
                main.classList.add('is-video');
                buildVideo(slide);
            } else {
                image.hidden = false;
                image.src = slide.src;
            }

            thumbs.forEach((t, idx) => t.classList.toggle('is-active', idx === current));
        };

        thumbs.forEach((thumb, i) => {
            thumb.addEventListener('click', () => show(i));
            // El hover sólo cambia de foto, nunca arranca un video sin querer.
            if (!slides[i].video) {
                thumb.addEventListener('mouseenter', () => { if (!main.classList.contains('is-video')) { show(i); } });
            }
        });

        // ---- Lightbox (sólo para fotos) ----
        const imageIndexes = slides.map((s, idx) => (s.video ? -1 : idx)).filter(idx => idx !== -1);

        const lightbox = document.createElement('div');
        lightbox.className = 'lightbox';
        lightbox.innerHTML =
            '<button class="lightbox__close" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>' +
            '<button class="lightbox__nav lightbox__nav--prev" aria-label="Anterior"><i class="bi bi-chevron-left"></i></button>' +
            '<img src="" alt="">' +
            '<button class="lightbox__nav lightbox__nav--next" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></button>';
        document.body.appendChild(lightbox);

        const lightboxImg = lightbox.querySelector('img');

        const openLightbox = () => {
            if (main.classList.contains('is-video')) { return; }
            lightboxImg.src = slides[current].src;
            lightbox.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        };
        const closeLightbox = () => {
            lightbox.classList.remove('is-open');
            document.body.style.overflow = '';
        };
        const stepLightbox = (dir) => {
            if (imageIndexes.length === 0) { return; }
            let pos = imageIndexes.indexOf(current);
            if (pos === -1) { pos = 0; }
            pos = (pos + dir + imageIndexes.length) % imageIndexes.length;
            show(imageIndexes[pos]);
            lightboxImg.src = slides[current].src;
        };

        main.addEventListener('click', openLightbox);
        lightbox.querySelector('.lightbox__close').addEventListener('click', closeLightbox);
        lightbox.addEventListener('click', e => { if (e.target === lightbox) { closeLightbox(); } });
        lightbox.querySelector('.lightbox__nav--prev').addEventListener('click', e => { e.stopPropagation(); stepLightbox(-1); });
        lightbox.querySelector('.lightbox__nav--next').addEventListener('click', e => { e.stopPropagation(); stepLightbox(1); });

        document.addEventListener('keydown', e => {
            if (!lightbox.classList.contains('is-open')) { return; }
            if (e.key === 'Escape')     { closeLightbox(); }
            if (e.key === 'ArrowLeft')  { stepLightbox(-1); }
            if (e.key === 'ArrowRight') { stepLightbox(1); }
        });

        // Zoom con el mouse sobre la foto principal (no sobre un video)
        main.addEventListener('mousemove', e => {
            if (main.classList.contains('is-video')) { return; }
            const rect = main.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            image.style.transformOrigin = x + '% ' + y + '%';
            image.style.transform = 'scale(1.55)';
        });
        main.addEventListener('mouseleave', () => { image.style.transform = ''; });
    })();

    /* -----------------------------------------------------------------
       Filtros del catálogo
       ----------------------------------------------------------------- */
    (function filters() {
        const form = $('#catalogFilters');
        if (!form) { return; }

        // Autoenvío al cambiar cualquier control (salvo campos de texto)
        form.addEventListener('change', e => {
            if (e.target.type !== 'text' && e.target.type !== 'number') {
                form.submit();
            }
        });

        // Filtros en móvil
        const openBtn  = $('#filtersOpen');
        const closeBtn = $('#filtersClose');
        const panel    = $('#filtersPanel');

        if (openBtn && panel) {
            const backdrop = document.createElement('div');
            backdrop.className = 'overlay-backdrop';
            document.body.appendChild(backdrop);

            const close = () => {
                panel.classList.remove('is-open');
                backdrop.classList.remove('is-visible');
                document.body.style.overflow = '';
            };

            openBtn.addEventListener('click', () => {
                panel.classList.add('is-open');
                backdrop.classList.add('is-visible');
                document.body.style.overflow = 'hidden';
            });

            backdrop.addEventListener('click', close);
            if (closeBtn) { closeBtn.addEventListener('click', close); }
        }

        // Acordeones
        $$('.filter-group__head').forEach(head => {
            head.addEventListener('click', () => {
                const expanded = head.getAttribute('aria-expanded') === 'true';
                head.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                const body = head.nextElementSibling;
                if (body) { body.style.display = expanded ? 'none' : ''; }
            });
        });
    })();

    /* -----------------------------------------------------------------
       Copiar códigos al portapapeles
       ----------------------------------------------------------------- */
    document.addEventListener('click', async e => {
        const btn = e.target.closest('[data-copy]');
        if (!btn) { return; }

        const text = btn.dataset.copy;

        try {
            await navigator.clipboard.writeText(text);
            toast('Código copiado: ' + text, 'success', 2400);
        } catch (err) {
            const input = document.createElement('input');
            input.value = text;
            document.body.appendChild(input);
            input.select();
            try { document.execCommand('copy'); toast('Código copiado.', 'success', 2200); } catch (e2) {}
            input.remove();
        }
    });

    /* -----------------------------------------------------------------
       Animación de aparición al hacer scroll
       ----------------------------------------------------------------- */
    (function reveal() {
        const items = $$('.reveal');
        if (items.length === 0) { return; }

        const show = (el) => {
            if (!el.classList.contains('is-visible')) { el.classList.add('is-visible'); }
        };

        // Sin soporte: se muestra todo de una.
        if (!('IntersectionObserver' in window)) {
            items.forEach(show);
            return;
        }

        items.forEach((item, i) => {
            item.style.transitionDelay = Math.min(i % 6, 5) * 60 + 'ms';
        });

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    show(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0, rootMargin: '0px 0px -30px 0px' });

        items.forEach(item => observer.observe(item));

        /* Red de seguridad: si el usuario hace un scroll muy rápido, el
           observer puede no llegar a procesar algún elemento. Acá se revela
           todo lo que ya pasó por la pantalla, así nunca queda contenido
           invisible. */
        let ticking = false;

        const sweep = () => {
            ticking = false;
            const limit = window.innerHeight * 0.98;

            items.forEach(item => {
                if (item.classList.contains('is-visible')) { return; }
                if (item.getBoundingClientRect().top < limit) {
                    show(item);
                    observer.unobserve(item);
                }
            });
        };

        const schedule = () => {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(sweep);
            }
        };

        window.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', schedule, { passive: true });
        window.addEventListener('load', schedule);
        schedule();
    })();

    /* -----------------------------------------------------------------
       Confirmaciones de formularios peligrosos
       ----------------------------------------------------------------- */
    document.addEventListener('submit', e => {
        const form = e.target.closest('[data-confirm]');
        if (form && !window.confirm(form.dataset.confirm)) {
            e.preventDefault();
        }
    });

    /* -----------------------------------------------------------------
       Modal de consulta rápida por producto
       ----------------------------------------------------------------- */
    (function quickInquiry() {
        const form = $('#quickInquiryForm');
        if (!form) { return; }

        form.addEventListener('submit', async e => {
            e.preventDefault();

            const button = form.querySelector('[type="submit"]');
            button.disabled = true;
            button.classList.add('is-loading');

            try {
                const data = await request(SHS.baseUrl + '/api/consulta', {
                    method: 'POST',
                    body: new FormData(form)
                });

                toast(data.message, data.ok ? 'success' : 'danger');

                if (data.ok) {
                    form.reset();
                    const modalEl = form.closest('.modal');
                    if (modalEl && window.bootstrap) {
                        bootstrap.Modal.getInstance(modalEl)?.hide();
                    }
                } else if (data.errors) {
                    Object.entries(data.errors).forEach(([field, message]) => {
                        const input = form.querySelector('[name="' + field + '"]');
                        if (input) {
                            input.classList.add('is-invalid');
                            const hint = input.parentElement.querySelector('.form-error');
                            if (hint) { hint.textContent = message; }
                        }
                    });
                }
            } catch (err) {
                toast('No pudimos enviar la consulta. Probá por WhatsApp.', 'danger');
            } finally {
                button.disabled = false;
                button.classList.remove('is-loading');
            }
        });

        form.addEventListener('input', e => e.target.classList.remove('is-invalid'));
    })();

    /* -----------------------------------------------------------------
       Cabecera compacta al hacer scroll
       ----------------------------------------------------------------- */
    (function stickyHeader() {
        const header = $('#siteHeader');
        if (!header) { return; }

        let last = 0;
        window.addEventListener('scroll', () => {
            const y = window.scrollY;
            header.classList.toggle('is-scrolled', y > 60);
            last = y;
        }, { passive: true });
    })();

    /* -----------------------------------------------------------------
       Handlers declarativos (reemplazan on*="" inline para cumplir la CSP)
         data-autosubmit             -> al cambiar, envía su <form>
         data-submit-form="idForm"   -> click envía ese form (opcional data-confirm="texto")
         data-form-action="url"      -> click: fija form.action y envía
         data-image-preview="idImg"  -> al elegir archivo, muestra la vista previa
       ----------------------------------------------------------------- */
    (function declarativeHandlers() {
        document.addEventListener('change', e => {
            const auto = e.target.closest('[data-autosubmit]');
            if (auto && auto.form) { auto.form.submit(); return; }

            const prev = e.target.closest('[data-image-preview]');
            if (prev && prev.files && prev.files[0]) {
                const img = document.getElementById(prev.dataset.imagePreview);
                if (img) { img.src = URL.createObjectURL(prev.files[0]); img.hidden = false; }
            }
        });

        document.addEventListener('click', e => {
            const sub = e.target.closest('[data-submit-form]');
            if (sub) {
                const msg = sub.dataset.confirm;
                if (msg && !window.confirm(msg)) { return; }
                document.getElementById(sub.dataset.submitForm)?.submit();
                return;
            }

            const act = e.target.closest('[data-form-action]');
            if (act) {
                const f = act.closest('form');
                if (f) { f.action = act.dataset.formAction; f.submit(); }
            }
        });
    })();

})();
