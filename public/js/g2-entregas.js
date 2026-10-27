const g2Admin = document.querySelector('[data-g2-role]')?.dataset.g2Role === 'admin';
/**
 * LÓGICA FRONTEND: MÓDULO ENTREGAS (HU #1 y HU #2)
 * ERP DISTRIBUIDORA JUAN XXIII
 */

const API_BASE = '/g2/api';

let pedidosGlobales = [];
let seleccionados = new Set();

document.addEventListener('DOMContentLoaded', () => {
    // Establecer fecha de hoy por defecto
    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('fechaSalida').value = hoy;

    // Cargar datos iniciales
    if (!g2Admin) document.querySelector('section.clay-card')?.setAttribute('hidden', '');
    if (!g2Admin) document.getElementById('btnVentasG4')?.setAttribute('hidden', '');
    cargarZonas();
    cargarRepartidores();
    if (g2Admin) cargarPedidosPendientes();
    cargarEntregas();
});

/**
 * Cargar combo de Zonas
 */
async function cargarZonas() {
    try {
        const res = await g2Fetch(`${API_BASE}/zonas`);
        const json = await res.json();
        if (!json.error && json.data) {
            const select = document.getElementById('filtroZona');
            json.data.forEach(z => {
                const opt = document.createElement('option');
                opt.value = z.id_zona;
                opt.textContent = `${z.nombre} (${z.descripcion || ''})`;
                select.appendChild(opt);
            });
        }
    } catch (err) {
        console.error('Error al cargar zonas:', err);
    }
}

/**
 * Cargar combo de Repartidores
 */
async function cargarRepartidores() {
    try {
        const res = await g2Fetch(`${API_BASE}/repartidores`);
        const json = await res.json();
        if (!json.error && json.data) {
            const select = document.getElementById('repartidorSelect');
            json.data.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u.id_usuario;
                opt.textContent = `${u.nombre}`;
                select.appendChild(opt);
            });

            // Si hay uno solo (ej. Matías), seleccionarlo automáticamente
            if (json.data.length === 1) {
                select.value = json.data[0].id_usuario;
            }
        }
    } catch (err) {
        console.error('Error al cargar repartidores:', err);
    }
}

/**
 * HU #1: Cargar pedidos confirmados pendientes de despacho
 */
