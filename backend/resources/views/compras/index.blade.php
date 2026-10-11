@extends('layouts.app')

@section('title', 'Compras y Órdenes - Distribuidora Pigüé')
@section('page_title', 'Compras y Órdenes de Compra')
@section('page_subtitle', 'Gestión de Compras a Proveedores, Recepciones de Lotes, Órdenes de Reposición y Control de Deudas')

@section('content')
<div class="space-y-6">

    <!-- BARRA SUPERIOR DE PESTAÑAS (Claymorphism Tabs) -->
    <div class="flex items-center justify-between flex-wrap gap-4 bg-white/70 backdrop-blur-md p-2 rounded-2xl border border-white/80 shadow-[inset_2px_2px_4px_rgba(255,255,255,0.8),0_4px_12px_rgba(0,0,0,0.02)]">
        <div class="flex items-center gap-1.5 overflow-x-auto w-full md:w-auto p-1">
            <button onclick="cambiarPestanaCompras('compras')" id="tabBtn-compras" 
                    class="tab-btn-comp px-4 py-2.5 rounded-xl font-black text-xs flex items-center gap-2 transition-all cursor-pointer bg-white text-blue-600 shadow-sm border border-slate-200/60">
                <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                <span>Compras Realizadas</span>
            </button>
            <button onclick="cambiarPestanaCompras('ordenes')" id="tabBtn-ordenes" 
                    class="tab-btn-comp px-4 py-2.5 rounded-xl font-black text-xs flex items-center gap-2 transition-all cursor-pointer text-slate-600 hover:text-slate-900">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span>Órdenes de Compra</span>
            </button>
            <button onclick="cambiarPestanaCompras('sugerencias')" id="tabBtn-sugerencias" 
                    class="tab-btn-comp px-4 py-2.5 rounded-xl font-black text-xs flex items-center gap-2 transition-all cursor-pointer text-slate-600 hover:text-slate-900">
                <i data-lucide="sparkles" class="w-4 h-4 text-violet-500"></i>
                <span>Sugerencias de Reposición (PC07)</span>
            </button>
            <button onclick="cambiarPestanaCompras('deudas')" id="tabBtn-deudas" 
                    class="tab-btn-comp px-4 py-2.5 rounded-xl font-black text-xs flex items-center gap-2 transition-all cursor-pointer text-slate-600 hover:text-slate-900">
                <i data-lucide="wallet" class="w-4 h-4 text-emerald-600"></i>
                <span>Deudas con Proveedores (C09)</span>
            </button>
        </div>

        <div class="flex items-center gap-2 px-2">
            <button onclick="recargarCompras()" class="p-2 bg-white text-slate-600 hover:text-blue-600 rounded-xl border border-slate-200/80 shadow-sm transition-all" title="Actualizar datos">
                <i data-lucide="rotate-cw" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- PESTAÑA 1: COMPRAS A PROVEEDORES (C01 a C07)              -->
    <!-- ========================================================= -->
    <div id="pestana-compras" class="pestana-comp-content space-y-4">
        
        <!-- RESUMEN / KPIS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="clay-card p-5 flex items-center gap-4">
                <div class="p-3.5 bg-blue-50 text-blue-600 rounded-2xl shadow-inner border border-blue-100">
                    <i data-lucide="shopping-bag" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Total Compras</span>
                    <span id="kpiTotalCompras" class="text-2xl font-black text-slate-800">0</span>
                    <span class="text-xs text-slate-500 font-bold ml-1">registradas</span>
                </div>
            </div>

            <div class="clay-card p-5 flex items-center gap-4">
                <div class="p-3.5 bg-amber-50 text-amber-600 rounded-2xl shadow-inner border border-amber-100">
                    <i data-lucide="clock" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Pendientes / Parciales</span>
                    <span id="kpiPendientesCompras" class="text-2xl font-black text-amber-600">0</span>
                    <span class="text-xs text-amber-600 font-bold ml-1">por recepcionar</span>
                </div>
            </div>

            <div class="clay-card p-5 flex items-center gap-4">
                <div class="p-3.5 bg-emerald-50 text-emerald-600 rounded-2xl shadow-inner border border-emerald-100">
                    <i data-lucide="check-circle" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Completadas</span>
                    <span id="kpiCompletadasCompras" class="text-2xl font-black text-emerald-700">0</span>
                    <span class="text-xs text-emerald-600 font-bold ml-1">finalizadas</span>
                </div>
            </div>

            <div class="clay-card p-5 flex items-center gap-4">
                <div class="p-3.5 bg-rose-50 text-rose-600 rounded-2xl shadow-inner border border-rose-100">
                    <i data-lucide="alert-circle" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Canceladas</span>
                    <span id="kpiCanceladasCompras" class="text-2xl font-black text-rose-600">0</span>
                    <span class="text-xs text-rose-600 font-bold ml-1">anuladas</span>
                </div>
            </div>
        </div>

        <!-- FILTROS Y BOTÓN NUEVA COMPRA -->
        <div class="clay-card p-5 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            <div class="flex items-center flex-1 gap-3 flex-wrap">
                <div class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5"></i>
                    <input type="text" id="filtroSearchCompras" placeholder="Buscar por número, proveedor o comprobante..." 
                           class="clay-input pl-10 pr-4 py-2 w-full text-sm font-bold text-slate-700"
                           oninput="renderCompras()">
                </div>

                <select id="filtroEstadoCompras" onchange="renderCompras()" class="clay-input py-2 text-xs font-bold text-slate-700 min-w-[150px]">
                    <option value="">Todos los Estados</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="parcialmente_recibida">Parcialmente Recibida</option>
                    <option value="completada">Completada</option>
                    <option value="cancelada">Cancelada</option>
                </select>

                <select id="filtroProveedorCompras" onchange="renderCompras()" class="clay-input py-2 text-xs font-bold text-slate-700 min-w-[170px]">
                    <option value="">Todos los Proveedores</option>
                </select>
            </div>

            <div class="shrink-0">
                <button onclick="abrirModalNuevaCompra()" class="clay-btn-primary px-5 py-2.5 text-xs font-black flex items-center gap-2 shadow-md">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Nueva Compra (C01)</span>
                </button>
            </div>
        </div>

        <!-- TABLA DE COMPRAS -->
        <div class="clay-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>N° Compra / Fecha</th>
                            <th>Proveedor</th>
                            <th>Comprobante</th>
                            <th>Vencimiento</th>
                            <th class="text-right">Monto Total</th>
                            <th class="text-right">Saldo Pendiente</th>
                            <th class="text-center">Estado (C07)</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaComprasBody">
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400 font-bold">Cargando compras...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- PESTAÑA 2: ÓRDENES DE COMPRA (PC01 a PC06)                -->
    <!-- ========================================================= -->
    <div id="pestana-ordenes" class="pestana-comp-content hidden space-y-4">
        
        <!-- RESUMEN / KPIS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="clay-card p-5 flex items-center gap-4">
                <div class="p-3.5 bg-indigo-50 text-indigo-600 rounded-2xl shadow-inner border border-indigo-100">
                    <i data-lucide="file-text" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Total Órdenes</span>
                    <span id="kpiTotalOrdenes" class="text-2xl font-black text-slate-800">0</span>
                    <span class="text-xs text-slate-500 font-bold ml-1">emitidas</span>
                </div>
            </div>

            <div class="clay-card p-5 flex items-center gap-4">
                <div class="p-3.5 bg-amber-50 text-amber-600 rounded-2xl shadow-inner border border-amber-100">
                    <i data-lucide="clock" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Pendientes</span>
                    <span id="kpiPendientesOrdenes" class="text-2xl font-black text-amber-600">0</span>
                    <span class="text-xs text-amber-600 font-bold ml-1">por enviar</span>
                </div>
            </div>

            <div class="clay-card p-5 flex items-center gap-4">
                <div class="p-3.5 bg-blue-50 text-blue-600 rounded-2xl shadow-inner border border-blue-100">
                    <i data-lucide="send" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Enviadas</span>
                    <span id="kpiEnviadasOrdenes" class="text-2xl font-black text-blue-600">0</span>
                    <span class="text-xs text-blue-600 font-bold ml-1">al proveedor</span>
                </div>
            </div>

            <div class="clay-card p-5 flex items-center gap-4">
                <div class="p-3.5 bg-emerald-50 text-emerald-600 rounded-2xl shadow-inner border border-emerald-100">
                    <i data-lucide="check-check" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Completadas</span>
                    <span id="kpiCompletadasOrdenes" class="text-2xl font-black text-emerald-700">0</span>
                    <span class="text-xs text-emerald-600 font-bold ml-1">recibidas</span>
                </div>
            </div>
        </div>

        <div class="clay-card p-5 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            <div class="flex items-center flex-1 gap-3 flex-wrap">
                <div class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5"></i>
                    <input type="text" id="filtroSearchOrdenes" placeholder="Buscar por número u orden..." 
                           class="clay-input pl-10 pr-4 py-2 w-full text-sm font-bold text-slate-700"
                           oninput="renderOrdenes()">
                </div>

                <select id="filtroEstadoOrdenes" onchange="renderOrdenes()" class="clay-input py-2 text-xs font-bold text-slate-700 min-w-[150px]">
                    <option value="">Todos los Estados</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="enviada">Enviada</option>
                    <option value="recibida_parcialmente">Recibida Parcialmente</option>
                    <option value="completada">Completada</option>
                    <option value="cancelada">Cancelada</option>
                </select>
            </div>

            <div class="shrink-0">
                <button onclick="abrirModalNuevaOrden()" class="clay-btn-primary px-5 py-2.5 text-xs font-black flex items-center gap-2 shadow-md">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Emitir Orden de Compra (PC01)</span>
                </button>
            </div>
        </div>

        <div class="clay-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>N° Orden</th>
                            <th>Proveedor</th>
                            <th>Fecha Emisión</th>
                            <th>Entrega Estimada</th>
                            <th class="text-right">Total Estimado</th>
                            <th class="text-center">Estado (PC06)</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaOrdenesBody">
                        <tr>
                            <td colspan="7" class="text-center py-6 text-slate-400 font-bold">Cargando órdenes de compra...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- PESTAÑA 3: SUGERENCIAS DE REPOSICIÓN (PC07)              -->
    <!-- ========================================================= -->
    <div id="pestana-sugerencias" class="pestana-comp-content hidden space-y-4">
        
        <!-- HEADER Y ACCIÓN ESCANEAR -->
        <div class="clay-card p-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-violet-50 text-violet-600 flex items-center justify-center border border-violet-100 shadow-inner">
                    <i data-lucide="sparkles" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-800">Motor de Sugerencias de Reposición (PC07)</h3>
                    <p class="text-xs text-slate-500 font-bold">Detección de stock crítico/mínimo, cálculo de lote óptimo y asignación del mejor proveedor (PV05)</p>
                </div>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <button onclick="generarSugerenciasAhora()" class="clay-btn-primary px-4 py-2 text-xs font-black flex items-center gap-2 shadow-md bg-gradient-to-r from-violet-600 to-indigo-600">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    <span>Escanear y Generar Sugerencias</span>
                </button>
                <a href="{{ url('/dashboard-reposicion') }}" class="clay-btn-secondary px-4 py-2 text-xs font-bold flex items-center gap-2">
                    <i data-lucide="trending-up" class="w-4 h-4 text-emerald-600"></i>
                    <span>Métricas y Dashboard</span>
                </a>
            </div>
        </div>

        <!-- KPIS SUGERENCIAS -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="clay-card p-5 flex items-center gap-4">
                <div class="p-3 bg-violet-50 text-violet-600 rounded-xl">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="text-[11px] font-extrabold text-slate-400 uppercase block">Sugerencias Pendientes</span>
                    <span id="kpiSugerenciasPendientes" class="text-2xl font-black text-violet-700">0</span>
                </div>
            </div>
            <div class="clay-card p-5 flex items-center gap-4">
                <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl">
                    <i data-lucide="package" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="text-[11px] font-extrabold text-slate-400 uppercase block">Unidades a Reponer</span>
                    <span id="kpiSugerenciasUnidades" class="text-2xl font-black text-indigo-700">0</span>
                </div>
            </div>
            <div class="clay-card p-5 flex items-center gap-4">
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                    <i data-lucide="badge-dollar-sign" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="text-[11px] font-extrabold text-slate-400 uppercase block">Costo Estimado Reposición</span>
                    <span id="kpiSugerenciasCosto" class="text-2xl font-black text-emerald-700">$0.00</span>
                </div>
            </div>
        </div>

        <!-- TABLA DE SUGERENCIAS -->
        <div class="clay-card overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <span class="text-xs font-black uppercase text-slate-600 tracking-wider">Productos que requieren reposición inmediata</span>
                <span id="badgeSugerenciasCount" class="clay-badge clay-badge-info">0 sugerencias</span>
            </div>
            <div class="overflow-x-auto">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Stock / Mínimo</th>
                            <th class="text-center">Cant. Sugerida</th>
                            <th>Proveedor Asignado (PV05)</th>
                            <th class="text-right">Precio Unitario</th>
                            <th class="text-right">Costo Estimado</th>
                            <th class="text-center">Motivo</th>
                            <th class="text-center">Acciones (PC07)</th>
                        </tr>
                    </thead>
                    <tbody id="tablaSugerenciasBody">
                        <tr>
                            <td colspan="8" class="text-center py-6 text-slate-400 font-bold">Cargando sugerencias de reposición...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- PESTAÑA 4: DEUDAS CON PROVEEDORES (C09 - Issue #42)       -->
    <!-- ========================================================= -->
    <div id="pestana-deudas" class="pestana-comp-content hidden space-y-4">
        
        <!-- RESUMEN / KPIS DEUDAS -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="clay-card p-5 flex items-center gap-4">
                <div class="p-3.5 bg-rose-50 text-rose-600 rounded-2xl shadow-inner border border-rose-100">
                    <i data-lucide="wallet" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Saldo Adeudado Total</span>
                    <span id="kpiTotalDeuda" class="text-2xl font-black text-rose-700">$0.00</span>
                    <span class="text-xs text-rose-600 font-bold ml-1">pendiente de pago</span>
                </div>
            </div>

            <div class="clay-card p-5 flex items-center gap-4">
                <div class="p-3.5 bg-amber-50 text-amber-600 rounded-2xl shadow-inner border border-amber-100">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Proveedores con Deuda</span>
                    <span id="kpiProveedoresDeuda" class="text-2xl font-black text-amber-700">0</span>
                    <span class="text-xs text-slate-500 font-bold ml-1">cuentas corrientes</span>
                </div>
            </div>

            <div class="clay-card p-5 flex items-center gap-4">
                <div class="p-3.5 bg-blue-50 text-blue-600 rounded-2xl shadow-inner border border-blue-100">
                    <i data-lucide="file-warning" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Compras con Saldo</span>
                    <span id="kpiComprasDeuda" class="text-2xl font-black text-blue-700">0</span>
                    <span class="text-xs text-slate-500 font-bold ml-1">facturas pendientes</span>
                </div>
            </div>
        </div>

        <!-- FILTROS DEUDAS -->
        <div class="clay-card p-4 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-wrap flex-1">
                <select id="filtroProveedorDeudas" onchange="cargarDeudas()" class="clay-input py-2 text-xs font-bold text-slate-700 min-w-[200px]">
                    <option value="">Todos los Proveedores</option>
                </select>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-500">Desde:</span>
                    <input type="date" id="filtroDesdeDeudas" onchange="cargarDeudas()" class="clay-input py-1.5 px-3 text-xs font-bold">
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-500">Hasta:</span>
                    <input type="date" id="filtroHastaDeudas" onchange="cargarDeudas()" class="clay-input py-1.5 px-3 text-xs font-bold">
                </div>
            </div>

            <button onclick="cargarDeudas()" class="clay-btn-secondary px-4 py-2 text-xs font-bold flex items-center gap-2">
                <i data-lucide="filter" class="w-4 h-4"></i>
                <span>Aplicar Filtros</span>
            </button>
        </div>

        <!-- TABLA DE DEUDAS POR PROVEEDOR Y FACTURAS -->
        <div class="clay-card overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-black text-slate-800">Estado de Cuenta y Vencimientos Próximos (C09)</h3>
                    <p class="text-xs text-slate-500 font-bold">Compras pendientes de pago con fecha de vencimiento y saldo pendiente</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>N° Compra / Comprobante</th>
                            <th>Fecha Compra</th>
                            <th>Vencimiento Próximo</th>
                            <th class="text-right">Monto Total</th>
                            <th class="text-right">Pagado (C08)</th>
                            <th class="text-right">Saldo Adeudado</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tablaDeudasBody">
                        <tr>
                            <td colspan="8" class="text-center py-6 text-slate-400 font-bold">Cargando deudas con proveedores...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@section('modals')
<!-- ========================================== -->
<!-- MODAL: REGISTRAR PAGO A COMPRA (C08)       -->
<!-- ========================================== -->
<div id="modalPago" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalPago()">
    <div class="clay-modal-content max-w-md p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-800">Registrar Pago a Proveedor (C08)</h3>
                <p id="pagoSubtituloCompra" class="text-xs text-slate-500 font-bold">Compra #</p>
            </div>
            <button onclick="cerrarModalPago()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form onsubmit="guardarPagoCompra(event)" class="space-y-4">
            <input type="hidden" id="pagoIdCompra">

            <!-- RESUMEN DE SALDO -->
            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/60 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-extrabold uppercase text-slate-400 block">Saldo Adeudado</span>
                    <span id="pagoSaldoDisponibleDisplay" class="text-lg font-black text-rose-600">$0.00</span>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-extrabold uppercase text-slate-400 block">Total Factura</span>
                    <span id="pagoTotalFacturaDisplay" class="text-sm font-bold text-slate-700">$0.00</span>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Monto a Abonar ($)</label>
                <input type="number" step="0.01" min="0.01" id="pagoMonto" required 
                       class="clay-input w-full font-black text-lg text-emerald-700" placeholder="0.00">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Método de Pago</label>
                    <select id="pagoMetodo" required class="clay-input w-full text-xs font-bold">
                        <option value="transferencia">Transferencia Bancaria</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="cheque">Cheque Físico</option>
                        <option value="echeq">E-Cheq</option>
                        <option value="tarjeta">Tarjeta (Débito / Crédito)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Fecha de Pago</label>
                    <input type="date" id="pagoFecha" required class="clay-input w-full text-xs font-bold">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="cerrarModalPago()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cancelar</button>
                <button type="submit" class="clay-btn-primary px-5 py-2 text-xs font-black shadow-md">Registrar Pago</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: RECEPCIÓN DE MERCADERÍA (C06 / S09) -->
<!-- ========================================== -->
<div id="modalRecepcion" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalRecepcion()">
    <div class="clay-modal-content max-w-3xl p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-800">Registrar Recepción de Mercadería (C06)</h3>
                <p id="recepcionSubtituloCompra" class="text-xs text-slate-500 font-bold">Ingreso a stock con lote y vencimiento</p>
            </div>
            <button onclick="cerrarModalRecepcion()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form onsubmit="guardarRecepcionCompra(event)" class="space-y-4">
            <input type="hidden" id="recepcionIdCompra">

            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Fecha de Recepción</label>
                <input type="date" id="recepcionFecha" required class="clay-input w-48 text-xs font-bold">
            </div>

            <!-- TABLA DE ÍTEMS DE LA COMPRA PARA RECEPCIONAR -->
            <div class="border border-slate-200/80 rounded-2xl overflow-hidden bg-slate-50/50">
                <div class="p-3 bg-slate-100/60 font-black text-xs text-slate-700 flex justify-between">
                    <span>Productos de la Compra a Recepcionar</span>
                    <span class="text-[11px] text-slate-500 font-normal">Complete cantidad, n° lote y vencimiento para cada ítem</span>
                </div>
                <div class="overflow-x-auto max-h-72">
                    <table class="clay-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Comprado</th>
                                <th class="text-center">Recibido</th>
                                <th class="text-center">Pendiente</th>
                                <th class="w-28 text-center">Cant. a Recibir</th>
                                <th class="w-36">N° Lote (S09)</th>
                                <th class="w-36">Vencimiento</th>
                            </tr>
                        </thead>
                        <tbody id="recepcionItemsBody">
                            <tr>
                                <td colspan="7" class="text-center py-4 text-slate-400 font-bold">Cargando productos de la compra...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Para mantener compatibilidad con selects en tests antiguos -->
            <div class="hidden">
                <select id="recepcionProductoSelect"></select>
                <input type="number" id="recepcionCantidad" value="0">
                <input type="text" id="recepcionNroLote" value="">
                <input type="date" id="recepcionVencimiento" value="">
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="cerrarModalRecepcion()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cancelar</button>
                <button type="submit" class="clay-btn-primary px-5 py-2 text-xs font-black shadow-md">Ingresar Mercadería a Stock</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: NUEVA / EDITAR COMPRA (C01, C04)    -->
<!-- ========================================== -->
<div id="modalNuevaCompra" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalNuevaCompra()">
    <div class="clay-modal-content max-w-3xl p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 id="modalCompraTitulo" class="text-base font-black text-slate-800">Registrar Compra de Mercadería (C01)</h3>
                <p id="modalCompraSubtitulo" class="text-xs text-slate-500 font-bold">Selección de proveedor y productos para abastecimiento</p>
            </div>
            <button onclick="cerrarModalNuevaCompra()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form onsubmit="guardarNuevaCompra(event)" class="space-y-4">
            <input type="hidden" id="compraModoEdicion" value="false">
            <input type="hidden" id="compraIdEditar" value="">
            <input type="hidden" id="compraIdOrden" value="">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-1">
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Proveedor (PV01)</label>
                    <select id="compraProveedor" required onchange="alCambiarProveedorCompra()" class="clay-input w-full text-xs font-bold">
                        <option value="">Seleccione proveedor activo...</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">N° Comprobante / Factura</label>
                    <input type="text" id="compraComprobante" placeholder="FC-A-0001-00001234" class="clay-input w-full text-xs font-bold">
                </div>

                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Fecha de Compra</label>
                    <input type="date" id="compraFecha" required class="clay-input w-full text-xs font-bold">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Fecha de Vencimiento de Pago (C09)</label>
                <input type="date" id="compraVencimiento" class="clay-input w-48 text-xs font-bold">
            </div>

            <!-- SECCIÓN AGREGAR PRODUCTOS -->
            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-3">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <span class="text-xs font-black uppercase text-slate-700">Agregar Producto al Detalle</span>
                    <span id="labelProductosProveedorInfo" class="text-[11px] font-bold text-blue-600"></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                    <div class="sm:col-span-6">
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase mb-1">Producto</label>
                        <select id="compraItemProducto" onchange="alCambiarProductoCompra()" class="clay-input w-full text-xs font-bold">
                            <option value="">Seleccione producto...</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase mb-1">Cantidad</label>
                        <input type="number" id="compraItemCantidad" min="1" value="1" class="clay-input w-full text-xs font-black text-center">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase mb-1">Precio Unitario ($)</label>
                        <input type="number" step="0.01" min="0" id="compraItemPrecio" placeholder="0.00" class="clay-input w-full text-xs font-black text-right">
                    </div>

                    <div class="sm:col-span-2">
                        <button type="button" onclick="agregarItemCompra()" class="clay-btn-secondary w-full py-2 text-xs font-black flex items-center justify-center gap-1">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>Agregar</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- TABLA DE DETALLE DE LA COMPRA -->
            <div class="border border-slate-200/80 rounded-2xl overflow-hidden">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-center w-24">Cantidad</th>
                            <th class="text-right w-32">Precio Unit.</th>
                            <th class="text-right w-32">Subtotal</th>
                            <th class="text-center w-12"></th>
                        </tr>
                    </thead>
                    <tbody id="detalleCompraItems">
                        <tr>
                            <td colspan="5" class="text-center py-4 text-slate-400 font-bold text-xs">Sin productos agregados.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between pt-2">
                <div class="text-xs font-bold text-slate-500">
                    Importe Total Calculado: <span id="compraTotalDisplay" class="text-base font-black text-blue-600">$0.00</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="cerrarModalNuevaCompra()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cancelar</button>
                    <button type="submit" id="btnGuardarCompraSubmit" class="clay-btn-primary px-5 py-2 text-xs font-black shadow-md">Guardar Compra</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: DETALLE COMPRA (C03 & C07)          -->
<!-- ========================================== -->
<div id="modalDetalleCompra" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalDetalleCompra()">
    <div class="clay-modal-content max-w-3xl p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-800">Detalle de Compra (C03)</h3>
                <p id="detalleCompraSubtitulo" class="text-xs font-bold text-slate-500">Compra</p>
            </div>
            <button onclick="cerrarModalDetalleCompra()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- BARRAS DE PROGRESO DE RECEPCIÓN Y PAGOS (C07) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60 space-y-1">
                <div class="flex justify-between text-xs font-bold">
                    <span class="text-slate-500">Recepción de Mercadería:</span>
                    <span id="detalleRecepcionProgresoTexto" class="font-black text-blue-600">0%</span>
                </div>
                <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                    <div id="detalleRecepcionProgresoBar" class="bg-blue-600 h-full rounded-full transition-all" style="width: 0%"></div>
                </div>
            </div>

            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60 space-y-1">
                <div class="flex justify-between text-xs font-bold">
                    <span class="text-slate-500">Estado de Pagos (C08):</span>
                    <span id="detallePagoProgresoTexto" class="font-black text-emerald-600">0%</span>
                </div>
                <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                    <div id="detallePagoProgresoBar" class="bg-emerald-600 h-full rounded-full transition-all" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto max-h-64">
            <table class="clay-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="text-center">Comprado</th>
                        <th class="text-center">Recibido</th>
                        <th class="text-right">Precio Unit.</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody id="detalleCompraTablaBody">
                    <tr><td colspan="5" class="text-center py-4 text-slate-400">Cargando...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- HISTORIAL DE RECEPCIONES Y PAGOS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <div class="p-3 bg-white rounded-xl border border-slate-200/80">
                <span class="text-[11px] font-black uppercase text-slate-500 block mb-2">Recepciones Realizadas</span>
                <div id="detalleRecepcionesHistorial" class="text-xs text-slate-600 space-y-1 max-h-32 overflow-y-auto">
                    <span class="text-slate-400 font-bold">Sin recepciones registradas.</span>
                </div>
            </div>

            <div class="p-3 bg-white rounded-xl border border-slate-200/80">
                <span class="text-[11px] font-black uppercase text-slate-500 block mb-2">Pagos Realizados (C08)</span>
                <div id="detallePagosHistorial" class="text-xs text-slate-600 space-y-1 max-h-32 overflow-y-auto">
                    <span class="text-slate-400 font-bold">Sin pagos registrados.</span>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-3 border-t border-slate-100">
            <button type="button" onclick="cerrarModalDetalleCompra()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cerrar</button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: NUEVA / EDITAR ORDEN (PC01, PC04)   -->
<!-- ========================================== -->
<div id="modalNuevaOrden" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalNuevaOrden()">
    <div class="clay-modal-content max-w-3xl p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 id="modalOrdenTitulo" class="text-base font-black text-slate-800">Emitir Orden de Compra (PC01)</h3>
                <p id="modalOrdenSubtitulo" class="text-xs text-slate-500 font-bold">Pedido formal de reposición hacia un proveedor</p>
            </div>
            <button onclick="cerrarModalNuevaOrden()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form onsubmit="guardarNuevaOrden(event)" class="space-y-4">
            <input type="hidden" id="ordenModoEdicion" value="false">
            <input type="hidden" id="ordenIdEditar" value="">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-1">
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Proveedor (PV01)</label>
                    <select id="ordenProveedor" required onchange="alCambiarProveedorOrden()" class="clay-input w-full text-xs font-bold">
                        <option value="">Seleccione proveedor activo...</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Fecha Emisión</label>
                    <input type="date" id="ordenFechaEmision" required class="clay-input w-full text-xs font-bold">
                </div>

                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Entrega Estimada</label>
                    <input type="date" id="ordenFechaEntrega" class="clay-input w-full text-xs font-bold">
                </div>
            </div>

            <!-- SECCIÓN AGREGAR PRODUCTOS A LA ORDEN -->
            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-3">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <span class="text-xs font-black uppercase text-slate-700">Productos Solicitados</span>
                    <span id="labelProductosOrdenInfo" class="text-[11px] font-bold text-indigo-600"></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                    <div class="sm:col-span-6">
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase mb-1">Producto</label>
                        <select id="ordenItemProducto" onchange="alCambiarProductoOrden()" class="clay-input w-full text-xs font-bold">
                            <option value="">Seleccione producto...</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase mb-1">Cantidad Solicitada</label>
                        <input type="number" id="ordenItemCantidad" min="1" value="1" class="clay-input w-full text-xs font-black text-center">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase mb-1">Precio Estimado ($)</label>
                        <input type="number" step="0.01" min="0" id="ordenItemPrecio" placeholder="0.00" class="clay-input w-full text-xs font-black text-right">
                    </div>

                    <div class="sm:col-span-2">
                        <button type="button" onclick="agregarItemOrden()" class="clay-btn-secondary w-full py-2 text-xs font-black flex items-center justify-center gap-1">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>Agregar</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- TABLA ÍTEMS ORDEN -->
            <div class="border border-slate-200/80 rounded-2xl overflow-hidden">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-center w-24">Cant. Solicitada</th>
                            <th class="text-right w-32">Precio Est.</th>
                            <th class="text-right w-32">Subtotal</th>
                            <th class="text-center w-12"></th>
                        </tr>
                    </thead>
                    <tbody id="detalleOrdenItems">
                        <tr>
                            <td colspan="5" class="text-center py-4 text-slate-400 font-bold text-xs">Sin productos en la orden.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between pt-2">
                <div class="text-xs font-bold text-slate-500">
                    Total Estimado: <span id="ordenTotalDisplay" class="text-base font-black text-indigo-600">$0.00</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="cerrarModalNuevaOrden()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cancelar</button>
                    <button type="submit" id="btnGuardarOrdenSubmit" class="clay-btn-primary px-5 py-2 text-xs font-black shadow-md">Generar Orden</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: DETALLE ORDEN (PC03 & PC06)         -->
<!-- ========================================== -->
<div id="modalDetalleOrden" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalDetalleOrden()">
    <div class="clay-modal-content max-w-2xl p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-800">Detalle de Orden de Compra (PC03)</h3>
                <p id="detalleOrdenSubtitulo" class="text-xs font-bold text-slate-500">Orden</p>
            </div>
            <button onclick="cerrarModalDetalleOrden()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="overflow-x-auto max-h-60">
            <table class="clay-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="text-center">Cant. Solicitada</th>
                        <th class="text-right">Precio Estimado</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody id="detalleOrdenTablaBody">
                    <tr><td colspan="4" class="text-center py-4 text-slate-400">Cargando ítems...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end pt-3 border-t border-slate-100">
            <button type="button" onclick="cerrarModalDetalleOrden()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cerrar</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const API_BASE = '{{ url("/api") }}';
    let comprasCache = [];
    let ordenesCache = [];
    let productosCache = [];
    let proveedoresCache = [];
    let sugerenciasCache = [];
    let deudasCache = [];

    // Cache de asociaciones proveedor-producto para autocompletar precios acordados (PV05)
    let productosProveedorCache = {};

    document.addEventListener('DOMContentLoaded', () => {
        recargarCompras();
    });

    async function recargarCompras() {
        await Promise.all([cargarCompras(), cargarOrdenes(), cargarProductos(), cargarProveedoresCompras()]);
        await Promise.all([cargarSugerencias(), cargarDeudas()]);
        if (window.lucide) window.lucide.createIcons();
    }

    function cambiarPestanaCompras(id) {
        document.querySelectorAll('.pestana-comp-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-btn-comp').forEach(btn => {
            btn.classList.remove('bg-white', 'text-blue-600', 'shadow-sm', 'border', 'border-slate-200/60');
            btn.classList.add('text-slate-600');
        });

        const pestana = document.getElementById(`pestana-${id}`);
        const boton = document.getElementById(`tabBtn-${id}`);
        if (pestana) pestana.classList.remove('hidden');
        if (boton) {
            boton.classList.add('bg-white', 'text-blue-600', 'shadow-sm', 'border', 'border-slate-200/60');
            boton.classList.remove('text-slate-600');
        }
        if (window.lucide) window.lucide.createIcons();
    }

    // =========================================================
    // COMPRAS (C01 a C07)
    // =========================================================

    async function cargarCompras() {
        try {
            const res = await fetch(`${API_BASE}/compras`);
            const json = await res.json();
            if (json.status === 'success') {
                comprasCache = json.data || [];
                renderCompras();
                actualizarKPIsCompras();
            }
        } catch (e) {
            console.error('Error cargando compras:', e);
        }
    }

    function renderCompras() {
        const search = (document.getElementById('filtroSearchCompras')?.value || '').toLowerCase().trim();
        const estado = document.getElementById('filtroEstadoCompras')?.value || '';
        const idProv = document.getElementById('filtroProveedorCompras')?.value || '';

        const filtradas = comprasCache.filter(c => {
            const provNombre = (c.proveedor || '').toLowerCase();
            const numComp = (c.numero_compra || '').toLowerCase();
            const fact = (c.numero_comprobante || '').toLowerCase();
            const idC = String(c.id_compra || '');

            const matchText = !search || provNombre.includes(search) || numComp.includes(search) || fact.includes(search) || idC.includes(search);
            const matchEstado = !estado || c.estado === estado;
            const matchProv = !idProv || String(c.id_proveedor) === String(idProv);

            return matchText && matchEstado && matchProv;
        });

        const tbody = document.getElementById('tablaComprasBody');
        if (!tbody) return;

        if (filtradas.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-slate-400 font-bold">No se encontraron compras registradas.</td></tr>`;
            return;
        }

        tbody.innerHTML = filtradas.map(c => {
            let badgeClass = 'clay-badge-info';
            if (c.estado === 'completada') badgeClass = 'clay-badge-success';
            if (c.estado === 'cancelada') badgeClass = 'clay-badge-danger';
            if (c.estado === 'pendiente' || c.estado === 'parcialmente_recibida') badgeClass = 'clay-badge-warning';

            const total = Number(c.importe_total || 0).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
            const saldo = Number(c.saldo_pendiente ?? c.importe_total ?? 0).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
            
            const esPendiente = c.estado === 'pendiente';
            const esParcial = c.estado === 'parcialmente_recibida';
            const admiteRecepcion = esPendiente || esParcial;
            const admitePago = c.estado !== 'cancelada';

            return `
                <tr>
                    <td>
                        <div class="font-black text-slate-800">${c.numero_compra || `#CMP-${c.id_compra}`}</div>
                        <div class="text-[11px] text-slate-400 font-bold">${c.fecha_compra ? c.fecha_compra.substring(0, 10) : '-'}</div>
                    </td>
                    <td class="font-bold text-slate-800">${c.proveedor || `Proveedor #${c.id_proveedor}`}</td>
                    <td class="text-xs text-slate-600 font-bold">${c.numero_comprobante || '<span class="text-slate-400 italic">Sin comprobante</span>'}</td>
                    <td class="text-xs font-bold text-slate-600">${c.fecha_vencimiento ? c.fecha_vencimiento.substring(0, 10) : '<span class="text-slate-400">-</span>'}</td>
                    <td class="text-right font-black text-slate-800">${total}</td>
                    <td class="text-right font-black ${Number(c.saldo_pendiente) > 0 ? 'text-amber-600' : 'text-slate-400'}">${saldo}</td>
                    <td class="text-center"><span class="clay-badge ${badgeClass} uppercase">${c.estado.replace('_', ' ')}</span></td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <button onclick="abrirModalDetalleCompra(${c.id_compra})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Ver Detalle (C03)">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            ${admiteRecepcion ? `
                            <button onclick="abrirModalRecepcionCompra(${c.id_compra})" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" title="Registrar Recepción (C06)">
                                <i data-lucide="package-check" class="w-4 h-4"></i>
                            </button>
                            ` : ''}
                            ${admitePago ? `
                            <button onclick="abrirModalPagoCompra(${c.id_compra})" class="p-1.5 text-violet-600 hover:bg-violet-50 rounded-lg transition-all" title="Registrar Pago (C08)">
                                <i data-lucide="credit-card" class="w-4 h-4"></i>
                            </button>
                            ` : ''}
                            ${esPendiente ? `
                            <button onclick="abrirModalEditarCompra(${c.id_compra})" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-all" title="Modificar Compra (C04)">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <button onclick="cancelarCompra(${c.id_compra})" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-all" title="Cancelar Compra (C05)">
                                <i data-lucide="x-circle" class="w-4 h-4"></i>
                            </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        if (window.lucide) window.lucide.createIcons();
    }

    function actualizarKPIsCompras() {
        document.getElementById('kpiTotalCompras').textContent = comprasCache.length;
        document.getElementById('kpiPendientesCompras').textContent = comprasCache.filter(c => c.estado === 'pendiente' || c.estado === 'parcialmente_recibida').length;
        document.getElementById('kpiCompletadasCompras').textContent = comprasCache.filter(c => c.estado === 'completada').length;
        document.getElementById('kpiCanceladasCompras').textContent = comprasCache.filter(c => c.estado === 'cancelada').length;
    }

    // =========================================================
    // PROVEEDORES Y ASOCIACIONES (PV01, PV05)
    // =========================================================

    async function cargarProveedoresCompras() {
        try {
            const res = await fetch(`${API_BASE}/proveedores?estado=activo`);
            const json = await res.json();
            if (json.status === 'success') {
                proveedoresCache = (json.data || []).filter(p => p.estado === 'activo');
                
                const selC = document.getElementById('compraProveedor');
                const selO = document.getElementById('ordenProveedor');
                const selFiltroC = document.getElementById('filtroProveedorCompras');
                const selFiltroD = document.getElementById('filtroProveedorDeudas');

                const opts = '<option value="">Seleccione proveedor activo...</option>' + 
                    proveedoresCache.map(p => `<option value="${p.id_proveedor}">${p.razon_social} (${p.cuit || p.CUIT || ''})</option>`).join('');

                const optsFiltro = '<option value="">Todos los Proveedores</option>' + 
                    proveedoresCache.map(p => `<option value="${p.id_proveedor}">${p.razon_social}</option>`).join('');

                if (selC) selC.innerHTML = opts;
                if (selO) selO.innerHTML = opts;
                if (selFiltroC) selFiltroC.innerHTML = optsFiltro;
                if (selFiltroD) selFiltroD.innerHTML = optsFiltro;
            }
        } catch (e) {
            console.error('Error cargando proveedores:', e);
        }
    }

    async function cargarProductos() {
        try {
            const res = await fetch(`${API_BASE}/productos`);
            const json = await res.json();
            if (json.status === 'success') {
                productosCache = (json.data || []).filter(p => p.estado === 'activo');
            }
        } catch (e) {
            console.error('Error cargando productos:', e);
        }
    }

    // Al seleccionar proveedor en Nueva Compra, cargamos sus productos asociados (PV05)
    async function alCambiarProveedorCompra() {
        const idProv = document.getElementById('compraProveedor').value;
        const selProd = document.getElementById('compraItemProducto');
        const lblInfo = document.getElementById('labelProductosProveedorInfo');
        if (!selProd) return;

        if (!idProv) {
            selProd.innerHTML = '<option value="">Seleccione primero un proveedor...</option>';
            if (lblInfo) lblInfo.textContent = '';
            return;
        }

        try {
            const res = await fetch(`${API_BASE}/proveedores/${idProv}/productos`);
            const json = await res.json();
            let asociados = [];
            if (json.status === 'success') {
                asociados = json.data || [];
                productosProveedorCache[idProv] = asociados;
            }

            const idAsociados = new Set(asociados.map(a => a.id_producto));

            let html = '<option value="">Seleccione producto...</option>';
            if (asociados.length > 0) {
                html += '<optgroup label="Productos Asociados al Proveedor (PV05)">';
                asociados.forEach(a => {
                    const precio = a.precio_acordado !== null ? a.precio_acordado : (a.precio_unitario || 0);
                    html += `<option value="${a.id_producto}" data-precio="${precio}" data-asociado="true">★ ${a.codigo} - ${a.nombre} ($${Number(precio).toFixed(2)})</option>`;
                });
                html += '</optgroup>';
            }

            // También dejamos los demás productos del catálogo por si desea abastecer un producto nuevo
            const otros = productosCache.filter(p => !idAsociados.has(p.id_producto));
            if (otros.length > 0) {
                html += '<optgroup label="Otros Productos del Catálogo (Se asociarán automáticamente)">';
                otros.forEach(p => {
                    html += `<option value="${p.id_producto}" data-precio="${p.precio_unitario || 0}" data-asociado="false">${p.codigo} - ${p.nombre}</option>`;
                });
                html += '</optgroup>';
            }

            selProd.innerHTML = html;
            if (lblInfo) {
                lblInfo.textContent = asociados.length > 0 ? `${asociados.length} producto(s) con precio acordado.` : 'Sin productos asociados previamente (se asociarán al comprar).';
            }
        } catch (e) {
            console.error('Error productos proveedor:', e);
        }
    }

    function alCambiarProductoCompra() {
        const sel = document.getElementById('compraItemProducto');
        const opt = sel.options[sel.selectedIndex];
        if (opt && opt.dataset.precio) {
            document.getElementById('compraItemPrecio').value = Number(opt.dataset.precio).toFixed(2);
        }
    }

    // =========================================================
    // NUEVA COMPRA Y MODIFICACIÓN DE COMPRA (C01, C04)
    // =========================================================

    let itemsNuevaCompra = [];

    function abrirModalNuevaCompra() {
        document.getElementById('compraModoEdicion').value = 'false';
        document.getElementById('compraIdEditar').value = '';
        document.getElementById('compraIdOrden').value = '';
        document.getElementById('modalCompraTitulo').textContent = 'Registrar Compra de Mercadería (C01)';
        document.getElementById('modalCompraSubtitulo').textContent = 'Selección de proveedor y productos para abastecimiento';
        document.getElementById('btnGuardarCompraSubmit').textContent = 'Registrar Compra';
        document.getElementById('compraProveedor').disabled = false;
        document.getElementById('compraProveedor').value = '';
        document.getElementById('compraComprobante').value = '';
        document.getElementById('compraFecha').value = new Date().toISOString().split('T')[0];
        document.getElementById('compraVencimiento').value = new Date(Date.now() + 30*24*60*60*1000).toISOString().split('T')[0];

        itemsNuevaCompra = [];
        renderItemsNuevaCompra();
        alCambiarProveedorCompra();

        document.getElementById('modalNuevaCompra').classList.remove('hidden');
    }

    async function abrirModalEditarCompra(idCompra) {
        try {
            const res = await fetch(`${API_BASE}/compras/${idCompra}`);
            const json = await res.json();
            if (json.status !== 'success' || !json.data) {
                mostrarNotificacion('No se pudo cargar la compra a editar.', 'error');
                return;
            }

            const c = json.data.compra;
            if (c.estado !== 'pendiente') {
                mostrarNotificacion('Solo se pueden modificar compras en estado pendiente. (C04)', 'warning');
                return;
            }

            document.getElementById('compraModoEdicion').value = 'true';
            document.getElementById('compraIdEditar').value = idCompra;
            document.getElementById('compraIdOrden').value = c.id_orden || '';
            document.getElementById('modalCompraTitulo').textContent = `Modificar Compra Pendiente #${c.numero_compra || idCompra} (C04)`;
            document.getElementById('modalCompraSubtitulo').textContent = 'Edición de cantidades, precios y productos antes de la recepción';
            document.getElementById('btnGuardarCompraSubmit').textContent = 'Guardar Cambios';

            document.getElementById('compraProveedor').value = c.id_proveedor;
            document.getElementById('compraProveedor').disabled = true; // El proveedor de la compra no cambia
            await alCambiarProveedorCompra();

            document.getElementById('compraComprobante').value = c.numero_comprobante || '';
            document.getElementById('compraFecha').value = c.fecha_compra ? c.fecha_compra.substring(0, 10) : new Date().toISOString().split('T')[0];
            document.getElementById('compraVencimiento').value = c.fecha_vencimiento ? c.fecha_vencimiento.substring(0, 10) : '';

            itemsNuevaCompra = (json.data.detalles || []).map(d => ({
                id_producto: d.id_producto,
                nombre: d.descripcion || d.codigo || `Prod #${d.id_producto}`,
                cantidad: d.cantidad,
                precio_unitario: d.precio_unitario,
                subtotal: d.subtotal
            }));

            renderItemsNuevaCompra();
            document.getElementById('modalNuevaCompra').classList.remove('hidden');
        } catch (e) {
            mostrarNotificacion('Error de conexión al cargar la compra.', 'error');
        }
    }

    function cerrarModalNuevaCompra() {
        document.getElementById('modalNuevaCompra').classList.add('hidden');
    }

    function agregarItemCompra() {
        const sel = document.getElementById('compraItemProducto');
        const idProd = Number(sel.value);
        const cant = Number(document.getElementById('compraItemCantidad').value);
        const precio = Number(document.getElementById('compraItemPrecio').value);

        if (!idProd || cant <= 0 || precio < 0) {
            mostrarNotificacion('Debe seleccionar un producto, cantidad mayor a 0 y precio unitario válido.', 'warning');
            return;
        }

        const opt = sel.options[sel.selectedIndex];
        const prod = productosCache.find(p => p.id_producto === idProd);
        const nombre = prod ? prod.nombre : (opt ? opt.text : `Prod #${idProd}`);

        // Si ya existe el ítem en la lista, sumar cantidad
        const idx = itemsNuevaCompra.findIndex(i => i.id_producto === idProd);
        if (idx >= 0) {
            itemsNuevaCompra[idx].cantidad += cant;
            itemsNuevaCompra[idx].precio_unitario = precio;
            itemsNuevaCompra[idx].subtotal = itemsNuevaCompra[idx].cantidad * precio;
        } else {
            itemsNuevaCompra.push({
                id_producto: idProd,
                nombre: nombre,
                cantidad: cant,
                precio_unitario: precio,
                subtotal: Math.round(cant * precio * 100) / 100
            });
        }

        renderItemsNuevaCompra();
        document.getElementById('compraItemCantidad').value = '1';
    }

    function quitarItemCompra(idx) {
        itemsNuevaCompra.splice(idx, 1);
        renderItemsNuevaCompra();
    }

    function renderItemsNuevaCompra() {
        const tbody = document.getElementById('detalleCompraItems');
        let total = 0;
        if (itemsNuevaCompra.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-slate-400 font-bold text-xs">Sin productos agregados.</td></tr>';
            document.getElementById('compraTotalDisplay').textContent = '$0.00';
            return;
        }

        tbody.innerHTML = itemsNuevaCompra.map((it, idx) => {
            total += it.subtotal;
            return `
                <tr>
                    <td class="font-bold text-slate-800">${it.nombre}</td>
                    <td class="text-center font-black">${it.cantidad}</td>
                    <td class="text-right font-black">$${it.precio_unitario.toFixed(2)}</td>
                    <td class="text-right font-black text-blue-600">$${it.subtotal.toFixed(2)}</td>
                    <td class="text-center">
                        <button type="button" onclick="quitarItemCompra(${idx})" class="p-1 text-rose-500 hover:text-rose-700 font-black text-sm">×</button>
                    </td>
                </tr>
            `;
        }).join('');
        document.getElementById('compraTotalDisplay').textContent = `$${total.toFixed(2)}`;
    }

    async function guardarNuevaCompra(e) {
        e.preventDefault();
        const idProv = document.getElementById('compraProveedor').value;
        if (!idProv || itemsNuevaCompra.length === 0) {
            mostrarNotificacion('Seleccione un proveedor activo y agregue al menos un producto.', 'warning');
            return;
        }

        const esEdicion = document.getElementById('compraModoEdicion').value === 'true';
        const idEditar = document.getElementById('compraIdEditar').value;
        const idOrden = document.getElementById('compraIdOrden').value;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const payload = {
            id_proveedor: Number(idProv),
            id_orden: idOrden ? Number(idOrden) : null,
            numero_comprobante: document.getElementById('compraComprobante').value.trim() || null,
            fecha_compra: document.getElementById('compraFecha').value,
            fecha_vencimiento: document.getElementById('compraVencimiento').value || null,
            items: itemsNuevaCompra.map(i => ({
                id_producto: i.id_producto,
                cantidad: i.cantidad,
                precio_unitario: i.precio_unitario
            }))
        };

        const url = esEdicion ? `${API_BASE}/compras/${idEditar}` : `${API_BASE}/compras`;
        const method = esEdicion ? 'PUT' : 'POST';

        try {
            const res = await fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                cerrarModalNuevaCompra();
                mostrarNotificacion(data.message || (esEdicion ? 'Compra modificada correctamente.' : 'Compra registrada exitosamente.'), 'success');
                await Promise.all([cargarCompras(), cargarOrdenes(), cargarDeudas()]);
            } else {
                mostrarNotificacion(data.message || 'Error al guardar compra.', 'error');
            }
        } catch (err) {
            mostrarNotificacion('Error de conexión al guardar la compra.', 'error');
        }
    }

    async function cancelarCompra(id) {
        if (!confirm('¿Confirma la cancelación de esta compra pendiente? Esta acción conservará el historial. (C05)')) return;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            const res = await fetch(`${API_BASE}/compras/${id}/cancelar`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ motivo: 'Cancelación manual desde interfaz' })
            });
            const data = await res.json();
            if (data.status === 'success') {
                mostrarNotificacion('Compra cancelada exitosamente.', 'success');
                await Promise.all([cargarCompras(), cargarDeudas()]);
            } else {
                mostrarNotificacion(data.message || 'Error al cancelar la compra.', 'error');
            }
        } catch (err) {
            mostrarNotificacion('Error de conexión al cancelar la compra.', 'error');
        }
    }

    // =========================================================
    // DETALLE DE COMPRA (C03 & C07)
    // =========================================================

    async function abrirModalDetalleCompra(id) {
        document.getElementById('detalleCompraSubtitulo').textContent = `Compra #${id}`;
        document.getElementById('modalDetalleCompra').classList.remove('hidden');
        const tbody = document.getElementById('detalleCompraTablaBody');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-slate-400">Cargando datos de la compra...</td></tr>';

        try {
            const res = await fetch(`${API_BASE}/compras/${id}`);
            const json = await res.json();
            if (json.status === 'success' && json.data) {
                const c = json.data.compra;
                const totComp = json.data.total_comprado || 1;
                const totRec = json.data.total_recibido || 0;
                const pctRec = Math.min(100, Math.round((totRec / totComp) * 100));

                const pagos = json.data.pagos || {};
                const impTot = Number(c.importe_total || 0);
                const totPag = Number(pagos.total_pagado || 0);
                const pctPag = impTot > 0 ? Math.min(100, Math.round((totPag / impTot) * 100)) : 100;

                // Actualizar barras de progreso C07
                document.getElementById('detalleRecepcionProgresoTexto').textContent = `${pctRec}% (${totRec} de ${totComp} un.)`;
                document.getElementById('detalleRecepcionProgresoBar').style.width = `${pctRec}%`;

                document.getElementById('detallePagoProgresoTexto').textContent = `${pctPag}% ($${totPag.toFixed(2)} de $${impTot.toFixed(2)})`;
                document.getElementById('detallePagoProgresoBar').style.width = `${pctPag}%`;

                tbody.innerHTML = (json.data.detalles || []).map(d => `
                    <tr>
                        <td class="font-bold text-slate-800">${d.descripcion || d.codigo || `Prod #${d.id_producto}`}</td>
                        <td class="text-center font-black">${d.cantidad}</td>
                        <td class="text-center font-bold text-emerald-600">${d.cantidad_recibida || 0}</td>
                        <td class="text-right font-black">$${Number(d.precio_unitario).toFixed(2)}</td>
                        <td class="text-right font-black text-blue-600">$${Number(d.subtotal).toFixed(2)}</td>
                    </tr>
                `).join('');

                // Recepciones históricas
                const divRecep = document.getElementById('detalleRecepcionesHistorial');
                if (json.data.recepciones && json.data.recepciones.length > 0) {
                    divRecep.innerHTML = json.data.recepciones.map(r => `
                        <div class="p-1.5 bg-slate-50 rounded-lg flex items-center justify-between">
                            <span>Recepción #${r.id_recepcion} (${r.fecha_recepcion})</span>
                            <span class="font-black text-emerald-600">+${r.total_recibido} unidades</span>
                        </div>
                    `).join('');
                } else {
                    divRecep.innerHTML = '<span class="text-slate-400 font-bold">Sin recepciones registradas todavía.</span>';
                }

                // Pagos históricos
                const divPagos = document.getElementById('detallePagosHistorial');
                if (pagos.por_metodo && pagos.por_metodo.length > 0) {
                    divPagos.innerHTML = pagos.por_metodo.map(p => `
                        <div class="p-1.5 bg-slate-50 rounded-lg flex items-center justify-between">
                            <span class="capitalize">${p.metodo_pago.replace('_', ' ')} (${p.cantidad_pagos} pago(s))</span>
                            <span class="font-black text-violet-600">$${Number(p.total).toFixed(2)}</span>
                        </div>
                    `).join('');
                } else {
                    divPagos.innerHTML = '<span class="text-slate-400 font-bold">Sin pagos registrados todavía.</span>';
                }
            }
        } catch (e) {
            console.error('Error detalle compra:', e);
        }
    }

    function cerrarModalDetalleCompra() {
        document.getElementById('modalDetalleCompra').classList.add('hidden');
    }

    // =========================================================
    // RECEPCIONES PARCIALES Y TOTALES (C06 / S09)
    // =========================================================

    let itemsRecepcionModal = [];

    async function abrirModalRecepcionCompra(idCompra) {
        document.getElementById('recepcionIdCompra').value = idCompra;
        document.getElementById('recepcionFecha').value = new Date().toISOString().split('T')[0];
        document.getElementById('recepcionSubtituloCompra').textContent = `Compra #${idCompra} - Ingrese las cantidades efectivamente recibidas`;

        const tbody = document.getElementById('recepcionItemsBody');
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-slate-400 font-bold">Cargando ítems pendientes...</td></tr>';
        document.getElementById('modalRecepcion').classList.remove('hidden');

        try {
            const res = await fetch(`${API_BASE}/compras/${idCompra}`);
            const json = await res.json();
            if (json.status !== 'success' || !json.data) {
                mostrarNotificacion('Error al cargar la compra para recepción.', 'error');
                return;
            }

            const detalles = json.data.detalles || [];
            itemsRecepcionModal = detalles;

            const hoy = new Date();
            const vencDefecto = new Date(hoy.setMonth(hoy.getMonth() + 6)).toISOString().split('T')[0];

            tbody.innerHTML = detalles.map((d, idx) => {
                const pend = d.cantidad_pendiente !== undefined ? d.cantidad_pendiente : Math.max(0, d.cantidad - (d.cantidad_recibida || 0));
                const loteSugerido = `LOTE-${d.id_producto}-${Date.now().toString().slice(-4)}`;
                const disabled = pend <= 0 ? 'disabled' : '';

                return `
                    <tr class="${pend <= 0 ? 'opacity-40 bg-slate-100/40' : ''}">
                        <td>
                            <div class="font-bold text-slate-800">${d.descripcion || d.codigo || `Prod #${d.id_producto}`}</div>
                            <input type="hidden" id="rec_prod_${idx}" value="${d.id_producto}">
                        </td>
                        <td class="text-center font-bold">${d.cantidad}</td>
                        <td class="text-center font-bold text-slate-500">${d.cantidad_recibida || 0}</td>
                        <td class="text-center font-black ${pend > 0 ? 'text-amber-600' : 'text-slate-400'}">${pend}</td>
                        <td class="text-center">
                            <input type="number" min="0" max="${pend}" value="${pend > 0 ? pend : 0}" id="rec_cant_${idx}" 
                                   ${disabled} class="clay-input w-20 py-1 text-center font-black text-xs">
                        </td>
                        <td>
                            <input type="text" value="${pend > 0 ? loteSugerido : ''}" id="rec_lote_${idx}" 
                                   ${disabled} placeholder="LOTE-001" class="clay-input w-full py-1 text-xs font-bold uppercase">
                        </td>
                        <td>
                            <input type="date" value="${pend > 0 ? vencDefecto : ''}" id="rec_venc_${idx}" 
                                   ${disabled} class="clay-input w-full py-1 text-xs font-bold">
                        </td>
                    </tr>
                `;
            }).join('');
        } catch (e) {
            mostrarNotificacion('Error al cargar los datos de recepción.', 'error');
        }
    }

    function cerrarModalRecepcion() {
        document.getElementById('modalRecepcion').classList.add('hidden');
    }

    async function guardarRecepcionCompra(e) {
        e.preventDefault();
        const idCompra = document.getElementById('recepcionIdCompra').value;
        const fecha = document.getElementById('recepcionFecha').value;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const itemsToSend = [];
        for (let i = 0; i < itemsRecepcionModal.length; i++) {
            const cantInput = document.getElementById(`rec_cant_${i}`);
            const loteInput = document.getElementById(`rec_lote_${i}`);
            const vencInput = document.getElementById(`rec_venc_${i}`);
            if (!cantInput) continue;

            const cant = Number(cantInput.value || 0);
            if (cant > 0) {
                itemsToSend.push({
                    id_producto: itemsRecepcionModal[i].id_producto,
                    cantidad_recibida: cant,
                    numero_lote: loteInput ? loteInput.value.trim() : null,
                    fecha_vencimiento: vencInput ? vencInput.value : null
                });
            }
        }

        if (itemsToSend.length === 0) {
            mostrarNotificacion('Debe ingresar una cantidad mayor a 0 en al menos un producto pendiente.', 'warning');
            return;
        }

        try {
            const res = await fetch(`${API_BASE}/compras/${idCompra}/recepciones`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    fecha_recepcion: fecha,
                    items: itemsToSend
                })
            });
            const data = await res.json();
            if (data.status === 'success') {
                cerrarModalRecepcion();
                mostrarNotificacion('Recepción registrada exitosamente. El stock y los lotes han sido actualizados.', 'success');
                await Promise.all([cargarCompras(), cargarOrdenes(), cargarDeudas()]);
            } else {
                mostrarNotificacion(data.message || 'Error al procesar la recepción.', 'error');
            }
        } catch (err) {
            mostrarNotificacion('Error de conexión al guardar la recepción.', 'error');
        }
    }

    // =========================================================
    // PAGOS DE COMPRA (C08)
    // =========================================================

    async function abrirModalPagoCompra(idCompra) {
        document.getElementById('pagoIdCompra').value = idCompra;
        document.getElementById('pagoFecha').value = new Date().toISOString().split('T')[0];
        document.getElementById('pagoSubtituloCompra').textContent = `Compra #${idCompra}`;

        try {
            const res = await fetch(`${API_BASE}/compras/${idCompra}/pagos/resumen`);
            const json = await res.json();
            if (json.status === 'success' && json.data) {
                const saldo = Number(json.data.saldo_a_pagar || 0);
                const total = Number(json.data.importe_total || 0);
                document.getElementById('pagoSaldoDisponibleDisplay').textContent = `$${saldo.toFixed(2)}`;
                document.getElementById('pagoTotalFacturaDisplay').textContent = `$${total.toFixed(2)}`;
                document.getElementById('pagoMonto').value = saldo > 0 ? saldo.toFixed(2) : '';
                document.getElementById('pagoMonto').max = saldo > 0 ? saldo : 0;
            }
        } catch (e) {}

        document.getElementById('modalPago').classList.remove('hidden');
    }

    function cerrarModalPago() {
        document.getElementById('modalPago').classList.add('hidden');
    }

    async function guardarPagoCompra(e) {
        e.preventDefault();
        const idCompra = document.getElementById('pagoIdCompra').value;
        const monto = Number(document.getElementById('pagoMonto').value);
        const metodo = document.getElementById('pagoMetodo').value;
        const fecha = document.getElementById('pagoFecha').value;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

        if (!monto || monto <= 0) {
            mostrarNotificacion('El importe del pago debe ser mayor a cero.', 'warning');
            return;
        }

        try {
            const res = await fetch(`${API_BASE}/compras/${idCompra}/pagos`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    metodo_pago: metodo,
                    importe: monto,
                    fecha_pago: fecha
                })
            });
            const data = await res.json();
            if (data.status === 'success') {
                cerrarModalPago();
                mostrarNotificacion('Pago registrado exitosamente. Saldo recalculado.', 'success');
                await Promise.all([cargarCompras(), cargarDeudas()]);
            } else {
                mostrarNotificacion(data.message || 'Error al registrar el pago.', 'error');
            }
        } catch (err) {
            mostrarNotificacion('Error de conexión al registrar el pago.', 'error');
        }
    }

    // =========================================================
    // ÓRDENES DE COMPRA (PC01 a PC06)
    // =========================================================

    let itemsNuevaOrden = [];

    async function cargarOrdenes() {
        try {
            const res = await fetch(`${API_BASE}/ordenes-compra`);
            const json = await res.json();
            if (json.status === 'success') {
                ordenesCache = json.data || [];
                renderOrdenes();
                actualizarKPIsOrdenes();
            }
        } catch (e) {
            console.error('Error cargando órdenes:', e);
        }
    }

    function renderOrdenes() {
        const search = (document.getElementById('filtroSearchOrdenes')?.value || '').toLowerCase().trim();
        const estado = document.getElementById('filtroEstadoOrdenes')?.value || '';

        const filtradas = ordenesCache.filter(o => {
            const prov = (o.proveedor || '').toLowerCase();
            const num = (o.numero_orden || '').toLowerCase();
            const idO = String(o.id_orden || '');

            const matchText = !search || prov.includes(search) || num.includes(search) || idO.includes(search);
            const matchEstado = !estado || o.estado === estado;

            return matchText && matchEstado;
        });

        const tbody = document.getElementById('tablaOrdenesBody');
        if (!tbody) return;

        if (filtradas.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-6 text-slate-400 font-bold">No se registran órdenes de compra emitidas.</td></tr>`;
            return;
        }

        tbody.innerHTML = filtradas.map(o => {
            let badgeClass = 'clay-badge-info';
            if (o.estado === 'completada') badgeClass = 'clay-badge-success';
            if (o.estado === 'enviada') badgeClass = 'clay-badge-primary';
            if (o.estado === 'cancelada') badgeClass = 'clay-badge-danger';
            if (o.estado === 'pendiente' || o.estado === 'recibida_parcialmente') badgeClass = 'clay-badge-warning';

            const total = Number(o.total_estimado || 0).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
            const esPendiente = o.estado === 'pendiente';
            const admiteFacturar = esPendiente || o.estado === 'enviada';

            return `
                <tr>
                    <td class="font-black text-slate-800">${o.numero_orden || `#ORD-${o.id_orden}`}</td>
                    <td class="font-bold text-slate-800">${o.proveedor || `Proveedor #${o.id_proveedor}`}</td>
                    <td class="text-xs text-slate-500 font-bold">${o.fecha_creacion ? o.fecha_creacion.substring(0, 10) : '-'}</td>
                    <td class="text-xs text-slate-600 font-bold">${o.fecha_entrega_estimada ? o.fecha_entrega_estimada.substring(0, 10) : '<span class="text-slate-400 italic">No fijada</span>'}</td>
                    <td class="text-right font-black text-slate-800">${total}</td>
                    <td class="text-center"><span class="clay-badge ${badgeClass} uppercase">${o.estado.replace('_', ' ')}</span></td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <button onclick="abrirModalDetalleOrden(${o.id_orden})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Ver Detalle (PC03)">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            ${esPendiente ? `
                            <button onclick="enviarOrden(${o.id_orden})" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all" title="Enviar al Proveedor (PC06)">
                                <i data-lucide="send" class="w-4 h-4"></i>
                            </button>
                            <button onclick="abrirModalEditarOrden(${o.id_orden})" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-all" title="Editar Orden (PC04)">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <button onclick="cancelarOrden(${o.id_orden})" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-all" title="Cancelar Orden (PC05)">
                                <i data-lucide="x-circle" class="w-4 h-4"></i>
                            </button>
                            ` : ''}
                            ${admiteFacturar ? `
                            <button onclick="convertirOrdenACompra(${o.id_orden})" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" title="Registrar Compra desde Orden (C01)">
                                <i data-lucide="file-check" class="w-4 h-4"></i>
                            </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        if (window.lucide) window.lucide.createIcons();
    }

    function actualizarKPIsOrdenes() {
        document.getElementById('kpiTotalOrdenes').textContent = ordenesCache.length;
        document.getElementById('kpiPendientesOrdenes').textContent = ordenesCache.filter(o => o.estado === 'pendiente').length;
        document.getElementById('kpiEnviadasOrdenes').textContent = ordenesCache.filter(o => o.estado === 'enviada').length;
        document.getElementById('kpiCompletadasOrdenes').textContent = ordenesCache.filter(o => o.estado === 'completada').length;
    }

    // Al seleccionar proveedor en Nueva Orden, cargamos sus productos asociados (PV05)
    async function alCambiarProveedorOrden() {
        const idProv = document.getElementById('ordenProveedor').value;
        const selProd = document.getElementById('ordenItemProducto');
        const lblInfo = document.getElementById('labelProductosOrdenInfo');
        if (!selProd) return;

        if (!idProv) {
            selProd.innerHTML = '<option value="">Seleccione primero un proveedor...</option>';
            if (lblInfo) lblInfo.textContent = '';
            return;
        }

        try {
            const res = await fetch(`${API_BASE}/proveedores/${idProv}/productos`);
            const json = await res.json();
            let asociados = [];
            if (json.status === 'success') {
                asociados = json.data || [];
            }

            const idAsociados = new Set(asociados.map(a => a.id_producto));

            let html = '<option value="">Seleccione producto...</option>';
            if (asociados.length > 0) {
                html += '<optgroup label="Productos del Proveedor (Precio Acordado)">';
                asociados.forEach(a => {
                    const precio = a.precio_acordado !== null ? a.precio_acordado : (a.precio_unitario || 0);
                    html += `<option value="${a.id_producto}" data-precio="${precio}">★ ${a.codigo} - ${a.nombre} ($${Number(precio).toFixed(2)})</option>`;
                });
                html += '</optgroup>';
            }

            const otros = productosCache.filter(p => !idAsociados.has(p.id_producto));
            if (otros.length > 0) {
                html += '<optgroup label="Otros Productos del Catálogo">';
                otros.forEach(p => {
                    html += `<option value="${p.id_producto}" data-precio="${p.precio_unitario || 0}">${p.codigo} - ${p.nombre}</option>`;
                });
                html += '</optgroup>';
            }

            selProd.innerHTML = html;
            if (lblInfo) {
                lblInfo.textContent = asociados.length > 0 ? `${asociados.length} producto(s) habitual(es) disponibles.` : 'Catálogo disponible.';
            }
        } catch (e) {
            console.error('Error productos proveedor en orden:', e);
        }
    }

    function alCambiarProductoOrden() {
        const sel = document.getElementById('ordenItemProducto');
        const opt = sel.options[sel.selectedIndex];
        if (opt && opt.dataset.precio) {
            document.getElementById('ordenItemPrecio').value = Number(opt.dataset.precio).toFixed(2);
        }
    }

    function abrirModalNuevaOrden() {
        document.getElementById('ordenModoEdicion').value = 'false';
        document.getElementById('ordenIdEditar').value = '';
        document.getElementById('modalOrdenTitulo').textContent = 'Emitir Orden de Compra (PC01)';
        document.getElementById('modalOrdenSubtitulo').textContent = 'Pedido formal de reposición hacia un proveedor';
        document.getElementById('btnGuardarOrdenSubmit').textContent = 'Generar Orden';
        document.getElementById('ordenProveedor').disabled = false;
        document.getElementById('ordenProveedor').value = '';
        document.getElementById('ordenFechaEmision').value = new Date().toISOString().split('T')[0];
        document.getElementById('ordenFechaEntrega').value = new Date(Date.now() + 7*24*60*60*1000).toISOString().split('T')[0];

        itemsNuevaOrden = [];
        renderItemsNuevaOrden();
        alCambiarProveedorOrden();

        document.getElementById('modalNuevaOrden').classList.remove('hidden');
    }

    async function abrirModalEditarOrden(idOrden) {
        try {
            const res = await fetch(`${API_BASE}/ordenes-compra/${idOrden}`);
            const json = await res.json();
            if (json.status !== 'success' || !json.data) {
                mostrarNotificacion('No se pudo cargar la orden para editar.', 'error');
                return;
            }

            const o = json.data.orden;
            if (o.estado !== 'pendiente') {
                mostrarNotificacion('Solo se pueden editar órdenes en estado pendiente. (PC04)', 'warning');
                return;
            }

            document.getElementById('ordenModoEdicion').value = 'true';
            document.getElementById('ordenIdEditar').value = idOrden;
            document.getElementById('modalOrdenTitulo').textContent = `Editar Orden de Compra #${o.numero_orden || idOrden} (PC04)`;
            document.getElementById('modalOrdenSubtitulo').textContent = 'Ajuste de productos, cantidades y entrega antes de enviar al proveedor';
            document.getElementById('btnGuardarOrdenSubmit').textContent = 'Guardar Cambios';

            document.getElementById('ordenProveedor').value = o.id_proveedor;
            document.getElementById('ordenProveedor').disabled = true;
            await alCambiarProveedorOrden();

            document.getElementById('ordenFechaEmision').value = o.fecha_creacion ? o.fecha_creacion.substring(0, 10) : new Date().toISOString().split('T')[0];
            document.getElementById('ordenFechaEntrega').value = o.fecha_entrega_estimada ? o.fecha_entrega_estimada.substring(0, 10) : '';

            itemsNuevaOrden = (json.data.detalles || []).map(d => ({
                id_producto: d.id_producto,
                nombre: d.descripcion || d.codigo || `Prod #${d.id_producto}`,
                cantidad_solicitada: d.cantidad_solicitada,
                precio_estimado: d.precio_estimado,
                subtotal: d.subtotal
            }));

            renderItemsNuevaOrden();
            document.getElementById('modalNuevaOrden').classList.remove('hidden');
        } catch (e) {
            mostrarNotificacion('Error al cargar la orden.', 'error');
        }
    }

    function cerrarModalNuevaOrden() {
        document.getElementById('modalNuevaOrden').classList.add('hidden');
    }

    function agregarItemOrden() {
        const sel = document.getElementById('ordenItemProducto');
        const idProd = Number(sel.value);
        const cant = Number(document.getElementById('ordenItemCantidad').value);
        const precio = Number(document.getElementById('ordenItemPrecio').value);

        if (!idProd || cant <= 0 || precio < 0) {
            mostrarNotificacion('Seleccione producto, cantidad mayor a 0 y precio unitario estimado.', 'warning');
            return;
        }

        const opt = sel.options[sel.selectedIndex];
        const prod = productosCache.find(p => p.id_producto === idProd);
        const nombre = prod ? prod.nombre : (opt ? opt.text : `Prod #${idProd}`);

        const idx = itemsNuevaOrden.findIndex(i => i.id_producto === idProd);
        if (idx >= 0) {
            itemsNuevaOrden[idx].cantidad_solicitada += cant;
            itemsNuevaOrden[idx].precio_estimado = precio;
            itemsNuevaOrden[idx].subtotal = itemsNuevaOrden[idx].cantidad_solicitada * precio;
        } else {
            itemsNuevaOrden.push({
                id_producto: idProd,
                nombre: nombre,
                cantidad_solicitada: cant,
                precio_estimado: precio,
                subtotal: Math.round(cant * precio * 100) / 100
            });
        }

        renderItemsNuevaOrden();
        document.getElementById('ordenItemCantidad').value = '1';
    }

    function quitarItemOrden(idx) {
        itemsNuevaOrden.splice(idx, 1);
        renderItemsNuevaOrden();
    }

    function renderItemsNuevaOrden() {
        const tbody = document.getElementById('detalleOrdenItems');
        let total = 0;
        if (itemsNuevaOrden.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-slate-400 font-bold text-xs">Sin productos en la orden.</td></tr>';
            document.getElementById('ordenTotalDisplay').textContent = '$0.00';
            return;
        }

        tbody.innerHTML = itemsNuevaOrden.map((it, idx) => {
            total += it.subtotal;
            return `
                <tr>
                    <td class="font-bold text-slate-800">${it.nombre}</td>
                    <td class="text-center font-black">${it.cantidad_solicitada}</td>
                    <td class="text-right font-black">$${it.precio_estimado.toFixed(2)}</td>
                    <td class="text-right font-black text-indigo-600">$${it.subtotal.toFixed(2)}</td>
                    <td class="text-center">
                        <button type="button" onclick="quitarItemOrden(${idx})" class="p-1 text-rose-500 hover:text-rose-700 font-black text-sm">×</button>
                    </td>
                </tr>
            `;
        }).join('');
        document.getElementById('ordenTotalDisplay').textContent = `$${total.toFixed(2)}`;
    }

    async function guardarNuevaOrden(e) {
        e.preventDefault();
        const idProv = document.getElementById('ordenProveedor').value;
        if (!idProv || itemsNuevaOrden.length === 0) {
            mostrarNotificacion('Seleccione un proveedor activo y agregue al menos un producto.', 'warning');
            return;
        }

        const esEdicion = document.getElementById('ordenModoEdicion').value === 'true';
        const idEditar = document.getElementById('ordenIdEditar').value;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const payload = {
            id_proveedor: Number(idProv),
            fecha_creacion: document.getElementById('ordenFechaEmision').value,
            fecha_entrega_estimada: document.getElementById('ordenFechaEntrega').value || null,
            items: itemsNuevaOrden.map(i => ({
                id_producto: i.id_producto,
                cantidad_solicitada: i.cantidad_solicitada,
                precio_estimado: i.precio_estimado
            }))
        };

        const url = esEdicion ? `${API_BASE}/ordenes-compra/${idEditar}` : `${API_BASE}/ordenes-compra`;
        const method = esEdicion ? 'PUT' : 'POST';

        try {
            const res = await fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                cerrarModalNuevaOrden();
                mostrarNotificacion(data.message || (esEdicion ? 'Orden modificada correctamente.' : 'Orden de compra emitida exitosamente.'), 'success');
                await cargarOrdenes();
            } else {
                mostrarNotificacion(data.message || 'Error al guardar la orden.', 'error');
            }
        } catch (err) {
            mostrarNotificacion('Error de conexión al emitir la orden.', 'error');
        }
    }

    async function enviarOrden(id) {
        if (!confirm('¿Desea marcar esta orden de compra como enviada al proveedor? Una vez enviada ya no podrá ser modificada. (PC04/PC06)')) return;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            const res = await fetch(`${API_BASE}/ordenes-compra/${id}/enviar`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ fecha_envio: new Date().toISOString().split('T')[0] })
            });
            const data = await res.json();
            if (data.status === 'success') {
                mostrarNotificacion('Orden marcada como enviada al proveedor.', 'success');
                await cargarOrdenes();
            } else {
                mostrarNotificacion(data.message || 'Error al enviar la orden.', 'error');
            }
        } catch (e) {
            mostrarNotificacion('Error de conexión al enviar la orden.', 'error');
        }
    }

    async function cancelarOrden(id) {
        if (!confirm('¿Confirma la cancelación de esta orden de compra? (PC05)')) return;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            const res = await fetch(`${API_BASE}/ordenes-compra/${id}/cancelar`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            });
            const data = await res.json();
            if (data.status === 'success') {
                mostrarNotificacion('Orden de compra cancelada.', 'success');
                await cargarOrdenes();
            } else {
                mostrarNotificacion(data.message || 'Error al cancelar la orden.', 'error');
            }
        } catch (e) {
            mostrarNotificacion('Error de conexión al cancelar la orden.', 'error');
        }
    }

    // Convertir una orden emitida en una Compra (flujo integral de abastecimiento)
    async function convertirOrdenACompra(idOrden) {
        try {
            const res = await fetch(`${API_BASE}/ordenes-compra/${idOrden}`);
            const json = await res.json();
            if (json.status !== 'success' || !json.data) {
                mostrarNotificacion('No se pudo cargar la orden.', 'error');
                return;
            }

            const o = json.data.orden;
            abrirModalNuevaCompra();
            document.getElementById('compraIdOrden').value = idOrden;
            document.getElementById('modalCompraTitulo').textContent = `Registrar Compra desde Orden #${o.numero_orden || idOrden}`;
            document.getElementById('compraComprobante').value = `ORD-${o.numero_orden || idOrden}`;
            document.getElementById('compraProveedor').value = o.id_proveedor;
            await alCambiarProveedorCompra();

            itemsNuevaCompra = (json.data.detalles || []).map(d => ({
                id_producto: d.id_producto,
                nombre: d.descripcion || d.codigo || `Prod #${d.id_producto}`,
                cantidad: d.cantidad_solicitada,
                precio_unitario: d.precio_estimado,
                subtotal: d.subtotal
            }));

            renderItemsNuevaCompra();
            cambiarPestanaCompras('compras');
            mostrarNotificacion('Datos de la orden cargados en la nueva compra.', 'info');
        } catch (e) {
            mostrarNotificacion('Error al vincular orden a compra.', 'error');
        }
    }

    async function abrirModalDetalleOrden(id) {
        document.getElementById('detalleOrdenSubtitulo').textContent = `Orden #${id}`;
        document.getElementById('modalDetalleOrden').classList.remove('hidden');
        const tbody = document.getElementById('detalleOrdenTablaBody');
        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-slate-400">Cargando ítems...</td></tr>';
        try {
            const res = await fetch(`${API_BASE}/ordenes-compra/${id}`);
            const json = await res.json();
            if (json.status === 'success' && json.data) {
                tbody.innerHTML = (json.data.detalles || []).map(d => `
                    <tr>
                        <td class="font-bold text-slate-800">${d.descripcion || d.codigo || `Prod #${d.id_producto}`}</td>
                        <td class="text-center font-black">${d.cantidad_solicitada}</td>
                        <td class="text-right font-black">$${Number(d.precio_estimado).toFixed(2)}</td>
                        <td class="text-right font-black text-indigo-600">$${Number(d.subtotal).toFixed(2)}</td>
                    </tr>
                `).join('');
            }
        } catch (e) {}
    }

    function cerrarModalDetalleOrden() {
        document.getElementById('modalDetalleOrden').classList.add('hidden');
    }

    // =========================================================
    // SUGERENCIAS DE REPOSICIÓN (PC07)
    // =========================================================

    async function cargarSugerencias() {
        try {
            const res = await fetch(`${API_BASE}/sugerencias-reposicion`);
            const json = await res.json();
            if (json.exito && json.datos) {
                sugerenciasCache = json.datos || [];
                renderSugerencias();
            }
        } catch (e) {
            console.error('Error cargando sugerencias:', e);
        }
    }

    function renderSugerencias() {
        const tbody = document.getElementById('tablaSugerenciasBody');
        const badgeCount = document.getElementById('badgeSugerenciasCount');
        if (!tbody) return;

        const pendientes = sugerenciasCache.filter(s => s.estado === 'pendiente');
        if (badgeCount) badgeCount.textContent = `${pendientes.length} sugerencias pendientes`;

        // KPIs
        const totUnidades = pendientes.reduce((acc, s) => acc + (s.cantidad_sugerida || 0), 0);
        const totCosto = pendientes.reduce((acc, s) => acc + (s.costo_total || 0), 0);
        document.getElementById('kpiSugerenciasPendientes').textContent = pendientes.length;
        document.getElementById('kpiSugerenciasUnidades').textContent = totUnidades;
        document.getElementById('kpiSugerenciasCosto').textContent = `$${totCosto.toLocaleString('es-AR', { minimumFractionDigits: 2 })}`;

        if (sugerenciasCache.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-6 text-slate-400 font-bold">No hay sugerencias de reposición pendientes. Haga clic en "Escanear y Generar Sugerencias".</td></tr>';
            return;
        }

        tbody.innerHTML = sugerenciasCache.map(s => {
            const esPendiente = s.estado === 'pendiente';
            const estadoBadge = esPendiente ? 'clay-badge-warning' : (s.estado === 'procesada' ? 'clay-badge-success' : 'clay-badge-danger');
            
            return `
                <tr>
                    <td>
                        <div class="font-black text-slate-800">${s.producto?.nombre || `Prod #${s.id_producto}`}</div>
                        <div class="text-[11px] text-slate-400 font-bold">${s.producto?.codigo || ''}</div>
                    </td>
                    <td class="text-center font-bold">
                        <span class="${s.stock_actual <= s.cantidad_minima ? 'text-rose-600 font-black' : 'text-slate-700'}">${s.stock_actual}</span>
                        <span class="text-slate-400 text-xs">/ ${s.cantidad_minima}</span>
                    </td>
                    <td class="text-center font-black text-indigo-700 text-sm">${s.cantidad_sugerida}</td>
                    <td class="font-bold text-slate-700">${s.proveedor?.razon_social || `Proveedor #${s.id_proveedor}`}</td>
                    <td class="text-right font-black">$${Number(s.precio_unitario || 0).toFixed(2)}</td>
                    <td class="text-right font-black text-emerald-700">$${Number(s.costo_total || 0).toFixed(2)}</td>
                    <td class="text-center">
                        <span class="text-[11px] font-bold text-slate-600 capitalize">${(s.motivo_generacion || 'stock_minimo').replace('_', ' ')}</span>
                    </td>
                    <td class="text-center">
                        ${esPendiente ? `
                        <div class="flex items-center justify-center gap-1.5">
                            <button onclick="generarOrdenDesdeSugerencia(${s.id_sugerencia})" class="clay-btn-primary px-2.5 py-1 text-[11px] font-black shadow-sm" title="Generar Orden de Compra (PC07)">
                                <i data-lucide="file-plus" class="w-3.5 h-3.5 inline mr-1"></i>Emitir Orden
                            </button>
                            <button onclick="rechazarSugerencia(${s.id_sugerencia})" class="p-1 text-slate-400 hover:text-rose-600 rounded" title="Rechazar">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                        ` : `
                        <span class="clay-badge ${estadoBadge} uppercase text-[10px]">${s.estado}</span>
                        `}
                    </td>
                </tr>
            `;
        }).join('');

        if (window.lucide) window.lucide.createIcons();
    }

    async function generarSugerenciasAhora() {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        mostrarNotificacion('Analizando catálogo y velocidad de ventas...', 'info');
        try {
            const res = await fetch(`${API_BASE}/sugerencias-reposicion/generar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            });
            const data = await res.json();
            if (data.exito) {
                mostrarNotificacion(data.mensaje || 'Sugerencias generadas con éxito.', 'success');
                await cargarSugerencias();
            } else {
                mostrarNotificacion(data.error || 'No se generaron nuevas sugerencias.', 'warning');
            }
        } catch (e) {
            mostrarNotificacion('Error al generar sugerencias.', 'error');
        }
    }

    async function generarOrdenDesdeSugerencia(idSugerencia) {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            const res = await fetch(`${API_BASE}/sugerencias-reposicion/${idSugerencia}/generar-orden`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            });
            const data = await res.json();
            if (data.exito) {
                mostrarNotificacion('Orden de compra generada exitosamente desde la sugerencia (PC07).', 'success');
                await Promise.all([cargarSugerencias(), cargarOrdenes()]);
                cambiarPestanaCompras('ordenes');
            } else {
                mostrarNotificacion(data.error || 'Error al generar la orden.', 'error');
            }
        } catch (e) {
            mostrarNotificacion('Error de conexión.', 'error');
        }
    }

    async function rechazarSugerencia(idSugerencia) {
        const motivo = prompt('Motivo del rechazo de la sugerencia (opcional):');
        if (motivo === null) return;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            const res = await fetch(`${API_BASE}/sugerencias-reposicion/${idSugerencia}/rechazar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ motivo: motivo || null })
            });
            const data = await res.json();
            if (data.exito) {
                mostrarNotificacion('Sugerencia rechazada.', 'info');
                await cargarSugerencias();
            } else {
                mostrarNotificacion(data.error || 'Error al rechazar sugerencia.', 'error');
            }
        } catch (e) {
            mostrarNotificacion('Error de conexión.', 'error');
        }
    }

    // =========================================================
    // DEUDAS CON PROVEEDORES (C09 - Issue #42)
    // =========================================================

    async function cargarDeudas() {
        const idProv = document.getElementById('filtroProveedorDeudas')?.value || '';
        const desde = document.getElementById('filtroDesdeDeudas')?.value || '';
        const hasta = document.getElementById('filtroHastaDeudas')?.value || '';

        let url = `${API_BASE}/compras/deudas?`;
        if (idProv) url += `id_proveedor=${encodeURIComponent(idProv)}&`;
        if (desde) url += `desde=${encodeURIComponent(desde)}&`;
        if (hasta) url += `hasta=${encodeURIComponent(hasta)}&`;

        try {
            const res = await fetch(url);
            const json = await res.json();
            if (json.status === 'success' && json.data) {
                deudasCache = json.data;
                renderDeudas();
            }
        } catch (e) {
            console.error('Error cargando deudas:', e);
        }
    }

    function renderDeudas() {
        const data = deudasCache;
        if (!data) return;

        document.getElementById('kpiTotalDeuda').textContent = Number(data.saldo_adeudado_total || 0).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
        document.getElementById('kpiProveedoresDeuda').textContent = data.proveedores_con_deuda || 0;
        document.getElementById('kpiComprasDeuda').textContent = data.compras_con_deuda || 0;

        const tbody = document.getElementById('tablaDeudasBody');
        if (!tbody) return;

        const compras = data.compras_pendientes || [];
        if (compras.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-6 text-slate-400 font-bold">¡Excelente! No hay deudas pendientes con proveedores según los filtros aplicados.</td></tr>';
            return;
        }

        tbody.innerHTML = compras.map(c => {
            let vencBadge = 'clay-badge-info';
            let vencTexto = c.fecha_vencimiento ? c.fecha_vencimiento.substring(0, 10) : 'Sin vencimiento';

            if (c.esta_vencida) {
                vencBadge = 'clay-badge-danger';
                vencTexto += ` (Vencida)`;
            } else if (c.dias_para_vencer !== null && c.dias_para_vencer <= 5) {
                vencBadge = 'clay-badge-warning';
                vencTexto += ` (Vence en ${c.dias_para_vencer} d)`;
            }

            return `
                <tr>
                    <td class="font-bold text-slate-800">${c.proveedor || `Proveedor #${c.id_proveedor}`}</td>
                    <td>
                        <span class="font-black text-slate-800">${c.numero_compra}</span>
                        ${c.numero_comprobante ? `<div class="text-[11px] text-slate-400 font-bold">${c.numero_comprobante}</div>` : ''}
                    </td>
                    <td class="text-xs text-slate-600 font-bold">${c.fecha_compra ? c.fecha_compra.substring(0, 10) : '-'}</td>
                    <td><span class="clay-badge ${vencBadge} text-[11px] font-extrabold">${vencTexto}</span></td>
                    <td class="text-right font-bold text-slate-700">${Number(c.importe_total).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</td>
                    <td class="text-right font-bold text-emerald-600">${Number(c.total_pagado).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</td>
                    <td class="text-right font-black text-rose-600 text-sm">${Number(c.saldo_a_pagar).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</td>
                    <td class="text-center">
                        <button onclick="abrirModalPagoCompra(${c.id_compra})" class="clay-btn-primary px-3 py-1 text-xs font-black shadow-sm" title="Abonar Deuda (C08)">
                            <i data-lucide="credit-card" class="w-3.5 h-3.5 inline mr-1"></i>Pagar
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        if (window.lucide) window.lucide.createIcons();
    }
</script>
@endsection
