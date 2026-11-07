@extends('layouts.app')

@section('titulo', 'Conciliación Bancaria')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/g2-finanzas.css') }}">
<style>
/* Estilos específicos para Conciliación Bancaria y Split Screen (Issue #147) */
.conciliacion-container {
    padding: 1.5rem;
    max-width: 1400px;
    margin: 0 auto;
    width: 100%;
}
.conciliacion-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}
.conciliacion-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1E293B;
}
.header-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.btn-gear {
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    padding: 0.6rem 0.8rem;
    cursor: pointer;
    font-size: 1.1rem;
    transition: all 0.2s;
}
.btn-gear:hover {
    background: #F1F5F9;
}
.filter-panel {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 1rem 1.25rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.filter-panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: #334155;
}
.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.75rem;
    align-items: end;
}
.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}
.filter-group label {
    font-size: 0.82rem;
    font-weight: 600;
    color: #64748B;
}
.filter-input {
    padding: 0.45rem 0.65rem;
    border: 1px solid #CBD5E1;
    border-radius: 6px;
    font-size: 0.9rem;
    outline: none;
}
.filter-input:focus {
    border-color: #2563EB;
    box-shadow: 0 0 0 2px rgba(37,99,235,0.15);
}
.split-screen-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
@media (max-width: 992px) {
    .split-screen-grid {
        grid-template-columns: 1fr;
    }
}
.table-panel {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    min-height: 480px;
}
.table-header-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    font-size: 1.05rem;
    font-weight: 700;
    color: #1E293B;
}
.table-responsive {
    overflow-x: auto;
    flex-grow: 1;
}
.table-erp {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
}
.table-erp th, .table-erp td {
    padding: 0.65rem 0.75rem;
    text-align: left;
    border-bottom: 1px solid #F1F5F9;
}
.table-erp th {
    background: #F8FAFC;
    color: #475569;
    font-weight: 600;
    position: sticky;
    top: 0;
}
.table-erp tr.selected-row {
    border: 2px solid #2563EB;
    font-weight: bold;
    background-color: #EFF6FF !important;
}
.badge-status {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}
.badge-pendiente { background: #FEF3C7; color: #92400E; }
.badge-matcheado { background: #DCFCE7; color: #166534; }
.badge-en_disputa { background: #FEE2E2; color: #991B1B; }
.badge-no_aplica { background: #F1F5F9; color: #475569; }

.contextual-bar {
    background: #1E293B;
    color: #FFFFFF;
    padding: 0.85rem 1.5rem;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
}
.reconciliation-area {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 1.25rem;
}
.nav-tabs {
    display: flex;
    gap: 0.5rem;
    border-bottom: 2px solid #E2E8F0;
    margin-bottom: 1rem;
}
.nav-tab-btn {
    padding: 0.6rem 1.2rem;
    border: none;
    background: none;
    cursor: pointer;
    font-weight: 600;
    color: #64748B;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
}
.nav-tab-btn.active {
    color: #2563EB;
    border-bottom-color: #2563EB;
}

/* Modales */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.6);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.modal-overlay.active {
    display: flex;
}
.modal-box {
    background: #FFFFFF;
    border-radius: 12px;
    max-width: 650px;
    width: 90%;
    padding: 1.5rem;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    border-bottom: 1px solid #E2E8F0;
    padding-bottom: 0.75rem;
}
.drop-zone {
    border: 2px dashed #94A3B8;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    background: #F8FAFC;
    cursor: pointer;
    margin-bottom: 1rem;
}
.drop-zone:hover {
    border-color: #2563EB;
    background: #EFF6FF;
}
</style>
@endpush

@section('contenido')
<div class="conciliacion-container">
    {{-- 1. Encabezado y acciones generales --}}
    <div class="conciliacion-header">
        <div>
            <h1>Conciliación Bancaria</h1>
            <p style="color:#64748B; font-size: 0.95rem;">Auditoría, importación de extractos y conciliación automática/manual.</p>
        </div>
        <div class="header-actions">
            <button type="button" class="btn-gear" onclick="abrirConfiguracion()" title="Configuración">⚙</button>
            <button type="button" class="clay-btn-primary" onclick="abrirModalExtracto()" style="background:#2563EB; color:#fff; border:none; padding:0.6rem 1.2rem; border-radius:8px; font-weight:600; cursor:pointer;">
                Subir extracto bancario
            </button>
        </div>
    </div>

    {{-- 2. Filtros de Movimientos Bancarios --}}
    <div class="filter-panel">
        <div class="filter-panel-header">
            <span>Filtros de movimientos bancarios</span>
            <button type="button" onclick="limpiarFiltrosBancarios()" style="background:none; border:none; color:#2563EB; cursor:pointer; font-size:0.85rem; font-weight:600;">Limpiar filtros</button>
        </div>
        <div class="filter-grid">
            <div class="filter-group">
                <label>Fecha desde</label>
                <input type="date" id="banco_fecha_desde" class="filter-input" onchange="cargarMovimientosBancarios()">
            </div>
            <div class="filter-group">
                <label>Fecha hasta</label>
                <input type="date" id="banco_fecha_hasta" class="filter-input" onchange="cargarMovimientosBancarios()">
            </div>
            <div class="filter-group">
                <label>Estado</label>
                <select id="banco_estado" class="filter-input" onchange="cargarMovimientosBancarios()">
                    <option value="">Todos</option>
                    <option value="pendiente" selected>Pendiente</option>
                    <option value="matcheado">Matcheado</option>
                    <option value="en_disputa">En disputa</option>
                    <option value="no_aplica">No aplica</option>
                    <option value="revision_manual">Revisión manual</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Tipo movimiento</label>
                <select id="banco_tipo" class="filter-input" onchange="cargarMovimientosBancarios()">
                    <option value="">Todos</option>
                    <option value="cobranza_cliente">Cobranza cliente</option>
                    <option value="pago_proveedor">Pago proveedor</option>
                    <option value="gasto_bancario">Gasto bancario</option>
                    <option value="impuesto_bancario">Impuesto bancario</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            <div class="filter-group">
                <label>CUIT / Descripción</label>
                <input type="text" id="banco_q" class="filter-input" placeholder="Buscar texto..." oninput="debounce(cargarMovimientosBancarios, 400)()">
            </div>
        </div>
    </div>

    {{-- 3. Filtros de Cobranzas ERP --}}
    <div class="filter-panel">
        <div class="filter-panel-header">
            <span>Filtros de cobranzas ERP</span>
            <button type="button" onclick="limpiarFiltrosCobranzas()" style="background:none; border:none; color:#2563EB; cursor:pointer; font-size:0.85rem; font-weight:600;">Limpiar filtros</button>
        </div>
        <div class="filter-grid">
            <div class="filter-group">
                <label>Fecha desde</label>
                <input type="date" id="erp_fecha_desde" class="filter-input" onchange="cargarCobranzasErp()">
            </div>
            <div class="filter-group">
                <label>Fecha hasta</label>
                <input type="date" id="erp_fecha_hasta" class="filter-input" onchange="cargarCobranzasErp()">
            </div>
            <div class="filter-group">
                <label>Estado cobranza</label>
                <select id="erp_estado" class="filter-input" onchange="cargarCobranzasErp()">
                    <option value="">Todos</option>
                    <option value="confirmado" selected>Confirmado / Aprobado</option>
                    <option value="anulado">Anulado</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Comprobante</label>
                <input type="text" id="erp_comprobante" class="filter-input" placeholder="Nro comprobante..." oninput="debounce(cargarCobranzasErp, 400)()">
            </div>
        </div>
    </div>

    {{-- 4. Split Screen: Listados en Paralelo --}}
    <div class="split-screen-grid">
        {{-- Columna Izquierda: Movimientos Bancarios --}}
        <div class="table-panel">
            <div class="table-header-title">
                <span>Movimientos Bancarios (Extracto)</span>
                <span id="banco_total_badge" style="font-size:0.85rem; color:#64748B; font-weight:normal;">0 registros</span>
            </div>
            <div class="table-responsive">
                <table class="table-erp">
                    <thead>
                        <tr>
                            <th style="width:30px;"><input type="checkbox" id="chk_all_banco" onclick="toggleSelectAllBanco(this)"></th>
                            <th>Fecha</th>
                            <th>Descripción</th>
                            <th>CUIT</th>
                            <th>Comprobante</th>
                            <th>Monto</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tbody_banco">
                        <tr><td colspan="7" style="text-align:center; color:#94A3B8; padding:2rem;">Cargando movimientos...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Columna Derecha: Cobranzas ERP --}}
        <div class="table-panel">
            <div class="table-header-title">
                <span>Cobranzas ERP (Rendiciones / Caja)</span>
                <span id="erp_total_badge" style="font-size:0.85rem; color:#64748B; font-weight:normal;">0 registros</span>
            </div>
            <div class="table-responsive">
                <table class="table-erp">
                    <thead>
                        <tr>
                            <th style="width:30px;"><input type="checkbox" id="chk_all_erp" onclick="toggleSelectAllErp(this)"></th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Medio</th>
                            <th>Comprobante</th>
                            <th>Monto</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tbody_erp">
                        <tr><td colspan="7" style="text-align:center; color:#94A3B8; padding:2rem;">Cargando cobranzas ERP...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 5. Barra de Acciones Contextual --}}
    <div id="contextual_bar" class="contextual-bar" style="display:none;">
        <span id="contextual_selection_text">0 movimientos y 0 cobranzas seleccionados</span>
        <div style="display:flex; gap:0.75rem;">
            <button type="button" onclick="organizarSeleccion()" style="background:#2563EB; color:#fff; border:none; padding:0.5rem 1rem; border-radius:6px; font-weight:600; cursor:pointer;">Organizar selección</button>
            <button type="button" onclick="quitarSeleccion()" style="background:#475569; color:#fff; border:none; padding:0.5rem 1rem; border-radius:6px; cursor:pointer;">Quitar selección</button>
        </div>
    </div>

    {{-- 6. Área de Conciliación --}}
    <div class="reconciliation-area">
        <div class="nav-tabs">
            <button type="button" class="nav-tab-btn active" onclick="cambiarPestana('manual', this)">Manual</button>
            <button type="button" class="nav-tab-btn" onclick="cambiarPestana('alta_confianza', this)">Alta confianza</button>
            <button type="button" class="nav-tab-btn" onclick="cambiarPestana('para_revisar', this)">Para revisar</button>
            <button type="button" class="nav-tab-btn" onclick="cambiarPestana('historial', this)">Historial</button>
        </div>
        <div id="tab_content_area" style="min-height:120px; color:#475569;">
            <p style="margin-top:1rem;">Seleccioná registros bancarios y cobranzas del listado superior para armar grupos y conciliar.</p>
        </div>
    </div>