async function cargarPedidosPendientes() {
    const idZona = document.getElementById('filtroZona').value;
    const url = idZona ? `${API_BASE}/entregas/pendientes-despacho?zona_id=${idZona}` : `${API_BASE}/entregas/pendientes-despacho`;

    const tbody = document.getElementById('tablaPedidosBody');
    tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; padding: 2rem; color: var(--text-muted);">Cargando pedidos...</td></tr>`;

    try {
        const res = await g2Fetch(url);
        const json = await res.json();

        if (json.error) {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; color: var(--danger); padding: 2rem;">${json.mensaje}</td></tr>`;
            return;
        }

        pedidosGlobales = json.data || [];
        seleccionados.clear();
        document.getElementById('selectAll').checked = false;
        actualizarResumenSeleccion();

        if (pedidosGlobales.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                No hay pedidos pendientes de despacho para la zona seleccionada.
            </td></tr>`;
            return;
        }

        tbody.innerHTML = '';
        pedidosGlobales.forEach(p => {
            const tr = document.createElement('tr');
            
            const bultos = parseInt(p.total_bultos || 0);
            const sueltos = parseInt(p.total_sueltos || 0);
            const itemsCount = (p.items || []).length;

            // Badges de carga física
            let cargaHtml = `<span class="badge-bultos">${bultos} bultos</span>`;
            if (sueltos > 0) {
                cargaHtml += ` <span class="badge-sueltos">+${sueltos} sueltas</span>`;
            }

            tr.innerHTML = `
                <td style="text-align: center;">
                    <input type="checkbox" class="custom-checkbox pedido-checkbox" value="${p.id_venta}" data-bultos="${bultos}" data-sueltos="${sueltos}" onchange="togglePedido(this)">
                </td>
                <td><strong>#PED-${p.id_venta.toString().padStart(4, '0')}</strong></td>
                <td>
                    <strong>${p.cliente_nombre}</strong><br>
                    <small style="color: var(--text-muted);">${p.razon_social || ''}</small>
                </td>
                <td><span class="badge-zona">${p.zona_nombre}</span></td>
                <td>${p.direccion}, ${p.localidad}</td>
                <td style="text-align: center;">${cargaHtml}</td>
                <td style="text-align: center;">
                    <button class="clay-btn clay-btn-secondary" style="min-height: 36px; padding: 0.35rem 0.85rem; font-size: 0.92rem;" onclick="abrirModalArticulos(${p.id_venta})">
                        Ver Items (${itemsCount})
                    </button>
                </td>
                <td style="text-align: right; font-weight: 800; color: #1E3A8A;">
                    $${Number(p.total).toLocaleString('es-AR', {minimumFractionDigits: 2})}
                </td>
                <td>
                    <small style="color: var(--text-muted);">${p.observaciones || 'Sin observaciones'}</small>
                </td>
            `;
            tbody.appendChild(tr);
        });

    } catch (err) {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; color: var(--danger); padding: 2rem;">Error de conexión con el servidor.</td></tr>`;
    }
}

/**
 * Abrir modal para ver el listado exacto de cosas de un pedido (sin ser remito)
 */
function abrirModalArticulos(idVenta) {
    const pedido = pedidosGlobales.find(p => parseInt(p.id_venta) === parseInt(idVenta));
    if (!pedido) return;

    document.getElementById('articulosPedidoSubtitulo').textContent = `Pedido #PED-${pedido.id_venta.toString().padStart(4, '0')} • Fecha: ${pedido.fecha}`;
    document.getElementById('articulosClienteNombre').textContent = pedido.cliente_nombre;
    document.getElementById('articulosDireccion').textContent = `${pedido.direccion}, ${pedido.localidad}`;
    document.getElementById('articulosZona').textContent = pedido.zona_nombre;
    document.getElementById('articulosObservaciones').textContent = pedido.observaciones || 'Sin observaciones de entrega';

    const tbody = document.getElementById('articulosDetalleBody');
    tbody.innerHTML = '';

    (pedido.items || []).forEach(item => {
        const tr = document.createElement('tr');
        const bultos = parseInt(item.bultos || 0);
        const sueltos = parseInt(item.unidades_sueltas || 0);

        tr.innerHTML = `
            <td style="font-weight: 700;">${item.codigo || '-'}</td>
            <td>
                <strong>${item.producto_nombre}</strong><br>
                <small style="color: var(--text-muted);">${item.producto_descripcion || ''}</small>
            </td>
            <td style="text-align: center;">
                ${bultos > 0 ? `<span class="badge-bultos">${bultos} bulto(s)</span>` : '<span style="color: var(--text-muted);">-</span>'}
            </td>
            <td style="text-align: center;">
                ${sueltos > 0 ? `<span class="badge-sueltos">+${sueltos} sueltas</span>` : '<span style="color: var(--text-muted);">-</span>'}
            </td>
        `;
        tbody.appendChild(tr);
    });

    document.getElementById('modalArticulos').style.display = 'flex';
}

function cerrarModalArticulos() {
    document.getElementById('modalArticulos').style.display = 'none';
}

/**
 * Tildar o destildar un pedido individual
 */
function togglePedido(checkbox) {
    const id = parseInt(checkbox.value);
    if (checkbox.checked) {
        seleccionados.add(id);
    } else {
        seleccionados.delete(id);
    }
    actualizarResumenSeleccion();
}

/**
 * Tildar todos los pedidos de la lista
 */
function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.pedido-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = master.checked;
        const id = parseInt(cb.value);
        if (master.checked) {
            seleccionados.add(id);
        } else {
            seleccionados.delete(id);
        }
    });
    actualizarResumenSeleccion();
}

/**
 * Limpiar selección
 */
