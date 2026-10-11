const API_URL = '/api/vencimientos';
const TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

let criticidadCargada = false;

// S11 - Estado de orden y filtros de la tabla. Vive fuera de la función para que
// sobreviva a la re-render de la tabla y a la paginación.
let ordenActual = { columna: 'fecha_vencimiento', direccion: 'asc' };
let filtroActual = {};
// El rango de días también se recuerda: sin esto, ordenar o limpiar volvía
// silenciosamente a los 30 días del default y el usuario perdía el filtro.
let diasActual = 30;

document.addEventListener('DOMContentLoaded', function() {
    cargarProximosAVencer();
    cargarAlertas();

    // La pestaña "Por Criticidad" no tenía ningún disparador: sus contenedores
    // se quedaban en "Cargando..." para siempre. Se pide al inicio y el flag
    // evita repetir la llamada si el usuario abre la pestaña después.
    cargarPorCriticidad();

    document.getElementById('tab-porCriticidad')
        ?.addEventListener('shown.bs.tab', cargarPorCriticidad);

    // S11 - "Debe permitir buscar o filtrar productos de la lista".
    // El buscador del header filtra al vuelo; el del modal sigue siendo válido
    // para combinarlo con el rango de días.
    const buscar = document.getElementById('buscarProductoInline');
    if (buscar) {
        let temporizador;
        buscar.addEventListener('input', function() {
            clearTimeout(temporizador);
            temporizador = setTimeout(() => {
                const termino = buscar.value.trim();
                // No se deja "producto: undefined": URLSearchParams convierte
                // undefined en la CADENA "undefined" y el backend la usaba como
                // filtro, dejando la lista vacia al borrar el buscador.
                filtroActual = { ...filtroActual };
                if (termino) {
                    filtroActual.producto = termino;
                } else {
                    delete filtroActual.producto;
                }
                cargarProximosAVencer(1);
            }, 300);
        });
    }

    document.getElementById('limpiarFiltros')
        ?.addEventListener('click', limpiarFiltros);

    marcarEncabezadosOrdenables();

    // Los <th> ordenables son botones de verdad: click, Enter y Espacio ordenan.
    // Los listeners se registran UNA sola vez acá (no en marcarEncabezadosOrdenables,
    // que se corre en cada render y duplicaria los handlers).
    document.querySelectorAll('[data-orden]').forEach(th => {
        th.addEventListener('click', function() {
            ordenarPor(th.dataset.orden);
        });
        th.addEventListener('keydown', function(ev) {
            if (ev.key === 'Enter' || ev.key === ' ') {
                ev.preventDefault();
                ordenarPor(th.dataset.orden);
            }
        });
    });
});

/** Relega la tabla a los filtros y al orden actuales, desde la página 1. */
function recargar() {
    cargarProximosAVencer(1);
}

/**
 * Los defaults leen el estado global a propósito: antes los callers pasaban
 * filtros = {} y el buscador inline terminaba sin enviar `producto`, y cualquier
 * cambio de orden borraba el filtro aplicado.
 */
function cargarProximosAVencer(pagina = 1, dias = diasActual, filtros = filtroActual) {
    diasActual = dias;

    const params = new URLSearchParams({
        dias,
        pagina,
        orden: ordenActual.columna,
        direccion: ordenActual.direccion,
        ...filtros
    });

    fetch(`${API_URL}/proximos?${params.toString()}`, {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.exito) {
            renderTablaProximos(data.datos);
            renderPaginacion(data.paginacion, 'paginacionProximos',
                (p) => cargarProximosAVencer(p, dias, filtros));
            actualizarResumen(data.resumen);
            // El backend normaliza columna y dirección (lista blanca): se adopta
            // su respuesta en vez de asumir que el pedido se aplicó.
            if (data.orden) ordenActual = data.orden;
            marcarEncabezadosOrdenables();
            mostrarFiltroActivo();
        }
    })
    .catch(err => console.error('Error:', err));
}