</div>

{{-- MODAL: Subir Extracto Bancario --}}
<div id="modal_extracto" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 style="font-weight:700;">Subir extracto bancario</h3>
            <button type="button" onclick="cerrarModalExtracto()" style="background:none; border:none; font-size:1.25rem; cursor:pointer;">✕</button>
        </div>
        <p style="font-size:0.9rem; color:#64748B; margin-bottom:1rem;">
            Seleccioná la planilla oficial (.xls o .txt) emitida por Banco Provincia (BIP). El sistema calculará el Hash SHA-256 para auditoría y clasificará las acreditaciones.
        </p>

        <form id="form_subir_extracto" onsubmit="enviarExtracto(event)">
            @csrf
            <div class="drop-zone" onclick="document.getElementById('input_archivo_extracto').click()">
                <div style="font-size:2rem; margin-bottom:0.5rem;">📄</div>
                <div style="font-weight:600; color:#1E293B;" id="drop_zone_text">Arrastrá tu archivo aquí o hacé clic para buscar</div>
                <div style="font-size:0.8rem; color:#64748B; margin-top:0.25rem;">Formatos soportados: .xls y .txt (Hasta 20MB)</div>
                <input type="file" id="input_archivo_extracto" name="archivo" accept=".xls,.txt" style="display:none;" onchange="archivoSeleccionado(this)">
            </div>

            <div id="import_spinner" style="display:none; text-align:center; padding:1rem; font-weight:600; color:#2563EB;">
                Procesando extracto bancario...
            </div>

            <div id="import_resultado" style="display:none; padding:1rem; border-radius:8px; margin-bottom:1rem; font-size:0.9rem;"></div>

            <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1rem;">
                <button type="button" onclick="cerrarModalExtracto()" style="padding:0.6rem 1rem; border:1px solid #CBD5E1; border-radius:6px; background:#fff; cursor:pointer;">Cerrar</button>
                <button type="submit" id="btn_submit_extracto" style="padding:0.6rem 1.25rem; border:none; border-radius:6px; background:#2563EB; color:#fff; font-weight:600; cursor:pointer;" disabled>Subir y procesar</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: Configuración --}}