function limpiarSeleccion() {
    seleccionados.clear();
    document.querySelectorAll('.pedido-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    actualizarResumenSeleccion();
}

/**
 * Actualizar contadores de pedidos y bultos seleccionados
 */
function actualizarResumenSeleccion() {
    let totalBultos = 0;
    let totalSueltos = 0;
    pedidosGlobales.forEach(p => {
        if (seleccionados.has(parseInt(p.id_venta))) {
            totalBultos += parseInt(p.total_bultos || 0);
            totalSueltos += parseInt(p.total_sueltos || 0);
        }
    });

    document.getElementById('contadorSeleccionados').textContent = seleccionados.size;
    document.getElementById('contadorBultos').textContent = totalBultos;
    document.getElementById('contadorSueltos').textContent = totalSueltos;
}

/**
 * HU #2: Armar Entrega y Emitir Remitos
 */
async function armarEntrega(event) {
    event.preventDefault();

    if (seleccionados.size === 0) {
        alert('Por favor seleccioná al menos un pedido de la lista de pendientes.');
        return;
    }

    const repartidorId = document.getElementById('repartidorSelect').value;
    const fechaSalida = document.getElementById('fechaSalida').value;
    const observaciones = document.getElementById('observacionesEntrega').value;

    if (!repartidorId) {
        alert('Por favor asigná un repartidor/chofer para el viaje.');
        return;
    }

    const payload = {
        repartidor_id: parseInt(repartidorId),
        pedidos_ids: Array.from(seleccionados),
        fecha_salida: fechaSalida,
        observaciones: observaciones,
    };

    try {
        const res = await g2Fetch(`${API_BASE}/entregas`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const json = await res.json();

        if (json.error) {
            alert('Error al armar la entrega: ' + json.mensaje);
            return;
        }

        alert(json.mensaje);

        // Recargar pedidos pendientes y lista de entregas
        if (g2Admin) cargarPedidosPendientes();
        cargarEntregas();
        document.getElementById('formArmarEntrega').reset();
        document.getElementById('fechaSalida').value = new Date().toISOString().split('T')[0];

        // Abrir automáticamente el primer remito generado para ver/imprimir
        if (json.data && json.data.remitos && json.data.remitos.length > 0) {
            verRemito(json.data.remitos[0].id_remito);
        }

    } catch (err) {
        console.error(err);
        alert('Error de comunicación con el servidor.');
    }
}

/**
 * Cargar historial de viajes/entregas
 */
async function cargarEntregas() {
    const tbody = document.getElementById('tablaEntregasBody');

    try {
        const res = await g2Fetch(`${API_BASE}/entregas`);
        const json = await res.json();

        if (!json.error && json.data) {
            if (json.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">Aún no hay viajes de entrega creados.</td></tr>`;
                return;
            }

            tbody.innerHTML = '';
            json.data.forEach(e => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>#ENT-${e.id_entrega.toString().padStart(4, '0')}</strong></td>
                    <td>${e.fecha_salida || e.fecha_creacion}</td>
                    <td><strong>${e.repartidor_nombre}</strong></td>
                    <td>${e.cantidad_pedidos} pedidos</td>
                    <td><span class="badge-bultos">${e.total_bultos} bultos</span></td>
                    <td><span class="badge badge-${e.estado}">${e.estado.replace('_', ' ').toUpperCase()}</span></td>
                    <td>
                        <div style="display: flex; gap: 0.4rem; justify-content: center; flex-wrap: wrap;">
                            <button class="clay-btn clay-btn-primary" style="min-height: 36px; padding: 0.35rem 0.85rem; font-size: 0.92rem;" onclick="abrirModalHojaRuta(${e.id_entrega})" title="Ver hoja de ruta y confirmar entregas">
                                📋 Hoja de Ruta
                            </button>
                            <button class="clay-btn clay-btn-secondary" style="min-height: 36px; padding: 0.35rem 0.85rem; font-size: 0.92rem;" onclick="verDetalleEntrega(${e.id_entrega})" title="Ver comprobante de remito">
                                Ver Remito
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    } catch (err) {
        console.error(err);
    }
}

/**
 * HU #3 (Issue #124): Ver Hoja de Ruta de Reparto y gestión de pedidos
 */
async function abrirModalHojaRuta(idEntrega) {
    entregaActualId = idEntrega;
    try {
        const res = await g2Fetch(`${API_BASE}/entregas/${idEntrega}`);
        const json = await res.json();
        if (json.error || !json.data) {
            showToast('No se pudo cargar la hoja de ruta de la entrega.', 'error');
            return;
        }

        const e = json.data;
        document.getElementById('hrEntregaNumero').textContent = `#ENT-${e.id_entrega.toString().padStart(4, '0')}`;
        document.getElementById('hrChofer').textContent = e.repartidor_nombre;
        document.getElementById('hrFechaSalida').textContent = e.fecha_salida || e.fecha_creacion;

        const hrEstado = document.getElementById('hrEstadoBadge');
        hrEstado.className = `badge badge-${e.estado}`;
        hrEstado.textContent = e.estado.replace('_', ' ').toUpperCase();

        const pedidos = e.pedidos || [];
        document.getElementById('hrTotalPedidos').textContent = `${pedidos.length} pedido(s)`;

        let totalBultos = 0;
        pedidos.forEach(p => totalBultos += parseInt(p.total_bultos || 0));
        document.getElementById('hrTotalBultos').textContent = `${totalBultos} bultos`;

        const tbody = document.getElementById('hrPedidosTableBody');
        tbody.innerHTML = '';

        if (pedidos.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">No hay pedidos asignados a este viaje.</td></tr>`;
        } else {
            pedidos.forEach(p => {
                const tr = document.createElement('tr');
                const esEntregado = (p.estado_pedido_entrega === 'entregado');
                const badgeEstado = esEntregado
                    ? `<span class="badge badge-entregada">✅ Entregado</span>`
                    : `<span class="badge badge-en_camino">🚚 En Reparto</span>`;

                const btnConfirmar = esEntregado
                    ? `<span style="color: var(--success); font-weight: 700; font-size: 0.88rem;">Completado</span>`
                    : `<button class="clay-btn clay-btn-success" style="min-height: 32px; padding: 0.3rem 0.75rem; font-size: 0.88rem;" onclick="confirmarEntregaPedido(${e.id_entrega}, ${p.id_venta})" title="Confirmar entrega física realizada">
                        ✅ Confirmar Entrega
                       </button>`;

                const btnRemito = p.id_remito
                    ? `<button class="clay-btn clay-btn-secondary" style="min-height: 32px; padding: 0.3rem 0.75rem; font-size: 0.88rem;" onclick="verRemito(${p.id_remito})" title="Ver remito oficial">
                        📄 Remito
                       </button>`
                    : '';

                tr.innerHTML = `
                    <td><strong style="color: var(--primary);">#PED-${p.id_venta.toString().padStart(4, '0')}</strong></td>
                    <td><strong>${p.cliente_nombre}</strong></td>
                    <td>${p.direccion}, ${p.localidad} <span class="badge-zona">${p.zona_nombre}</span></td>
                    <td style="text-align: center;"><span class="badge-bultos">${p.total_bultos} b</span></td>
                    <td style="text-align: right; font-weight: 700; color: #1E3A8A;">$${Number(p.total).toLocaleString('es-AR', {minimumFractionDigits: 2})}</td>
                    <td style="text-align: center;">${badgeEstado}</td>
                    <td style="text-align: center;">
                        <div style="display: flex; gap: 0.35rem; justify-content: center; align-items: center; flex-wrap: wrap;">
                            ${btnConfirmar}
                            ${btnRemito}
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        document.getElementById('modalHojaRuta').style.display = 'flex';

    } catch (err) {
        console.error(err);
        showToast('Error de comunicación con el servidor.', 'error');
    }
}

let entregaActualId = null;

function cerrarModalHojaRuta() {
    document.getElementById('modalHojaRuta').style.display = 'none';
}

/**
 * Consulta de pedidos confirmados a la API de Ventas del Grupo 4
 */
async function consultarVentasGrupo4() {
    const btn = document.getElementById('btnVentasG4');
    if (btn) btn.disabled = true;
    showToast('Consultando API de Ventas (Grupo 4)...', 'info');

    try {
        const idZona = document.getElementById('filtroZona').value;
        const url = idZona ? `${API_BASE}/ventas/remotas?id_zona=${idZona}` : `${API_BASE}/ventas/remotas`;
        const res = await g2Fetch(url);
        const json = await res.json();

        if (json.error) {
            showToast('Error al consultar ventas: ' + json.mensaje, 'error');
        } else {
            const cant = json.data?.cantidad || 0;
            const modo = 'API de Ventas';
            showToast(`¡${cant} pedidos confirmados sincronizados (${modo})!`, 'success');
            await cargarPedidosPendientes();
        }
    } catch (err) {
        console.error(err);
        showToast('Error de conexión al consultar API de Ventas.', 'error');
    } finally {
        if (btn) btn.disabled = false;
    }
}

/**
 * HU #3 (Issue #124): Confirmar pedido entregado por el chofer
 */
async function confirmarEntregaPedido(idEntrega, idVenta) {
    if (!confirm(`¿Confirmar entrega física del pedido #PED-${idVenta.toString().padStart(4, '0')} al cliente?`)) {
        return;
    }

    try {
        const res = await g2Fetch(`${API_BASE}/entregas/${idEntrega}/pedidos/${idVenta}/entregado`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({})
        });

        const json = await res.json();

        if (json.error) {
            showToast('Error: ' + json.mensaje, 'error');
            return;
        }

        showToast(json.mensaje, 'success');

        // Refrescar modal de hoja de ruta si está abierto
        abrirModalHojaRuta(idEntrega);
        // Refrescar listado de viajes y pedidos pendientes
        cargarEntregas();
        if (g2Admin) cargarPedidosPendientes();

    } catch (err) {
        console.error(err);
        showToast('Error al conectar con el servidor.', 'error');
    }
}

/**
 * Ver detalle de una entrega y abrir su primer remito
 */
async function verDetalleEntrega(idEntrega) {
    try {
        const res = await g2Fetch(`${API_BASE}/entregas/${idEntrega}`);
        const json = await res.json();

        if (!json.error && json.data) {
            const e = json.data;
            if (e.pedidos && e.pedidos.length > 0) {
                const primerRemito = e.pedidos.find(p => p.id_remito);
                if (primerRemito) {
                    verRemito(primerRemito.id_remito);
                } else {
                    alert(`Entrega #${idEntrega} no tiene remitos asociados.`);
                }
            }
        }
    } catch (err) {
        console.error(err);
    }
}

/**
 * Mostrar Comprobante de Remito Oficial en Modal con botón de confirmación de entrega
 */
async function verRemito(idRemito) {
    try {
        const res = await g2Fetch(`${API_BASE}/remitos/${idRemito}`);
        const json = await res.json();

        if (json.error || !json.data) {
            showToast('No se pudo cargar el remito solicitado.', 'error');
            return;
        }

        const r = json.data;
        document.getElementById('remitoNumero').textContent = r.numero_remito;
        document.getElementById('remitoFecha').textContent = r.fecha_emision;
        document.getElementById('remitoChofer').textContent = r.repartidor_nombre;
        document.getElementById('remitoClienteNombre').textContent = r.cliente_nombre;
        document.getElementById('remitoClienteRazon').textContent = r.razon_social || '-';
        document.getElementById('remitoClienteCuit').textContent = r.cuit || 'Consumidor Final';
        document.getElementById('remitoClienteDireccion').textContent = r.direccion;
        document.getElementById('remitoClienteLocalidad').textContent = r.localidad;
        document.getElementById('remitoClienteZona').textContent = r.zona_nombre;

        const tbody = document.getElementById('remitoDetalleBody');
        tbody.innerHTML = '';
        (r.items || []).forEach(item => {
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #E2E8F0';
            tr.innerHTML = `
                <td style="padding: 0.6rem; font-weight: 700;">${item.codigo || '-'}</td>
                <td style="padding: 0.6rem;">${item.descripcion}</td>
                <td style="padding: 0.6rem; text-align: center; font-weight: 700;">${item.cantidad}</td>
                <td style="padding: 0.6rem; text-align: center;">${item.bultos} bulto(s)</td>
            `;
            tbody.appendChild(tr);
        });

        // Configurar acción de entrega en el pie del remito
        const contAccion = document.getElementById('remitoAccionEntrega');
        if (contAccion) {
            if (r.estado === 'entregado') {
                contAccion.innerHTML = `<span class="badge badge-entregada" style="padding: 0.5rem 1rem; font-size: 0.95rem;">✅ Mercadería Entregada Conforme</span>`;
            } else {
                contAccion.innerHTML = `
                    <button class="clay-btn clay-btn-success" onclick="confirmarEntregaPedido(${r.id_entrega}, ${r.id_venta}); cerrarModalRemito();">
                        ✅ Confirmar Pedido como Entregado
                    </button>
                `;
            }
        }

        document.getElementById('modalRemito').style.display = 'flex';

    } catch (err) {
        console.error(err);
        showToast('Error al abrir el remito.', 'error');
    }
}

/**
 * Cerrar modal de remito
 */
function cerrarModalRemito() {
    document.getElementById('modalRemito').style.display = 'none';
}

/**
 * Toast notifications
 */
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

// Cerrar modales con Escape y click fuera
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        cerrarModalArticulos();
        cerrarModalRemito();
        cerrarModalHojaRuta();
    }
});

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            cerrarModalArticulos();
            cerrarModalRemito();
            cerrarModalHojaRuta();
        }
    });
});
