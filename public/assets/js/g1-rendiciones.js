/* Capa de presentación de G1. Los contratos y operaciones de G2 no se modifican. */
(() => {
    'use strict';
    const byId = id => document.getElementById(id);
    const gate = byId('g1-rendiciones-controls');
    if (!gate) return;
    const zone = 'America/Argentina/Buenos_Aires';
    const today = () => {
        const parts = new Intl.DateTimeFormat('en', {timeZone: zone, year: 'numeric', month: '2-digit', day: '2-digit'}).formatToParts(new Date());
        const part = type => parts.find(p => p.type === type).value;
        return `${part('year')}-${part('month')}-${part('day')}`;
    };
    const ids = ['kpiTotalRendiciones', 'kpiPendientes', 'kpiTotalDinero', 'kpiRechazadas',
        'cntTodas', 'cntPendientes', 'cntAprobadas', 'cntRechazadas'];
    let countersReady = false, listReady = false, listVersion = 0, countersVersion = 0;
    const updateStatus = () => {
        const ready = countersReady && listReady;
        gate.disabled = !ready;
        if (!ready) document.querySelectorAll('.g2-page .modal-overlay.active').forEach(modal => modal.classList.remove('active'));
        byId('g1-rendiciones-status').textContent = ready
            ? 'Datos recibidos de G2. Los indicadores y contadores corresponden a hoy (hora argentina); la tabla aplica sus propios filtros. Una rendición aprobada no certifica cada pago ni su impacto en Caja o saldos.'
            : 'Sin datos disponibles: no se pudo completar la consulta a G2. Las operaciones permanecen deshabilitadas. Podés reintentar la conexión.';
    };
    const resetCounters = () => ids.forEach(id => {
        const element = byId(id);
        if (element) { element.textContent = '—'; element.title = 'Sin datos disponibles'; }
    });
    const emptyTable = message => {
        const tbody = byId('tablaRendicionesBody');
        const row = document.createElement('tr'), cell = document.createElement('td');
        cell.colSpan = 8; cell.textContent = message; row.appendChild(cell); tbody.replaceChildren(row);
    };
    const validId = value => /^\d+$/.test(String(value)) && Number.isSafeInteger(Number(value)) && Number(value) > 0;
    async function readList(query) {
        const response = await g2Fetch(`/g2/api/rendiciones?${query}`);
        const body = await response.json();
        if (!response.ok || body.error || !Array.isArray(body.data)) throw new Error('Listado no disponible');
        const seen = new Set();
        for (const row of body.data) {
            if (!row || !validId(row.id_rendicion) || !validId(row.id_repartidor)
                || !['pendiente', 'aprobada', 'rechazada'].includes(row.estado)
                || typeof row.fecha !== 'string' || !/^\d{4}-\d{2}-\d{2}/.test(row.fecha) || !Number.isFinite(Date.parse(row.fecha))
                || row.total_rendido === null || row.total_rendido === '' || !Number.isFinite(Number(row.total_rendido)) || Number(row.total_rendido) < 0
                || (row.id_entrega != null && !validId(row.id_entrega))
                || seen.has(Number(row.id_rendicion))) throw new Error('Listado inválido');
            seen.add(Number(row.id_rendicion));
        }
        return body.data;
    }
    // El renderizador original usa HTML: escapar los campos externos antes de delegar.
    const escape = value => String(value ?? '').replace(/[&<>"']/g, character => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character]));
    const originalRender = window.renderizarTabla;
    window.renderizarTabla = rows => originalRender(rows.map(row => ({...row,
        id_rendicion: Number(row.id_rendicion), id_repartidor: Number(row.id_repartidor),
        id_entrega: row.id_entrega == null ? null : Number(row.id_entrega),
        observaciones: escape(row.observaciones),
        repartidor: row.repartidor ? {...row.repartidor, nombre: escape(row.repartidor.nombre)} : null,
    })));
    window.cargarContadoresGlobales = async () => {
        const version = ++countersVersion;
        try {
            const rows = await readList(new URLSearchParams({fecha: today()}));
            if (version !== countersVersion) return;
            actualizarKPIs(rows);
            actualizarContadoresTabs(rows);
            ids.forEach(id => { if (byId(id)) byId(id).title = 'Hoy, hora argentina. Independiente de los filtros de la tabla.'; });
            countersReady = true;
        } catch {
            if (version !== countersVersion) return;
            countersReady = false;
            resetCounters();
        }
        updateStatus();
    };
    window.cargarListadoRendiciones = async () => {
        const version = ++listVersion;
        const query = new URLSearchParams();
        if (estadoFiltroActual) query.set('estado', estadoFiltroActual);
        if (byId('filtroRepartidor')?.value) query.set('id_repartidor', byId('filtroRepartidor').value);
        if (byId('filtroFecha')?.value) query.set('fecha', byId('filtroFecha').value);
        emptyTable('Consultando rendiciones de G2…');
        try {
            const rows = await readList(query);
            if (version !== listVersion) return;
            _todasLasRendiciones = rows;
            renderizarTabla(rows);
            listReady = true;
        } catch {
            if (version !== listVersion) return;
            _todasLasRendiciones = [];
            listReady = false;
            emptyTable('Sin datos disponibles. G2 no devolvió un listado válido de rendiciones.');
        }
        updateStatus();
    };
    // No prometer liquidación de cada pago a partir de la aprobación del contenedor.
    window.showToast = (message, type = 'info') => {
        if (typeof message === 'string' && message.includes('Los cobros han sido liquidados e impactados en Caja/Cobros.')) {
            message = 'G2 confirmó la aprobación de la rendición. La liquidación individual de los cobros no está informada.';
        }
        const toast = document.createElement('div');
        toast.className = `toast toast-${['info', 'success', 'error', 'warning'].includes(type) ? type : 'info'}`;
        toast.textContent = String(message);
        byId('toastContainer')?.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    };
    // También bloquear aperturas automáticas (?id_entrega=...) y envíos programáticos
    // del frontend original mientras no haya una consulta satisfactoria.
    for (const name of ['abrirModalNuevaRendicion', 'abrirModalDevolucion', 'verDetalleRendicion',
        'aprobarRapido', 'rechazarRapido', 'procesarValidacionAdmin', 'guardarNuevaRendicion', 'guardarDevolucion']) {
        const original = window[name];
        window[name] = (...args) => {
            if (gate.disabled) {
                args[0]?.preventDefault?.();
                return;
            }
            return original(...args);
        };
    }
    byId('g1-rendiciones-retry').onclick = () => recargarTodo();
    resetCounters();
    // La primera consulta debe incluir la fecha argentina, antes del callback original.
    if (byId('filtroFecha')) byId('filtroFecha').value = today();
    document.addEventListener('DOMContentLoaded', () => {
        // El callback original vuelve a escribir la fecha UTC: corregir su presentación.
        if (byId('filtroFecha')) byId('filtroFecha').value = today();
        const labels = document.querySelectorAll('.g2-page .kpi-label');
        if (labels[1]) labels[1].textContent = 'Pendientes hoy';
        if (labels[3]) labels[3].textContent = 'Rechazadas hoy';
    });
})();
