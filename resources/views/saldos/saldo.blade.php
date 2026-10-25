@extends('layouts.app')

@section('title', 'Cuentas Corrientes')

@push('styles')
<style>
    .saldo-page { display: flex; flex-direction: column; min-height: 0; }
    .saldo-heading {
        display: flex;
        align-items: center;
        gap: .7rem;
        padding: .8rem 1rem;
    }
    .saldo-heading h1 { margin: 0; font-size: 1.05rem; font-weight: 800; }
    .saldo-heading .ico { color: var(--primary); }
    .filtros-movimientos {
        display: grid;
        grid-template-columns: minmax(220px, .8fr) minmax(0, 2fr) auto;
        align-items: end;
        gap: .85rem;
        padding: 1rem;
    }
    .filtro-campo { display: flex; flex-direction: column; gap: .35rem; min-width: 0; }
    .filtro-campo label {
        color: var(--muted);
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
    }
    .filtro-campo select,
    .filtro-campo input {
        width: 100%;
        min-height: 44px;
        padding: 0 .75rem;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #fff;
        color: var(--text);
        font: inherit;
        font-size: .9rem;
    }
    .filtro-fechas { display: flex; align-items: end; gap: .75rem; }
    .filtro-fechas[hidden] { display: none; }
    .filtro-fechas .filtro-campo { flex: 1; }
    .filtro-alerta { grid-column: 2 / -1; margin: 0; color: var(--danger); font-size: .82rem; }
    .filtros-acciones { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
    .filtros-resumen { margin: 0; color: var(--muted); font-size: .82rem; white-space: nowrap; }
    .filtros-limpiar {
        min-height: 42px;
        padding: 0 .9rem;
        border: 0;
        border-radius: 10px;
        background: transparent;
        color: var(--primary);
        font: inherit;
        font-size: .88rem;
        font-weight: 600;
        cursor: pointer;
    }
    .filtros-limpiar:hover { background: #eff6ff; }
    .saldo-layout {
        display: grid;
        grid-template-columns: minmax(230px, 30%) minmax(0, 1fr);
        gap: 1rem;
        flex: 1;
        min-height: 500px;
    }
    .clientes-panel { padding: .65rem 0; overflow: hidden; }
    .clientes-search { padding: 0 .65rem .55rem; }
    .clientes-search input { min-height: 42px; font-size: .88rem; }
    .clientes-list { border-top: 1px solid var(--border); }
    .cliente-item {
        display: block;
        width: 100%;
        padding: .75rem .9rem;
        border: 0;
        border-bottom: 1px solid #f1f5f9;
        border-left: 3px solid transparent;
        background: transparent;
        color: var(--text);
        text-align: left;
        cursor: pointer;
        font: inherit;
    }
    .cliente-item:hover { background: #f8fafc; }
    .cliente-item.active {
        border-left-color: var(--primary);
        background: #fff7ed;
    }
    .cliente-nombre { display: block; font-weight: 600; font-size: .88rem; }
    .cliente-cuit { display: block; margin-top: .15rem; color: #94a3b8; font-size: .75rem; }
    .cliente-saldo { display: block; margin-top: .35rem; font-size: .85rem; font-weight: 700; }
    .saldo-negativo { color: #ff2938; }
    .saldo-positivo { color: #00a63e; }
    .saldo-cero { color: #94a3b8; }
    .cliente-no-encontrado { padding: 1rem; color: var(--muted); font-size: .88rem; }
    .detalle-panel { display: flex; flex-direction: column; gap: .75rem; min-width: 0; }
    .detalle-vacio {
        display: flex;
        flex: 1;
        min-height: 360px;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .7rem;
        color: #cbd5e1;
        text-align: center;
    }
    .detalle-vacio .icono { font-size: 2.6rem; }
    .detalle-vacio p { margin: 0; }
    .resumen-cuenta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
    }
    .resumen-identidad { min-width: 0; }
    .resumen-identidad strong { display: block; margin-bottom: .2rem; }
    .resumen-identidad span { display: block; color: var(--muted); font-size: .83rem; }
    .resumen-monto { flex: 0 0 auto; text-align: right; }
    .resumen-monto .label {
        display: block;
        color: #94a3b8;
        font-size: .65rem;
        letter-spacing: .5px;
        text-transform: uppercase;
    }
    .resumen-monto strong {
        display: block;
        font-size: clamp(1.4rem, 3vw, 2rem);
        font-variant-numeric: tabular-nums;
        line-height: 1.3;
    }
    .resumen-monto span:last-child { display: block; font-size: .8rem; }
    .gestion-movimiento {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .75rem;
        border: 1px solid var(--border);
        border-radius: 14px;
        background: #f8fafc;
    }
    .gestion-movimiento p { margin: 0; color: var(--muted); font-size: .85rem; }
    .movimiento-dialog {
        position: fixed;
        inset: 50% auto auto 50%;
        transform: translate(-50%, -50%);
        max-height: calc(100dvh - 2rem);
        overflow-y: auto;
        width: min(440px, calc(100% - 2rem));
        margin: 0;
        padding: 0;
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(15, 23, 42, .25);
    }
    .movimiento-dialog::backdrop { background: rgba(15, 23, 42, .48); }
    .movimiento-dialog form { display: grid; gap: 1rem; padding: 1.25rem; }
    .movimiento-dialog h2 { margin: 0; font-size: 1.05rem; }
    .movimiento-dialog .cliente-gestion { margin: -.5rem 0 0; color: var(--muted); font-size: .9rem; }
    .movimiento-dialog label { display: grid; gap: .35rem; font-size: .83rem; font-weight: 700; }
    .movimiento-dialog input,
    .movimiento-dialog select { width: 100%; min-height: 42px; background: #fff; }
    .movimiento-dialog .ayuda { margin: -.6rem 0 0; color: var(--muted); font-size: .78rem; }
    .movimiento-dialog .modal-acciones { display: flex; justify-content: flex-end; gap: .6rem; }
    .movimiento-dialog .modal-acciones button { min-height: 40px; padding: 0 1rem; }
    .pago-feedback { margin: 0; font-size: .82rem; }
    .pago-feedback.success { color: #008a35; }
    .pago-feedback.error { color: var(--danger); }
    .movimientos-card { padding: 0; overflow: hidden; }
    .movimientos-title {
        padding: .8rem 1rem;
        border-bottom: 1px solid var(--border);
        color: #94a3b8;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .45px;
        text-transform: uppercase;
    }
    .movimientos-scroll { overflow-x: auto; }
    .movimientos-table { width: 100%; min-width: 560px; border-collapse: collapse; }
    .movimientos-table th {
        padding: .6rem .75rem;
        border-bottom: 1px solid var(--border);
        color: #94a3b8;
        font-size: .65rem;
        letter-spacing: .35px;
        text-align: left;
        text-transform: uppercase;
    }
    .movimientos-table td {
        padding: .65rem .75rem;
        border-bottom: 1px solid #f1f5f9;
        font-size: .82rem;
    }
    .movimientos-table tbody tr:last-child td { border-bottom: 0; }
    .movimientos-table .fecha { color: var(--muted); white-space: nowrap; }
    .movimientos-table .importe,
    .movimientos-table .saldo { text-align: right; white-space: nowrap; font-weight: 700; }
    .movimientos-vacios { padding: 1.25rem; color: var(--muted); text-align: center; font-size: .88rem; }
    @media (max-width: 760px) {
        .filtros-movimientos { grid-template-columns: 1fr; }
        .filtro-alerta { grid-column: auto; }
        .filtros-acciones { align-items: flex-start; flex-direction: column; }
        .saldo-layout { grid-template-columns: 1fr; min-height: 0; }
        .clientes-panel { max-height: 280px; }
        .resumen-cuenta { align-items: flex-start; flex-direction: column; }
        .resumen-monto { text-align: left; }
    }
    @media (max-width: 520px) {
        .gestion-movimiento { align-items: stretch; flex-direction: column; }
        .gestion-movimiento button { width: 100%; }
        .movimiento-dialog .modal-acciones { flex-direction: column-reverse; }
        .movimiento-dialog .modal-acciones button { width: 100%; }
    }
</style>
@endpush

@section('content')
<div class="saldo-page">
    <header class="clay-card saldo-heading">
        <span class="ico" aria-hidden="true">♙</span>
        <h1>CUENTAS CORRIENTES</h1>
    </header>

    <section class="clay-card filtros-movimientos" aria-label="Filtros de movimientos">
        <div class="filtro-campo">
            <label for="modoFecha">Filtrar por fecha</label>
            <select id="modoFecha" class="clay-input">
                <option value="todas" selected>Todas</option>
                <option value="especifica">Fecha específica</option>
                <option value="periodo">Período</option>
            </select>
        </div>
        <div class="filtro-fechas" id="filtroFechaEspecifica" hidden>
            <div class="filtro-campo">
                <label for="fechaEspecifica">Fecha</label>
                <input id="fechaEspecifica" type="date" class="clay-input">
            </div>
        </div>
        <div class="filtro-fechas" id="filtroPeriodo" hidden>
            <div class="filtro-campo">
                <label for="fechaDesde">Desde</label>
                <input id="fechaDesde" type="date" class="clay-input">
            </div>
            <div class="filtro-campo">
                <label for="fechaHasta">Hasta</label>
                <input id="fechaHasta" type="date" class="clay-input">
            </div>
        </div>
        <div class="filtros-acciones">
            <p class="filtros-resumen" id="resumenFiltros" aria-live="polite"></p>
            <button type="button" class="filtros-limpiar" id="limpiarFiltrosSaldo">Limpiar filtros</button>
        </div>
        <p class="filtro-alerta" id="errorRangoFechas" role="alert" hidden>
            La fecha «Desde» no puede ser posterior a «Hasta».
        </p>
    </section>

    <div class="saldo-layout">
        <aside class="clay-card clientes-panel" aria-label="Listado de clientes">
            <div class="clientes-search">
                <input id="buscarCliente" class="clay-input" type="search"
                    placeholder="Buscar cliente..." aria-label="Buscar cliente por nombre o CUIT">
            </div>
            <div class="clientes-list" id="listaClientes"></div>
        </aside>

        <section class="detalle-panel" aria-label="Detalle de cuenta">
            <div class="clay-card detalle-vacio" id="detalleVacio">
                <span class="icono" aria-hidden="true">♧</span>
                <p>Seleccione un cliente de la lista</p>
            </div>

            <div id="detalleCuenta" hidden>
                <header class="clay-card resumen-cuenta">
                    <div class="resumen-identidad">
                        <strong id="nombreCliente"></strong>
                        <span id="datosCliente"></span>
                        <span id="domicilioCliente"></span>
                    </div>
                    <div class="resumen-monto">
                        <span class="label" id="etiquetaSaldo">Saldo actual</span>
                        <strong id="saldoActual"></strong>
                        <span id="estadoSaldo"></span>
                    </div>
                </header>

                <div class="gestion-movimiento">
                    <p>Registrar un pago recibido o agregar una nota de crédito al cliente.</p>
                    <button class="clay-btn-primary" id="btnGestionarMovimiento" type="button">
                        Gestionar movimiento
                    </button>
                </div>
                <p class="pago-feedback" id="pagoFeedback" role="status" aria-live="polite" hidden></p>

                <section class="clay-card movimientos-card" aria-label="Movimientos de cuenta">
                    <header class="movimientos-title">Movimientos de cuenta</header>
                    <div class="movimientos-scroll">
                        <table class="movimientos-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Descripción</th>
                                    <th>Venta</th>
                                    <th>Método</th>
                                    <th style="text-align:right;">Monto</th>
                                    <th style="text-align:right;">Saldo</th>
                                </tr>
                            </thead>
                            <tbody id="tablaMovimientos"></tbody>
                        </table>
                        <div class="movimientos-vacios" id="movimientosVacios" hidden>
                            <span id="mensajeMovimientosVacios">Este cliente todavía no tiene movimientos.</span>
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </div>

    <dialog class="movimiento-dialog" id="modalMovimiento" aria-labelledby="tituloModalMovimiento">
        <form id="formMovimiento">
            <h2 id="tituloModalMovimiento">Gestionar movimiento</h2>
            <p class="cliente-gestion">Cliente: <strong id="clienteGestionMovimiento"></strong></p>
            <label for="tipoMovimiento">
                Movimiento
                <select id="tipoMovimiento" class="clay-input" required>
                    <option value="pago">Registrar pago de deuda</option>
                    <option value="nota_credito">Agregar nota de crédito</option>
                </select>
            </label>
            <div id="contenedorMetodoPago">
                <label for="metodoPago">
                    Método de pago
                    <select id="metodoPago" class="clay-input" required>
                        <option value="" selected disabled>Seleccioná un método</option>
                        <option>Tarjeta de débito</option>
                        <option>Tarjeta de crédito</option>
                        <option>Efectivo</option>
                        <option>Cheque</option>
                        <option>Transferencia</option>
                        <option>QR</option>
                    </select>
                </label>
            </div>
            <label for="montoMovimiento">
                Monto
                <input id="montoMovimiento" class="clay-input" type="number" min="0.01" step="0.01"
                    inputmode="decimal" placeholder="Ingresá el monto" required>
            </label>
            <p class="ayuda" id="ayudaMovimiento"></p>
            <p class="pago-feedback error" id="errorMovimiento" role="alert" hidden></p>
            <div class="modal-acciones">
                <button class="clay-btn-secondary" id="cancelarMovimiento" type="button">Cancelar</button>
                <button class="clay-btn-primary" id="confirmarMovimiento" type="submit">Guardar</button>
            </div>
        </form>
    </dialog>
</div>
@endsection

@push('scripts')
<script>
    const CLIENTES = @json($clientes);
    const URL_MOVIMIENTOS = @json(route('saldo.movimientos', ['cliente' => '__CLIENTE__']));
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
    const $ = (id) => document.getElementById(id);
    const moneda = new Intl.NumberFormat('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    let clienteSeleccionado = null;
    const filters = {
        search: '',
        modoFecha: 'todas',
        fecha: '',
        fechaDesde: '',
        fechaHasta: ''
    };

    function fechaDelMovimiento(movimiento) {
        return String(movimiento.fecha ?? '').slice(0, 10);
    }

    function filtrarMovimientos(movimientos, filtros) {
        return movimientos.filter(movimiento => {
            const fecha = fechaDelMovimiento(movimiento);
            if (filtros.modoFecha === 'especifica') return !filtros.fecha || fecha === filtros.fecha;
            if (filtros.modoFecha !== 'periodo') return true;
            if (filtros.fechaDesde && fecha < filtros.fechaDesde) return false;
            if (filtros.fechaHasta && fecha > filtros.fechaHasta) return false;
            return true;
        });
    }

    function movimientosVisibles(cliente) {
        return filtrarMovimientos(cliente.movimientos, filters);
    }

    function hayFiltroFechaActivo() {
        return (filters.modoFecha === 'especifica' && Boolean(filters.fecha))
            || (filters.modoFecha === 'periodo' && Boolean(filters.fechaDesde || filters.fechaHasta));
    }

    function saldoDe(cliente) {
        return cliente.movimientos.reduce((total, movimiento) => total + Number(movimiento.monto), 0);
    }

    function saldoVisibleDe(cliente) {
        return movimientosVisibles(cliente).reduce((total, movimiento) => total + Number(movimiento.monto), 0);
    }

    function formatoSaldo(monto) {
        return `$ ${moneda.format(monto)}`;
    }

    function formatoMovimiento(monto) {
        return `${monto > 0 ? '+' : ''}$ ${moneda.format(monto)}`;
    }

    function claseSaldo(monto) {
        if (monto < 0) return 'saldo-negativo';
        if (monto > 0) return 'saldo-positivo';
        return 'saldo-cero';
    }

    function escaparHtml(valor) {
        return String(valor).replace(/[&<>"']/g, caracter => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[caracter]);
    }

    function renderClientes() {
        const consulta = filters.search.trim().toLocaleLowerCase('es-AR');
        const clientesVisibles = CLIENTES.filter(cliente => {
            const coincideBusqueda = `${cliente.nombre} ${cliente.cuit}`.toLocaleLowerCase('es-AR').includes(consulta);
            const tieneMovimientosEnFecha = !hayFiltroFechaActivo()
                || movimientosVisibles(cliente).length > 0;
            return coincideBusqueda && tieneMovimientosEnFecha;
        });

        $('listaClientes').innerHTML = clientesVisibles.length
            ? clientesVisibles.map(cliente => {
                const saldo = saldoVisibleDe(cliente);
                return `
                    <button type="button"
                        class="cliente-item ${clienteSeleccionado?.id === cliente.id ? 'active' : ''}"
                        data-cliente-id="${cliente.id}" aria-pressed="${clienteSeleccionado?.id === cliente.id}">
                        <span class="cliente-nombre">${escaparHtml(cliente.nombre)}</span>
                        <span class="cliente-cuit">${escaparHtml(cliente.cuit)}</span>
                        <span class="cliente-saldo ${claseSaldo(saldo)}">${formatoSaldo(saldo)}</span>
                    </button>
                `;
            }).join('')
            : `<p class="cliente-no-encontrado">${
                !hayFiltroFechaActivo()
                    ? 'No se encontraron clientes.'
                    : 'No se encontraron registros con los filtros aplicados.'
            }</p>`;
        $('resumenFiltros').textContent = `${clientesVisibles.length} clientes · ${
            clientesVisibles.reduce((total, cliente) => total + movimientosVisibles(cliente).length, 0)
        } movimientos`;
    }

    function renderMovimientos() {
        let saldoAcumulado = 0;
        const movimientos = [...movimientosVisibles(clienteSeleccionado)]
            .sort((a, b) => a.fecha.localeCompare(b.fecha));

        $('tablaMovimientos').innerHTML = movimientos.map(movimiento => {
            saldoAcumulado += Number(movimiento.monto);
            return `
                <tr>
                    <td class="fecha">${escaparHtml(movimiento.fecha)}</td>
                    <td>${escaparHtml(movimiento.descripcion)}</td>
                    <td>${movimiento.venta_id ? `#${escaparHtml(movimiento.venta_id)}` : '—'}</td>
                    <td>${escaparHtml(movimiento.referencia_externa || '—')}</td>
                    <td class="importe ${claseSaldo(movimiento.monto)}">${formatoMovimiento(Number(movimiento.monto))}</td>
                    <td class="saldo ${claseSaldo(saldoAcumulado)}">${formatoSaldo(saldoAcumulado)}</td>
                </tr>
            `;
        }).join('');

        $('movimientosVacios').hidden = movimientos.length > 0;
        $('mensajeMovimientosVacios').textContent = filters.modoFecha === 'todas'
            ? 'Este cliente todavía no tiene movimientos.'
            : 'No se encontraron registros con los filtros aplicados.';
    }

    function renderDetalle() {
        const saldo = saldoVisibleDe(clienteSeleccionado);
        const hayDeuda = saldo < 0;

        $('detalleVacio').hidden = true;
        $('detalleCuenta').hidden = false;
        $('nombreCliente').textContent = clienteSeleccionado.nombre;
        $('datosCliente').textContent = `${clienteSeleccionado.cuit} · ${clienteSeleccionado.condicion}`;
        $('domicilioCliente').textContent = clienteSeleccionado.domicilio;
        $('saldoActual').textContent = formatoSaldo(saldo);
        $('saldoActual').className = claseSaldo(saldo);
        $('etiquetaSaldo').textContent = hayFiltroFechaActivo() ? 'Saldo filtrado' : 'Saldo actual';
        $('estadoSaldo').textContent = hayDeuda ? 'Deuda pendiente' : saldo > 0 ? 'Saldo a favor' : 'Sin deuda pendiente';
        $('estadoSaldo').className = claseSaldo(saldo);
        $('pagoFeedback').hidden = true;
        $('pagoFeedback').textContent = '';
        renderMovimientos();
        renderClientes();
    }

    function renderFiltros() {
        $('filtroFechaEspecifica').hidden = filters.modoFecha !== 'especifica';
        $('filtroPeriodo').hidden = filters.modoFecha !== 'periodo';
        renderClientes();

        if (clienteSeleccionado && !CLIENTES.includes(clienteSeleccionado)) {
            clienteSeleccionado = null;
        }
        if (clienteSeleccionado && !clientesConFiltro().some(cliente => cliente.id === clienteSeleccionado.id)) {
            clienteSeleccionado = null;
        }

        if (clienteSeleccionado) {
            renderDetalle();
        } else {
            $('detalleVacio').hidden = false;
            $('detalleCuenta').hidden = true;
            $('detalleVacio').querySelector('p').textContent = !hayFiltroFechaActivo()
                ? 'Seleccione un cliente de la lista'
                : 'No se encontraron registros con los filtros aplicados';
        }
    }

    function clientesConFiltro() {
        const consulta = filters.search.trim().toLocaleLowerCase('es-AR');
        return CLIENTES.filter(cliente => {
            const coincideBusqueda = `${cliente.nombre} ${cliente.cuit}`.toLocaleLowerCase('es-AR').includes(consulta);
            return coincideBusqueda && (!hayFiltroFechaActivo() || movimientosVisibles(cliente).length > 0);
        });
    }

    function actualizarFiltrosFecha() {
        const fechaDesde = $('fechaDesde').value;
        const fechaHasta = $('fechaHasta').value;
        const rangoInvalido = filters.modoFecha === 'periodo'
            && fechaDesde
            && fechaHasta
            && fechaDesde > fechaHasta;
        $('errorRangoFechas').hidden = !rangoInvalido;
        if (rangoInvalido) return;

        filters.fecha = filters.modoFecha === 'especifica' ? $('fechaEspecifica').value : '';
        filters.fechaDesde = filters.modoFecha === 'periodo' ? fechaDesde : '';
        filters.fechaHasta = filters.modoFecha === 'periodo' ? fechaHasta : '';
        renderFiltros();
    }

    $('listaClientes').addEventListener('click', evento => {
        const boton = evento.target.closest('[data-cliente-id]');
        if (!boton) return;
        clienteSeleccionado = CLIENTES.find(cliente => cliente.id === Number(boton.dataset.clienteId));
        renderDetalle();
    });

    $('buscarCliente').addEventListener('input', evento => {
        filters.search = evento.target.value;
        renderFiltros();
    });

    $('modoFecha').addEventListener('change', evento => {
        filters.modoFecha = evento.target.value;
        $('fechaEspecifica').value = '';
        $('fechaDesde').value = '';
        $('fechaHasta').value = '';
        $('errorRangoFechas').hidden = true;
        actualizarFiltrosFecha();
    });
    ['fechaEspecifica', 'fechaDesde', 'fechaHasta'].forEach(id => {
        $(id).addEventListener('change', actualizarFiltrosFecha);
    });
    $('limpiarFiltrosSaldo').addEventListener('click', () => {
        $('buscarCliente').value = '';
        $('modoFecha').value = 'todas';
        $('fechaEspecifica').value = '';
        $('fechaDesde').value = '';
        $('fechaHasta').value = '';
        filters.search = '';
        filters.modoFecha = 'todas';
        filters.fecha = '';
        filters.fechaDesde = '';
        filters.fechaHasta = '';
        $('errorRangoFechas').hidden = true;
        renderFiltros();
    });

    const modalMovimiento = $('modalMovimiento');
    const tipoMovimiento = $('tipoMovimiento');
    const montoMovimiento = $('montoMovimiento');
    const botonGuardarMovimiento = $('confirmarMovimiento');

    function actualizarAyudaMovimiento() {
        const esPago = tipoMovimiento.value === 'pago';
        const saldo = clienteSeleccionado ? saldoDe(clienteSeleccionado) : 0;
        $('contenedorMetodoPago').hidden = !esPago;
        $('metodoPago').required = esPago;
        montoMovimiento.max = esPago && saldo < 0 ? Math.abs(saldo).toFixed(2) : '';
        $('ayudaMovimiento').textContent = esPago && saldo < 0
            ? `El pago no puede superar la deuda de ${formatoSaldo(Math.abs(saldo))}.`
            : esPago
                ? 'Este cliente no tiene deuda pendiente; elegí nota de crédito para agregar saldo a favor.'
                : 'La nota de crédito se suma al saldo de la cuenta.';
    }

    $('btnGestionarMovimiento').addEventListener('click', () => {
        if (!clienteSeleccionado) return;
        $('clienteGestionMovimiento').textContent = clienteSeleccionado.nombre;
        $('formMovimiento').reset();
        $('errorMovimiento').hidden = true;
        actualizarAyudaMovimiento();
        modalMovimiento.showModal();
        montoMovimiento.focus();
    });

    $('cancelarMovimiento').addEventListener('click', () => modalMovimiento.close());
    tipoMovimiento.addEventListener('change', actualizarAyudaMovimiento);

    $('formMovimiento').addEventListener('submit', async evento => {
        evento.preventDefault();
        if (!clienteSeleccionado) return;

        const tipo = tipoMovimiento.value;
        const monto = Number(montoMovimiento.value);
        const metodoPago = $('metodoPago').value;
        const errorMovimientoElement = $('errorMovimiento');
        botonGuardarMovimiento.disabled = true;
        errorMovimientoElement.textContent = '';
        errorMovimientoElement.hidden = true;

        try {
            const respuesta = await fetch(URL_MOVIMIENTOS.replace('__CLIENTE__', clienteSeleccionado.id), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({
                    tipo,
                    monto,
                    ...(tipo === 'pago' ? { metodo_pago: metodoPago } : {})
                })
            });
            const resultado = await respuesta.json();

            if (!respuesta.ok) {
                const mensaje = resultado.errors?.monto?.[0]
                    || resultado.errors?.metodo_pago?.[0]
                    || resultado.errors?.tipo?.[0]
                    || resultado.message
                    || 'No se pudo guardar el movimiento.';
                throw new Error(mensaje);
            }

            clienteSeleccionado.movimientos.push(resultado.movimiento);
            modalMovimiento.close();
            renderDetalle();
            const feedback = $('pagoFeedback');
            feedback.textContent = tipo === 'pago'
                ? 'Pago registrado en el historial.'
                : 'Nota de crédito registrada en el historial.';
            feedback.className = 'pago-feedback success';
            feedback.hidden = false;
        } catch (error) {
            errorMovimientoElement.textContent = error.message || 'No se pudo guardar el movimiento.';
            errorMovimientoElement.hidden = false;
        } finally {
            botonGuardarMovimiento.disabled = false;
        }
    });

    renderFiltros();
</script>
@endpush
