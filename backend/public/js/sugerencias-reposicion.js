const API_URL = '/api/sugerencias-reposicion';
const TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

document.addEventListener('DOMContentLoaded', function() {
    cargarSugerencias();
    cargarResumen();
});

function cargarSugerencias(pagina = 1) {
    fetch(`${API_URL}?pagina=${pagina}`, {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.exito) {
            renderTablaSugerencias(data.datos);
            renderPaginacion(data.paginacion, 'paginacionSugerencias', cargarSugerencias);
        }
    })
    .catch(err => console.error('Error:', err));
}

function cargarResumen() {
    fetch(`${API_URL}/resumen`, {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.exito) {
            document.getElementById('total-pendientes').textContent = data.resumen.sugerencias_pendientes;
            document.getElementById('cantidad-total').textContent = data.resumen.cantidad_total;
            document.getElementById('costo-total').textContent = data.resumen.costo_total_sugerido;
            document.getElementById('porcentaje-reposicion').textContent = data.resumen.porcentaje_reposicion;
        }
    })
    .catch(err => console.error('Error:', err));
}

let sugerenciaActivaId = null;

function renderTablaSugerencias(datos) {
    const tbody = document.getElementById('bodySugerencias');
    tbody.innerHTML = '';

    if (datos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted">No hay sugerencias pendientes</td></tr>';
        return;
    }

    datos.forEach(sugerencia => {
        const badgeMotivo = obtenerBadgeMotivo(sugerencia.motivo_generacion);
        const row = `
            <tr>
                <td><input type="checkbox" class="check-sugerencia" value="${sugerencia.id}" onchange="actualizarContadorSeleccionadas()"></td>
                <td>
                    <a href="javascript:void(0)" onclick="verDetalle(${sugerencia.id})">
                        <strong>${sugerencia.producto.nombre}</strong>
                    </a><br><small>${sugerencia.producto.codigo}</small>
                </td>
                <td>${sugerencia.proveedor.nombre}</td>
                <td>${sugerencia.cantidad_sugerida}</td>
                <td>$${parseFloat(sugerencia.precio_unitario).toFixed(2)}</td>
                <td><strong>$${parseFloat(sugerencia.costo_total).toFixed(2)}</strong></td>
                <td>${parseFloat(sugerencia.velocidad_venta_diaria).toFixed(2)}</td>
                <td>${sugerencia.plazo_entrega_dias}</td>
                <td>${sugerencia.fecha_reorden}</td>
                <td><span class="${badgeMotivo}">${obtenerTextoMotivo(sugerencia.motivo_generacion)}</span></td>
                <td><span class="badge bg-primary">${sugerencia.estado}</span></td>
                <td>
                    <button class="clay-btn clay-btn-sm clay-btn-primary" onclick="generarOrdenDesdeSugerencia(${sugerencia.id})">
                        🛒 Generar orden
                    </button>
                    <button class="clay-btn clay-btn-sm clay-btn-success" onclick="procesarSugerencia(${sugerencia.id})">
                        ✓ Procesar
                    </button>
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });

    actualizarContadorSeleccionadas();
}

function toggleTodas(checkboxMaestro) {
    document.querySelectorAll('.check-sugerencia').forEach(chk => {
        chk.checked = checkboxMaestro.checked;
    });
    actualizarContadorSeleccionadas();
}

function obtenerIdsSeleccionados() {
    return Array.from(document.querySelectorAll('.check-sugerencia:checked')).map(chk => parseInt(chk.value, 10));
}

function actualizarContadorSeleccionadas() {
    const ids = obtenerIdsSeleccionados();
    document.getElementById('contadorSeleccionadas').textContent = ids.length;
    document.getElementById('btnProcesarLote').disabled = ids.length === 0;
}

function procesarLoteSeleccionadas() {
    const ids = obtenerIdsSeleccionados();
    if (ids.length === 0) return;
    if (!confirm(`¿Procesar ${ids.length} sugerencia(s) seleccionada(s)?`)) return;

    fetch(`${API_URL}/procesar-lote`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ ids })
    })
    .then(res => res.json())
    .then(data => {
        if (data.exito) {
            alert(`✓ Se procesaron ${data.total_procesadas} sugerencia(s)`);
            cargarSugerencias();
            cargarResumen();
        } else {
            alert('❌ Error al procesar en lote');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('❌ Error en la solicitud');
    });
}

function verDetalle(id) {
    sugerenciaActivaId = id;

    fetch(`${API_URL}`, {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (!data.exito) return;
        const sugerencia = data.datos.find(s => s.id === id);
        if (!sugerencia) return;

        document.getElementById('detalleContent').innerHTML = `
            <p><strong>Producto:</strong> ${sugerencia.producto.nombre} (${sugerencia.producto.codigo})</p>
            <p><strong>Proveedor:</strong> ${sugerencia.proveedor.nombre}</p>
            <p><strong>Cantidad sugerida:</strong> ${sugerencia.cantidad_sugerida}</p>
            <p><strong>Costo total:</strong> $${parseFloat(sugerencia.costo_total).toFixed(2)}</p>
            <p><strong>Motivo:</strong> ${obtenerTextoMotivo(sugerencia.motivo_generacion)}</p>
            <p><strong>Fecha de reorden:</strong> ${sugerencia.fecha_reorden}</p>
            <p><strong>Observaciones:</strong> ${sugerencia.observaciones ?? '—'}</p>
            ${sugerencia.motivo_rechazo ? `<p><strong>Motivo del rechazo:</strong> ${sugerencia.motivo_rechazo}</p>` : ''}
            ${sugerencia.id_orden_compra
                ? `<p><strong>Orden generada:</strong> <a href="/Interfaz/compras.html" target="_blank">#${sugerencia.id_orden_compra}</a></p>`
                : ''}
        `;

        document.getElementById('motivoRechazo').value = sugerencia.motivo_rechazo ?? '';

        const modal = new bootstrap.Modal(document.getElementById('detalleModal'));
        modal.show();
    })
    .catch(err => console.error('Error:', err));
}

function procesarDesdeModal() {
    if (!sugerenciaActivaId) return;
    procesarSugerencia(sugerenciaActivaId);
    bootstrap.Modal.getInstance(document.getElementById('detalleModal'))?.hide();
}

function rechazarDesdeModal() {
    if (!sugerenciaActivaId) return;
    if (!confirm('¿Rechazar esta sugerencia?')) return;

    const motivo = document.getElementById('motivoRechazo')?.value.trim() || null;

    fetch(`${API_URL}/${sugerenciaActivaId}/rechazar`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ motivo })
    })
    .then(async res => {
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.exito) {
            throw new Error(data.error || `Error ${res.status}`);
        }
        alert('✓ Sugerencia rechazada');
        bootstrap.Modal.getInstance(document.getElementById('detalleModal'))?.hide();
        cargarSugerencias();
        cargarResumen();
    })
    .catch(err => {
        console.error('Error:', err);
        alert('❌ Error al rechazar: ' + err.message);
    });
}

function generarSugerencias() {
    if (!confirm('¿Deseas generar nuevas sugerencias automáticas?')) return;

    document.body.style.cursor = 'wait';

    fetch(`${API_URL}/generar`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        document.body.style.cursor = 'auto';

        if (data.exito) {
            alert(`✓ Se generaron ${data.total_generadas} sugerencias correctamente`);
            cargarSugerencias();
            cargarResumen();
        } else {
            alert('❌ Error al generar sugerencias');
        }
    })
    .catch(err => {
        document.body.style.cursor = 'auto';
        console.error('Error:', err);
        alert('❌ Error en la solicitud');
    });
}

function procesarSugerencia(id) {
    if (!confirm('¿Procesar esta sugerencia?')) return;

    fetch(`${API_URL}/${id}/procesar`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.exito) {
            alert('✓ Sugerencia procesada correctamente');
            cargarSugerencias();
            cargarResumen();
        } else {
            alert('❌ Error al procesar');
        }
    })
    .catch(err => console.error('Error:', err));
}

/**
 * PC07 - "Debe permitir generar una orden de compra a partir de la sugerencia"
 * y "asociar la orden al proveedor correspondiente".
 *
 * El proveedor no se elige acá: lo asigna el backend (el activo de mejor precio
 * con el que se generó la sugerencia), que es justamente lo que pide el criterio.
 * El botón avisa cuál va a ser para que el usuario no la asocie a ciegas.
 */
function generarOrdenDesdeSugerencia(id) {
    fetch(`${API_URL}`, { headers: { 'Accept': 'application/json' } })
    .then(res => res.json())
    .then(data => {
        if (!data.exito) return;
        const sugerencia = data.datos.find(s => s.id === id);
        if (!sugerencia) return;

        const proveedor = sugerencia.proveedor?.nombre ?? 'el proveedor asignado';
        const confirmado = confirm(
            `¿Generar la orden de compra para "${sugerencia.producto.nombre}"?\n\n` +
            `Proveedor: ${proveedor}\n` +
            `Cantidad: ${sugerencia.cantidad_sugerida}\n` +
            `Precio unitario: $${parseFloat(sugerencia.precio_unitario).toFixed(2)}\n` +
            `Total estimado: $${parseFloat(sugerencia.costo_total).toFixed(2)}`
        );
        if (!confirmado) return;

        return fetch(`${API_URL}/${id}/generar-orden`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(async res => {
            const r = await res.json().catch(() => ({}));
            if (!res.ok || !r.exito) {
                throw new Error(r.error || `Error ${res.status}`);
            }
            return r;
        });
    })
    .then(r => {
        if (!r) return;
        alert(
            `✓ Orden ${r.orden.numero_orden} generada\n\n` +
            `Proveedor: ${r.orden.proveedor}\n` +
            `Producto: ${r.orden.producto}\n` +
            `Total estimado: $${parseFloat(r.orden.total_estimado).toFixed(2)}`
        );
        bootstrap.Modal.getInstance(document.getElementById('detalleModal'))?.hide();
        cargarSugerencias();
        cargarResumen();
    })
    .catch(err => {
        console.error('Error:', err);
        alert('❌ No se pudo generar la orden: ' + err.message);
    });
}

function exportarSugerencias() {
    fetch(`${API_URL}`, {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.exito) {
            descargarCSV(data.datos, 'sugerencias-reposicion.csv');
        }
    })
    .catch(err => console.error('Error:', err));
}

function renderPaginacion(paginacion, elementId, callback) {
    const container = document.getElementById(elementId);
    container.innerHTML = '';

    if (paginacion.total_paginas <= 1) return;

    let html = '<div class="d-flex justify-content-center gap-2">';

    if (paginacion.pagina_actual > 1) {
        html += `<button class="clay-btn clay-btn-sm clay-btn-secondary" onclick="${callback.name}(1)">« Primera</button>`;
        html += `<button class="clay-btn clay-btn-sm clay-btn-secondary" onclick="${callback.name}(${paginacion.pagina_actual - 1})">‹ Anterior</button>`;
    }

    html += `<span class="clay-btn clay-btn-sm" style="cursor: default; background: #e8ecf1;">Página ${paginacion.pagina_actual} de ${paginacion.total_paginas}</span>`;

    if (paginacion.tiene_siguiente) {
        html += `<button class="clay-btn clay-btn-sm clay-btn-secondary" onclick="${callback.name}(${paginacion.pagina_actual + 1})">Siguiente ›</button>`;
        html += `<button class="clay-btn clay-btn-sm clay-btn-secondary" onclick="${callback.name}(${paginacion.total_paginas})">Última »</button>`;
    }

    html += '</div>';
    container.innerHTML = html;
}

function obtenerBadgeMotivo(motivo) {
    const badges = {
        'bajo_stock': 'badge-warning',
        'proximo_vencer': 'badge-danger',
        'agotamiento_inmediato': 'badge-info'
    };
    return badges[motivo] || 'badge-secondary';
}

function obtenerTextoMotivo(motivo) {
    const textos = {
        'bajo_stock': '📉 Stock Bajo',
        'proximo_vencer': '⏰ Próx. Vencer',
        'agotamiento_inmediato': '🚨 Agotam.'
    };
    return textos[motivo] || motivo;
}

function obtenerToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
           localStorage.getItem('api_token') || '';
}

function descargarCSV(datos, nombreArchivo) {
    if (datos.length === 0) return;

    const headers = Object.keys(datos[0]);
    const csv = [headers.join(',')];

    datos.forEach(fila => {
        const valores = headers.map(header => {
            let valor = fila[header];
            if (typeof valor === 'string' && valor.includes(',')) {
                valor = `"${valor}"`;
            }
            return valor;
        });
        csv.push(valores.join(','));
    });

    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = nombreArchivo;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
}

function limpiarFiltros() {
    document.getElementById('filtro-motivo').value = '';
    document.getElementById('filtro-estado').value = 'pendiente';
    cargarSugerencias();
}