/**
 * S11 - Alterna el orden de una columna. Volver a tocar la misma columna
 * invierte asc/desc; tocar otra columna arranca en asc.
 */
function ordenarPor(columna) {
    if (ordenActual.columna === columna) {
        ordenActual.direccion = ordenActual.direccion === 'asc' ? 'desc' : 'asc';
    } else {
        ordenActual.columna = columna;
        ordenActual.direccion = 'asc';
    }
    recargar();
}

/** Pone la flecha en la columna activa y deja los encabezados accesibles por teclado. */
function marcarEncabezadosOrdenables() {
    document.querySelectorAll('[data-orden]').forEach(th => {
        const activa = th.dataset.orden === ordenActual.columna;
        th.classList.toggle('orden-activo', activa);
        th.setAttribute('aria-sort', activa ? (ordenActual.direccion === 'asc' ? 'ascending' : 'descending') : 'none');

        let flecha = th.querySelector('.orden-flecha');
        if (!flecha) {
            flecha = document.createElement('span');
            flecha.className = 'orden-flecha';
            th.appendChild(flecha);
        }
        flecha.textContent = activa ? (ordenActual.direccion === 'asc' ? ' ▲' : ' ▼') : ' ↕';
    });
}

/** Muestra el filtro activo para que el usuario sepa por qué ve menos filas. */
function mostrarFiltroActivo() {
    const aviso = document.getElementById('filtroActivo');
    if (!aviso) return;

    const partes = [];
    if (filtroActual.producto) partes.push(`producto "${filtroActual.producto}"`);

    if (partes.length === 0) {
        aviso.classList.add('d-none');
        aviso.innerHTML = '';
        return;
    }

    aviso.classList.remove('d-none');
    aviso.innerHTML = `<span class="badge bg-info">Filtrando por ${partes.join(', ')}</span>`;
}

function limpiarFiltros() {
    filtroActual = {};
    ordenActual = { columna: 'fecha_vencimiento', direccion: 'asc' };
    diasActual = 30;

    const buscar = document.getElementById('buscarProductoInline');
    if (buscar) buscar.value = '';
    const modalBuscar = document.getElementById('filtro-producto');
    if (modalBuscar) modalBuscar.value = '';
    const modalDias = document.getElementById('filtro-dias');
    if (modalDias) modalDias.value = '30';

    recargar();
}

function cargarPorCriticidad(forzar = false) {
    if (criticidadCargada && !forzar) return;
    criticidadCargada = true;

    fetch(`${API_URL}/por-criticidad`, {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.exito) {
            renderCriticos(data.critico);
            renderProximos(data.proximo);
            renderSeguros(data.seguro);
        }
    })
    .catch(err => console.error('Error:', err));
}

function cargarAlertas() {
    fetch(`${API_URL}/alertas`, {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.exito) {
            actualizarAlertas(data.alertas);
        }
    })
    .catch(err => console.error('Error:', err));
}

