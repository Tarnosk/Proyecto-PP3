const g2Admin = document.querySelector('[data-g2-role]')?.dataset.g2Role === 'admin';
/**
 * LÓGICA FRONTEND: MÓDULO DE RENDICIONES & COBROS (GRUPO 2)
 * ERP DISTRIBUIDORA JUAN XXIII — Versión 2.0
 */

const API_BASE = '/g2/api';

let estadoFiltroActual = '';
let rendicionSeleccionadaId = null;
let _todasLasRendiciones = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarContadoresGlobales();
    cargarListadoRendiciones();
    // Fecha hoy por defecto en filtro de fecha
    const filtroFecha = document.getElementById('filtroFecha');
    if (filtroFecha) {
        const hoy = new Date().toISOString().split('T')[0];
        filtroFecha.value = hoy;
    }

    // Si viene redirigido desde Entregas con ?id_entrega=X, abrir modal precargada
    const urlParams = new URLSearchParams(window.location.search);
    const idEntregaParam = urlParams.get('id_entrega');
    if (idEntregaParam) {
        abrirModalNuevaRendicion(parseInt(idEntregaParam));
    }
});


/* ==========================================================
   MÉTRICAS Y CONTADORES GLOBALES (ESTÁTICOS FRENTE A FILTROS)
   ========================================================== */
async function cargarContadoresGlobales() {
    try {
        const res = await g2Fetch(`${API_BASE}/rendiciones`);
        const json = await res.json();
        if (!json.error && json.data) {
            actualizarKPIs(json.data);
            actualizarContadoresTabs(json.data);
        }
    } catch (err) {
        console.error('Error al cargar contadores globales:', err);
    }
}

async function recargarTodo() {
    await Promise.all([
        cargarContadoresGlobales(),
        cargarListadoRendiciones()
    ]);
}

/* ==========================================================
   TOAST NOTIFICATIONS
   ========================================================== */
