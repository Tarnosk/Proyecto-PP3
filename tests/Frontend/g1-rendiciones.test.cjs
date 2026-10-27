const { readFileSync } = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { test } = require('node:test');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '../..');

function element() {
    return {
        value: '', textContent: '', title: '', disabled: false, children: [], dataset: {}, style: {},
        classList: { add() {}, remove() {} }, setAttribute() {}, addEventListener() {}, remove() {},
        appendChild(child) { this.children.push(child); }, replaceChildren(...children) { this.children = children; },
        get innerHTML() { return this.html || ''; }, set innerHTML(value) { this.html = value; this.children = []; },
    };
}
function setup() {
    const elements = new Map(), listeners = [], calls = [], labels = Array.from({length: 4}, element);
    const get = id => { if (!elements.has(id)) elements.set(id, element()); return elements.get(id); };
    get('g1-rendiciones-controls').disabled = true;
    let handler = async () => ({ok: true, json: async () => ({error: false, data: []})});
    class FixedDate extends Date {
        constructor(...args) { super(...(args.length ? args : ['2026-10-24T01:30:00Z'])); }
    }
    const context = vm.createContext({
        document: {
            getElementById: get, createElement: element,
            querySelector: () => ({dataset: {g2Role: 'admin'}}),
            querySelectorAll: selector => selector === '.g2-page .kpi-label' ? labels : [],
            addEventListener: (type, callback) => { if (type === 'DOMContentLoaded') listeners.push(callback); },
        },
        location: { search: '', reload() {} }, URLSearchParams, Intl, Date: FixedDate, console,
        setTimeout() {}, g2Fetch: async url => { calls.push(url); return handler(url); },
    });
    context.window = context;
    vm.runInContext(readFileSync(path.join(root, 'public/js/g2-rendiciones.js'), 'utf8'), context);
    vm.runInContext(readFileSync(path.join(root, 'public/assets/js/g1-rendiciones.js'), 'utf8'), context);
    return { context, get, calls, labels,
        setHandler: value => { handler = value; },
        async start() { listeners.forEach(callback => callback()); await new Promise(setImmediate); },
    };
}
const response = (data, ok = true) => ({ok, json: async () => ({error: !ok, data})});
const row = (id = 1) => ({id_rendicion: id, id_repartidor: 41, id_entrega: null,
    fecha: '2026-10-23 10:00:00', total_rendido: '120.50', estado: 'aprobada', repartidor: {nombre: 'Ana'}});