<div id="modal_config" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 style="font-weight:700;">Configuración de Conciliación</h3>
            <button type="button" onclick="cerrarConfiguracion()" style="background:none; border:none; font-size:1.25rem; cursor:pointer;">✕</button>
        </div>
        <div style="font-size:0.9rem; color:#475569;">
            <p><strong>Cuentas bancarias:</strong> Banco Provincia (Cuenta corriente 6206-540520/3).</p>
            <p style="margin-top:0.5rem;"><strong>Apariencia y visualización:</strong> Paginación predeterminada en 25 filas.</p>
            <p style="margin-top:0.5rem;"><strong>Inmutabilidad:</strong> Los movimientos bancarios son de solo lectura bajo el principio de operaciones en firme de BIP.</p>
        </div>
        <div style="display:flex; justify-content:flex-end; margin-top:1.5rem;">
            <button type="button" onclick="cerrarConfiguracion()" style="padding:0.6rem 1rem; border:none; border-radius:6px; background:#2563EB; color:#fff; cursor:pointer;">Aceptar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let seleccionBanco = new Set();
let seleccionErp = new Set();

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

async function cargarMovimientosBancarios() {
    const params = new URLSearchParams();
    const fechaDesde = document.getElementById('banco_fecha_desde').value;
    const fechaHasta = document.getElementById('banco_fecha_hasta').value;
    const estado = document.getElementById('banco_estado').value;
    const tipo = document.getElementById('banco_tipo').value;
    const q = document.getElementById('banco_q').value;

    if (fechaDesde) params.append('fecha_desde', fechaDesde);
    if (fechaHasta) params.append('fecha_hasta', fechaHasta);
    if (estado) params.append('estado_conciliacion', estado);
    if (tipo) params.append('tipo_movimiento', tipo);
    if (q) params.append('q', q);

    try {
        const res = await fetch('/api/movimientos-bancarios?' + params.toString());
        const json = await res.json();
        const tbody = document.getElementById('tbody_banco');
        tbody.innerHTML = '';

        if (!json.data || json.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#94A3B8; padding:2rem;">No hay movimientos bancarios con los filtros actuales.</td></tr>';
            document.getElementById('banco_total_badge').innerText = '0 registros';
            return;
        }

        document.getElementById('banco_total_badge').innerText = `${json.total} registros`;

        json.data.forEach(m => {
            const isSelected = seleccionBanco.has(m.id_movimiento_bancario);
            const tr = document.createElement('tr');
            if (isSelected) tr.classList.add('selected-row');

            tr.innerHTML = `
                <td><input type="checkbox" onchange="toggleSelectBanco(${m.id_movimiento_bancario}, this)" ${isSelected ? 'checked' : ''}></td>
                <td>${m.fecha_operacion}</td>
                <td title="${m.descripcion_original}">${m.descripcion_normalizada.substring(0, 30)}...</td>
                <td>${m.cuit_extraido || '-'}</td>
                <td>${m.nro_comprobante_extraido || '-'}</td>
                <td style="font-weight:600; color:${m.signo_monto === 'ingreso' ? '#16A34A' : '#DC2626'}">
                    ${m.signo_monto === 'egreso' ? '-' : ''}$${parseFloat(m.monto).toLocaleString('es-AR', {minimumFractionDigits: 2})}
                </td>
                <td><span class="badge-status badge-${m.estado_conciliacion}">${m.estado_conciliacion}</span></td>
            `;
            tbody.appendChild(tr);
        });
    } catch (e) {
        console.error("Error al cargar movimientos:", e);
    }
}