function showToast(mensaje, tipo = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
    const toast = document.createElement('div');
    toast.className = `toast toast-${tipo}`;
    toast.innerHTML = `<span>${icons[tipo] || 'ℹ️'}</span><span>${mensaje}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('hiding');
        setTimeout(() => toast.remove(), 320);
    }, 3500);
}

/* ==========================================================
   CARGAR LISTADO DE RENDICIONES
   ========================================================== */
async function cargarListadoRendiciones() {
    const repartidorId = document.getElementById('filtroRepartidor')?.value || '';
    const tbody = document.getElementById('tablaRendicionesBody');
    tbody.innerHTML = `<tr><td colspan="8"><div class="table-empty"><span class="table-empty-icon">⏳</span>Cargando rendiciones…</div></td></tr>`;

    let url = `${API_BASE}/rendiciones?1=1`;
    if (estadoFiltroActual) url += `&estado=${estadoFiltroActual}`;
    if (repartidorId)       url += `&id_repartidor=${repartidorId}`;

    try {
        const res = await g2Fetch(url);
        const json = await res.json();

        if (json.error) {
            tbody.innerHTML = `<tr><td colspan="8"><div class="table-empty"><span class="table-empty-icon">❌</span>${json.mensaje}</div></td></tr>`;
            return;
        }

        const rendiciones = json.data || [];
        _todasLasRendiciones = rendiciones;
        // NOTA: Los contadores globales superiores y los badges de tabs son
        // estáticos e independientes del filtro de la tabla.
        renderizarTabla(rendiciones);

    } catch (err) {
        console.error('Error al cargar rendiciones:', err);
        tbody.innerHTML = `<tr><td colspan="8"><div class="table-empty"><span class="table-empty-icon">⚠️</span>Error de conexión con el servidor.</div></td></tr>`;
    }
}

function renderizarTabla(rendiciones) {
    const tbody = document.getElementById('tablaRendicionesBody');

    if (rendiciones.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8"><div class="table-empty"><span class="table-empty-icon">📋</span>No se encontraron rendiciones para los filtros seleccionados.</div></td></tr>`;
        return;
    }

    tbody.innerHTML = '';
    rendiciones.forEach(r => {
        const tr = document.createElement('tr');

        let badgeClass = 'badge-pendiente';
        let estadoTexto = 'Pendiente';
        let estadoEmoji = '⏳';
        if (r.estado === 'aprobada') {
            badgeClass = 'badge-aprobada';
            estadoTexto = 'Aprobada';
            estadoEmoji = '✅';
        } else if (r.estado === 'rechazada') {
            badgeClass = 'badge-rechazada';
            estadoTexto = 'Rechazada';
            estadoEmoji = '❌';
        }

        const fechaFormat = new Date(r.fecha).toLocaleDateString('es-AR', {
            day: '2-digit', month: '2-digit', year: 'numeric'
        });

        const nombreRepartidor = r.repartidor ? r.repartidor.nombre : `Repartidor #${r.id_repartidor}`;
        const entregaTag = r.id_entrega
            ? `<span class="badge badge-zona">Reparto #${r.id_entrega}</span>`
            : `<span style="color: var(--text-light); font-size: 0.8rem;">—</span>`;

        const totalFormateado = Number(r.total_rendido).toLocaleString('es-AR', { minimumFractionDigits: 2 });
        const obs = r.observaciones
            ? `<span title="${r.observaciones}" style="max-width: 160px; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${r.observaciones}</span>`
            : `<span style="color: var(--text-light); font-size: 0.8rem;">—</span>`;

        tr.innerHTML = `
            <td><strong style="color: var(--primary); font-family: monospace;">#REND-${r.id_rendicion.toString().padStart(4, '0')}</strong></td>
            <td style="color: var(--text-muted); font-size: 0.875rem;">${fechaFormat}</td>
            <td><strong>${nombreRepartidor}</strong></td>
            <td>${entregaTag}</td>
            <td style="text-align: right; font-weight: 800; color: var(--text-main); font-variant-numeric: tabular-nums;">
                $${totalFormateado}
            </td>
            <td style="text-align: center;">
                <span class="badge ${badgeClass}">${estadoEmoji} ${estadoTexto}</span>
            </td>
            <td style="font-size: 0.875rem;">${obs}</td>
            <td style="text-align: center;">
                <div style="display: flex; gap: 0.4rem; justify-content: center; flex-wrap: wrap;">
                    <button class="clay-btn clay-btn-secondary clay-btn-xs" onclick="verDetalleRendicion(${r.id_rendicion})" title="Ver detalle y validar">
                        🔍 Ver
                    </button>
                    ${g2Admin && r.estado === 'pendiente' ? `
                    <button class="clay-btn clay-btn-xs" style="background: var(--success); color: white;" onclick="aprobarRapido(${r.id_rendicion})" title="Aprobar directamente">
                        ✅
                    </button>
                    <button class="clay-btn clay-btn-xs" style="background: var(--danger); color: white;" onclick="rechazarRapido(${r.id_rendicion})" title="Rechazar">
                        ❌
                    </button>` : ''}
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

/* ==========================================================
   TABS Y KPIs
   ========================================================== */
function filtrarEstadoTab(estado) {
    estadoFiltroActual = estado;
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.setAttribute('aria-selected', 'false');
    });

    const tabMap = { '': 'tabTodas', 'pendiente': 'tabPendientes', 'aprobada': 'tabAprobadas', 'rechazada': 'tabRechazadas' };
    const activeTab = document.getElementById(tabMap[estado] || 'tabTodas');
    if (activeTab) {
        activeTab.classList.add('active');
        activeTab.setAttribute('aria-selected', 'true');
    }

    cargarListadoRendiciones();
}

function actualizarKPIs(rendiciones) {
    const todas     = rendiciones.length;
    const pendientes = rendiciones.filter(r => r.estado === 'pendiente').length;
    const rechazadas = rendiciones.filter(r => r.estado === 'rechazada').length;
    const sumaTotal = rendiciones
        .filter(r => r.estado === 'aprobada')
        .reduce((acc, r) => acc + Number(r.total_rendido), 0);

    setText('kpiTotalRendiciones', todas);
    setText('kpiPendientes', pendientes);
    setText('kpiRechazadas', rechazadas);
    setText('kpiTotalDinero', `$${sumaTotal.toLocaleString('es-AR', { minimumFractionDigits: 2 })}`);
}

function actualizarContadoresTabs(rendiciones) {
    const counts = {
        cntTodas:     rendiciones.length,
        cntPendientes: rendiciones.filter(r => r.estado === 'pendiente').length,
        cntAprobadas:  rendiciones.filter(r => r.estado === 'aprobada').length,
        cntRechazadas: rendiciones.filter(r => r.estado === 'rechazada').length,
    };
    Object.entries(counts).forEach(([id, val]) => setText(id, val));
}

function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}

/* ==========================================================
   MODAL: NUEVA RENDICIÓN DINÁMICA (POR ENTREGA / COBRO)
   ========================================================== */
async function abrirModalNuevaRendicion(idEntregaPrevia = null) {
    document.getElementById('formNuevaRendicion').reset();
    document.getElementById('seccionCobrosDinamicos').style.display = 'none';
    document.getElementById('seccionCobroManual').style.display = 'block';
    document.getElementById('tablaCobrosDinamicosBody').innerHTML = '';
    document.getElementById('modalNuevaRendicion').classList.add('active');

    await cargarSelectEntregas(idEntregaPrevia);
}

function cerrarModalNuevaRendicion() {
    document.getElementById('modalNuevaRendicion').classList.remove('active');
}

/**
 * Consulta las entregas listas para rendición y llena el select
 */
async function cargarSelectEntregas(idEntregaPrevia = null) {
    const select = document.getElementById('formEntregaSelect');
    select.innerHTML = '<option value="">— Carga manual sin viaje formal —</option>';

    try {
        const res = await g2Fetch(`${API_BASE}/entregas/pendientes-rendicion`);
        const json = await res.json();

        if (!json.error && json.data) {
            json.data.forEach(e => {
                const opt = document.createElement('option');
                opt.value = e.id_entrega;
                opt.textContent = `Entrega #${e.id_entrega} (${e.fecha_salida}) — ${e.repartidor_nombre} (${e.pedidos_entregados} pedido/s)`;
                select.appendChild(opt);
            });
        }

        if (idEntregaPrevia) {
            select.value = idEntregaPrevia;
            await alCambiarEntregaSelect(idEntregaPrevia);
        }
    } catch (err) {
        console.error('Error al cargar entregas pendientes:', err);
    }
}

