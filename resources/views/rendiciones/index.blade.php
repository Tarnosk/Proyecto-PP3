@extends('layouts.app')
@section('title', 'Rendiciones y Cobros')
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
                    <span>Cobros</span>
                    <span class="breadcrumb-sep">›</span>
                    <strong>Rendiciones</strong>
                </nav>
                <h2>Rendiciones de Jornada & Cobros</h2>
                <p>Control de rendiciones de choferes, cobros liquidados y arqueo de caja</p>
            </div>
            <div class="page-header-actions">
                <button class="clay-btn clay-btn-secondary btn-spin" onclick="recargarTodo()" aria-label="Actualizar lista de rendiciones">
                    <svg class="icon-svg icon-svg-lg" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M21.5 2v6h-6M2.5 22v-6h6M21.34 15.57a10 10 0 1 1-.57-8.38"/>
                    </svg>
                    <span>Actualizar</span>
                </button>
                <button class="clay-btn clay-btn-secondary" onclick="abrirModalDevolucion()">
                    📦 Devolución
                </button>
                <button class="clay-btn clay-btn-primary" onclick="abrirModalNuevaRendicion()" id="btnNuevaRendicion">
                    <svg class="icon-svg icon-svg-lg" viewBox="0 0 24 24" aria-hidden="true">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <span>Nueva Rendición</span>
                </button>
            </div>
        </div>

        <!-- KPI SUMMARY -->
        <div class="kpi-grid" role="region" aria-label="Indicadores clave">
            <div class="kpi-card">
                <div class="kpi-icon" style="background: linear-gradient(135deg, #DBEAFE, #BFDBFE);">📋</div>
                <div class="kpi-body">
                    <div class="kpi-label">Rendiciones Hoy</div>
                    <div class="kpi-value" id="kpiTotalRendiciones">—</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background: linear-gradient(135deg, #FEF3C7, #FDE68A);">⏳</div>
                <div class="kpi-body">
                    <div class="kpi-label">Pendientes Revisión</div>
                    <div class="kpi-value" id="kpiPendientes" style="color: var(--warning);">—</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background: linear-gradient(135deg, #D1FAE5, #6EE7B7);">✅</div>
                <div class="kpi-body">
                    <div class="kpi-label">Cobros Liquidados</div>
                    <div class="kpi-value" id="kpiTotalDinero" style="color: var(--success);">—</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background: linear-gradient(135deg, #FEE2E2, #FCA5A5);">❌</div>
                <div class="kpi-body">
                    <div class="kpi-label">Rechazadas</div>
                    <div class="kpi-value" id="kpiRechazadas" style="color: var(--danger);">—</div>
                </div>
            </div>
        </div>

        <!-- TABLA & FILTROS -->
        <section class="clay-card" aria-labelledby="seccion-rendiciones-title">
            <h3 class="clay-card-title" id="seccion-rendiciones-title" style="margin-bottom: 1.25rem;">
                <span class="card-icon" aria-hidden="true">📊</span>
                Listado de Rendiciones
            </h3>

            <!-- TABS DE ESTADO -->
            <div class="tab-bar" role="tablist" aria-label="Filtrar por estado">
                <button class="tab-btn active" id="tabTodas" role="tab" aria-selected="true"
                    onclick="filtrarEstadoTab('')">
                    Todas
                    <span class="tab-count" id="cntTodas">0</span>
                </button>
                <button class="tab-btn" id="tabPendientes" role="tab" aria-selected="false"
                    onclick="filtrarEstadoTab('pendiente')">
                    Pendientes
                    <span class="tab-count" id="cntPendientes">0</span>
                </button>
                <button class="tab-btn" id="tabAprobadas" role="tab" aria-selected="false"
                    onclick="filtrarEstadoTab('aprobada')">
                    Aprobadas (Cobros)
                    <span class="tab-count" id="cntAprobadas">0</span>
                </button>
                <button class="tab-btn" id="tabRechazadas" role="tab" aria-selected="false"
                    onclick="filtrarEstadoTab('rechazada')">
                    Rechazadas
                    <span class="tab-count" id="cntRechazadas">0</span>
                </button>
            </div>

            <!-- TOOLBAR FILTROS -->
            <div class="toolbar">
                <div class="toolbar-left">
                    <div class="filter-group">
                        <label for="filtroRepartidor">Repartidor:</label>
                        <select id="filtroRepartidor" class="clay-select" style="min-width: 210px;"
                            onchange="cargarListadoRendiciones()"><option value="">Seleccionar repartidor</option>
@foreach (\App\Modules\Shared\Services\UsuarioService::obtenerRepartidores() as $driver)
    @if (\App\Modules\Shared\Auth\G2Identity::role() === 'admin' || $driver['id_usuario'] === \App\Modules\Shared\Auth\G2Identity::id())
        <option value="{{ $driver['id_usuario'] }}">{{ $driver['nombre'] }}</option>
    @endif
@endforeach</select>
                    </div>
                    <div class="filter-group">
                        <label for="filtroFecha">Fecha:</label>
                        <input type="date" id="filtroFecha" class="clay-input"
                            style="min-width: 160px;" onchange="cargarListadoRendiciones()">
                    </div>
                </div>
                <div class="toolbar-right">
                    <button class="clay-btn clay-btn-secondary clay-btn-sm" onclick="limpiarFiltros()">
                        <svg class="icon-svg" viewBox="0 0 24 24" aria-hidden="true">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <!-- TABLA DE RENDICIONES -->
            <div class="table-responsive" role="region" aria-label="Lista de rendiciones" tabindex="0">
                <table class="clay-table" aria-live="polite">
                    <thead>
                        <tr>
                            <th style="width: 110px;">Nº Rendición</th>
                            <th style="width: 100px;">Fecha</th>
                            <th>Chofer / Repartidor</th>
                            <th>Entrega</th>
                            <th style="text-align: right; width: 130px;">Total $</th>
                            <th style="text-align: center; width: 110px;">Estado</th>
                            <th>Observaciones</th>
                            <th style="text-align: center; width: 120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaRendicionesBody">
                        <tr>
                            <td colspan="8">
                                <div class="table-empty">
                                    <span class="table-empty-icon">📋</span>
                                    Cargando rendiciones…
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </section>
    </div>

    <!-- ===== MODAL: NUEVA RENDICIÓN ===== -->
    <div id="modalNuevaRendicion" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalNuevaTitle">
        <div class="modal-content" style="max-width: 620px;">
            <div class="modal-header">
                <h3 class="modal-title" id="modalNuevaTitle">
                    <span aria-hidden="true">➕</span>
                    Cargar Rendición de Reparto
                </h3>
                <button class="modal-close" onclick="cerrarModalNuevaRendicion()" aria-label="Cerrar modal">✕</button>
            </div>

            <form id="formNuevaRendicion" onsubmit="guardarNuevaRendicion(event)" novalidate>
                <div class="form-grid form-grid-2" style="margin-bottom: 1.1rem;">
                    <div class="form-group">
                        <label for="formEntregaSelect" class="form-label">
                            🚚 Seleccionar Entrega / Viaje
                        </label>
                        <select id="formEntregaSelect" class="clay-select" onchange="alCambiarEntregaSelect(this.value)">
                            <option value="">Cargando entregas…</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="formRepartidor" class="form-label">
                            Chofer Repartidor <span class="required" aria-hidden="true">*</span>
                        </label>
                        <select id="formRepartidor" class="clay-select" required aria-required="true"><option value="">Seleccionar repartidor</option>
@foreach (\App\Modules\Shared\Services\UsuarioService::obtenerRepartidores() as $driver)
    @if (\App\Modules\Shared\Auth\G2Identity::role() === 'admin' || $driver['id_usuario'] === \App\Modules\Shared\Auth\G2Identity::id())
        <option value="{{ $driver['id_usuario'] }}">{{ $driver['nombre'] }}</option>
    @endif
@endforeach</select>
                    </div>
                </div>

                <!-- CONTENEDOR DE COBROS DINÁMICOS POR ENTREGA -->
                <div id="seccionCobrosDinamicos" class="form-section" style="display: none; margin-bottom: 1.1rem;">
                    <p class="form-section-title">
                        <span aria-hidden="true">📦</span>
                        Pedidos Entregados en este Viaje
                    </p>
                    <div class="table-responsive">
                        <table class="clay-table" style="font-size: 0.9rem;">
                            <thead>
                                <tr>
                                    <th>Pedido / Cliente</th>
                                    <th style="text-align: right; width: 110px;">Total ($)</th>
                                    <th style="width: 170px;">Medio de Pago</th>
                                </tr>
                            </thead>
                            <tbody id="tablaCobrosDinamicosBody">
                            </tbody>
                        </table>
                    </div>
                    <div style="text-align: right; margin-top: 0.75rem; font-weight: 700; font-size: 1.05rem; color: var(--primary);">
                        Total a Rendir: <span id="totalRendidoDinamicoTxt">$0,00</span>
                    </div>
                </div>

                <!-- CONTENEDOR MANUAL (SI NO SE SELECCIONA ENTREGA) -->
                <div id="seccionCobroManual" class="form-section">
                    <p class="form-section-title">
                        <span aria-hidden="true">💰</span>
                        Cobro Rendido (Manual)
                    </p>
                    <div class="form-grid form-grid-2" style="margin-bottom: 0.875rem;">
                        <div class="form-group">
                            <label for="formCliente" class="form-label">Cliente / Factura</label>
                            <select id="formCliente" class="clay-select"><option value="">Elegí una entrega para cargar sus clientes y facturas</option></select>
                        </div>
                        <div class="form-group">
                            <label for="formMedioPago" class="form-label">Medio de Pago</label>
                            <select id="formMedioPago" class="clay-select">
                                <option value="efectivo">💵 Efectivo (Impacta en Caja)</option>
                                <option value="transferencia">🏦 Transferencia (Conciliación Bancaria)</option>
                                <option value="cheque">📄 Cheque Diferido / Al Día</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="formMonto" class="form-label">
                            Monto a Rendir ($) <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input type="number" step="0.01" id="formMonto" class="clay-input"
                            value="" required aria-required="true" min="0.01" placeholder="0.00">
                    </div>
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label for="formObservaciones" class="form-label">Observaciones / Novedades</label>
                    <textarea id="formObservaciones" class="clay-input"
                        placeholder="Ej. El cliente abonó en efectivo según comprobante…"></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="clay-btn clay-btn-secondary" onclick="cerrarModalNuevaRendicion()">
                        Cancelar
                    </button>
                    <button type="submit" class="clay-btn clay-btn-primary">
                        <svg class="icon-svg" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M5 12l5 5L20 7"/>
                        </svg>
                        Registrar Rendición
                    </button>
                </div>
            </form>
        </div>
    </div>


    <!-- ===== MODAL: DETALLE Y REVISIÓN ===== -->
    <div id="modalDetalleRendicion" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalDetalleTitle">
        <div class="modal-content" style="max-width: 680px;">
            <div class="modal-header">
                <h3 class="modal-title" id="modalDetalleTitle">
                    <span aria-hidden="true">🔍</span>
                    Detalle Rendición <span id="detIdRendicion">#--</span>
                </h3>
                <button class="modal-close" onclick="cerrarModalDetalle()" aria-label="Cerrar modal">✕</button>
            </div>

            <!-- DATOS GENERALES -->
            <div class="form-section" style="margin-bottom: 1.25rem;">
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Repartidor</span>
                        <span class="detail-value" id="detRepartidor">--</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Fecha Rendición</span>
                        <span class="detail-value" id="detFecha">--</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Total Rendido</span>
                        <span class="detail-value" id="detTotal" style="color: var(--primary); font-size: 1.1rem;">$0,00</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Estado Actual</span>
                        <span class="detail-value" id="detEstadoBadge">--</span>
                    </div>
                </div>
                <div style="margin-top: 0.75rem;">
                    <span class="detail-label">Observaciones</span>
                    <p id="detObs" style="color: var(--text-muted); margin-top: 3px; font-size: 0.9rem;">--</p>
                </div>
            </div>

            <!-- COBROS DE LA RENDICIÓN -->
            <h4 style="font-size: 1rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.75rem;">
                Cobros Recibidos en la Rendición
            </h4>
            <div class="table-responsive" style="margin-bottom: 1.25rem;">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>Cliente / Factura</th>
                            <th>Medio de Pago</th>
                            <th style="text-align: right;">Monto</th>
                            <th style="text-align: center;">Destino de Fondos</th>
                        </tr>
                    </thead>
                    <tbody id="detCobrosBody"></tbody>
                </table>
            </div>

            <!-- ACCIÓN ADMINISTRATIVA -->
            <div id="boxAccionAdmin" class="admin-action-box">
                <p class="admin-action-title">
                    <span aria-hidden="true">⚡</span>
                    Acción Administrativa — Validación de Rendición
                </p>
                <div class="form-group" style="margin-bottom: 0.875rem;">
                    <label for="motivoRechazoAdmin" class="form-label">
                        Motivo de Rechazo <span style="color: var(--text-muted); font-weight: 500;">(sólo si rechaza)</span>
                    </label>
                    <input type="text" id="motivoRechazoAdmin" class="clay-input"
                        placeholder="Ej. Diferencia de dinero en efectivo no justificada…">
                </div>
                <div class="admin-action-btns">
                    <button class="clay-btn clay-btn-success" onclick="procesarValidacionAdmin('aprobar')">
                        <svg class="icon-svg" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M5 12l5 5L20 7"/>
                        </svg>
                        Aprobar & Liquidar Cobros
                    </button>
                    <button class="clay-btn clay-btn-danger" onclick="procesarValidacionAdmin('rechazar')">
                        <svg class="icon-svg" viewBox="0 0 24 24" aria-hidden="true">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                        Rechazar Rendición
                    </button>
                    <button class="clay-btn clay-btn-secondary" onclick="procesarValidacionAdmin('cancelar')">
                        Cancelar / Anular
                    </button>
                </div>
            </div>

            <div class="modal-footer">
                <button class="clay-btn clay-btn-ghost" onclick="cerrarModalDetalle()">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- ===== MODAL: REGISTRAR DEVOLUCIÓN ===== -->
    <div id="modalDevolucion" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalDevolucionTitle">
        <div class="modal-content" style="max-width: 520px;">
            <div class="modal-header">
                <h3 class="modal-title" id="modalDevolucionTitle">
                    <span aria-hidden="true">📦</span>
                    Registrar Devolución de Pedido
                </h3>
                <button class="modal-close" onclick="cerrarModalDevolucion()" aria-label="Cerrar modal">✕</button>
            </div>

            <form id="formDevolucion" onsubmit="guardarDevolucion(event)">
                <div class="form-group" style="margin-bottom: 0.875rem;">
                    <label for="devRendicionId" class="form-label">ID Rendición Asociada</label>
                    <input type="number" id="devRendicionId" class="clay-input" value="" required min="1">
                </div>
                <div class="form-group" style="margin-bottom: 0.875rem;">
                    <label for="devPedidoId" class="form-label">ID Pedido No Entregado</label>
                    <input type="number" id="devPedidoId" class="clay-input" value="" required min="1">
                </div>
                <div class="form-group" style="margin-bottom: 0.875rem;">
                    <label for="devCliente" class="form-label">Cliente</label>
                    <input type="text" id="devCliente" class="clay-input" value="">
                </div>
                <div class="form-group" style="margin-bottom: 0.875rem;">
                    <label for="devMotivo" class="form-label">Motivo de Devolución</label>
                    <select id="devMotivo" class="clay-select">
                        <option value="Local cerrado">Local cerrado / Cliente ausente</option>
                        <option value="Rechazo de mercadería">Rechazo por estado/error en pedido</option>
                        <option value="Sin dinero disponible">Sin dinero disponible para abonar</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="devObs" class="form-label">Observaciones Adicionales</label>
                    <textarea id="devObs" class="clay-input" placeholder="Detalles extra…"></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="clay-btn clay-btn-secondary" onclick="cerrarModalDevolucion()">Cancelar</button>
                    <button type="submit" class="clay-btn clay-btn-primary">Registrar Devolución</button>
                </div>
            </form>
        </div>
    </div>

    <!-- CONTENEDOR DE TOASTS -->
    <div class="toast-container" id="toastContainer" aria-live="assertive" aria-atomic="true"></div>

    
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/g2-http.js') }}" defer></script>
<script src="{{ asset('js/g2-rendiciones.js') }}" defer></script>
@endpush
