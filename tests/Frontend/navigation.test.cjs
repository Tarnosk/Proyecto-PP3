const {readFileSync} = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const {test} = require('node:test');
const assert = require('node:assert/strict');
const script = readFileSync(path.resolve(__dirname, '../../public/assets/js/navigation.js'), 'utf8');

function setup({saved = new Map(), blocked = false, current = null, navMissing = false} = {}) {
    const classes = new Set(), events = new Map(), navEvents = new Map(), docEvents = new Map();
    const attributes = new Map();
    const inside = {};
    const sidebar = {inert: false, contains: target => target === inside,
        setAttribute: (key, value) => attributes.set(`sidebar-${key}`, value)};
    const toggle = {hidden: true, title: '',
        setAttribute: (key, value) => attributes.set(key, value),
        addEventListener: (type, callback) => events.set(type, callback),
        focus: () => { document.activeElement = toggle; }};
    const nav = {scrollTop: 0, querySelector: () => current,
        getBoundingClientRect: () => ({top: 20, bottom: 120}),
        addEventListener: (type, callback) => navEvents.set(type, callback)};
    const document = {activeElement: null,
        body: {classList: {
            toggle(name, enabled) { if (enabled) classes.add(name); else classes.delete(name); },
            contains: name => classes.has(name),
        }},
        querySelector: selector => ({'[data-erp-sidebar]': sidebar, '[data-erp-sidebar-toggle]': toggle,
            '[data-erp-sidebar-nav]': navMissing ? null : nav}[selector] ?? null),
        addEventListener: (type, callback) => docEvents.set(type, callback),
    };
    const sessionStorage = {
        getItem(key) { if (blocked) throw new Error('Storage bloqueado'); return saved.get(key) ?? null; },
        setItem(key, value) { if (blocked) throw new Error('Storage bloqueado'); saved.set(key, value); },
    };
    vm.runInNewContext(script, {document, sessionStorage});
    return {document, sidebar, toggle, nav, inside, attributes, saved,
        click: () => events.get('click')(),
        key: event => docEvents.get('keydown')(event),
        scroll: () => navEvents.get('scroll')(),
        collapsed: () => classes.has('erp-sidebar-collapsed'),
    };
}

test('el botón cierra y vuelve a abrir el menú con estado accesible coherente', () => {
    const ui = setup();
    assert.equal(ui.toggle.hidden, false);
    assert.equal(ui.collapsed(), false);
    assert.equal(ui.attributes.get('aria-expanded'), 'true');
    ui.click();
    assert.equal(ui.collapsed(), true);
    assert.equal(ui.sidebar.inert, true);
    assert.equal(ui.attributes.get('sidebar-aria-hidden'), 'true');
    assert.equal(ui.attributes.get('aria-expanded'), 'false');
    assert.equal(ui.toggle.title, 'Mostrar menú');
    ui.click();
    assert.equal(ui.collapsed(), false);
    assert.equal(ui.sidebar.inert, false);
    assert.equal(ui.attributes.get('aria-label'), 'Ocultar menú');
});

test('se conserva la elección de cerrar el menú al navegar y se puede reabrir', () => {
    const saved = new Map();
    setup({saved}).click();
    const next = setup({saved});
    assert.equal(next.collapsed(), true);
    assert.equal(next.sidebar.inert, true);
    next.click();
    assert.equal(setup({saved}).collapsed(), false);
});

test('el botón sigue funcionando cuando el navegador bloquea el almacenamiento', () => {
    const ui = setup({blocked: true});
    assert.equal(ui.collapsed(), false);
    assert.doesNotThrow(() => ui.click());
    assert.equal(ui.collapsed(), true);
    ui.click();
    assert.equal(ui.collapsed(), false);
});

test('Escape dentro del menú lo cierra y devuelve el foco al botón hamburguesa', () => {
    const ui = setup();
    ui.document.activeElement = ui.inside;
    let prevented = false;
    ui.key({key: 'Escape', preventDefault() { prevented = true; }});
    assert.equal(prevented, true);
    assert.equal(ui.collapsed(), true);
    assert.equal(ui.document.activeElement, ui.toggle);
});

test('Escape fuera del menú no interfiere con formularios o diálogos de la pantalla', () => {
    const ui = setup();
    ui.document.activeElement = {};
    ui.key({key: 'Escape', preventDefault() { throw new Error('No debía capturarse'); }});
    assert.equal(ui.collapsed(), false);
});

test('la posición del menú se recupera y guarda junto al estado de apertura', () => {
    const ui = setup({saved: new Map([['erp-navigation-scroll', '85']])});
    assert.equal(ui.nav.scrollTop, 85);
    ui.nav.scrollTop = 110;
    ui.scroll();
    ui.click();
    const next = setup({saved: ui.saved});
    assert.equal(next.nav.scrollTop, 110);
    assert.equal(next.collapsed(), true);
});

test('la opción activa queda visible aunque la posición guardada sea distinta', () => {
    const ui = setup({current: {getBoundingClientRect: () => ({top: 130, bottom: 170})}});
    assert.equal(ui.nav.scrollTop, 50);
});

test('el control puede cerrar el panel aunque no haya listado de navegación', () => {
    const ui = setup({navMissing: true});
    assert.doesNotThrow(() => ui.click());
    assert.equal(ui.collapsed(), true);
});