/**
 * Al cambiar de entrega en el select, consulta los pedidos entregados y arma la tabla de cobros
 */
async function alCambiarEntregaSelect(idEntrega) {
    const seccionDinamica = document.getElementById('seccionCobrosDinamicos');
    const seccionManual   = document.getElementById('seccionCobroManual');
    const tbody           = document.getElementById('tablaCobrosDinamicosBody');

    if (!idEntrega) {
        seccionDinamica.style.display = 'none';
        seccionManual.style.display   = 'block';
        return;
    }

    try {
        const res = await g2Fetch(`${API_BASE}/rendiciones/preparar/${idEntrega}`);
        const json = await res.json();

        if (json.error || !json.data) {
            showToast('No se pudieron obtener los datos de la entrega seleccionada.', 'error');
            return;
        }

        const data = json.data;
        // Establecer chofer
        if (data.id_repartidor) {
            document.getElementById('formRepartidor').value = data.id_repartidor;
        }

        tbody.innerHTML = '';
        const cobrosSugeridos = data.cobros_sugeridos || [];

        if (cobrosSugeridos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:1rem;color:var(--text-muted);">Esta entrega no tiene pedidos confirmados como entregados.</td></tr>';
        } else {
            cobrosSugeridos.forEach(c => {
                const tr = document.createElement('tr');
                tr.className = 'fila-cobro-dinamico';
                tr.dataset.cliente = c.id_cliente;
                tr.dataset.factura = c.id_factura;
                tr.dataset.monto = c.monto;

                tr.innerHTML = `
                    <td>
                        <strong>#PED-${c.id_venta.toString().padStart(4, '0')}</strong> — ${c.cliente_nombre}
                        ${c.numero_remito ? `<br><small style="color:var(--text-muted);">Remito: ${c.numero_remito}</small>` : ''}
                    </td>
                    <td style="text-align:right; font-weight:700; color:#1E3A8A;">
                        $${Number(c.monto).toLocaleString('es-AR', {minimumFractionDigits: 2})}
                    </td>
                    <td>
                        <select class="clay-select select-medio-pago-dinamico" style="font-size:0.85rem; padding:0.35rem 0.5rem; width:100%;">
                            <option value="efectivo">💵 Efectivo (Caja)</option>
                            <option value="transferencia">🏦 Transferencia (Banco)</option>
                            <option value="cheque">📄 Cheque Diferido</option>
                        </select>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        document.getElementById('totalRendidoDinamicoTxt').textContent = `$${Number(data.total_esperado).toLocaleString('es-AR', {minimumFractionDigits: 2})}`;

        seccionDinamica.style.display = 'block';
        seccionManual.style.display   = 'none';

    } catch (err) {
        console.error(err);
        showToast('Error al conectar con el servidor para preparar la rendición.', 'error');
    }
}

async function guardarNuevaRendicion(e) {
    e.preventDefault();

    const submitBtn = e.target.querySelector('[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Guardando…';

    const idRepartidor = parseInt(document.getElementById('formRepartidor').value);
    const idEntregaVal = document.getElementById('formEntregaSelect').value ? parseInt(document.getElementById('formEntregaSelect').value) : null;
    const observaciones = document.getElementById('formObservaciones').value.trim();

    let cobros = [];

    const seccionDinamica = document.getElementById('seccionCobrosDinamicos');
    if (seccionDinamica.style.display !== 'none') {
        const filas = document.querySelectorAll('.fila-cobro-dinamico');
        filas.forEach(f => {
            cobros.push({
                id_cliente: parseInt(f.dataset.cliente),
                id_factura: parseInt(f.dataset.factura),
                monto: parseFloat(f.dataset.monto),
                medio_pago: f.querySelector('.select-medio-pago-dinamico').value
            });
        });

        if (cobros.length === 0) {
            showToast('No hay cobros de pedidos entregados para rendir en este viaje.', 'warning');
            submitBtn.disabled = false;
            submitBtn.innerHTML = `<svg class="icon-svg" viewBox="0 0 24 24"><path d="M5 12l5 5L20 7"/></svg> Registrar Rendición`;
            return;
        }
    } else {
        const selectCliente = document.getElementById('formCliente');
        const optionSel     = selectCliente.options[selectCliente.selectedIndex];
        const idFactura     = optionSel.getAttribute('data-factura');
        const montoVal      = parseFloat(document.getElementById('formMonto').value) || 0;

        if (montoVal <= 0) {
            showToast('El monto debe ser mayor a $0.', 'warning');
            submitBtn.disabled = false;
            submitBtn.innerHTML = `<svg class="icon-svg" viewBox="0 0 24 24"><path d="M5 12l5 5L20 7"/></svg> Registrar Rendición`;
            return;
        }

        cobros.push({
            id_cliente: parseInt(selectCliente.value),
            id_factura: parseInt(idFactura),
            monto:      montoVal,
            medio_pago: document.getElementById('formMedioPago').value
        });
    }

    const payload = {
        id_repartidor: idRepartidor,
        id_entrega:    idEntregaVal,
        observaciones: observaciones || 'Rendición de reparto',
        cobros:        cobros
    };

    try {
        const res  = await g2Fetch(`${API_BASE}/rendiciones`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload)
        });
        const json = await res.json();

        if (!json.error) {
            showToast('¡Rendición de reparto registrada con éxito!', 'success');
            cerrarModalNuevaRendicion();
            cargarContadoresGlobales();
            cargarListadoRendiciones();
        } else {
            showToast('Error: ' + json.mensaje, 'error');
        }
    } catch (err) {
        showToast('Error de comunicación con el servidor.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = `<svg class="icon-svg" viewBox="0 0 24 24"><path d="M5 12l5 5L20 7"/></svg> Registrar Rendición`;
    }
}


/* ==========================================================
   MODAL: DETALLE Y REVISIÓN
   ========================================================== */
async function verDetalleRendicion(idRendicion) {
    rendicionSeleccionadaId = idRendicion;
    document.getElementById('modalDetalleRendicion').classList.add('active');
    document.getElementById('detIdRendicion').textContent = `#${idRendicion}`;
    document.getElementById('detRepartidor').textContent  = '…';
    document.getElementById('detCobrosBody').innerHTML   = `<tr><td colspan="4" style="text-align:center;padding:1.5rem;color:var(--text-muted);">Cargando…</td></tr>`;

    try {
        const res  = await g2Fetch(`${API_BASE}/rendiciones/${idRendicion}`);
        const json = await res.json();

        if (json.error || !json.data) {
            showToast('No se pudo obtener el detalle de la rendición.', 'error');
            cerrarModalDetalle();
            return;
        }

        const r = json.data;
        document.getElementById('detIdRendicion').textContent = `#REND-${r.id_rendicion.toString().padStart(4,'0')}`;
        document.getElementById('detRepartidor').textContent  = r.repartidor ? r.repartidor.nombre : `Repartidor #${r.id_repartidor}`;
        document.getElementById('detFecha').textContent       = new Date(r.fecha).toLocaleString('es-AR');
        document.getElementById('detTotal').textContent       = `$${Number(r.total_rendido).toLocaleString('es-AR', { minimumFractionDigits: 2 })}`;
        document.getElementById('detObs').textContent         = r.observaciones || 'Sin observaciones.';

        // Badge de estado
        const estadoBadge = document.getElementById('detEstadoBadge');
        const adminBox    = document.getElementById('boxAccionAdmin');
        const motivoInput = document.getElementById('motivoRechazoAdmin');

        if (r.estado === 'aprobada') {
            estadoBadge.innerHTML = `<span class="badge badge-aprobada">✅ Aprobada</span>`;
            adminBox.style.display = 'none';
        } else if (r.estado === 'rechazada') {
            estadoBadge.innerHTML = `<span class="badge badge-rechazada">❌ Rechazada${r.motivo_rechazo ? ' — ' + r.motivo_rechazo : ''}</span>`;
            adminBox.style.display = 'none';
        } else {
            estadoBadge.innerHTML = `<span class="badge badge-pendiente">⏳ Pendiente</span>`;
            adminBox.style.display = g2Admin ? 'block' : 'none';
            if (motivoInput) motivoInput.value = '';
        }

        // Cobros de la rendición
        const cobrosBody = document.getElementById('detCobrosBody');
        cobrosBody.innerHTML = '';
        const cobros = r.cobros || [];

        if (cobros.length === 0) {
            cobrosBody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:1rem;">Sin cobros detallados.</td></tr>`;
        } else {
            const medioPagoMeta = {
                efectivo:      { label: '💵 Efectivo', badge: 'badge-aprobada', destino: '🏠 Caja Arqueo' },
                transferencia: { label: '🏦 Transferencia', badge: 'badge-zona', destino: '🏦 Conciliación Bancaria' },
                cheque:        { label: '📄 Cheque', badge: 'badge-pendiente', destino: '💼 Cartera de Cheques' }
            };

            cobros.forEach(c => {
                const tr = document.createElement('tr');
                const meta = medioPagoMeta[c.medio_pago] || { label: c.medio_pago, badge: 'badge-zona', destino: 'General' };

                tr.innerHTML = `
                    <td><strong>Cliente #${c.id_cliente}</strong> — Fac. #${c.id_factura}</td>
                    <td><span class="badge ${meta.badge}">${meta.label}</span></td>
                    <td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">
                        $${Number(c.monto).toLocaleString('es-AR', { minimumFractionDigits: 2 })}
                    </td>
                    <td style="text-align:center;font-size:0.85rem;font-weight:600;color:var(--text-main);">
                        ${meta.destino}
                    </td>
                `;
                cobrosBody.appendChild(tr);
            });
        }

    } catch (err) {
        showToast('Error al consultar la rendición.', 'error');
        cerrarModalDetalle();
    }
}

function cerrarModalDetalle() {
    document.getElementById('modalDetalleRendicion').classList.remove('active');
    rendicionSeleccionadaId = null;
}

/* ==========================================================
   ACCIONES ADMIN
   ========================================================== */
async function procesarValidacionAdmin(accion) {
    if (!rendicionSeleccionadaId) return;

    const motivoRechazo = document.getElementById('motivoRechazoAdmin').value.trim();

    if (accion === 'rechazar' && !motivoRechazo) {
        showToast('Por favor ingrese el motivo de rechazo.', 'warning');
        document.getElementById('motivoRechazoAdmin').focus();
        return;
    }

    if (accion === 'cancelar') {
        if (!confirm('¿Confirma que desea cancelar/anular esta rendición?')) return;
    }

    const payload = {
        accion,
        motivo_rechazo: motivoRechazo
    };

    try {
        const res  = await g2Fetch(`${API_BASE}/rendiciones/${rendicionSeleccionadaId}/revisar`, {
            method:  'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload)
        });
        const json = await res.json();

        if (!json.error) {
            const mensajes = {
                aprobar:  '¡Rendición aprobada! Los cobros han sido liquidados e impactados en Caja/Cobros.',
                rechazar: 'La rendición ha sido rechazada.',
                cancelar: 'La rendición ha sido cancelada/anulada.'
            };
            showToast(mensajes[accion] || 'Operación completada.', accion === 'aprobar' ? 'success' : 'info');
            cerrarModalDetalle();
            cargarContadoresGlobales();
            cargarListadoRendiciones();
        } else {
            showToast('Error: ' + json.mensaje, 'error');
        }
    } catch (err) {
        showToast('Error de comunicación con el servidor.', 'error');
    }
}

// Acciones rápidas desde la tabla
async function aprobarRapido(idRendicion) {
    if (!confirm(`¿Aprobar y liquidar la Rendición #${idRendicion}?`)) return;
    rendicionSeleccionadaId = idRendicion;
    await procesarValidacionAdmin('aprobar');
}

async function rechazarRapido(idRendicion) {
    const motivo = prompt('Ingrese el motivo de rechazo:');
    if (motivo === null) return; // canceló
    if (!motivo.trim()) {
        showToast('Debe ingresar un motivo de rechazo.', 'warning');
        return;
    }
    rendicionSeleccionadaId = idRendicion;
    // Seteamos el campo de motivo temporalmente
    const motivoInput = document.getElementById('motivoRechazoAdmin');
    if (motivoInput) motivoInput.value = motivo;
    await procesarValidacionAdmin('rechazar');
}

/* ==========================================================
   MODAL: DEVOLUCIONES
   ========================================================== */
function abrirModalDevolucion() {
    document.getElementById('formDevolucion').reset();
    document.getElementById('modalDevolucion').classList.add('active');
}

function cerrarModalDevolucion() {
    document.getElementById('modalDevolucion').classList.remove('active');
}

async function guardarDevolucion(e) {
    e.preventDefault();

    const payload = {
        id_rendicion:  parseInt(document.getElementById('devRendicionId').value),
        id_pedido:     parseInt(document.getElementById('devPedidoId').value),
        cliente:       document.getElementById('devCliente').value,
        motivo:        document.getElementById('devMotivo').value,
        observaciones: document.getElementById('devObs').value
    };

    try {
        const res = await g2Fetch(`${API_BASE}/rendiciones/devoluciones`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();

        if (!json.error) {
            showToast('¡Devolución registrada correctamente!', 'success');
            cerrarModalDevolucion();
        } else {
            showToast('Error: ' + json.mensaje, 'error');
        }
    } catch (err) {
        showToast('Error al conectar con el servidor.', 'error');
    }
}

/* ==========================================================
   CERRAR MODALES CON ESC / CLICK FUERA
   ========================================================== */
document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    cerrarModalNuevaRendicion();
    cerrarModalDetalle();
    cerrarModalDevolucion();
});

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            cerrarModalNuevaRendicion();
            cerrarModalDetalle();
            cerrarModalDevolucion();
        }
    });
});

function limpiarFiltros() {
    document.getElementById('filtroRepartidor').value = '';
    document.getElementById('filtroFecha').value = '';
    filtrarEstadoTab('');
}
