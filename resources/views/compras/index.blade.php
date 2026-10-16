@extends('layouts.app')

@section('title', 'Compras y Órdenes - Distribuidora Pigüé')
@section('page_title', 'Compras y Órdenes de Compra')
@section('page_subtitle', 'Gestión de Compras a Proveedores, Recepción de Mercadería, Órdenes de Reposición y Control de Deudas')

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
                <span>Sugerencias de Reposición</span>
            </button>
            <button onclick="cambiarPestanaCompras('deudas')" id="tabBtn-deudas" 
                    class="tab-btn-comp px-4 py-2.5 rounded-xl font-black text-xs flex items-center gap-2 transition-all cursor-pointer text-slate-600 hover:text-slate-900">
                <i data-lucide="wallet" class="w-4 h-4 text-emerald-600"></i>
                <span>Deudas con Proveedores</span>
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
                    <span>Nueva Compra</span>
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
                            <th class="text-center">Estado</th>
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
                    <span>Emitir Orden de Compra</span>
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
                            <th class="text-center">Estado</th>
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
    <!-- PESTAÑA 3: SUGERENCIAS DE REPOSICIÓN              -->
    <!-- ========================================================= -->
    <div id="pestana-sugerencias" class="pestana-comp-content hidden space-y-4">
        
        <!-- HEADER Y ACCIÓN ESCANEAR -->
        <div class="clay-card p-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-violet-50 text-violet-600 flex items-center justify-center border border-violet-100 shadow-inner">
                    <i data-lucide="sparkles" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-800">Motor de Sugerencias de Reposición</h3>
                    <p class="text-xs text-slate-500 font-bold">Detección de stock crítico/mínimo, cálculo de cantidad óptima y asignación del mejor proveedor</p>
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
                            <th>Proveedor Asignado</th>
                            <th class="text-right">Precio Unitario</th>
                            <th class="text-right">Costo Estimado</th>
                            <th class="text-center">Motivo</th>
                            <th class="text-center">Acciones</th>
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
                    <h3 class="text-sm font-black text-slate-800">Estado de Cuenta y Vencimientos Próximos</h3>
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
                            <th class="text-right">Pagado</th>
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
<!-- MODAL: REGISTRAR PAGO A COMPRA       -->
<!-- ========================================== -->
<div id="modalPago" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalPago()">
    <div class="clay-modal-content max-w-md p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-800">Registrar Pago a Proveedor</h3>
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
                <div class="flex items-center gap-2 mt-1.5">
                    <span class="text-[10px] font-extrabold text-slate-400 uppercase">Monto Rápido:</span>
                    <button type="button" onclick="setMontoPagoRapido(1)" class="px-2 py-0.5 text-[10px] font-black bg-emerald-100 text-emerald-800 hover:bg-emerald-200 rounded-md transition-all">
                        Pagar Total (100%)
                    </button>
                    <button type="button" onclick="setMontoPagoRapido(0.5)" class="px-2 py-0.5 text-[10px] font-black bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-md transition-all">
                        Pagar 50%
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Método de Pago</label>
                    <select id="pagoMetodo" required onchange="alCambiarMetodoPago()" class="clay-input w-full text-xs font-bold">
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

            <!-- DETALLE CONDICIONAL PARA CHEQUES / E-CHEQ -->
            <div id="pagoCamposCheque" class="hidden p-3 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-2">
                <div class="flex items-center gap-1.5 text-xs font-extrabold text-slate-700">
                    <i data-lucide="file-check" class="w-3.5 h-3.5 text-blue-600"></i>
                    <span>Datos del Cheque / Comprobante Diferido</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase mb-0.5">N° de Cheque / Comprobante</label>
                        <input type="text" id="pagoNroCheque" placeholder="00012345" class="clay-input w-full text-xs font-bold">
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase mb-0.5">Fecha Cobro Diferido</label>
                        <input type="date" id="pagoFechaDiferida" class="clay-input w-full text-xs font-bold">
                    </div>
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
                <h3 class="text-base font-black text-slate-800">Registrar Recepción de Mercadería</h3>
                <p id="recepcionSubtituloCompra" class="text-xs text-slate-500 font-bold">Ingreso directo de mercadería a stock</p>
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
                <div class="p-3 bg-slate-100/60 font-black text-xs text-slate-700 flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <span>Productos de la Compra a Recepcionar</span>
                        <span class="text-[11px] text-slate-500 font-normal ml-1">Complete la cantidad recibida para cada ítem</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="recepcionarTodoModal()" class="px-2.5 py-1 text-[11px] font-black bg-emerald-100 text-emerald-800 hover:bg-emerald-200 rounded-lg transition-all flex items-center gap-1">
                            <i data-lucide="check-check" class="w-3.5 h-3.5"></i>
                            <span>Recibir Todo</span>
                        </button>
                        <button type="button" onclick="limpiarCantidadesRecepcion()" class="px-2.5 py-1 text-[11px] font-black bg-slate-200 text-slate-700 hover:bg-slate-300 rounded-lg transition-all">
                            Poner en Cero
                        </button>
                    </div>
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
                            </tr>
                        </thead>
                        <tbody id="recepcionItemsBody">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-slate-400 font-bold">Cargando productos de la compra...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PREVIEW DINÁMICO DEL ESTADO TRAS LA RECEPCIÓN -->
            <div id="recepcionPreviewEstado" class="p-2.5 rounded-xl text-xs font-bold transition-all bg-blue-50 text-blue-700 border border-blue-100 flex items-center gap-2">
                <i data-lucide="info" class="w-4 h-4 shrink-0"></i>
                <span id="recepcionPreviewTexto">Ajuste las cantidades a recepcionar para ver el impacto en el estado.</span>
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
                <h3 id="modalCompraTitulo" class="text-base font-black text-slate-800">Registrar Compra de Mercadería</h3>
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
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Proveedor</label>
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
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Fecha de Vencimiento de Pago</label>
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
                        <input type="number" id="compraItemCantidad" min="1" value="1" 
                               onkeydown="if(event.key === 'Enter'){ event.preventDefault(); agregarItemCompra(); }" 
                               class="clay-input w-full text-xs font-black text-center">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase mb-1">Precio Unitario ($)</label>
                        <input type="number" step="0.01" min="0" id="compraItemPrecio" placeholder="0.00" 
                               onkeydown="if(event.key === 'Enter'){ event.preventDefault(); agregarItemCompra(); }" 
                               class="clay-input w-full text-xs font-black text-right">
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
                <h3 class="text-base font-black text-slate-800">Detalle de Compra</h3>
                <p id="detalleCompraSubtitulo" class="text-xs font-bold text-slate-500">Compra</p>
            </div>
            <button onclick="cerrarModalDetalleCompra()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- LÍNEA DE TIEMPO / STEPPER DE ESTADOS DE COMPRA (C07) -->
        <div class="p-3 bg-slate-50/80 rounded-2xl border border-slate-200/60">
            <div class="flex items-center justify-between max-w-md mx-auto text-xs font-black">
                <div id="stepCompraPendiente" class="flex flex-col items-center gap-1">
                    <div id="circleCompraPendiente" class="w-7 h-7 rounded-full flex items-center justify-center bg-blue-600 text-white shadow-sm text-xs">1</div>
                    <span id="labelCompraPendiente" class="text-[11px] text-slate-700">Pendiente</span>
                </div>
                <div id="lineCompraStep1" class="flex-1 h-0.5 bg-slate-200 mx-2 transition-all"></div>
                <div id="stepCompraParcial" class="flex flex-col items-center gap-1">
                    <div id="circleCompraParcial" class="w-7 h-7 rounded-full flex items-center justify-center bg-slate-200 text-slate-500 text-xs">2</div>
                    <span id="labelCompraParcial" class="text-[11px] text-slate-400">Recepción Parcial</span>
                </div>
                <div id="lineCompraStep2" class="flex-1 h-0.5 bg-slate-200 mx-2 transition-all"></div>
                <div id="stepCompraCompletada" class="flex flex-col items-center gap-1">
                    <div id="circleCompraCompletada" class="w-7 h-7 rounded-full flex items-center justify-center bg-slate-200 text-slate-500 text-xs">3</div>
                    <span id="labelCompraCompletada" class="text-[11px] text-slate-400">Completada</span>
                </div>
            </div>
            <div id="bannerCompraCancelada" class="hidden mt-2 p-1.5 bg-rose-50 text-rose-700 rounded-lg text-center text-xs font-black border border-rose-200">
                Esta compra fue CANCELADA.
            </div>
        </div>

        <!-- BARRAS DE PROGRESO DE RECEPCIÓN Y PAGOS -->
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
                    <span class="text-slate-500">Estado de Pagos:</span>
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
                <span class="text-[11px] font-black uppercase text-slate-500 block mb-2">Pagos Realizados</span>
                <div id="detallePagosHistorial" class="text-xs text-slate-600 space-y-1 max-h-32 overflow-y-auto">
                    <span class="text-slate-400 font-bold">Sin pagos registrados.</span>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-slate-100 flex-wrap gap-2">
            <button type="button" onclick="imprimirFichaCompra()" class="clay-btn-secondary px-3 py-2 text-xs font-bold flex items-center gap-1.5" title="Imprimir Comprobante">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Imprimir Ficha</span>
            </button>
            <div class="flex items-center gap-2">
                <button type="button" id="btnDetalleAccionRecepcionar" onclick="irDesdeDetalleARecepcion()" class="clay-btn-secondary px-3 py-2 text-xs font-bold text-emerald-700 flex items-center gap-1.5 hidden">
                    <i data-lucide="package-check" class="w-4 h-4"></i>
                    <span>Recepcionar</span>
                </button>
                <button type="button" id="btnDetalleAccionPagar" onclick="irDesdeDetalleAPago()" class="clay-btn-secondary px-3 py-2 text-xs font-bold text-violet-700 flex items-center gap-1.5 hidden">
                    <i data-lucide="credit-card" class="w-4 h-4"></i>
                    <span>Pagar</span>
                </button>
                <button type="button" onclick="cerrarModalDetalleCompra()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cerrar</button>
            </div>
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
                <h3 id="modalOrdenTitulo" class="text-base font-black text-slate-800">Emitir Orden de Compra</h3>
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
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Proveedor</label>
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
                <h3 class="text-base font-black text-slate-800">Detalle de Orden de Compra</h3>
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

    // Cache de asociaciones proveedor-producto para autocompletar precios acordados
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
                            <button onclick="abrirModalDetalleCompra(${c.id_compra})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Ver Detalle">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button onclick="imprimirFichaCompra(${c.id_compra})" class="p-1.5 text-slate-600 hover:bg-slate-100 rounded-lg transition-all" title="Imprimir Comprobante (Factura)">
                                <i data-lucide="printer" class="w-4 h-4"></i>
                            </button>
                            ${admiteRecepcion ? `
                            <button onclick="abrirModalRecepcionCompra(${c.id_compra})" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" title="Registrar Recepción">
                                <i data-lucide="package-check" class="w-4 h-4"></i>
                            </button>
                            ` : ''}
                            ${admitePago ? `
                            <button onclick="abrirModalPagoCompra(${c.id_compra})" class="p-1.5 text-violet-600 hover:bg-violet-50 rounded-lg transition-all" title="Registrar Pago">
                                <i data-lucide="credit-card" class="w-4 h-4"></i>
                            </button>
                            ` : ''}
                            ${esPendiente ? `
                            <button onclick="abrirModalEditarCompra(${c.id_compra})" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-all" title="Modificar Compra">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <button onclick="cancelarCompra(${c.id_compra})" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-all" title="Cancelar Compra">
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

    // Al seleccionar proveedor en Nueva Compra, cargamos sus productos asociados
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
                html += '<optgroup label="Productos Asociados al Proveedor">';
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

            // Sugerir fecha de vencimiento según los días de plazo del proveedor
            const provObj = proveedoresCache.find(p => String(p.id_proveedor) === String(idProv));
            if (provObj && provObj.plazo_entrega_dias) {
                const fechaCompraInput = document.getElementById('compraFecha');
                const fechaVencInput = document.getElementById('compraVencimiento');
                if (fechaVencInput && !fechaVencInput.value && fechaCompraInput && fechaCompraInput.value) {
                    const baseDate = new Date(fechaCompraInput.value + 'T00:00:00');
                    baseDate.setDate(baseDate.getDate() + Number(provObj.plazo_entrega_dias));
                    fechaVencInput.value = baseDate.toISOString().split('T')[0];
                }
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
        document.getElementById('modalCompraTitulo').textContent = 'Registrar Compra de Mercadería';
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
                mostrarNotificacion('Solo se pueden modificar compras en estado pendiente.', 'warning');
                return;
            }

            document.getElementById('compraModoEdicion').value = 'true';
            document.getElementById('compraIdEditar').value = idCompra;
            document.getElementById('compraIdOrden').value = c.id_orden || '';
            document.getElementById('modalCompraTitulo').textContent = `Modificar Compra Pendiente #${c.numero_compra || idCompra}`;
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
                nombre: d.nombre || d.descripcion || d.codigo || `Prod #${d.id_producto}`,
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
        if (!confirm('¿Confirma la cancelación de esta compra pendiente? Esta acción conservará el historial.')) return;
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

    let idCompraDetalleActual = null;
    let compraDetalleActualObjeto = null;
    let compraDetalleDataCompleta = null;

    async function abrirModalDetalleCompra(id) {
        idCompraDetalleActual = id;
        document.getElementById('detalleCompraSubtitulo').textContent = `Compra #${id}`;
        document.getElementById('modalDetalleCompra').classList.remove('hidden');
        const tbody = document.getElementById('detalleCompraTablaBody');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-slate-400">Cargando datos de la compra...</td></tr>';

        try {
            const res = await fetch(`${API_BASE}/compras/${id}`);
            const json = await res.json();
            if (json.status === 'success' && json.data) {
                const c = json.data.compra;
                compraDetalleActualObjeto = c;
                compraDetalleDataCompleta = json.data;
                const totComp = json.data.total_comprado || 1;
                const totRec = json.data.total_recibido || 0;
                const pctRec = Math.min(100, Math.round((totRec / totComp) * 100));

                const pagos = json.data.pagos || {};
                const impTot = Number(c.importe_total || 0);
                const totPag = Number(pagos.total_pagado || 0);
                const pctPag = impTot > 0 ? Math.min(100, Math.round((totPag / impTot) * 100)) : 100;

                // Actualizar stepper de estados (C07)
                const circlePend = document.getElementById('circleCompraPendiente');
                const labelPend = document.getElementById('labelCompraPendiente');
                const line1 = document.getElementById('lineCompraStep1');
                const circleParc = document.getElementById('circleCompraParcial');
                const labelParc = document.getElementById('labelCompraParcial');
                const line2 = document.getElementById('lineCompraStep2');
                const circleComp = document.getElementById('circleCompraCompletada');
                const labelComp = document.getElementById('labelCompraCompletada');
                const bannerCanc = document.getElementById('bannerCompraCancelada');

                if (bannerCanc) bannerCanc.classList.add('hidden');
                
                if (circlePend) circlePend.className = 'w-7 h-7 rounded-full flex items-center justify-center bg-blue-600 text-white shadow-sm text-xs font-black';
                if (labelPend) labelPend.className = 'text-[11px] font-bold text-slate-700';
                if (line1) line1.className = 'flex-1 h-0.5 bg-slate-200 mx-2 transition-all';
                if (circleParc) circleParc.className = 'w-7 h-7 rounded-full flex items-center justify-center bg-slate-200 text-slate-500 text-xs font-black';
                if (labelParc) labelParc.className = 'text-[11px] font-bold text-slate-400';
                if (line2) line2.className = 'flex-1 h-0.5 bg-slate-200 mx-2 transition-all';
                if (circleComp) circleComp.className = 'w-7 h-7 rounded-full flex items-center justify-center bg-slate-200 text-slate-500 text-xs font-black';
                if (labelComp) labelComp.className = 'text-[11px] font-bold text-slate-400';

                if (c.estado === 'parcialmente_recibida') {
                    if (line1) line1.className = 'flex-1 h-0.5 bg-indigo-500 mx-2 transition-all';
                    if (circleParc) circleParc.className = 'w-7 h-7 rounded-full flex items-center justify-center bg-indigo-600 text-white shadow-sm text-xs font-black';
                    if (labelParc) labelParc.className = 'text-[11px] font-bold text-indigo-700';
                } else if (c.estado === 'completada') {
                    if (circlePend) circlePend.className = 'w-7 h-7 rounded-full flex items-center justify-center bg-emerald-600 text-white shadow-sm text-xs font-black';
                    if (line1) line1.className = 'flex-1 h-0.5 bg-emerald-500 mx-2 transition-all';
                    if (circleParc) circleParc.className = 'w-7 h-7 rounded-full flex items-center justify-center bg-emerald-600 text-white shadow-sm text-xs font-black';
                    if (line2) line2.className = 'flex-1 h-0.5 bg-emerald-500 mx-2 transition-all';
                    if (circleComp) circleComp.className = 'w-7 h-7 rounded-full flex items-center justify-center bg-emerald-600 text-white shadow-sm text-xs font-black';
                    if (labelComp) labelComp.className = 'text-[11px] font-bold text-emerald-700';
                } else if (c.estado === 'cancelada') {
                    if (bannerCanc) bannerCanc.classList.remove('hidden');
                    if (circlePend) circlePend.className = 'w-7 h-7 rounded-full flex items-center justify-center bg-rose-600 text-white shadow-sm text-xs font-black';
                }

                // Acciones directas desde el detalle
                const btnRecep = document.getElementById('btnDetalleAccionRecepcionar');
                const btnPago = document.getElementById('btnDetalleAccionPagar');
                if (btnRecep) {
                    if (c.estado === 'pendiente' || c.estado === 'parcialmente_recibida') {
                        btnRecep.classList.remove('hidden');
                    } else {
                        btnRecep.classList.add('hidden');
                    }
                }
                if (btnPago) {
                    const saldo = Number(c.saldo_pendiente ?? c.importe_total ?? 0);
                    if (c.estado !== 'cancelada' && saldo > 0) {
                        btnPago.classList.remove('hidden');
                    } else {
                        btnPago.classList.add('hidden');
                    }
                }

                // Actualizar barras de progreso C07
                document.getElementById('detalleRecepcionProgresoTexto').textContent = `${pctRec}% (${totRec} de ${totComp} un.)`;
                document.getElementById('detalleRecepcionProgresoBar').style.width = `${pctRec}%`;

                document.getElementById('detallePagoProgresoTexto').textContent = `${pctPag}% ($${totPag.toFixed(2)} de $${impTot.toFixed(2)})`;
                document.getElementById('detallePagoProgresoBar').style.width = `${pctPag}%`;

                tbody.innerHTML = (json.data.detalles || []).map(d => `
                    <tr>
                        <td>
                            <div class="font-bold text-slate-800">${d.nombre || d.descripcion || `Prod #${d.id_producto}`}</div>
                            ${d.codigo ? `<div class="text-[11px] text-slate-400 font-bold">${d.codigo}${d.descripcion && d.descripcion !== d.nombre ? ` • ${d.descripcion}` : ''}</div>` : ''}
                        </td>
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

                if (window.lucide) window.lucide.createIcons();
            }
        } catch (e) {
            console.error('Error detalle compra:', e);
        }
    }

    function cerrarModalDetalleCompra() {
        document.getElementById('modalDetalleCompra').classList.add('hidden');
    }

    function irDesdeDetalleARecepcion() {
        if (!idCompraDetalleActual) return;
        cerrarModalDetalleCompra();
        abrirModalRecepcionCompra(idCompraDetalleActual);
    }

    function irDesdeDetalleAPago() {
        if (!idCompraDetalleActual) return;
        cerrarModalDetalleCompra();
        abrirModalPagoCompra(idCompraDetalleActual);
    }

    async function imprimirFichaCompra(id = null) {
        let data = null;
        const targetId = id || idCompraDetalleActual;
        
        if (!targetId && compraDetalleDataCompleta) {
            data = compraDetalleDataCompleta;
        } else if (targetId) {
            if (compraDetalleDataCompleta && compraDetalleDataCompleta.compra && compraDetalleDataCompleta.compra.id_compra === targetId) {
                data = compraDetalleDataCompleta;
            } else {
                try {
                    const res = await fetch(`${API_BASE}/compras/${targetId}`);
                    const json = await res.json();
                    if (json.status === 'success' && json.data) {
                        data = json.data;
                    }
                } catch (e) {
                    console.error('Error al obtener datos para imprimir:', e);
                }
            }
        }

        if (!data || !data.compra) {
            mostrarNotificacion('No se pudo cargar la información para imprimir el comprobante.', 'error');
            return;
        }

        const c = data.compra;
        const detalles = data.detalles || [];
        const pagos = data.pagos || {};
        const totComp = data.total_comprado || detalles.reduce((acc, d) => acc + Number(d.cantidad || 0), 0);
        const totRec = data.total_recibido || detalles.reduce((acc, d) => acc + Number(d.cantidad_recibida || 0), 0);
        const impTot = Number(c.importe_total || 0);
        const totPag = Number(pagos.total_pagado || 0);
        const saldoAPagar = Math.max(0, impTot - totPag);

        // Determinación de tipo de letra (A, B, C o X)
        let letra = 'X';
        let codigoLetra = 'DOC. 000';
        const compUpper = String(c.numero_comprobante || '').toUpperCase();
        if (compUpper.startsWith('A') || compUpper.includes('FAC-A') || compUpper.includes('FC-A')) {
            letra = 'A';
            codigoLetra = 'COD. 001';
        } else if (compUpper.startsWith('B') || compUpper.includes('FAC-B') || compUpper.includes('FC-B')) {
            letra = 'B';
            codigoLetra = 'COD. 006';
        } else if (compUpper.startsWith('C') || compUpper.includes('FAC-C') || compUpper.includes('FC-C')) {
            letra = 'C';
            codigoLetra = 'COD. 011';
        }

        const fmtMonto = (val) => '$ ' + Number(val || 0).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const fmtFecha = (f) => f ? f.substring(0, 10).split('-').reverse().join('/') : '-';

        let estadoColor = '#2563eb';
        let estadoLabel = (c.estado || 'pendiente').replace('_', ' ').toUpperCase();
        if (c.estado === 'completada') estadoColor = '#16a34a';
        else if (c.estado === 'parcialmente_recibida') estadoColor = '#4f46e5';
        else if (c.estado === 'cancelada') estadoColor = '#dc2626';

        const printWindow = window.open('', '_blank', 'width=950,height=900,scrollbars=yes');
        if (!printWindow) {
            mostrarNotificacion('El navegador bloqueó la ventana emergente. Por favor permita popups para imprimir.', 'error');
            return;
        }

        const html = `<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Compra #${c.id_compra} - ${c.proveedor || 'Proveedor'}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm;
        }
        * {
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
        }
        body {
            margin: 0;
            padding: 15px;
            background: #f8fafc;
            font-size: 12px;
        }
        .no-print-bar {
            background: #0f172a;
            color: #fff;
            padding: 10px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .btn-print {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-print:hover { background: #1d4ed8; }
        .btn-close {
            background: #475569;
            color: #fff;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
        }
        .btn-close:hover { background: #334155; }
        
        .factura-box {
            background: #fff;
            max-width: 820px;
            margin: 0 auto;
            border: 2px solid #0f172a;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        }
        
        /* HEADER COMERCIAL ESTILO FACTURA */
        .factura-header {
            display: flex;
            position: relative;
            border-bottom: 2px solid #0f172a;
        }
        .header-col {
            flex: 1;
            padding: 14px 18px;
        }
        .header-col-left {
            border-right: 1px solid #cbd5e1;
        }
        .header-letter-box {
            position: absolute;
            left: 50%;
            top: 0;
            transform: translateX(-50%);
            width: 72px;
            height: 65px;
            background: #fff;
            border: 2px solid #0f172a;
            border-top: none;
            border-radius: 0 0 8px 8px;
            text-align: center;
            z-index: 10;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .header-letter-box .letter {
            font-size: 32px;
            font-weight: 900;
            line-height: 1;
            color: #0f172a;
        }
        .header-letter-box .subtext {
            font-size: 9px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-name {
            font-size: 18px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }
        .company-subtitle {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .company-info {
            font-size: 11px;
            color: #334155;
            line-height: 1.4;
        }
        .invoice-title {
            font-size: 17px;
            font-weight: 900;
            color: #0f172a;
            text-align: right;
            margin-bottom: 4px;
        }
        .invoice-number {
            font-size: 15px;
            font-weight: 800;
            color: #1e40af;
            text-align: right;
            margin-bottom: 8px;
        }
        .invoice-meta {
            font-size: 11px;
            text-align: right;
            line-height: 1.45;
            color: #334155;
        }

        /* PROVEEDOR */
        .prov-section {
            padding: 12px 18px;
            border-bottom: 2px solid #0f172a;
            background: #f8fafc;
        }
        .prov-grid {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 8px 20px;
            font-size: 11.5px;
        }
        .prov-item strong {
            color: #0f172a;
            font-weight: 800;
        }

        /* TABLA ITEMS */
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th {
            background: #0f172a;
            color: #fff;
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #0f172a;
        }
        .items-table td {
            padding: 8px 10px;
            font-size: 11.5px;
            border: 1px solid #e2e8f0;
            color: #334155;
        }
        .items-table tr:nth-child(even) {
            background: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* FOOTER TOTALES Y FIRMAS */
        .factura-bottom {
            display: flex;
            border-top: 2px solid #0f172a;
            background: #fff;
        }
        .bottom-left {
            flex: 1.3;
            padding: 14px 18px;
            border-right: 2px solid #0f172a;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .bottom-right {
            flex: 1;
            padding: 14px 18px;
            background: #f8fafc;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 12px;
            color: #475569;
        }
        .totals-row.main-total {
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            margin: 6px 0;
            padding: 8px 0;
            font-size: 15px;
            font-weight: 900;
            color: #0f172a;
        }
        .totals-row.saldo-row {
            font-size: 13px;
            font-weight: 900;
            color: #d97706;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            color: #fff;
            letter-spacing: 0.5px;
        }
        .signatures {
            display: flex;
            gap: 20px;
            margin-top: 25px;
        }
        .signature-box {
            flex: 1;
            border-top: 1px dashed #64748b;
            padding-top: 5px;
            text-align: center;
            font-size: 10px;
            font-weight: 700;
            color: #475569;
        }
        .doc-disclaimer {
            font-size: 9.5px;
            color: #64748b;
            text-align: center;
            padding: 10px 15px;
            background: #f1f5f9;
            border-top: 1px solid #cbd5e1;
            line-height: 1.35;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .no-print-bar { display: none !important; }
            .factura-box {
                border: 2px solid #000;
                box-shadow: none;
                max-width: 100%;
            }
            .factura-header, .prov-section, .factura-bottom {
                border-color: #000;
            }
            .header-letter-box {
                border-color: #000;
            }
            .items-table th {
                background: #000 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="no-print-bar">
        <div style="font-weight: bold; font-size: 14px;">Vista Previa de Comprobante Comercial de Compra</div>
        <div style="display: flex; gap: 8px;">
            <button class="btn-print" onclick="window.print()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                Imprimir Factura
            </button>
            <button class="btn-close" onclick="window.close()">Cerrar</button>
        </div>
    </div>

    <div class="factura-box">
        <!-- HEADER COMERCIAL ESTILO FACTURA -->
        <div class="factura-header">
            <div class="header-col header-col-left">
                <div class="company-name">DISTRIBUIDORA PYB S.A.</div>
                <div class="company-subtitle">Comercialización y Distribución Mayorista</div>
                <div class="company-info">
                    <strong>Razón Social:</strong> DISTRIBUIDORA PYB S.A.<br>
                    <strong>Domicilio Comercial:</strong> San Martín 1234 - Rosario, Santa Fe<br>
                    <strong>Condición IVA:</strong> IVA Responsable Inscripto<br>
                    <strong>CUIT:</strong> 30-71458923-4 | <strong>IIBB:</strong> 902-847291-0<br>
                    <strong>Inicio de Actividades:</strong> 01/03/2021
                </div>
            </div>

            <div class="header-letter-box">
                <span class="letter">${letra}</span>
                <span class="subtext">${codigoLetra}</span>
            </div>

            <div class="header-col">
                <div class="invoice-title">COMPROBANTE DE COMPRA</div>
                <div class="invoice-number">N° ${c.numero_comprobante || ('0001-' + String(c.id_compra).padStart(8, '0'))}</div>
                <div class="invoice-meta">
                    <strong>Orden Interna:</strong> ${c.numero_compra || ('#CMP-' + c.id_compra)}<br>
                    <strong>Fecha Emisión:</strong> ${fmtFecha(c.fecha_compra)}<br>
                    <strong>Fecha Vencimiento:</strong> ${fmtFecha(c.fecha_vencimiento)}<br>
                    <strong>Condición de Pago:</strong> Cuenta Corriente Proveedores<br>
                    <strong>Estado Operativo:</strong> <span class="status-badge" style="background:${estadoColor};">${estadoLabel}</span>
                </div>
            </div>
        </div>

        <!-- DATOS DEL PROVEEDOR -->
        <div class="prov-section">
            <div class="prov-grid">
                <div class="prov-item">
                    <strong>Proveedor / Emisor:</strong> ${c.proveedor || 'Sin Razón Social'}<br>
                    <strong>Domicilio:</strong> ${c.proveedor_direccion || 'No informado'}<br>
                    <strong>Contacto:</strong> ${c.proveedor_telefono || '-'} | ${c.proveedor_email || '-'}
                </div>
                <div class="prov-item">
                    <strong>CUIT Proveedor:</strong> ${c.proveedor_cuit || 'Consumidor Final / Sin CUIT'}<br>
                    <strong>Condición frente al IVA:</strong> IVA Responsable Inscripto<br>
                    <strong>Comprobante Origen:</strong> ${c.numero_comprobante || 'Pendiente de carga'}
                </div>
            </div>
        </div>

        <!-- ITEMS / DETALLE -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 35px;" class="text-center">#</th>
                    <th style="width: 90px;">Código</th>
                    <th>Descripción / Producto</th>
                    <th style="width: 70px;" class="text-center">Cant.</th>
                    <th style="width: 75px;" class="text-center">Recibido</th>
                    <th style="width: 95px;" class="text-right">Precio Unit.</th>
                    <th style="width: 105px;" class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                ${detalles.map((d, idx) => `
                    <tr>
                        <td class="text-center" style="font-weight: bold; color: #64748b;">${idx + 1}</td>
                        <td style="font-weight: bold; font-family: monospace;">${d.codigo || '-'}</td>
                        <td>
                            <strong style="color: #0f172a;">${d.nombre || d.descripcion || ('Producto #' + d.id_producto)}</strong>
                            ${d.descripcion && d.descripcion !== d.nombre ? `<div style="font-size: 10.5px; color: #64748b;">${d.descripcion}</div>` : ''}
                        </td>
                        <td class="text-center" style="font-weight: 800;">${d.cantidad}</td>
                        <td class="text-center" style="font-weight: 800; color: ${Number(d.cantidad_recibida) >= Number(d.cantidad) ? '#16a34a' : '#d97706'};">
                            ${d.cantidad_recibida || 0}
                        </td>
                        <td class="text-right">${fmtMonto(d.precio_unitario)}</td>
                        <td class="text-right" style="font-weight: 800; color: #1e3a8a;">${fmtMonto(d.subtotal)}</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>

        <!-- TOTALES Y FIRMAS -->
        <div class="factura-bottom">
            <div class="bottom-left">
                <div>
                    <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; margin-bottom: 6px;">
                        Control Logístico & Financiero
                    </div>
                    <div style="font-size: 11px; color: #334155; line-height: 1.5;">
                        • <strong>Recepción en Depósito:</strong> ${totRec} de ${totComp} unidades (${Math.round((totRec / (totComp || 1)) * 100)}%)<br>
                        • <strong>Pagos Registrados:</strong> ${pagos.por_metodo && pagos.por_metodo.length > 0 
                            ? pagos.por_metodo.map(p => `${p.metodo_pago.replace('_', ' ')} (${fmtMonto(p.total)})`).join(', ') 
                            : 'Sin pagos registrados a la fecha.'}
                    </div>
                </div>

                <div class="signatures">
                    <div class="signature-box">
                        Firma / Control Depósito<br>
                        <span style="font-size: 8.5px; color: #94a3b8;">Recepción de Mercadería</span>
                    </div>
                    <div class="signature-box">
                        Firma / Administración<br>
                        <span style="font-size: 8.5px; color: #94a3b8;">Aprobación y Pago</span>
                    </div>
                </div>
            </div>

            <div class="bottom-right">
                <div class="totals-row">
                    <span>Subtotal Neto:</span>
                    <strong style="color: #0f172a;">${fmtMonto(impTot)}</strong>
                </div>
                <div class="totals-row">
                    <span>IVA Discriminado:</span>
                    <span>$ 0,00</span>
                </div>
                <div class="totals-row">
                    <span>Percepciones / Impuestos:</span>
                    <span>$ 0,00</span>
                </div>
                <div class="totals-row main-total">
                    <span>TOTAL COMPRA:</span>
                    <span style="color: #1e40af;">${fmtMonto(impTot)}</span>
                </div>
                <div class="totals-row">
                    <span>Total Pagado a la Fecha:</span>
                    <strong style="color: #16a34a;">-${fmtMonto(totPag)}</strong>
                </div>
                <div class="totals-row saldo-row">
                    <span>SALDO PENDIENTE:</span>
                    <span style="color: ${saldoAPagar > 0 ? '#d97706' : '#16a34a'};">${fmtMonto(saldoAPagar)}</span>
                </div>
            </div>
        </div>

        <div class="doc-disclaimer">
            <strong>DOCUMENTO DE USO INTERNO Y CONTROL ADMINISTRATIVO</strong><br>
            Este comprobante no reemplaza la factura fiscal original emitida por el proveedor de conformidad con la normativa impositiva vigente.
            Emitido por Sistema ERP Distribuidora PyB el ${new Date().toLocaleDateString('es-AR')} a las ${new Date().toLocaleTimeString('es-AR')}.
        </div>
    </div>
</body>
</html>`;

        printWindow.document.open();
        printWindow.document.write(html);
        printWindow.document.close();
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

            tbody.innerHTML = detalles.map((d, idx) => {
                const pend = d.cantidad_pendiente !== undefined ? d.cantidad_pendiente : Math.max(0, d.cantidad - (d.cantidad_recibida || 0));
                const disabled = pend <= 0 ? 'disabled' : '';

                return `
                    <tr class="${pend <= 0 ? 'opacity-40 bg-slate-100/40' : ''}">
                        <td>
                            <div class="font-bold text-slate-800">${d.nombre || d.descripcion || `Prod #${d.id_producto}`}</div>
                            ${d.codigo ? `<div class="text-[11px] text-slate-400 font-bold">${d.codigo}</div>` : ''}
                            <input type="hidden" id="rec_prod_${idx}" value="${d.id_producto}">
                        </td>
                        <td class="text-center font-bold">${d.cantidad}</td>
                        <td class="text-center font-bold text-slate-500">${d.cantidad_recibida || 0}</td>
                        <td class="text-center font-black ${pend > 0 ? 'text-amber-600' : 'text-slate-400'}">${pend}</td>
                        <td class="text-center">
                            <input type="number" min="0" max="${pend}" value="${pend > 0 ? pend : 0}" id="rec_cant_${idx}" 
                                   oninput="actualizarPreviewRecepcion()" 
                                   ${disabled} class="clay-input w-20 py-1 text-center font-black text-xs">
                        </td>
                    </tr>
                `;
            }).join('');

            actualizarPreviewRecepcion();
            if (window.lucide) window.lucide.createIcons();
        } catch (e) {
            mostrarNotificacion('Error al cargar los datos de recepción.', 'error');
        }
    }

    function recepcionarTodoModal() {
        itemsRecepcionModal.forEach((d, idx) => {
            const input = document.getElementById(`rec_cant_${idx}`);
            const pend = d.cantidad_pendiente !== undefined ? d.cantidad_pendiente : Math.max(0, d.cantidad - (d.cantidad_recibida || 0));
            if (input && pend > 0) {
                input.value = pend;
            }
        });
        actualizarPreviewRecepcion();
    }

    function limpiarCantidadesRecepcion() {
        itemsRecepcionModal.forEach((d, idx) => {
            const input = document.getElementById(`rec_cant_${idx}`);
            if (input && !input.disabled) {
                input.value = 0;
            }
        });
        actualizarPreviewRecepcion();
    }

    function actualizarPreviewRecepcion() {
        let totalPendiente = 0;
        let totalARecibir = 0;

        itemsRecepcionModal.forEach((d, idx) => {
            const input = document.getElementById(`rec_cant_${idx}`);
            const pend = d.cantidad_pendiente !== undefined ? d.cantidad_pendiente : Math.max(0, d.cantidad - (d.cantidad_recibida || 0));
            if (pend > 0) {
                totalPendiente += pend;
                const cant = Number(input?.value || 0);
                totalARecibir += cant;
            }
        });

        const box = document.getElementById('recepcionPreviewEstado');
        const txt = document.getElementById('recepcionPreviewTexto');
        if (!box || !txt) return;

        if (totalARecibir === 0) {
            box.className = 'p-2.5 rounded-xl text-xs font-bold transition-all bg-slate-100 text-slate-600 border border-slate-200 flex items-center gap-2';
            txt.textContent = 'Ingrese las cantidades a recibir para ver el impacto en el estado de la compra.';
        } else if (totalARecibir >= totalPendiente) {
            box.className = 'p-2.5 rounded-xl text-xs font-bold transition-all bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center gap-2';
            txt.textContent = `✓ Esta recepción completará la compra al 100% (${totalARecibir} un. a ingresar. Estado final: Completada).`;
        } else {
            box.className = 'p-2.5 rounded-xl text-xs font-bold transition-all bg-indigo-50 text-indigo-800 border border-indigo-200 flex items-center gap-2';
            txt.textContent = `ℹ Esta recepción registrará un ingreso parcial de ${totalARecibir} un. (Restarán ${totalPendiente - totalARecibir} un. Estado: Parcialmente Recibida).`;
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
            if (!cantInput) continue;

            const cant = Number(cantInput.value || 0);
            if (cant > 0) {
                itemsToSend.push({
                    id_producto: itemsRecepcionModal[i].id_producto,
                    cantidad_recibida: cant
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
                mostrarNotificacion('Recepción registrada exitosamente. El stock ha sido actualizado.', 'success');
                await Promise.all([cargarCompras(), cargarOrdenes(), cargarDeudas()]);
            } else {
                mostrarNotificacion(data.message || 'Error al procesar la recepción.', 'error');
            }
        } catch (err) {
            mostrarNotificacion('Error de conexión al guardar la recepción.', 'error');
        }
    }

    // =========================================================
    // PAGOS DE COMPRA
    // =========================================================

    let saldoActualModalPago = 0;

    async function abrirModalPagoCompra(idCompra) {
        document.getElementById('pagoIdCompra').value = idCompra;
        document.getElementById('pagoFecha').value = new Date().toISOString().split('T')[0];
        document.getElementById('pagoSubtituloCompra').textContent = `Compra #${idCompra}`;
        document.getElementById('pagoMetodo').value = 'transferencia';
        alCambiarMetodoPago();

        try {
            const res = await fetch(`${API_BASE}/compras/${idCompra}/pagos/resumen`);
            const json = await res.json();
            if (json.status === 'success' && json.data) {
                const saldo = Number(json.data.saldo_a_pagar || 0);
                saldoActualModalPago = saldo;
                const total = Number(json.data.importe_total || 0);
                document.getElementById('pagoSaldoDisponibleDisplay').textContent = `$${saldo.toFixed(2)}`;
                document.getElementById('pagoTotalFacturaDisplay').textContent = `$${total.toFixed(2)}`;
                document.getElementById('pagoMonto').value = saldo > 0 ? saldo.toFixed(2) : '';
                document.getElementById('pagoMonto').max = saldo > 0 ? saldo : 0;
            }
        } catch (e) {}

        document.getElementById('modalPago').classList.remove('hidden');
        if (window.lucide) window.lucide.createIcons();
    }

    function setMontoPagoRapido(factor) {
        if (saldoActualModalPago <= 0) return;
        const monto = Math.round(saldoActualModalPago * factor * 100) / 100;
        document.getElementById('pagoMonto').value = monto.toFixed(2);
    }

    function alCambiarMetodoPago() {
        const metodo = document.getElementById('pagoMetodo')?.value;
        const bloqueCheque = document.getElementById('pagoCamposCheque');
        if (!bloqueCheque) return;
        if (metodo === 'cheque' || metodo === 'echeq') {
            bloqueCheque.classList.remove('hidden');
            if (window.lucide) window.lucide.createIcons();
        } else {
            bloqueCheque.classList.add('hidden');
        }
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
                            <button onclick="abrirModalDetalleOrden(${o.id_orden})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Ver Detalle">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            ${esPendiente ? `
                            <button onclick="enviarOrden(${o.id_orden})" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all" title="Enviar al Proveedor">
                                <i data-lucide="send" class="w-4 h-4"></i>
                            </button>
                            <button onclick="abrirModalEditarOrden(${o.id_orden})" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-all" title="Editar Orden">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <button onclick="cancelarOrden(${o.id_orden})" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-all" title="Cancelar Orden">
                                <i data-lucide="x-circle" class="w-4 h-4"></i>
                            </button>
                            ` : ''}
                            ${admiteFacturar ? `
                            <button onclick="convertirOrdenACompra(${o.id_orden})" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" title="Registrar Compra desde Orden">
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

    // Al seleccionar proveedor en Nueva Orden, cargamos sus productos asociados
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
        document.getElementById('modalOrdenTitulo').textContent = 'Emitir Orden de Compra';
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
                mostrarNotificacion('Solo se pueden editar órdenes en estado pendiente.', 'warning');
                return;
            }

            document.getElementById('ordenModoEdicion').value = 'true';
            document.getElementById('ordenIdEditar').value = idOrden;
            document.getElementById('modalOrdenTitulo').textContent = `Editar Orden de Compra #${o.numero_orden || idOrden}`;
            document.getElementById('modalOrdenSubtitulo').textContent = 'Ajuste de productos, cantidades y entrega antes de enviar al proveedor';
            document.getElementById('btnGuardarOrdenSubmit').textContent = 'Guardar Cambios';

            document.getElementById('ordenProveedor').value = o.id_proveedor;
            document.getElementById('ordenProveedor').disabled = true;
            await alCambiarProveedorOrden();

            document.getElementById('ordenFechaEmision').value = o.fecha_creacion ? o.fecha_creacion.substring(0, 10) : new Date().toISOString().split('T')[0];
            document.getElementById('ordenFechaEntrega').value = o.fecha_entrega_estimada ? o.fecha_entrega_estimada.substring(0, 10) : '';

            itemsNuevaOrden = (json.data.detalles || []).map(d => ({
                id_producto: d.id_producto,
                nombre: d.nombre || d.descripcion || d.codigo || `Prod #${d.id_producto}`,
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
        if (!confirm('¿Desea marcar esta orden de compra como enviada al proveedor? Una vez enviada ya no podrá ser modificada.')) return;
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
        if (!confirm('¿Confirma la cancelación de esta orden de compra?')) return;
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
                nombre: d.nombre || d.descripcion || d.codigo || `Prod #${d.id_producto}`,
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
                        <td>
                            <div class="font-bold text-slate-800">${d.nombre || d.descripcion || `Prod #${d.id_producto}`}</div>
                            ${d.codigo ? `<div class="text-[11px] text-slate-400 font-bold">${d.codigo}${d.descripcion && d.descripcion !== d.nombre ? ` • ${d.descripcion}` : ''}</div>` : ''}
                        </td>
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
    // SUGERENCIAS DE REPOSICIÓN
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
                            <button onclick="generarOrdenDesdeSugerencia(${s.id_sugerencia})" class="clay-btn-primary px-2.5 py-1 text-[11px] font-black shadow-sm" title="Generar Orden de Compra">
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
                mostrarNotificacion('Orden de compra generada exitosamente desde la sugerencia.', 'success');
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
                        <button onclick="abrirModalPagoCompra(${c.id_compra})" class="clay-btn-primary px-3 py-1 text-xs font-black shadow-sm" title="Abonar Deuda">
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
