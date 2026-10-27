/* G1: mejora progresiva. El formulario y las fichas funcionan también sin JavaScript. */
(() => {
    'use strict';
    document.documentElement.classList.add('cat-js');
    const sidebar = document.getElementById('catalogo-sidebar');
    const toggle = document.querySelector('[data-cat-toggle]');
    const close = document.querySelector('[data-cat-close]');
    const setMenu = open => {
        sidebar?.classList.toggle('is-open', open);
        toggle?.setAttribute('aria-expanded', String(open));
        if (open) (close || sidebar?.querySelector('a, input, button'))?.focus();
        else toggle?.focus();
    };
    toggle?.addEventListener('click', () => setMenu(!sidebar?.classList.contains('is-open')));
    close?.addEventListener('click', () => setMenu(false));
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && sidebar?.classList.contains('is-open')) setMenu(false);
        if (e.key === 'Tab' && sidebar?.classList.contains('is-open')) {
            const nodes = [...sidebar.querySelectorAll('a, button, input, select')].filter(n => !n.disabled && !n.hidden);
            const first = nodes[0], last = nodes[nodes.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last?.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first?.focus(); }
        }
    });
    const bindImages = () => document.querySelectorAll('[data-cat-image]').forEach(img => {
        const fallback = () => { img.hidden = true; const placeholder = img.parentElement.querySelector('.cat-picture-fallback'); if (placeholder) placeholder.hidden = false; };
        img.addEventListener('error', fallback, { once: true });
        if (img.complete && !img.naturalWidth) fallback();
    });
    bindImages();
    const form = document.getElementById('catalogo-filters');
    if (!form || typeof fetch !== 'function') return;
    const results = document.getElementById('cat-results');
    const freshness = document.getElementById('cat-freshness');
    let sequence = 0, pending = null, timer;
    let current = new URLSearchParams(location.search);
    const paramsFromForm = () => {
        const params = new URLSearchParams();
        for (const [key, value] of new FormData(form)) if (String(value).trim() !== '') params.set(key, String(value).trim());
        return params;
    };
    const updateFacets = (categories, brands) => {
        const container = document.getElementById('cat-category-options');
        const selected = current.get('categoria') || '';
        const rows = [{ id: '', nombre: 'Todos los productos', cantidad: null }, ...categories];
        if (selected && !rows.some(row => row.id === selected)) rows.push({ id: selected, nombre: 'Categoría seleccionada', cantidad: 0 });
        container.replaceChildren(...rows.map(row => {
            const label = document.createElement('label'); label.className = 'cat-category';
            const input = document.createElement('input'); input.type = 'radio'; input.name = 'categoria'; input.value = row.id; input.checked = row.id === selected;
            const name = document.createElement('span'); name.textContent = row.nombre;
            const count = document.createElement('small'); count.textContent = row.cantidad === null ? 'Todas' : String(row.cantidad);
            label.append(input, name, count); return label;
        }));
        const brand = document.getElementById('cat-brand'); const selectedBrand = current.get('marca') || '';
        const brandRows = [{ id: '', nombre: 'Todas las marcas', cantidad: null }, ...brands];
        if (selectedBrand && !brandRows.some(row => row.id === selectedBrand)) brandRows.push({ id: selectedBrand, nombre: 'Marca seleccionada', cantidad: 0 });
        brand.replaceChildren(...brandRows.map(row => {
            const option = document.createElement('option'); option.value = row.id; option.textContent = row.nombre + (row.cantidad === null ? '' : ` (${row.cantidad})`); option.selected = row.id === selectedBrand; return option;
        }));
    };
    const fail = () => {
        const box = document.createElement('section'); box.className = 'cat-empty cat-service-error'; box.setAttribute('role', 'status');
        const title = document.createElement('h3'); title.textContent = 'No pudimos actualizar el catálogo';
        const message = document.createElement('p'); message.textContent = 'La información anterior ya no se muestra como vigente. Reintentá para consultar productos y disponibilidad.';
        const retry = document.createElement('button'); retry.type = 'button'; retry.className = 'cat-button'; retry.textContent = 'Reintentar consulta'; retry.addEventListener('click', () => load(current));
        box.append(title, message, retry); results.replaceChildren(box); freshness.textContent = 'Sin actualización disponible';
    };
    async function load(params, navigate = false) {
        pending?.abort(); pending = new AbortController();
        const id = ++sequence; const signal = pending.signal;
        current = new URLSearchParams(params); results.setAttribute('aria-busy', 'true');
        freshness.textContent = 'Actualizando productos y disponibilidad…';
        try {
            const response = await fetch(`${form.dataset.endpoint}?${current}`, { signal, headers: { Accept: 'application/json' }, cache: 'no-store', credentials: 'omit' });
            if (!response.ok) throw new Error('unavailable');
            const data = await response.json();
            if (id !== sequence) return;
            if (typeof data.html !== 'string' || !Array.isArray(data.categories) || !Array.isArray(data.brands) || !/^\d{2}:\d{2}$/.test(data.checked_at)) throw new Error('invalid');
            // HTML generado por nuestras vistas Blade con salida escapada; nunca HTML crudo de G3.
            results.innerHTML = data.html;
            updateFacets(data.categories, data.brands); bindImages();
            freshness.textContent = `Consultado a las ${data.checked_at} · Argentina`;
            if (navigate) history.pushState(null, '', `${form.action}${current.size ? '?' + current : ''}`);
        } catch (error) {
            if (id === sequence && error.name !== 'AbortError') fail();
        } finally { if (id === sequence) results.setAttribute('aria-busy', 'false'); }
    }
    form.addEventListener('submit', e => { e.preventDefault(); clearTimeout(timer); load(paramsFromForm(), true); if (sidebar?.classList.contains('is-open')) setMenu(false); });
    form.addEventListener('change', () => { clearTimeout(timer); load(paramsFromForm(), true); });
    document.getElementById('cat-query')?.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => load(paramsFromForm(), true), 400); });
    document.querySelector('[data-cat-refresh]')?.addEventListener('click', () => { clearTimeout(timer); load(paramsFromForm(), true); });
    results.addEventListener('click', e => {
        const link = e.target.closest('.cat-pagination a');
        if (!link || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
        e.preventDefault(); load(new URL(link.href).searchParams, true); results.scrollIntoView({ block: 'start', behavior: 'auto' });
    });
    window.addEventListener('popstate', () => location.reload());
    setInterval(() => { if (!document.hidden && !results.matches('[aria-busy="true"]') && !form.contains(document.activeElement)) load(current); }, Math.max(30, Number(form.dataset.refresh) || 60) * 1000);
})();