async function cargarCobranzasErp() {
    const params = new URLSearchParams();
    const fechaDesde = document.getElementById('erp_fecha_desde').value;
    const fechaHasta = document.getElementById('erp_fecha_hasta').value;
    const estado = document.getElementById('erp_estado').value;
    const comprobante = document.getElementById('erp_comprobante').value;

    if (fechaDesde) params.append('fecha_desde', fechaDesde);
    if (fechaHasta) params.append('fecha_hasta', fechaHasta);
    if (estado) params.append('estado', estado);
    if (comprobante) params.append('comprobante', comprobante);

    try {
        const res = await fetch('/api/cobranzas-erp?' + params.toString());
        const json = await res.json();
        const tbody = document.getElementById('tbody_erp');
        tbody.innerHTML = '';

        if (!json.data || json.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#94A3B8; padding:2rem;">No hay cobranzas ERP registradas.</td></tr>';
            document.getElementById('erp_total_badge').innerText = '0 registros';
            return;
        }

        document.getElementById('erp_total_badge').innerText = `${json.total} registros`;

        json.data.forEach(c => {
            const isSelected = seleccionErp.has(c.id);
            const tr = document.createElement('tr');
            if (isSelected) tr.classList.add('selected-row');

            tr.innerHTML = `
                <td><input type="checkbox" onchange="toggleSelectErp(${c.id}, this)" ${isSelected ? 'checked' : ''}></td>
                <td>${c.fecha ? c.fecha.substring(0, 10) : '-'}</td>
                <td>Cliente #${c.cliente_id}</td>
                <td>${c.medio_pago || '-'}</td>
                <td>${c.comprobante_nro || '-'}</td>
                <td style="font-weight:600; color:#16A34A;">$${parseFloat(c.monto_total).toLocaleString('es-AR', {minimumFractionDigits: 2})}</td>
                <td><span class="badge-status badge-matcheado">${c.estado}</span></td>
            `;
            tbody.appendChild(tr);
        });
    } catch (e) {
        console.error("Error al cargar cobranzas ERP:", e);
    }
}

