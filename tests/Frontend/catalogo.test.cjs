const {readFileSync} = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const {test} = require('node:test');
const assert = require('node:assert/strict');
const script = readFileSync(path.resolve(__dirname, '../../public/assets/js/catalogo.js'), 'utf8');

function setup() {
    const make = (tag = 'div') => {
        const classes = new Set(), events = new Map(), attrs = new Map();
        return {tag, events, attrs, children: [], innerHTML: '', textContent: '', hidden: false,
            classList: {add: name => classes.add(name), contains: name => classes.has(name), toggle(name, on) { on ? classes.add(name) : classes.delete(name); }},
            addEventListener: (name, fn) => events.set(name, fn), setAttribute: (name, val) => attrs.set(name, val),
            append(...nodes) { this.children.push(...nodes); }, replaceChildren(...nodes) { this.innerHTML = ''; this.children = nodes; },
            querySelectorAll: () => [], querySelector: () => null, contains: node => node === 'input',
            matches: () => attrs.get('aria-busy') === 'true', focus() { document.activeElement = this; }, scrollIntoView() {},
        };
    };
    const document = {activeElement: null, hidden: false, documentElement: make(),
        createElement: make, events: new Map(), querySelectorAll: () => [],
        addEventListener(name, fn) { this.events.set(name, fn); },
    };
    const form = make('form'); form.dataset = {endpoint: 'http://localhost/catalogo/datos', refresh: '60'}; form.action = 'http://localhost/catalogo';
    const ids = {'catalogo-sidebar': make(), 'catalogo-filters': form, 'cat-results': make(), 'cat-freshness': make(),
        'cat-category-options': make(), 'cat-brand': make(), 'cat-query': make()};
    const toggle = make('button'), close = make('button'), refresh = make('button');
    document.getElementById = id => ids[id] ?? null;
    document.querySelector = selector => ({'[data-cat-toggle]': toggle, '[data-cat-close]': close, '[data-cat-refresh]': refresh}[selector] ?? null);
    const requests = [], interval = [], history = [], fields = new Map([['q', 'aceite'], ['categoria', ''], ['orden', 'nombre']]);
    const window = {events: new Map(), addEventListener(name, fn) { this.events.set(name, fn); }};
    const location = {search: '', reload() { this.reloaded = true; }};
    vm.runInNewContext(script, {document, window, location, history: {pushState: (...args) => history.push(args)},
        URL, URLSearchParams, AbortController, FormData: class { [Symbol.iterator]() { return fields.entries(); } },
        fetch(url, options) { return new Promise((resolve, reject) => requests.push({url, options, resolve, reject})); },
        setInterval: (fn, ms) => interval.push({fn, ms}), setTimeout, clearTimeout,
    });
    return {document, ids, toggle, close, refresh, form, requests, interval, history, fields};
}
const flush = () => new Promise(resolve => setImmediate(resolve));
const success = (html = 'productos nuevos', categories = []) => ({ok: true, json: async () => ({html, categories, brands: [], checked_at: '14:20'})});

test('una consulta fallida retira los productos anteriores y permite reintentar sin inventar stock', async () => {
    const ui = setup(); ui.ids['cat-results'].innerHTML = 'datos viejos';
    ui.refresh.events.get('click')(); ui.requests[0].resolve({ok: false}); await flush();
    assert.equal(ui.ids['cat-results'].innerHTML, '');
    assert.equal(ui.ids['cat-freshness'].textContent, 'Sin actualización disponible');
    const retry = ui.ids['cat-results'].children[0].children[2]; retry.events.get('click')();
    ui.requests[1].resolve(success()); await flush();
    assert.equal(ui.ids['cat-results'].innerHTML, 'productos nuevos');
    assert.equal(ui.ids['cat-results'].attrs.get('aria-busy'), 'false');
});

test('una respuesta vieja no reemplaza el resultado de una búsqueda más reciente', async () => {
    const ui = setup(); ui.refresh.events.get('click')();
    ui.fields.set('q', 'yerba'); ui.refresh.events.get('click')();
    assert.equal(ui.requests[0].options.signal.aborted, true);
    ui.requests[1].resolve(success('yerba')); await flush();
    ui.requests[0].resolve(success('aceite')); await flush();
    assert.equal(ui.ids['cat-results'].innerHTML, 'yerba');
    assert.match(ui.requests[1].url, /q=yerba/);
});

test('el formulario conserva filtros combinados y solicita datos sin cookies', async () => {
    const ui = setup(); ui.fields.set('categoria', '7'); ui.fields.set('marca', '4');
    ui.form.events.get('submit')({preventDefault() {}});
    const request = ui.requests[0];
    assert.match(request.url, /categoria=7/); assert.match(request.url, /marca=4/);
    assert.equal(request.options.credentials, 'omit'); assert.equal(request.options.cache, 'no-store');
    request.resolve(success()); await flush();
    assert.match(ui.history[0][2], /^http:\/\/localhost\/catalogo\?/);
});

test('categorías con marcado se muestran como texto y mantienen la selección sin resultados', async () => {
    const ui = setup(); ui.fields.set('categoria', '8'); ui.refresh.events.get('click')();
    ui.requests[0].resolve(success('ok', [{id: '7', nombre: '<img onerror=EVIL()>', cantidad: 2}])); await flush();
    const labels = ui.ids['cat-category-options'].children;
    assert.equal(labels[1].children[1].textContent, '<img onerror=EVIL()>');
    assert.equal(labels[1].children[1].innerHTML, '');
    assert.equal(labels[2].children[0].checked, true); assert.equal(labels[2].children[2].textContent, '0');
});

test('una respuesta inválida deja de mostrar los datos como actualizados', async () => {
    const ui = setup(); ui.refresh.events.get('click')();
    ui.requests[0].resolve({ok: true, json: async () => ({html: 'old'})}); await flush();
    assert.equal(ui.ids['cat-freshness'].textContent, 'Sin actualización disponible');
    assert.notEqual(ui.ids['cat-results'].innerHTML, 'old');
});

test('la actualización periódica no interrumpe la escritura ni consulta desde una pestaña oculta', () => {
    const ui = setup(); assert.equal(ui.interval[0].ms, 60000);
    ui.document.activeElement = 'input'; ui.interval[0].fn(); assert.equal(ui.requests.length, 0);
    ui.document.activeElement = null; ui.document.hidden = true; ui.interval[0].fn(); assert.equal(ui.requests.length, 0);
    ui.document.hidden = false; ui.interval[0].fn(); assert.equal(ui.requests.length, 1);
});

test('el menú móvil se puede abrir y cerrar con Escape recuperando el foco', () => {
    const ui = setup(); ui.toggle.events.get('click')();
    assert.equal(ui.toggle.attrs.get('aria-expanded'), 'true'); assert.equal(ui.document.activeElement, ui.close);
    ui.document.events.get('keydown')({key: 'Escape'});
    assert.equal(ui.toggle.attrs.get('aria-expanded'), 'false'); assert.equal(ui.document.activeElement, ui.toggle);
});
