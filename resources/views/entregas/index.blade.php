@extends('layouts.app')
@section('title', 'Entregas y Despacho')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/g2.css') }}">
@endpush
@section('content')
<div class="g2-page" data-g2-role="{{ \App\Modules\Shared\Auth\G2Identity::role() }}">
<div class="g2-content">

        <!-- ENCABEZADO -->
        <div class="page-header">
            <div class="page-header-info">
                <nav class="breadcrumb" aria-label="Ruta de navegación">
                    <span>ERP</span>
                    <span class="breadcrumb-sep">›</span>
                    <span>Logística</span>
                    <span class="breadcrumb-sep">›</span>
                    <strong>Entregas</strong>
                </nav>
                <h2>Logística y Despacho de Pedidos</h2>
                <p>Gestión de Hojas de Ruta y Emisión de Remitos de Transporte</p>
            </div>
            <div class="page-header-actions" style="display: flex; gap: 0.5rem; align-items: center;">
                <button class="clay-btn clay-btn-secondary" onclick="consultarVentasGrupo4()" id="btnVentasG4" title="Consultar pedidos confirmados en la API de Ventas (Grupo 4)">
                    <span aria-hidden="true">🛒</span>
                    <span>API Ventas (G4)</span>
                </button>
                <button class="clay-btn clay-btn-secondary btn-spin" onclick="cargarPedidosPendientes()" title="Actualizar datos" aria-label="Actualizar lista de pedidos">
                    <svg class="icon-svg icon-svg-lg" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M21.5 2v6h-6M2.5 22v-6h6M21.34 15.57a10 10 0 1 1-.57-8.38"/>
                    </svg>
                    <span>Actualizar</span>
                </button>
            </div>
        </div>

        <!-- SECCIÓN 1: PEDIDOS PENDIENTES -->
        <section class="clay-card" aria-labelledby="seccion-pedidos-title">
            <div class="clay-card-header">
                <h3 class="clay-card-title" id="seccion-pedidos-title">
                    <span class="card-icon" aria-hidden="true">📋</span>
                    1. Pedidos Confirmados — Pendientes de Despacho
                </h3>
                <div class="filter-group">
                    <label for="filtroZona">Zona:</label>
                    <select id="filtroZona" class="clay-select" style="min-width: 200px;" onchange="cargarPedidosPendientes()">
                        <option value="">Todas las Zonas</option>
                    </select>
                </div>
            </div>

            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.25rem;">
                Seleccioná los pedidos que saldrán en el próximo reparto:
            </p>

            <!-- TABLA DE PEDIDOS -->
            <div class="table-responsive" role="region" aria-label="Lista de pedidos pendientes" tabindex="0">
                <table class="clay-table" aria-live="polite">
                    <thead>
                        <tr>
                            <th style="width: 44px; text-align: center;">
                                <input type="checkbox" id="selectAll" class="custom-checkbox"
                                    onchange="toggleSelectAll(this)" title="Seleccionar todos" aria-label="Seleccionar todos los pedidos">
                            </th>
                            <th>Nº Pedido</th>
                            <th>Cliente / Razón Social</th>
                            <th>Zona</th>
                            <th>Dirección de Entrega</th>
                            <th style="text-align: center;">Carga Física</th>
                            <th style="text-align: right;">Total $</th>
                            <th>Obs.</th>
                        </tr>
                    </thead>
                    <tbody id="tablaPedidosBody">
                        <tr>
                            <td colspan="8">
                                <div class="table-empty">
                                    <span class="table-empty-icon">📦</span>
                                    Cargando pedidos confirmados…
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- RESUMEN DE SELECCIÓN -->
            <div class="summary-bar" role="status" aria-live="polite">
                <div class="summary-bar-stats">
                    <span>
                        <strong>Seleccionados:</strong>
                        <span id="contadorSeleccionados" style="color: var(--primary); font-weight: 800;">0</span> pedidos
                    </span>
                    <span>
                        <span id="contadorBultos" style="color: var(--warning); font-weight: 800;">0</span>
                        <span style="color: var(--text-muted);">bultos +</span>
                        <span id="contadorSueltos" style="color: #0284C7; font-weight: 800;">0</span>
                        <span style="color: var(--text-muted);">art. sueltos</span>
                    </span>
                </div>
                <button class="clay-btn clay-btn-ghost clay-btn-sm" onclick="limpiarSeleccion()">
                    Desmarcar Todos
                </button>
            </div>
        </section>

        <!-- SECCIÓN 2: ARMAR ENTREGA -->
        <section class="clay-card" aria-labelledby="seccion-armar-title">
            <h3 class="clay-card-title" id="seccion-armar-title" style="margin-bottom: 1.25rem;">
                <span class="card-icon" aria-hidden="true">🗺️</span>
                2. Armar Entrega y Emitir Remitos de Transporte
            </h3>

            <form id="formArmarEntrega" onsubmit="armarEntrega(event)">
                <div class="form-grid form-grid-3" style="margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label for="repartidorSelect" class="form-label">
                            Chofer / Repartidor <span class="required" aria-hidden="true">*</span>
                        </label>
                        <select id="repartidorSelect" class="clay-select" required aria-required="true">
                            <option value="">Seleccionar repartidor…</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fechaSalida" class="form-label">
                            Fecha de Salida a Ruta <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input type="date" id="fechaSalida" class="clay-input" required aria-required="true">
                    </div>
                    <div class="form-group">
                        <label for="observacionesEntrega" class="form-label">Observaciones de Carga</label>
                        <input type="text" id="observacionesEntrega" class="clay-input"
                            placeholder="Ej: Revisar bultos frágiles…">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="clay-btn clay-btn-success" style="font-size: 1rem; min-height: 50px; padding: 0 2rem;">
                        <svg class="icon-svg icon-svg-lg" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M5 12l5 5L20 7"/>
                        </svg>
                        Armar Entrega y Generar Remitos
                    </button>
                </div>
            </form>
        </section>

        <!-- SECCIÓN 3: HISTORIAL DE VIAJES -->
        <section class="clay-card" aria-labelledby="seccion-viajes-title">
            <h3 class="clay-card-title" id="seccion-viajes-title" style="margin-bottom: 1.25rem;">
                <span class="card-icon" aria-hidden="true">🚛</span>
                3. Viajes de Reparto Creados — Historial
            </h3>
            <div class="table-responsive" role="region" aria-label="Historial de viajes" tabindex="0">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>Nº Entrega</th>
                            <th>Fecha Salida</th>
                            <th>Chofer</th>
                            <th style="text-align: center;">Pedidos</th>
                            <th style="text-align: center;">Total Bultos</th>
                            <th style="text-align: center;">Estado</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaEntregasBody">
                        <tr>
                            <td colspan="7">
                                <div class="table-empty">
                                    <span class="table-empty-icon">🚛</span>
                                    Cargando viajes de reparto…
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

    </div>

    <!-- MODAL: DETALLE ARTÍCULOS DEL PEDIDO -->
    <div id="modalArticulos" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalArticulosTitulo">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalArticulosTitulo">
                    <span aria-hidden="true">📦</span>
                    Detalle del Pedido
                </h3>
                <button class="modal-close" onclick="cerrarModalArticulos()" aria-label="Cerrar modal">✕</button>
            </div>

            <div class="form-section" style="margin-bottom: 1.25rem;">
                <p><span style="font-weight:700;">Cliente:</span> <span id="articulosClienteNombre">--</span></p>
                <p style="margin-top: 0.35rem;"><span style="font-weight:700;">Destino:</span> <span id="articulosDireccion">--</span> (<span id="articulosZona">--</span>)</p>
                <p style="margin-top: 0.35rem;"><span style="font-weight:700;">Nota:</span> <span id="articulosObservaciones" style="font-style:italic; color: var(--text-muted);">--</span></p>
            </div>

            <p id="articulosPedidoSubtitulo" style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 0.875rem;"></p>

            <div class="table-responsive">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th style="text-align: center;">Bultos Cerrados</th>
                            <th style="text-align: center;">Art. Sueltos</th>
                        </tr>
                    </thead>
                    <tbody id="articulosDetalleBody"></tbody>
                </table>
            </div>

            <div class="modal-footer">
                <button class="clay-btn clay-btn-primary" onclick="cerrarModalArticulos()">Aceptar</button>
            </div>
        </div>
    </div>

    <!-- MODAL: REMITO OFICIAL -->
    <div id="modalRemito" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalRemitoTitulo">
        <div class="modal-content" style="max-width: 780px;">
            <div class="modal-header no-print">
                <h3 class="modal-title" id="modalRemitoTitulo">
                    <span aria-hidden="true">🖨️</span>
                    Comprobante de Remito
                </h3>
                <div style="display: flex; gap: 0.65rem;">
                    <button class="clay-btn clay-btn-primary clay-btn-sm" onclick="window.print()">
                        <svg class="icon-svg" viewBox="0 0 24 24" aria-hidden="true">
                            <polyline points="6 9 6 2 18 2 18 9"/>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                            <rect x="6" y="14" width="12" height="8"/>
                        </svg>
                        Imprimir Remito
                    </button>
                    <button class="modal-close" onclick="cerrarModalRemito()" aria-label="Cerrar modal">✕</button>
                </div>
            </div>

            <!-- COMPROBANTE IMPRIMIBLE -->
            <div class="remito-box" id="remitoImprimible">
                <div class="remito-header">
                    <div>
                        <h2 style="font-size: 1.4rem; font-weight: 800; color: #1E3A8A;">DISTRIBUIDORA JUAN XXIII</h2>
                        <p style="font-size: 0.875rem; color: var(--text-muted);">Pigüé — Buenos Aires — Tel: (02923) 45-0000</p>
                        <p style="font-size: 0.875rem; color: var(--text-muted);">Distribución Mayorista de Alimentos</p>
                    </div>
                    <div style="text-align: center;">
                        <div class="remito-letra">R</div>
                        <p style="font-size: 0.75rem; font-weight: 700; margin-top: 4px;">NO VÁLIDO COMO FACTURA</p>
                    </div>
                    <div style="text-align: right;">
                        <h3 id="remitoNumero" style="font-size: 1.25rem; color: var(--primary); font-weight: 800;">R-0001-00000001</h3>
                        <p><strong>Fecha:</strong> <span id="remitoFecha">--/--/----</span></p>
                        <p><strong>Chofer:</strong> <span id="remitoChofer">--</span></p>
                    </div>
                </div>

                <div class="form-section" style="margin-bottom: 1.25rem;">
                    <p><strong>Destinatario:</strong> <span id="remitoClienteNombre">--</span> (<span id="remitoClienteRazon">--</span>)</p>
                    <p style="margin-top: 0.35rem;"><strong>CUIT / DNI:</strong> <span id="remitoClienteCuit">--</span></p>
                    <p style="margin-top: 0.35rem;"><strong>Domicilio de Entrega:</strong> <span id="remitoClienteDireccion">--</span>, <span id="remitoClienteLocalidad">--</span> (<span id="remitoClienteZona">--</span>)</p>
                </div>

                <h4 style="margin-bottom: 0.75rem; color: #334155; font-size: 0.95rem;">Detalle de Mercadería Transportada:</h4>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; font-size: 0.9rem;">
                    <thead>
                        <tr style="background: #F1F5F9; border-bottom: 2px solid #CBD5E1;">
                            <th style="padding: 0.55rem 0.75rem; text-align: left;">Código</th>
                            <th style="padding: 0.55rem 0.75rem; text-align: left;">Descripción del Artículo</th>
                            <th style="padding: 0.55rem 0.75rem; text-align: center;">Cant.</th>
                            <th style="padding: 0.55rem 0.75rem; text-align: center;">Bultos</th>
                        </tr>
                    </thead>
                    <tbody id="remitoDetalleBody"></tbody>
                </table>

                <div class="remito-firma-box">
                    <div class="firma-linea">Firma y Aclaración del Chofer (Entrega)</div>
                    <div class="firma-linea">Firma y Sello del Cliente (Recepción Conforme)</div>
                </div>
            </div>

            <div class="modal-footer no-print" style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.25rem;">
                <div id="remitoAccionEntrega">
                    <!-- Botón de confirmar entrega o badge de entregado -->
                </div>
                <button class="clay-btn clay-btn-secondary" onclick="cerrarModalRemito()">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- MODAL: HOJA DE RUTA Y CONFIRMACIÓN DE PEDIDOS ENTREGADOS (ISSUE #124 / HU #3) -->
    <div id="modalHojaRuta" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalHojaRutaTitulo">
        <div class="modal-content" style="max-width: 960px;">
            <div class="modal-header">
                <h3 class="modal-title" id="modalHojaRutaTitulo">
                    <span aria-hidden="true">🗺️</span>
                    Hoja de Ruta de Reparto — Control de Entregas
                </h3>
                <button class="modal-close" onclick="cerrarModalHojaRuta()" aria-label="Cerrar modal">✕</button>
            </div>

            <div class="form-section" style="margin-bottom: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                    <div>
                        <p><strong style="color: var(--primary);">Entrega:</strong> <span id="hrEntregaNumero" style="font-weight: 700;">--</span></p>
                        <p style="margin-top: 0.25rem;"><strong>Chofer:</strong> <span id="hrChofer">--</span></p>
                        <p style="margin-top: 0.25rem;"><strong>Fecha Salida:</strong> <span id="hrFechaSalida">--</span></p>
                    </div>
                    <div style="text-align: right;">
                        <p><strong>Estado Viaje:</strong> <span id="hrEstadoBadge" class="badge">--</span></p>
                        <p style="margin-top: 0.25rem;"><strong>Total Pedidos:</strong> <span id="hrTotalPedidos">--</span></p>
                        <p style="margin-top: 0.25rem;"><strong>Total Bultos:</strong> <span id="hrTotalBultos" class="badge badge-bultos">--</span></p>
                    </div>
                </div>
            </div>

            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.85rem;">
                ℹ️ Como repartidor, confirme cada pedido entregado al finalizar la descarga en el cliente para notificar a la distribuidora en tiempo real.
            </p>

            <div class="table-responsive">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Dirección y Zona</th>
                            <th style="text-align: center;">Carga</th>
                            <th style="text-align: right;">Total</th>
                            <th style="text-align: center;">Estado Entrega</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="hrPedidosTableBody">
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem;">Cargando pedidos de la hoja de ruta…</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="modal-footer" style="margin-top: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.85rem; color: var(--success); font-weight: 600;">
                    ✅ Los pedidos entregados se precargan automáticamente en Rendiciones para la administración.
                </span>
                <button class="clay-btn clay-btn-primary" onclick="cerrarModalHojaRuta()">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- CONTENEDOR DE TOASTS -->
    <div class="toast-container" id="toastContainer" aria-live="assertive" aria-atomic="true"></div>

    
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/g2-http.js') }}" defer></script>
<script src="{{ asset('js/g2-entregas.js') }}" defer></script>
@endpush