function toggleSelectBanco(id, checkbox) {
    if (checkbox.checked) seleccionBanco.add(id);
    else seleccionBanco.delete(id);
    actualizarBarraContextual();
    checkbox.closest('tr').classList.toggle('selected-row', checkbox.checked);
}

function toggleSelectErp(id, checkbox) {
    if (checkbox.checked) seleccionErp.add(id);
    else seleccionErp.delete(id);
    actualizarBarraContextual();
    checkbox.closest('tr').classList.toggle('selected-row', checkbox.checked);
}

function toggleSelectAllBanco(master) {
    document.querySelectorAll('#tbody_banco input[type="checkbox"]').forEach(chk => {
        chk.checked = master.checked;
        chk.dispatchEvent(new Event('change'));
    });
}

function toggleSelectAllErp(master) {
    document.querySelectorAll('#tbody_erp input[type="checkbox"]').forEach(chk => {
        chk.checked = master.checked;
        chk.dispatchEvent(new Event('change'));
    });
}

function actualizarBarraContextual() {
    const bar = document.getElementById('contextual_bar');
    const bCount = seleccionBanco.size;
    const eCount = seleccionErp.size;

    if (bCount > 0 || eCount > 0) {
        bar.style.display = 'flex';
        document.getElementById('contextual_selection_text').innerText = `${bCount} movimiento(s) bancario(s) y ${eCount} cobranza(s) ERP seleccionados`;
    } else {
        bar.style.display = 'none';
    }
}

function quitarSeleccion() {
    seleccionBanco.clear();
    seleccionErp.clear();
    document.querySelectorAll('.table-erp input[type="checkbox"]').forEach(chk => chk.checked = false);
    document.querySelectorAll('.table-erp tr').forEach(tr => tr.classList.remove('selected-row'));
    actualizarBarraContextual();
}

function organizarSeleccion() {
    alert("Ventana de organización visual: " + seleccionBanco.size + " movimientos y " + seleccionErp.size + " cobranzas listos para agrupar.");
}