function renderTablaProximos(datos) {
    const tbody = document.getElementById('bodyProximos');
    tbody.innerHTML = '';

    if (datos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No hay productos próximos a vencer</td></tr>';
        return;
    }

    datos.forEach(lote => {
        const badgeClass = obtenerBadgeClass(lote.vencimiento.estado);
        const row = `
            <tr>
                <td><strong>${lote.producto.nombre}</strong><br><small>${lote.producto.codigo}</small></td>
                <td>${lote.lote.numero}</td>
                <td>${lote.lote.cantidad}</td>
                <td>${lote.vencimiento.fecha}</td>
                <td><strong>${lote.vencimiento.dias_restantes}</strong></td>
                <td><span class="${badgeClass}">${lote.vencimiento.urgencia}</span></td>
                <td>
                    <button class="clay-btn clay-btn-sm clay-btn-secondary">
                        👁️ Ver
                    </button>
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

function renderCriticos(data) {
    const container = document.getElementById('criticosContainer');
    container.innerHTML = `
        <div class="text-center">
            <h2 class="text-danger">${data.total}</h2>
            <p>Productos</p>
            <p class="text-muted"><small>${data.cantidad} unidades en riesgo</small></p>
        </div>
    `;
}

function renderProximos(data) {
    const container = document.getElementById('proximosContainer');
    container.innerHTML = `
        <div class="text-center">
            <h2 class="text-warning">${data.total}</h2>
            <p>Productos</p>
            <p class="text-muted"><small>${data.cantidad} unidades</small></p>
        </div>
    `;
}

function renderSeguros(data) {
    const container = document.getElementById('segurosContainer');
    container.innerHTML = `
        <div class="text-center">
            <h2 class="text-success">${data.total}</h2>
            <p>Productos</p>
            <p class="text-muted"><small>${data.cantidad} unidades</small></p>
        </div>
    `;
}

function actualizarResumen(resumen) {
    document.getElementById('total-vencidos').textContent = resumen.total_vencidos;
    document.getElementById('total-criticos').textContent = resumen.criticos_7dias;
    document.getElementById('total-proximos').textContent = resumen.proximos_30dias;
    document.getElementById('total-cantidad').textContent = resumen.total_items_en_riesgo;
}

function actualizarAlertas(alertas) {
    // Actualizar tarjetas de alerta con datos reales
}

function aplicarFiltros() {
    const dias = document.getElementById('filtro-dias').value;
    const producto = document.getElementById('filtro-producto')?.value?.trim();

    // Se escribe en el estado central para que el buscador del header y el del
    // modal no se pisen entre sí.
    filtroActual = producto ? { producto } : {};

    // El buscador del header refleja lo que se acaba de aplicar.
    const buscar = document.getElementById('buscarProductoInline');
    if (buscar) buscar.value = producto || '';

    cargarProximosAVencer(1, parseInt(dias), filtroActual);

    // getInstance() devolvía null si el modal aún no se había mostrado y el hide()
    // lanzaba TypeError, dejando la pantalla a medias. El operador opcional lo evita.
    bootstrap.Modal.getInstance(document.getElementById('filtrosModal'))?.hide();
}

function exportarReporte() {
    fetch(`${API_URL}/reporte`, {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.exito) {
            descargarCSV(data.datos, 'reporte-vencimientos.csv');
        }
    })
    .catch(err => console.error('Error:', err));
}

function renderPaginacion(paginacion, elementId, callback) {
    const container = document.getElementById(elementId);
    container.innerHTML = '';

    if (paginacion.total_paginas <= 1) return;

    const html = `
        <div class="d-flex justify-content-center gap-2">
            ${paginacion.pagina_actual > 1 ? `<button class="clay-btn clay-btn-sm clay-btn-secondary" onclick="callback(1)">« Primera</button>` : ''}
            ${paginacion.pagina_actual > 1 ? `<button class="clay-btn clay-btn-sm clay-btn-secondary" onclick="callback(${paginacion.pagina_actual - 1})">‹ Anterior</button>` : ''}

            <span class="clay-btn clay-btn-sm" style="cursor: default; background: #e8ecf1;">
                Página ${paginacion.pagina_actual} de ${paginacion.total_paginas}
            </span>

            ${paginacion.tiene_siguiente ? `<button class="clay-btn clay-btn-sm clay-btn-secondary" onclick="callback(${paginacion.pagina_actual + 1})">Siguiente ›</button>` : ''}
            ${paginacion.pagina_actual < paginacion.total_paginas ? `<button class="clay-btn clay-btn-sm clay-btn-secondary" onclick="callback(${paginacion.total_paginas})">Última »</button>` : ''}
        </div>
    `;

    container.innerHTML = html;
}

function obtenerBadgeClass(estado) {
    const clases = {
        'critico': 'badge-critico',
        'proximo': 'badge-proximo',
        'seguro': 'badge-seguro',
        'vencido': 'badge-critico'
    };
    return clases[estado] || 'badge-seguro';
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