test('la primera consulta y el filtro usan hoy en Argentina, aun cuando UTC ya cambió de día', async () => {
    const ui = setup();
    assert.equal(ui.get('g1-rendiciones-controls').disabled, true);
    await ui.start();
    assert.equal(ui.calls.length, 2);
    ui.calls.forEach(url => assert.equal(new URLSearchParams(url.split('?')[1]).get('fecha'), '2026-10-23'));
    assert.equal(ui.get('filtroFecha').value, '2026-10-23');
    assert.equal(ui.get('g1-rendiciones-controls').disabled, false);
    assert.equal(ui.get('cntTodas').textContent, 0);
    assert.equal(ui.labels[1].textContent, 'Pendientes hoy');
});
test('el listado envía juntos fecha, repartidor y estado sin afectar las métricas de hoy', async () => {
    const ui = setup(); await ui.start();
    ui.get('filtroFecha').value = '2026-10-20';
    ui.get('filtroRepartidor').value = '41';
    vm.runInContext("estadoFiltroActual = 'pendiente'", ui.context);
    await ui.context.cargarListadoRendiciones();
    const query = new URLSearchParams(ui.calls.at(-1).split('?')[1]);
    assert.equal(query.get('fecha'), '2026-10-20');
    assert.equal(query.get('id_repartidor'), '41');
    assert.equal(query.get('estado'), 'pendiente');
});
test('503 no se muestra como cero cobros: bloquea controles y permite reintentar', async () => {
    const ui = setup(); ui.setHandler(async () => response(null, false)); await ui.start();
    assert.equal(ui.get('g1-rendiciones-controls').disabled, true);
    assert.equal(ui.get('cntTodas').textContent, '—');
    assert.match(ui.get('g1-rendiciones-status').textContent, /Sin datos disponibles/);
    assert.match(ui.get('tablaRendicionesBody').children[0].children[0].textContent, /Sin datos disponibles/);
    ui.setHandler(async () => response([]));
    await ui.get('g1-rendiciones-retry').onclick();
    assert.equal(ui.get('g1-rendiciones-controls').disabled, false);
    assert.equal(ui.get('cntTodas').textContent, 0);
});
test('la apertura automática desde Entregas y los envíos quedan bloqueados sin servicio', async () => {
    const ui = setup();
    ui.context.location.search = '?id_entrega=8';
    ui.setHandler(async () => response(null, false));
    await ui.start();
    assert.equal(ui.calls.length, 2); // Sólo lecturas del listado; no precarga ni operación.
    let prevented = false;
    ui.context.guardarNuevaRendicion({preventDefault() { prevented = true; }});
    assert.equal(prevented, true);
    ui.context.procesarValidacionAdmin('aprobar');
    assert.equal(ui.calls.length, 2);
});
test('un fallo posterior vuelve a bloquear acciones y retira indicadores desactualizados', async () => {
    const ui = setup(); ui.setHandler(async () => response([row()])); await ui.start();
    assert.equal(ui.get('g1-rendiciones-controls').disabled, false);
    ui.setHandler(async () => { throw new Error('sesión vencida'); });
    await ui.context.recargarTodo();
    assert.equal(ui.get('g1-rendiciones-controls').disabled, true);
    assert.equal(ui.get('kpiTotalDinero').textContent, '—');
});
test('una respuesta inválida o con IDs repetidos no habilita operaciones', async () => {
    for (const rows of [[{...row(), estado: 'liquidada'}], [row(), row()], [null]]) {
        const ui = setup(); ui.setHandler(async () => response(rows)); await ui.start();
        assert.equal(ui.get('g1-rendiciones-controls').disabled, true);
        assert.equal(ui.get('kpiTotalDinero').textContent, '—');
    }
});
test('los textos externos se escapan antes de llegar al renderizador HTML de G2', async () => {
    const ui = setup();
    ui.setHandler(async () => response([{...row(), repartidor: {nombre: '<img onerror="evil()">'}, observaciones: '\"><script>evil()</script>'}]));
    await ui.start();
    const html = ui.get('tablaRendicionesBody').children.map(child => child.innerHTML).join('');
    assert.match(html, /&lt;img/);
    assert.match(html, /&quot;&gt;&lt;script&gt;/);
    assert.doesNotMatch(html, /<img|<script>/);
});
test('la confirmación sólo describe aprobación de la rendición, sin prometer impacto en Caja', () => {
    const ui = setup();
    ui.context.showToast('¡Rendición aprobada! Los cobros han sido liquidados e impactados en Caja/Cobros.', 'success');
    assert.match(ui.get('toastContainer').children[0].textContent, /confirmó la aprobación de la rendición/);
    assert.doesNotMatch(ui.get('toastContainer').children[0].textContent, /han sido liquidados/);
    ui.context.showToast('<img onerror="evil()">', 'error');
    assert.equal(ui.get('toastContainer').children[1].innerHTML, '');
});
test('una respuesta vieja de la tabla no pisa el filtro más reciente', async () => {
    const ui = setup(); await ui.start();
    let firstResolve;
    ui.setHandler(() => new Promise(resolve => { firstResolve = resolve; }));
    const old = ui.context.cargarListadoRendiciones();
    ui.setHandler(async () => response([row(2)]));
    await ui.context.cargarListadoRendiciones();
    firstResolve(response([row(1)])); await old;
    const html = ui.get('tablaRendicionesBody').children.map(child => child.innerHTML).join('');
    assert.match(html, /REND-0002/);
    assert.doesNotMatch(html, /REND-0001/);
});