function cambiarPestana(pestana, btn) {
    document.querySelectorAll('.nav-tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const area = document.getElementById('tab_content_area');
    if (pestana === 'manual') {
        area.innerHTML = '<p style="margin-top:1rem;">Área de conciliación manual: utilizá la selección conjunta de movimientos y cobranzas para confirmar cruces.</p>';
    } else if (pestana === 'alta_confianza') {
        area.innerHTML = '<p style="margin-top:1rem;">Propuestas de coincidencia exacta por CUIT, importe y fecha disponibles para revisión.</p>';
    } else if (pestana === 'para_revisar') {
        area.innerHTML = '<p style="margin-top:1rem;">Movimientos con diferencias menores o descripciones ambiguas pendientes de validación administrativa.</p>';
    } else if (pestana === 'historial') {
        area.innerHTML = '<p style="margin-top:1rem;">Historial de lotes conciliados previamente.</p>';
    }
}

function limpiarFiltrosBancarios() {
    document.getElementById('banco_fecha_desde').value = '';
    document.getElementById('banco_fecha_hasta').value = '';
    document.getElementById('banco_estado').value = 'pendiente';
    document.getElementById('banco_tipo').value = '';
    document.getElementById('banco_q').value = '';
    cargarMovimientosBancarios();
}

function limpiarFiltrosCobranzas() {
    document.getElementById('erp_fecha_desde').value = '';
    document.getElementById('erp_fecha_hasta').value = '';
    document.getElementById('erp_estado').value = 'confirmado';
    document.getElementById('erp_comprobante').value = '';
    cargarCobranzasErp();
}

function abrirModalExtracto() {
    document.getElementById('modal_extracto').classList.add('active');
}
function cerrarModalExtracto() {
    document.getElementById('modal_extracto').classList.remove('active');
}
function abrirConfiguracion() {
    document.getElementById('modal_config').classList.add('active');
}
function cerrarConfiguracion() {
    document.getElementById('modal_config').classList.remove('active');
}

function archivoSeleccionado(input) {
    if (input.files && input.files[0]) {
        document.getElementById('drop_zone_text').innerText = "Archivo: " + input.files[0].name;
        document.getElementById('btn_submit_extracto').disabled = false;
    }
}

async function enviarExtracto(event) {
    event.preventDefault();
    const input = document.getElementById('input_archivo_extracto');
    if (!input.files || !input.files[0]) return;

    const formData = new FormData();
    formData.append('archivo', input.files[0]);

    const spinner = document.getElementById('import_spinner');
    const resultadoDiv = document.getElementById('import_resultado');
    const btnSubmit = document.getElementById('btn_submit_extracto');

    spinner.style.display = 'block';
    resultadoDiv.style.display = 'none';
    btnSubmit.disabled = true;

    try {
        const res = await fetch('/api/extractos/importar', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData
        });
        const data = await res.json();
        spinner.style.display = 'none';
        resultadoDiv.style.display = 'block';

        if (res.ok && data.success) {
            resultadoDiv.style.background = '#DCFCE7';
            resultadoDiv.style.color = '#166534';
            resultadoDiv.innerHTML = `
                <strong>¡Importación completada!</strong><br>
                ${data.mensaje}<br>
                • Filas leídas: ${data.filas_leidas}<br>
                • Filas insertadas: ${data.filas_insertadas}<br>
                • Filas omitidas (duplicadas): ${data.filas_omitidas}<br>
                • Acreditaciones listas para conciliar: ${data.acreditaciones_para_conciliar}
            `;
            cargarMovimientosBancarios();
        } else {
            resultadoDiv.style.background = '#FEE2E2';
            resultadoDiv.style.color = '#991B1B';
            resultadoDiv.innerHTML = `<strong>Error:</strong> ${data.mensaje || 'No se pudo procesar el archivo.'}`;
            btnSubmit.disabled = false;
        }
    } catch (e) {
        spinner.style.display = 'none';
        resultadoDiv.style.display = 'block';
        resultadoDiv.style.background = '#FEE2E2';
        resultadoDiv.style.color = '#991B1B';
        resultadoDiv.innerHTML = `<strong>Error de conexión:</strong> ${e.message}`;
        btnSubmit.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    cargarMovimientosBancarios();
    cargarCobranzasErp();
});
</script>
@endpush
