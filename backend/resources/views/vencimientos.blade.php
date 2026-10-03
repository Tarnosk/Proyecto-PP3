@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="clay-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="clay-title">📦 Consulta de Productos Próximos a Vencer</h1>
                <p class="clay-subtitle">Monitorea y controla los vencimientos de tu inventario</p>
            </div>
            <button class="clay-btn clay-btn-primary" data-bs-toggle="modal" data-bs-target="#filtrosModal">
                <i class="fas fa-filter"></i> Filtros
            </button>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="clay-card clay-alert-critica">
                <div class="clay-card-icon">🔴</div>
                <div class="clay-card-content">
                    <p class="clay-label">Vencidos</p>
                    <h3 id="total-vencidos">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="clay-card clay-alert-alta">
                <div class="clay-card-icon">🟠</div>
                <div class="clay-card-content">
                    <p class="clay-label">Crítico (7 días)</p>
                    <h3 id="total-criticos">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="clay-card clay-alert-media">
                <div class="clay-card-icon">🟡</div>
                <div class="clay-card-content">
                    <p class="clay-label">Próximos (30 días)</p>
                    <h3 id="total-proximos">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="clay-card clay-alert-info">
                <div class="clay-card-icon">💾</div>
                <div class="clay-card-content">
                    <p class="clay-label">Total en Riesgo</p>
                    <h3 id="total-cantidad">0</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="clay-tabs mb-4">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="tab-proximosAvencer" data-bs-toggle="tab" data-bs-target="#proximosAvencer" type="button">
                    📅 Próximos a Vencer
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="tab-porCriticidad" data-bs-toggle="tab" data-bs-target="#porCriticidad" type="button">
                    🎯 Por Criticidad
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="proximosAvencer">
            <div class="clay-card">
                <div class="clay-card-header">
                    <h5>Productos Próximos a Vencer (30 días)</h5>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <input type="text" id="buscarProductoInline" class="clay-input" style="width: 240px;"
                               placeholder="🔍 Buscar producto (nombre o código)"
                               aria-label="Buscar producto por nombre o código">
                        <button type="button" class="clay-btn clay-btn-sm clay-btn-secondary" id="limpiarFiltros">
                            Limpiar
                        </button>
                        <button class="clay-btn clay-btn-sm clay-btn-secondary" onclick="exportarReporte()">
                            📥 Exportar CSV
                        </button>
                    </div>
                </div>

                <div id="filtroActivo" class="d-none mb-2"></div>

                <div class="table-responsive">
                    <table class="clay-table" id="tablaProximos">
                        <thead>
                            <tr>
                                <th data-orden="producto" role="button" tabindex="0"
                                    style="cursor: pointer;" title="Ordenar por producto">Producto</th>
                                <th>Lote</th>
                                <th data-orden="cantidad" role="button" tabindex="0"
                                    style="cursor: pointer;" title="Ordenar por cantidad">Cantidad</th>
                                <th data-orden="fecha_vencimiento" role="button" tabindex="0"
                                    style="cursor: pointer;" title="Ordenar por fecha de vencimiento">Vencimiento</th>
                                <th data-orden="dias_restantes" role="button" tabindex="0"
                                    style="cursor: pointer;" title="Ordenar por días restantes">Días Restantes</th>
                                <th>Urgencia</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="bodyProximos">
                            <tr class="clay-loading">
                                <td colspan="8" class="text-center">
                                    <div class="spinner-border" role="status"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div id="paginacionProximos" class="clay-pagination mt-3"></div>
            </div>
        </div>

        <div class="tab-pane fade" id="porCriticidad">
            <div class="row">
                <div class="col-md-4">
                    <div class="clay-card clay-alert-critica">
                        <div class="clay-card-header">
                            <h5>🔴 Crítico (7 días)</h5>
                        </div>
                        <div id="criticosContainer">
                            <p class="text-muted">Cargando...</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="clay-card clay-alert-media">
                        <div class="clay-card-header">
                            <h5>🟡 Próximo (7-30 días)</h5>
                        </div>
                        <div id="proximosContainer">
                            <p class="text-muted">Cargando...</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="clay-card clay-alert-baja">
                        <div class="clay-card-header">
                            <h5>🟢 Seguro (>30 días)</h5>
                        </div>
                        <div id="segurosContainer">
                            <p class="text-muted">Cargando...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="filtrosModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content clay-modal">
            <div class="modal-header clay-modal-header">
                <h5 class="modal-title">🔍 Filtrar Vencimientos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="clay-label">Rango de días</label>
                    <select id="filtro-dias" class="clay-select">
                        <option value="7">Próximos 7 días</option>
                        <option value="15">Próximos 15 días</option>
                        <option value="30" selected>Próximos 30 días</option>
                        <option value="60">Próximos 60 días</option>
                        <option value="90">Próximos 90 días</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="clay-label">Producto (nombre o código)</label>
                    <input type="text" id="filtro-producto" class="clay-input" placeholder="Opcional">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="clay-btn clay-btn-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="clay-btn clay-btn-primary" onclick="aplicarFiltros()">
                    Aplicar Filtros
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
    /* Columna ordenada: se resalta y muestra la flecha de dirección. */
    #tablaProximos th.orden-activo {
        color: var(--clay-primary, #6366f1);
        background: rgba(99, 102, 241, .08);
    }
    #tablaProximos th.orden-flecha {
        font-size: .75em;
        opacity: .65;
    }
</style>
<script src="{{ asset('js/vencimientos.js') }}"></script>
@endsection
