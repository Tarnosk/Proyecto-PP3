@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="clay-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="clay-title">📋 Sugerencias de Reposición</h1>
                <p class="clay-subtitle">Sistema inteligente de sugerencias de compra</p>
            </div>
            <div class="btn-group">
                <button class="clay-btn clay-btn-primary" onclick="generarSugerencias()">
                    <i class="fas fa-sync"></i> Generar Sugerencias
                </button>
                <button class="clay-btn clay-btn-success" id="btnProcesarLote" onclick="procesarLoteSeleccionadas()" disabled>
                    <i class="fas fa-check-double"></i> Procesar Seleccionadas (<span id="contadorSeleccionadas">0</span>)
                </button>
                <button class="clay-btn clay-btn-secondary" onclick="exportarSugerencias()">
                    <i class="fas fa-download"></i> Exportar
                </button>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="clay-card clay-card-highlight">
                <div class="clay-card-icon">📦</div>
                <div class="clay-card-content">
                    <p class="clay-label">Sugerencias Pendientes</p>
                    <h3 id="total-pendientes">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="clay-card clay-card-highlight">
                <div class="clay-card-icon">📊</div>
                <div class="clay-card-content">
                    <p class="clay-label">Cantidad Total</p>
                    <h3 id="cantidad-total">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="clay-card clay-card-highlight">
                <div class="clay-card-icon">💰</div>
                <div class="clay-card-content">
                    <p class="clay-label">Costo Estimado</p>
                    <h3 id="costo-total">$0.00</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="clay-card clay-card-highlight">
                <div class="clay-card-icon">📈</div>
                <div class="clay-card-content">
                    <p class="clay-label">% Reposición</p>
                    <h3 id="porcentaje-reposicion">0%</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="clay-card mb-4">
        <div class="clay-card-header">
            <h5>Filtrar Sugerencias</h5>
        </div>
        <div class="row g-3 p-3">
            <div class="col-md-3">
                <label class="clay-label">Motivo</label>
                <select id="filtro-motivo" class="clay-select">
                    <option value="">Todos los motivos</option>
                    <option value="bajo_stock">Stock Bajo</option>
                    <option value="proximo_vencer">Próximo a Vencer</option>
                    <option value="agotamiento_inmediato">Agotamiento Inmediato</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="clay-label">Estado</label>
                <select id="filtro-estado" class="clay-select">
                    <option value="pendiente" selected>Pendientes</option>
                    <option value="procesada">Procesadas</option>
                    <option value="rechazada">Rechazadas</option>
                </select>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <button class="clay-btn clay-btn-secondary w-100" onclick="limpiarFiltros()">
                    🔄 Limpiar Filtros
                </button>
            </div>
        </div>
    </div>

    <div class="clay-card">
        <div class="clay-card-header">
            <h5>Listado de Sugerencias</h5>
        </div>

        <div class="table-responsive">
            <table class="clay-table" id="tablaSugerencias">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="checkAll" onchange="toggleTodas(this)"></th>
                        <th>Producto</th>
                        <th>Proveedor</th>
                        <th>Cantidad</th>
                        <th>Precio Unit.</th>
                        <th>Costo Total</th>
                        <th>Velocidad/Día</th>
                        <th>Plazo (días)</th>
                        <th>Fecha Reorden</th>
                        <th>Motivo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="bodySugerencias">
                    <tr class="clay-loading">
                        <td colspan="12" class="text-center">
                            <div class="spinner-border" role="status"></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="paginacionSugerencias" class="clay-pagination mt-3"></div>
    </div>
</div>

<div class="modal fade" id="detalleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content clay-modal">
            <div class="modal-header clay-modal-header">
                <h5 class="modal-title">Detalle de Sugerencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detalleContent">
                </div>
                <div class="mt-3">
                    <label for="motivoRechazo" class="form-label">Motivo del rechazo</label>
                    <textarea id="motivoRechazo" class="form-control" rows="2" maxlength="500"
                        placeholder="Opcional: por qué se descarta esta sugerencia"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="clay-btn clay-btn-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>
<button type="button" class="clay-btn clay-btn-danger" onclick="rechazarDesdeModal()">
                            ✕ Rechazar
                        </button>
                        <button type="button" class="clay-btn clay-btn-primary" onclick="generarOrdenDesdeSugerencia(sugerenciaActivaId)">
                            🛒 Generar Orden de Compra
                        </button>
                        <button type="button" class="clay-btn clay-btn-primary" onclick="procesarDesdeModal()">
                            ✓ Procesar Sugerencia
                        </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/sugerencias-reposicion.js') }}"></script>
@endsection
