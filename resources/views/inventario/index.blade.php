@extends('layouts.app')

@section('title', 'Inventario y Stock - Distribuidora Pigüé')
@section('page_title', 'Inventario y Stock')
@section('page_subtitle', 'Catálogo de Productos, Control de Stock y Movimientos')

@section('content')
<div class="space-y-6">

    <!-- BARRA SUPERIOR DE PESTAÑAS (Claymorphism Tabs idéntica a InventoryView) -->
    <div class="flex items-center justify-between flex-wrap gap-4 bg-white/70 backdrop-blur-md p-2 rounded-2xl border border-white/80 shadow-[inset_2px_2px_4px_rgba(255,255,255,0.8),0_4px_12px_rgba(0,0,0,0.02)]">
        <div class="flex items-center gap-1.5 overflow-x-auto w-full md:w-auto p-1">
            <button onclick="cambiarPestana('catalogo')" id="tabBtn-catalogo" 
                    class="tab-btn px-4 py-2.5 rounded-xl font-black text-xs flex items-center gap-2 transition-all cursor-pointer bg-white text-blue-600 shadow-sm border border-slate-200/60">
                <i data-lucide="package" class="w-4 h-4"></i>
                <span>Catálogo de Productos</span>
            </button>
            <button onclick="cambiarPestana('stock')" id="tabBtn-stock" 
                    class="tab-btn px-4 py-2.5 rounded-xl font-black text-xs flex items-center gap-2 transition-all cursor-pointer text-slate-600 hover:text-slate-900">
                <i data-lucide="shield-alert" class="w-4 h-4"></i>
                <span>Stock y Alertas</span>
            </button>
            <button onclick="cambiarPestana('movimientos')" id="tabBtn-movimientos" 
                    class="tab-btn px-4 py-2.5 rounded-xl font-black text-xs flex items-center gap-2 transition-all cursor-pointer text-slate-600 hover:text-slate-900">
                <i data-lucide="history" class="w-4 h-4"></i>
                <span>Movimientos</span>
            </button>
            <button onclick="cambiarPestana('conteo')" id="tabBtn-conteo" 
                    class="tab-btn px-4 py-2.5 rounded-xl font-black text-xs flex items-center gap-2 transition-all cursor-pointer text-slate-600 hover:text-slate-900">
                <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                <span>Conteo Físico</span>
            </button>
        </div>

        <div class="flex items-center gap-2 px-2">
            <button onclick="recargarTodo()" class="p-2 bg-white text-slate-600 hover:text-blue-600 rounded-xl border border-slate-200/80 shadow-sm" title="Actualizar datos">
                <i data-lucide="rotate-cw" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- PESTAÑA 1: CATÁLOGO DE PRODUCTOS                         -->
    <!-- ========================================================= -->
    <div id="pestana-catalogo" class="pestana-content space-y-4">
        
        <!-- RESUMEN RÁPIDO DE CATÁLOGO (Cards 3D Claymorphism) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="clay-card p-5 flex items-center gap-4">
                <div class="p-3.5 bg-blue-50 text-blue-600 rounded-2xl shadow-inner border border-blue-100">
                    <i data-lucide="package" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Total Catálogo</span>
                    <span id="kpiTotalCat" class="text-2xl font-black text-slate-800">0</span>
                    <span class="text-xs text-slate-500 font-bold ml-1">artículos</span>
                </div>
            </div>

            <div class="clay-card p-5 flex items-center gap-4">
                <div class="p-3.5 bg-emerald-50 text-emerald-600 rounded-2xl shadow-inner border border-emerald-100">
                    <i data-lucide="check-circle" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Artículos Activos</span>
                    <span id="kpiActivosCat" class="text-2xl font-black text-emerald-700">0</span>
                    <span class="text-xs text-emerald-600 font-bold ml-1">en venta</span>
                </div>
            </div>

            <div class="clay-card p-5 flex items-center gap-4">
                <div class="p-3.5 bg-rose-50 text-rose-600 rounded-2xl shadow-inner border border-rose-100">
                    <i data-lucide="shield-alert" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Stock Crítico / Alerta</span>
                    <span id="kpiCriticosCat" class="text-2xl font-black text-rose-700">0</span>
                    <span class="text-xs text-rose-600 font-bold ml-1">por reponer</span>
                </div>
            </div>
        </div>

        <!-- BARRA DE ACCIONES Y FILTROS -->
        <div class="clay-card p-5 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            <div class="flex items-center flex-1 gap-3 flex-wrap">
                <!-- Buscador -->
                <div class="relative flex-1 min-w-[220px]">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5"></i>
                    <input type="text" id="filtroSearch" placeholder="Buscar por código o nombre..." 
                           class="clay-input pl-10 pr-4 py-2 w-full text-sm font-bold text-slate-700"
                           oninput="renderProductos()">
                </div>

                <!-- Filtro Categoría -->
                <select id="filtroCategoria" onchange="renderProductos()" class="clay-input py-2 text-xs font-bold text-slate-700 min-w-[150px]">
                    <option value="">Todas las Categorías</option>
                </select>

                <!-- Filtro Marca -->
                <select id="filtroMarca" onchange="renderProductos()" class="clay-input py-2 text-xs font-bold text-slate-700 min-w-[140px]">
                    <option value="">Todas las Marcas</option>
                </select>

                <!-- Filtro Estado -->
                <select id="filtroEstado" onchange="renderProductos()" class="clay-input py-2 text-xs font-bold text-slate-700 min-w-[120px]">
                    <option value="todos">Todos los Estados</option>
                    <option value="activo">Solo Activos</option>
                    <option value="inactivo">Solo Inactivos</option>
                </select>

                <!-- Filtro Nivel de Stock (S15 / S16) -->
                <select id="filtroNivelStock" onchange="renderProductos()" class="clay-input py-2 text-xs font-bold text-slate-700 min-w-[145px]" title="Filtrar por Nivel de Stock">
                    <option value="todos">Todos los Niveles</option>
                    <option value="suficiente">🟢 Stock Suficiente</option>
                    <option value="bajo">🟡 Stock Bajo</option>
                    <option value="critico">🔴 Stock Crítico (≤ Mín)</option>
                    <option value="agotado">⚫ Sin Stock (0 u.)</option>
                </select>

                <!-- Rango de Cantidad de Stock -->
                <div class="flex items-center gap-1.5 bg-slate-50 px-2.5 py-1 rounded-xl border border-slate-200 text-xs font-bold" title="Filtrar por Rango de Cantidad de Stock">
                    <span class="text-slate-400 text-[11px] uppercase font-black">Stock:</span>
                    <input type="number" id="filtroStockMinCant" placeholder="Mín" min="0" oninput="renderProductos()" class="w-16 bg-white border border-slate-200 rounded px-1.5 py-0.5 text-center font-bold text-xs">
                    <span class="text-slate-300">-</span>
                    <input type="number" id="filtroStockMaxCant" placeholder="Máx" min="0" oninput="renderProductos()" class="w-16 bg-white border border-slate-200 rounded px-1.5 py-0.5 text-center font-bold text-xs">
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex items-center gap-2 flex-wrap shrink-0">
                <button onclick="abrirModalProducto()" class="clay-btn-primary px-4 py-2.5 text-xs font-bold flex items-center gap-2 shadow-md">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Nuevo Producto</span>
                </button>
                <button onclick="abrirModalAumentoMasivo()" class="clay-btn-secondary px-3.5 py-2.5 text-xs font-bold flex items-center gap-2" title="Aumento Porcentual Masivo">
                    <i data-lucide="trending-up" class="w-4 h-4 text-blue-600"></i>
                    <span class="hidden sm:inline">Aumento %</span>
                </button>
                <button onclick="abrirHistorialPreciosGlobal()" class="clay-btn-secondary px-3.5 py-2.5 text-xs font-bold flex items-center gap-2" title="Historial de Precios y Aumentos Masivos">
                    <i data-lucide="history" class="w-4 h-4 text-emerald-600"></i>
                    <span class="hidden sm:inline">Historial Precios</span>
                </button>
                <button onclick="abrirModalCategorias()" class="clay-btn-secondary px-3.5 py-2.5 text-xs font-bold flex items-center gap-2" title="Administrar Categorías">
                    <i data-lucide="layers" class="w-4 h-4 text-indigo-600"></i>
                    <span>Categorías</span>
                </button>
                <button onclick="abrirModalMarcas()" class="clay-btn-secondary px-3.5 py-2.5 text-xs font-bold flex items-center gap-2" title="Administrar Marcas">
                    <i data-lucide="tag" class="w-4 h-4 text-amber-600"></i>
                    <span>Marcas</span>
                </button>
            </div>
        </div>

        <!-- TABLA DE CATÁLOGO -->
        <div class="clay-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto / Descripción</th>
                            <th>Categoría</th>
                            <th>Marca</th>
                            <th class="text-center">Precio Unitario</th>
                            <th class="text-center">Stock Actual</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaProductosBody">
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400 font-bold">Cargando catálogo...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ========================================================= -->
    <!-- PESTAÑA 2: STOCK Y ALERTAS (S01, S02, S07, S15, S16)       -->
    <!-- ========================================================= -->
    <div id="pestana-stock" class="pestana-content hidden space-y-4">
        
        <!-- RESUMEN KPI DE STOCK Y EXISTENCIAS -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="clay-card p-4 flex items-center gap-3">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl shadow-inner border border-blue-100">
                    <i data-lucide="boxes" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Unidades en Stock</span>
                    <span id="kpiStockTotalUnidades" class="text-xl font-black text-slate-800">0</span>
                    <span class="text-[11px] text-slate-500 font-bold ml-1">u. físicas</span>
                </div>
            </div>

            <div class="clay-card p-4 flex items-center gap-3">
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl shadow-inner border border-emerald-100">
                    <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Stock Suficiente</span>
                    <span id="kpiStockSuficiente" class="text-xl font-black text-emerald-700">0</span>
                    <span class="text-[11px] text-emerald-600 font-bold ml-1">artículos</span>
                </div>
            </div>

            <div class="clay-card p-4 flex items-center gap-3">
                <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl shadow-inner border border-amber-100">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Bajo / Crítico</span>
                    <span id="kpiStockBajoCritico" class="text-xl font-black text-amber-700">0</span>
                    <span class="text-[11px] text-amber-600 font-bold ml-1">artículos</span>
                </div>
            </div>

            <div class="clay-card p-4 flex items-center gap-3">
                <div class="p-3 bg-rose-50 text-rose-600 rounded-2xl shadow-inner border border-rose-100">
                    <i data-lucide="x-circle" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Sin Stock (Agotado)</span>
                    <span id="kpiStockAgotado" class="text-xl font-black text-rose-700">0</span>
                    <span class="text-[11px] text-rose-600 font-bold ml-1">artículos</span>
                </div>
            </div>
        </div>

        <!-- BARRA DE ACCIONES Y FILTROS DE STOCK -->
        <div class="clay-card p-5 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            <div class="flex items-center flex-1 gap-3 flex-wrap">
                <!-- Buscador rápido -->
                <div class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5"></i>
                    <input type="text" id="filtroStockSearch" placeholder="Buscar por código o producto..." 
                           class="clay-input pl-10 pr-4 py-2 w-full text-xs font-bold text-slate-700"
                           oninput="renderTablaStockCompleto()">
                </div>

                <!-- Filtro Nivel de Stock (S15 / S16) -->
                <select id="filtroStockNivel" onchange="renderTablaStockCompleto()" class="clay-input py-2 text-xs font-bold text-slate-700 min-w-[140px]">
                    <option value="todos">Todos los Niveles</option>
                    <option value="suficiente">🟢 Stock Suficiente</option>
                    <option value="bajo">🟡 Stock Bajo</option>
                    <option value="critico">🔴 Stock Crítico (≤ Mín)</option>
                    <option value="agotado">⚫ Sin Existencias (0 u.)</option>
                </select>

                <!-- Selector de Vista: Todos los Productos vs Solo Alertas Críticas -->
                <div class="bg-slate-100 p-1 rounded-xl flex items-center border border-slate-200/80 text-xs font-black">
                    <button type="button" id="btnSubtabStockTodos" onclick="cambiarSubtabStock('todos')" class="px-3 py-1.5 rounded-lg bg-white shadow-xs text-blue-700 transition-all cursor-pointer">
                        Todos los Productos
                    </button>
                    <button type="button" id="btnSubtabStockAlertas" onclick="cambiarSubtabStock('alertas')" class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition-all cursor-pointer flex items-center gap-1.5">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-amber-500"></i>
                        <span>Solo Críticos</span>
                        <span id="badgeContadorAlertasStock" class="clay-badge clay-badge-danger text-[10px] px-1.5 py-0">0</span>
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap shrink-0">
                <button onclick="abrirModalIngreso()" class="clay-btn-primary px-3.5 py-2 text-xs font-bold flex items-center gap-1.5 shadow-md" title="Registrar Ingreso de Mercadería">
                    <i data-lucide="arrow-down-right" class="w-4 h-4"></i>
                    <span>Ingreso Mercadería</span>
                </button>
                <button onclick="abrirModalDevolucion()" class="clay-btn-secondary px-3 py-2 text-xs font-bold flex items-center gap-1.5" title="Registrar Devolución">
                    <i data-lucide="rotate-ccw" class="w-4 h-4 text-purple-600"></i>
                    <span>Devolución</span>
                </button>
                <button onclick="abrirModalAjuste()" class="clay-btn-secondary px-3 py-2 text-xs font-bold flex items-center gap-1.5" title="Ajuste Manual de Inventario">
                    <i data-lucide="sliders" class="w-4 h-4 text-amber-600"></i>
                    <span>Ajuste Manual</span>
                </button>
                <button onclick="abrirModalUbicaciones()" class="clay-btn-secondary px-3 py-2 text-xs font-bold flex items-center gap-1.5" title="Gestión de Ubicaciones Físicas">
                    <i data-lucide="map-pin" class="w-4 h-4 text-slate-600"></i>
                    <span>Ubicaciones</span>
                </button>
            </div>
        </div>

        <!-- VISTA 1: TABLA COMPLETA DE STOCK Y DISPONIBILIDAD PARA VENTAS (S01, S02, S16) -->
        <div id="contenedorStockCompleto" class="clay-card overflow-hidden">
            <div class="p-3.5 border-b border-slate-100 flex items-center justify-between">
                <span class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="package-check" class="w-4 h-4 text-blue-600"></i>
                    Inventario Completo y Disponibilidad para Venta en Tiempo Real
                </span>
                <span id="totalStockProductosTag" class="text-xs font-bold text-slate-500">0 artículos</span>
            </div>
            <div class="overflow-x-auto">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto / Descripción</th>
                            <th>Ubicación Física</th>
                            <th class="text-center">Formato de Venta</th>
                            <th class="text-center">Stock Físico</th>
                            <th class="text-center">Comprometido</th>
                            <th class="text-center bg-blue-50/50">Disponible Venta</th>
                            <th class="text-center">Stock Mínimo</th>
                            <th class="text-center">Nivel Semáforo</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaStockCompletoBody">
                        <tr>
                            <td colspan="10" class="text-center py-6 text-slate-400 font-bold">Cargando inventario de stock...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- VISTA 2: TABLA DE ALERTAS DE STOCK MÍNIMO Y CRÍTICO -->
        <div id="contenedorStockAlertas" class="clay-card overflow-hidden hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <span class="text-xs font-extrabold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-500"></i>
                    Artículos con Stock Bajo o Crítico (≤ Stock Mínimo)
                </span>
                <span id="totalAlertasTag" class="clay-badge clay-badge-danger">0 productos</span>
            </div>
            <div class="overflow-x-auto">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th class="text-center">Stock Actual</th>
                            <th class="text-center">Stock Mínimo</th>
                            <th class="text-center">Faltante Reposición</th>
                            <th class="text-center">Nivel Semáforo</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tablaStockAlertasBody">
                        <tr>
                            <td colspan="8" class="text-center py-6 text-slate-400 font-bold">Cargando alertas de stock...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- PESTAÑA: MOVIMIENTOS E HISTORIALES                        -->
    <!-- ========================================================= -->
    <div id="pestana-movimientos" class="pestana-content hidden space-y-4">
        <div class="clay-card p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-black text-slate-800">Historiales y Auditoría del Sistema</h3>
                <p class="text-xs text-slate-500 font-bold">Registro cronológico de movimientos de stock (Kardex), variaciones de precios y modificaciones de productos</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Segmented Control para elegir entre Stock, Precios y Auditoría -->
                <div class="bg-slate-100 p-1 rounded-xl flex items-center border border-slate-200/80 text-xs font-black">
                    <button type="button" id="btnSubtabMovStock" onclick="cambiarSubtabMovimientos('stock')" class="px-3 py-1.5 rounded-lg bg-white shadow-xs text-blue-700 transition-all cursor-pointer">
                        Movimientos Stock
                    </button>
                    <button type="button" id="btnSubtabMovPrecios" onclick="cambiarSubtabMovimientos('precios')" class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition-all cursor-pointer">
                        Historial Precios
                    </button>
                    <button type="button" id="btnSubtabMovAuditoria" onclick="cambiarSubtabMovimientos('auditoria')" class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition-all cursor-pointer">
                        Modificaciones Productos
                    </button>
                </div>
                <button type="button" onclick="recargarHistorialActual()" class="clay-btn-secondary px-3 py-1.5 text-xs font-bold flex items-center gap-1.5">
                    <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i>
                    <span>Actualizar</span>
                </button>
            </div>
        </div>

        <!-- SUBTAB 1: MOVIMIENTOS DE STOCK (Kardex) -->
        <div id="contenedorMovimientosStock" class="clay-card overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                <span class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="arrow-left-right" class="w-4 h-4 text-blue-600"></i>
                    Libro Diario de Movimientos de Inventario (Kardex)
                </span>
                <div class="flex items-center gap-2 flex-wrap">
                    <select id="filtroTipoMovStock" onchange="cargarMovimientos()" class="clay-input py-1 px-2.5 text-xs font-bold">
                        <option value="">Todos los Tipos</option>
                        <option value="ingreso">🟢 Ingresos</option>
                        <option value="venta">🔵 Ventas</option>
                        <option value="ajuste">🟠 Ajustes Manuales</option>
                        <option value="devolucion">🟣 Devoluciones</option>
                    </select>
                    <select id="filtroProdMovStock" onchange="cargarMovimientos()" class="clay-input py-1 px-2.5 text-xs font-bold min-w-[180px]">
                        <option value="">Todos los Productos</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Tipo Operación</th>
                            <th>Producto</th>
                            <th class="text-center">Cantidad</th>
                            <th>Motivo / Observación</th>
                            <th>Usuario Responsable</th>
                        </tr>
                    </thead>
                    <tbody id="tablaMovimientosBody">
                        <tr>
                            <td colspan="6" class="text-center py-6 text-slate-400 font-bold">Cargando movimientos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SUBTAB 2: HISTORIAL DE PRECIOS Y AUMENTOS MASIVOS -->
        <div id="contenedorHistorialPrecios" class="clay-card overflow-hidden hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                <span class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="trending-up" class="w-4 h-4 text-emerald-600"></i>
                    Registro de Modificaciones y Aumentos Masivos de Precios
                </span>
                <div class="flex items-center gap-2">
                    <select id="filtroOrigenMovPrecios" onchange="cargarMovimientosPrecios()" class="clay-input py-1 px-2.5 text-xs font-bold">
                        <option value="">Todos los Orígenes</option>
                        <option value="aumento_masivo">Solo Aumentos Masivos</option>
                        <option value="manual">Solo Modificación Manual</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Producto</th>
                            <th class="text-right">Precio Anterior</th>
                            <th class="text-right">Nuevo Precio</th>
                            <th class="text-center">Variación %</th>
                            <th class="text-center">Origen</th>
                            <th>Usuario Responsable</th>
                        </tr>
                    </thead>
                    <tbody id="tablaMovimientosPreciosBody">
                        <tr>
                            <td colspan="7" class="text-center py-6 text-slate-400 font-bold">Cargando historial de precios...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SUBTAB 3: AUDITORÍA DE MODIFICACIONES DE PRODUCTOS -->
        <div id="contenedorMovimientosAuditoria" class="clay-card overflow-hidden hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                <span class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="clipboard-check" class="w-4 h-4 text-indigo-600"></i>
                    Historial de Modificaciones y Auditoría de Productos
                </span>
                <div class="flex items-center gap-2">
                    <select id="filtroProdMovAuditoria" onchange="cargarMovimientosAuditoria()" class="clay-input py-1 px-2.5 text-xs font-bold">
                        <option value="">Todos los Productos Modificados</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Producto</th>
                            <th class="text-center">Acción</th>
                            <th>Campos Modificados</th>
                            <th>Usuario Responsable</th>
                        </tr>
                    </thead>
                    <tbody id="tablaMovimientosAuditoriaBody">
                        <tr>
                            <td colspan="5" class="text-center py-6 text-slate-400 font-bold">Cargando modificaciones de productos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- PESTAÑA 5: CONTEO FÍSICO E INCONSISTENCIAS          -->
    <!-- ========================================================= -->
    <div id="pestana-conteo" class="pestana-content hidden space-y-4">
        <div class="clay-card p-6 max-w-2xl mx-auto space-y-4">
            <div>
                <h3 class="text-base font-black text-slate-800">Sesión de Conteo Físico e Inventario</h3>
                <p class="text-xs text-slate-500 font-bold">Ingresá la cantidad real contada en depósito para detectar diferencias y conciliar inconsistencias</p>
            </div>

            <form onsubmit="ejecutarConteoFisico(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-extrabold text-slate-600 uppercase mb-1">Producto a Conciliar *</label>
                    <select id="conteoProductoSelect" onchange="actualizarStockConteo()" required class="clay-input w-full text-sm font-bold">
                        <option value="">Seleccione un producto...</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-extrabold text-slate-500 uppercase mb-1">Stock Teórico en Sistema</label>
                        <input type="text" id="conteoStockSistema" readonly value="-" class="clay-input w-full bg-slate-50 font-black text-slate-600">
                    </div>
                    <div>
                        <label class="block text-xs font-extrabold text-blue-600 uppercase mb-1">Cantidad Contada en Físico *</label>
                        <input type="number" id="conteoCantidadFisica" min="0" oninput="actualizarPreviewConteo()" required class="clay-input w-full font-black text-blue-700" placeholder="0">
                    </div>
                </div>

                <!-- Diagnóstico y Comparativa en Vivo -->
                <div id="conteoPreviewBox" class="p-3.5 rounded-xl border border-slate-200 bg-slate-50 hidden space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600">Diferencia Detectada:</span>
                        <span id="conteoPreviewDif" class="font-black text-sm">0 u.</span>
                    </div>
                    <div id="conteoPreviewDiagnostico" class="text-xs font-extrabold pt-0.5"></div>
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-slate-600 uppercase mb-1">Observaciones / Motivo de Diferencia</label>
                    <input type="text" id="conteoObs" placeholder="Ej: Conteo mensual estantería A-03" class="clay-input w-full text-sm">
                </div>

                <button type="submit" class="clay-btn-primary w-full py-3 text-sm font-black shadow-lg">
                    Registrar y Conciliar Diferencia
                </button>
            </form>
        </div>

        <!-- TABLA: RESUMEN DE INCONSISTENCIAS Y CONTEOS FÍSICOS -->
        <div class="clay-card overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <span class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="clipboard-check" class="w-4 h-4 text-emerald-600"></i>
                    Resumen de Inconsistencias y Conteos Físicos Realizados
                </span>
                <span id="totalConteosTag" class="text-xs font-bold text-slate-500">Historial de conciliaciones</span>
            </div>
            <div class="overflow-x-auto">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Producto</th>
                            <th class="text-center">Stock Anterior</th>
                            <th class="text-center">Físico Contado</th>
                            <th class="text-center">Diferencia</th>
                            <th class="text-center">Resultado</th>
                            <th>Observaciones</th>
                            <th>Responsable</th>
                        </tr>
                    </thead>
                    <tbody id="tablaHistorialConteosBody">
                        <tr>
                            <td colspan="8" class="text-center py-6 text-slate-400 font-bold">Cargando conteos físicos...</td>
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
<!-- MODAL: ALTA / EDICIÓN DE PRODUCTO          -->
<!-- ========================================== -->
<div id="modalProducto" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalProducto()">
    <div class="clay-modal-content max-w-xl p-6 space-y-5" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 id="modalProductoTitulo" class="text-base font-black text-slate-800">Nuevo Producto</h3>
            <button onclick="cerrarModalProducto()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form onsubmit="guardarProducto(event)" class="space-y-4">
            <input type="hidden" id="prodId">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Código Único *</label>
                    <input type="text" id="prodCodigo" required placeholder="PROD-001" class="clay-input w-full uppercase font-bold text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Nombre Comercial *</label>
                    <input type="text" id="prodNombre" required placeholder="Nombre del artículo" class="clay-input w-full text-sm font-bold">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Descripción</label>
                <input type="text" id="prodDescripcion" placeholder="Detalle o presentación" class="clay-input w-full text-sm">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Categoría</label>
                    <select id="prodCategoria" class="clay-input w-full text-xs font-bold"></select>
                </div>
                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Marca</label>
                    <select id="prodMarca" class="clay-input w-full text-xs font-bold"></select>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Precio Unitario ($)</label>
                <input type="number" step="0.01" min="0" id="prodPrecioUnitario" required class="clay-input w-full font-black text-sm" placeholder="0.00">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Stock Mínimo Alerta</label>
                    <input type="number" min="0" id="prodStockMin" value="10" required class="clay-input w-full font-bold text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Ubicación Física</label>
                    <select id="prodUbicacion" class="clay-input w-full text-xs font-bold">
                        <option value="">Sin asignar</option>
                    </select>
                </div>
            </div>

            <div class="p-3 bg-slate-50/90 rounded-xl border border-slate-200/80 space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-[11px] font-black text-slate-700 uppercase tracking-wide flex items-center gap-1.5">
                        <i data-lucide="package" class="w-3.5 h-3.5 text-indigo-600"></i>
                        <span>Presentación Comercial / Bulto (Opcional)</span>
                    </label>
                    <span class="text-[10px] text-slate-400 font-bold">Referencia para depósito</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase mb-1">Formato de Presentación</label>
                        <select id="prodFormatoBulto" onchange="actualizarPreviewBultoProducto()" class="clay-input w-full text-xs font-bold">
                            <option value="Unidad">Unidad (Suelto / Individual)</option>
                            <option value="Caja">Caja</option>
                            <option value="Pack">Pack</option>
                            <option value="Pallet">Pallet</option>
                            <option value="Display">Display</option>
                            <option value="Bulto">Bulto Cerrado</option>
                            <option value="Botella">Botella</option>
                            <option value="Lata">Lata</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase mb-1">Unidades por Bulto</label>
                        <input type="number" min="1" id="prodUnidadesPorBulto" value="1" oninput="actualizarPreviewBultoProducto()" class="clay-input w-full font-bold text-xs" placeholder="1 por defecto">
                    </div>
                </div>
                <p id="prodBultoPreview" class="text-[11px] text-indigo-700 font-bold hidden"></p>
            </div>

            <div class="flex items-center justify-between gap-2 pt-3 border-t border-slate-100">
                <button type="button" id="btnVerHistorialDesdeModalProd" onclick="abrirHistorialDesdeProducto()" class="clay-btn-secondary px-3 py-2 text-xs font-bold hidden flex items-center gap-1.5 text-blue-600 hover:text-blue-800" title="Ver modificaciones registradas de este producto">
                    <i data-lucide="history" class="w-4 h-4"></i>
                    <span>Ver Modificaciones</span>
                </button>
                <div class="flex items-center gap-2 ml-auto">
                    <button type="button" onclick="cerrarModalProducto()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cancelar</button>
                    <button type="submit" class="clay-btn-primary px-5 py-2 text-xs font-black shadow-md">Guardar Producto</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: INGRESO DE MERCADERÍA               -->
<!-- ========================================== -->
<div id="modalIngreso" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalIngreso()">
    <div class="clay-modal-content max-w-lg p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-800">Registrar Ingreso de Mercadería</h3>
                <p class="text-[11px] text-slate-500 font-bold">Ingreso directo de mercadería al inventario</p>
            </div>
            <button onclick="cerrarModalIngreso()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form onsubmit="guardarIngresoMercaderia(event)" class="space-y-4">
            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Proveedor</label>
                <select id="ingresoProveedorSelect" required class="clay-input w-full text-sm font-bold"></select>
            </div>

            <div class="space-y-3">
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 sm:col-span-8">
                        <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Producto *</label>
                        <select id="ingresoProductoSelect" onchange="onProductoIngresoChange()" required class="clay-input w-full text-xs font-bold"></select>
                        <div id="ingresoPresentacionAyuda" class="text-[11px] text-indigo-700 font-bold mt-1.5 hidden flex items-center gap-1.5"></div>
                    </div>
                    <div class="col-span-12 sm:col-span-4">
                        <label id="lblIngresoCantidad" class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Cantidad a Ingresar (u.) *</label>
                        <input type="number" id="ingresoCantidad" min="1" value="10" oninput="actualizarEquivalenciaIngreso()" required class="clay-input w-full font-black text-sm" placeholder="Cantidad en unidades">
                    </div>
                    <!-- Select oculto para mantener compatibilidad de tests y endpoints internos -->
                    <select id="ingresoUnidadSelect" onchange="actualizarEquivalenciaIngreso()" class="hidden">
                        <option value="">Unidades (u.)</option>
                    </select>
                </div>

                <!-- Banner de referencia si el producto tiene bulto configurado -->
                <div id="ingresoEquivalenciaBox" class="hidden p-2.5 rounded-xl bg-indigo-50/80 border border-indigo-200/80 text-xs text-indigo-950 font-bold flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2">
                        <i data-lucide="info" class="w-4 h-4 text-indigo-600 shrink-0"></i>
                        <span id="ingresoEquivalenciaTexto"></span>
                    </div>
                </div>
            </div>

            <p class="text-[11px] text-slate-500 font-medium">Al confirmar, se incrementa el stock actual del producto en el depósito y queda registrado en el historial de Movimientos (Kardex).</p>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="cerrarModalIngreso()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cancelar</button>
                <button type="submit" class="clay-btn-primary px-5 py-2 text-xs font-black shadow-md">Registrar Ingreso</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->


<!-- ========================================== -->
<!-- MODAL: AJUSTE MANUAL DE STOCK              -->
<!-- ========================================== -->
<div id="modalAjuste" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalAjuste()">
    <div class="clay-modal-content max-w-md p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-black text-slate-800">Ajuste Manual de Inventario</h3>
            <button onclick="cerrarModalAjuste()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form onsubmit="guardarAjusteManual(event)" class="space-y-4">
            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Producto</label>
                <select id="ajusteProductoSelect" onchange="onProductoAjusteChange()" required class="clay-input w-full text-xs font-bold"></select>
            </div>

            <!-- Banner comparativo de consistencia entre Stock General y Lotes -->
            <div id="ajusteResumenDescuadre" class="hidden"></div>

            <!-- Tipo de Ajuste, Cantidad y Unidad -->
            <div class="grid grid-cols-12 gap-3">
                <div class="col-span-12 sm:col-span-4">
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Tipo de Ajuste</label>
                    <select id="ajusteTipo" onchange="onTipoAjusteChange()" class="clay-input w-full text-xs font-bold">
                        <option value="aumentar">Aumentar (+)</option>
                        <option value="disminuir">Disminuir (-)</option>
                        <option value="fijar">Fijar cantidad exacta (=)</option>
                    </select>
                </div>
                <div class="col-span-12 sm:col-span-4">
                    <label id="ajusteCantidadLabel" class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Cantidad</label>
                    <input type="number" id="ajusteCantidad" min="0" value="1" oninput="actualizarEquivalenciaAjuste()" required class="clay-input w-full font-black text-sm">
                </div>
                <div class="col-span-12 sm:col-span-4">
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Unidad</label>
                    <select id="ajusteUnidadSelect" onchange="actualizarEquivalenciaAjuste()" class="clay-input w-full text-xs font-bold bg-white">
                        <option value="">Unidades (u.)</option>
                    </select>
                </div>
            </div>

            <!-- Banner de Equivalencia para Ajuste -->
            <div id="ajusteEquivalenciaBox" class="hidden p-2.5 rounded-xl bg-indigo-50/80 border border-indigo-200/80 text-xs text-indigo-950 font-bold flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-indigo-600 shrink-0"></i>
                    <span id="ajusteEquivalenciaTexto">Equivalencia: 1 Cajas x12 = 12 unidades individuales (u.)</span>
                </div>
                <span class="clay-badge clay-badge-success text-[10px] font-black shrink-0">✓ Unidad Activa</span>
            </div>



            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Motivo / Causa</label>
                <input type="text" id="ajusteMotivo" required placeholder="Ej: Rotura en estantería / Corrección de descuadre" class="clay-input w-full text-sm font-bold">
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="cerrarModalAjuste()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cancelar</button>
                <button type="submit" class="clay-btn-primary px-5 py-2 text-xs font-black shadow-md">Aplicar Ajuste</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: AUMENTO MASIVO DE PRECIOS     -->
<!-- ========================================== -->
<div id="modalAumento" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalAumentoMasivo()">
    <div class="clay-modal-content max-w-xl p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-800">Aumento Masivo de Precios</h3>
                <p class="text-[11px] font-bold text-slate-500">Actualización porcentual directa sin regla de redondeo</p>
            </div>
            <button onclick="cerrarModalAumentoMasivo()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form onsubmit="aplicarAumentoMasivo(event)" class="space-y-4">
            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Criterio de Aplicación</label>
                <select id="aumentoCriterio" class="clay-input w-full text-xs font-bold" onchange="actualizarCriterioAumento()">
                    <option value="todos">Todos los Productos Activos</option>
                    <option value="categoria">Por Categoría</option>
                    <option value="marca">Por Marca</option>
                </select>
            </div>

            <div id="aumentoCategoriaGrupo" class="hidden">
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Categoría</label>
                <select id="aumentoCategoriaSelect" class="clay-input w-full text-xs font-bold" onchange="actualizarVistaPreviaAumento()"></select>
            </div>

            <div id="aumentoMarcaGrupo" class="hidden">
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Marca</label>
                <select id="aumentoMarcaSelect" class="clay-input w-full text-xs font-bold" onchange="actualizarVistaPreviaAumento()"></select>
            </div>

            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Porcentaje de Aumento (%)</label>
                <div class="relative">
                    <input type="number" step="0.1" min="0.1" id="aumentoPorcentaje" required class="clay-input w-full font-black text-sm pr-8" placeholder="15.0" oninput="actualizarVistaPreviaAumento()">
                    <span class="absolute right-3 top-2.5 text-xs font-black text-slate-400">%</span>
                </div>
            </div>

            <!-- Vista Previa de Productos Afectados (Criterio P07) -->
            <div class="border border-slate-200/80 rounded-2xl p-3 bg-slate-50/70 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-extrabold text-slate-700 flex items-center gap-1.5">
                        <i data-lucide="eye" class="w-3.5 h-3.5 text-blue-600"></i>
                        Vista previa de productos afectados
                    </span>
                    <span id="aumentoPreviewCount" class="font-black text-blue-700 text-[11px] bg-blue-100/70 px-2 py-0.5 rounded-full">0 artículos</span>
                </div>
                <div class="overflow-y-auto max-h-44 rounded-xl border border-slate-200/60 bg-white">
                    <table class="clay-table text-xs">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th class="text-right">Precio Actual</th>
                                <th class="text-right">Nuevo Precio</th>
                            </tr>
                        </thead>
                        <tbody id="aumentoPreviewBody">
                            <tr><td colspan="4" class="text-center py-3 text-slate-400 font-bold">Ingrese un porcentaje para previsualizar...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="cerrarModalAumentoMasivo()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cancelar</button>
                <button type="submit" id="btnConfirmarAumento" class="clay-btn-primary px-5 py-2 text-xs font-black shadow-md flex items-center gap-1.5">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Aplicar Aumento</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: HISTORIAL DE PRECIOS Y AUDITORÍA (P08 / P04) -->
<!-- ========================================== -->
<div id="modalHistorial" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalHistorial()">
    <div class="clay-modal-content max-w-4xl p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-800">Historial y Auditoría de Productos</h3>
                <p id="historialProductoTitulo" class="text-xs font-bold text-slate-500">Historial Global de Precios y Modificaciones</p>
            </div>
            <button onclick="cerrarModalHistorial()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Pestañas internas del Modal: Precios vs Auditoría de Modificaciones -->
        <div class="flex items-center gap-2 border-b border-slate-100 pb-2">
            <button type="button" id="modalHistTabBtn-precios" onclick="cambiarModalHistTab('precios')" class="px-3.5 py-1.5 rounded-xl font-black text-xs bg-white text-blue-600 shadow-xs border border-slate-200/80 transition-all cursor-pointer flex items-center gap-1.5">
                <i data-lucide="trending-up" class="w-3.5 h-3.5 text-blue-600"></i>
                <span>Historial de Precios</span>
            </button>
            <button type="button" id="modalHistTabBtn-auditoria" onclick="cambiarModalHistTab('auditoria')" class="px-3.5 py-1.5 rounded-xl font-bold text-xs text-slate-600 hover:text-slate-900 transition-all cursor-pointer flex items-center gap-1.5">
                <i data-lucide="clipboard-check" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Historial de Modificaciones / Auditoría</span>
            </button>
        </div>

        <!-- Filtros dentro del modal (Producto y Origen) -->
        <div class="flex items-center justify-between flex-wrap gap-2 pb-1">
            <div class="flex items-center gap-2 flex-1 min-w-[220px]">
                <label class="text-[11px] font-extrabold text-slate-600 uppercase shrink-0">Filtrar Producto:</label>
                <select id="historialFiltroProducto" onchange="cambiarFiltroHistorialModal()" class="clay-input py-1 px-2.5 text-xs font-bold w-full">
                    <option value="">Todos los productos (Aumentos Masivos y Cambios)</option>
                </select>
            </div>
            <div id="historialFiltroOrigenGrupo" class="flex items-center gap-2">
                <select id="historialFiltroOrigen" onchange="cambiarFiltroHistorialModal()" class="clay-input py-1 px-2.5 text-xs font-bold">
                    <option value="">Todos los Orígenes</option>
                    <option value="aumento_masivo">Aumentos Masivos</option>
                    <option value="manual">Modificación Manual</option>
                </select>
            </div>
        </div>

        <!-- VISTA 1: TABLA HISTORIAL DE PRECIOS (P08 / P07) -->
        <div id="contenedorModalHistPrecios" class="overflow-x-auto max-h-80 border border-slate-100 rounded-xl">
            <table class="clay-table">
                <thead>
                    <tr>
                        <th>Fecha / Hora</th>
                        <th>Producto</th>
                        <th class="text-right">Precio Anterior</th>
                        <th class="text-right">Nuevo Precio</th>
                        <th class="text-center">Variación %</th>
                        <th class="text-center">Origen</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody id="historialPreciosBody">
                    <tr><td colspan="7" class="text-center py-4 text-slate-400">Cargando historial de precios...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- VISTA 2: TABLA MODIFICACIONES Y AUDITORÍA -->
        <div id="contenedorModalHistAuditoria" class="overflow-x-auto max-h-80 border border-slate-100 rounded-xl hidden">
            <table class="clay-table">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Producto</th>
                        <th class="text-center">Acción</th>
                        <th>Campos Modificados</th>
                        <th>Usuario Responsable</th>
                    </tr>
                </thead>
                <tbody id="historialAuditoriaBody">
                    <tr><td colspan="5" class="text-center py-4 text-slate-400">Cargando modificaciones del producto...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="flex justify-between items-center pt-3 border-t border-slate-100">
            <span class="text-[11px] font-bold text-slate-400">Todos los cambios de datos (nombre, descripción, precio, categoría, marca, stock) quedan auditados con trazabilidad completa.</span>
            <button type="button" onclick="cerrarModalHistorial()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cerrar</button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- ========================================== -->
<!-- MODAL: GESTIÓN DE CATEGORÍAS        -->
<!-- ========================================== -->
<div id="modalCategoria" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalCategorias()">
    <div class="clay-modal-content max-w-lg p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-800">Administrar Categorías</h3>
                <p class="text-[11px] font-bold text-slate-500">Alta, modificación y baja controlada de categorías</p>
            </div>
            <button onclick="cerrarModalCategorias()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form onsubmit="guardarCategoria(event)" class="space-y-3 bg-slate-50/70 p-3.5 rounded-2xl border border-slate-200/60">
            <input type="hidden" id="catId">
            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Nombre de Categoría *</label>
                <input type="text" id="catNombre" required placeholder="Bebidas, Lácteos, Limpieza..." class="clay-input w-full text-sm font-bold bg-white">
            </div>
            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" id="btnCancelarEdicionCat" onclick="cancelarEdicionCategoria()" class="clay-btn-secondary px-3.5 py-1.5 text-xs font-bold hidden">
                    Cancelar Edición
                </button>
                <button type="submit" id="btnGuardarCategoria" class="clay-btn-primary px-4 py-1.5 text-xs font-black shadow-md flex items-center gap-1.5">
                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                    <span>Guardar Categoría</span>
                </button>
            </div>
        </form>

        <div class="border-t border-slate-100 pt-3">
            <h4 class="text-xs font-black text-slate-700 uppercase mb-2">Categorías Existentes</h4>
            <div class="overflow-y-auto max-h-56">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th class="text-center">Productos</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaCategoriasBody">
                        <tr><td colspan="4" class="text-center py-4 text-slate-400 font-bold">Cargando categorías...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-100">
            <button type="button" onclick="cerrarModalCategorias()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cerrar</button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: GESTIÓN DE MARCAS            -->
<!-- ========================================== -->
<div id="modalMarca" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalMarcas()">
    <div class="clay-modal-content max-w-lg p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-800">Administrar Marcas</h3>
                <p class="text-[11px] font-bold text-slate-500">Alta, modificación y baja controlada de marcas comerciales</p>
            </div>
            <button onclick="cerrarModalMarcas()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form onsubmit="guardarMarca(event)" class="space-y-3 bg-slate-50/70 p-3.5 rounded-2xl border border-slate-200/60">
            <input type="hidden" id="marcaId">
            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Nombre de Marca *</label>
                <input type="text" id="marcaNombre" required placeholder="Coca Cola, La Serenísima, Quilmes..." class="clay-input w-full text-sm font-bold bg-white">
            </div>
            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" id="btnCancelarEdicionMarca" onclick="cancelarEdicionMarca()" class="clay-btn-secondary px-3.5 py-1.5 text-xs font-bold hidden">
                    Cancelar Edición
                </button>
                <button type="submit" id="btnGuardarMarca" class="clay-btn-primary px-4 py-1.5 text-xs font-black shadow-md flex items-center gap-1.5">
                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                    <span>Guardar Marca</span>
                </button>
            </div>
        </form>

        <div class="border-t border-slate-100 pt-3">
            <h4 class="text-xs font-black text-slate-700 uppercase mb-2">Marcas Existentes</h4>
            <div class="overflow-y-auto max-h-56">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th class="text-center">Productos</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaMarcasBody">
                        <tr><td colspan="4" class="text-center py-4 text-slate-400 font-bold">Cargando marcas...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-100">
            <button type="button" onclick="cerrarModalMarcas()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cerrar</button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- ========================================== -->
<!-- MODAL: GESTIÓN DE UBICACIONES       -->
<!-- ========================================== -->
<div id="modalUbicaciones" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalUbicaciones()">
    <div class="clay-modal-content max-w-xl p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-800">Ubicaciones de Almacenamiento</h3>
                <p class="text-[11px] font-bold text-slate-500">Pasillos, estantes, niveles y góndolas del depósito</p>
            </div>
            <button onclick="cerrarModalUbicaciones()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form onsubmit="guardarUbicacion(event)" class="space-y-3 bg-slate-50/70 p-3.5 rounded-2xl border border-slate-200/60">
            <input type="hidden" id="ubicacionId">
            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Nomenclatura / Descripción Ubicación *</label>
                <input type="text" id="ubicacionDescripcion" required placeholder="Ej: P1-E3-N2, Pasillo 3, Góndola 2, DEP-01..." class="clay-input w-full text-sm font-bold bg-white">
            </div>
            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" id="btnCancelarEdicionUbicacion" onclick="cancelarEdicionUbicacion()" class="clay-btn-secondary px-3.5 py-1.5 text-xs font-bold hidden">
                    Cancelar Edición
                </button>
                <button type="submit" id="btnGuardarUbicacion" class="clay-btn-primary px-4 py-1.5 text-xs font-black shadow-md flex items-center gap-1.5">
                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                    <span>Crear Ubicación</span>
                </button>
            </div>
        </form>

        <div class="border-t border-slate-100 pt-3">
            <div class="flex items-center justify-between mb-2">
                <h4 class="text-xs font-black text-slate-700 uppercase">Ubicaciones Configuradas</h4>
                <span id="totalUbicacionesTag" class="text-[11px] font-bold text-slate-400"></span>
            </div>
            <div class="overflow-y-auto max-h-56">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nomenclatura</th>
                            <th class="text-center">Productos</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaUbicacionesBody">
                        <tr><td colspan="4" class="text-center py-4 text-slate-400 font-bold">Cargando ubicaciones...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Vista previa de productos en la ubicación seleccionada (S14 criterio 5) -->
        <div id="boxProductosUbicacion" class="border border-slate-200/80 bg-slate-50/90 rounded-2xl p-3.5 space-y-2 hidden">
            <div class="flex items-center justify-between">
                <h5 id="tituloProductosUbicacion" class="text-xs font-black text-slate-800"></h5>
                <button type="button" onclick="cerrarDetalleProductosUbicacion()" class="text-slate-400 hover:text-slate-600 text-xs font-bold">Cerrar lista</button>
            </div>
            <ul id="listaProductosUbicacion" class="text-xs space-y-1 max-h-32 overflow-y-auto divide-y divide-slate-100"></ul>
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-100">
            <button type="button" onclick="cerrarModalUbicaciones()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cerrar</button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: GESTIÓN DE UNIDADES Y EQUIVALENCIAS -->
<!-- ========================================== -->
<div id="modalUnidades" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalUnidades()">
    <div class="clay-modal-content max-w-xl p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-800">Unidades de Medida y Equivalencias</h3>
                <p id="modalUnidadProdTitulo" class="text-xs font-extrabold text-blue-600">Configuración para el producto seleccionado</p>
            </div>
            <button onclick="cerrarModalUnidades()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Formulario Alta/Edición de Unidad -->
        <form onsubmit="guardarUnidadMedida(event)" class="space-y-3 bg-slate-50/70 p-3.5 rounded-2xl border border-slate-200/60">
            <input type="hidden" id="unidadModalProdId">
            <input type="hidden" id="unidadModalId">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Nombre Unidad *</label>
                    <input type="text" id="unidadModalNombre" required placeholder="Caja x12, Pack x6, Pallet..." class="clay-input w-full text-xs font-bold bg-white">
                </div>
                <div>
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Equivalencia (Unidades Base) *</label>
                    <input type="number" step="0.0001" min="0.0001" id="unidadModalEquiv" required placeholder="12 (si equivale a 12 u.)" class="clay-input w-full text-xs font-black bg-white">
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Descripción u Observaciones</label>
                <input type="text" id="unidadModalDesc" placeholder="Presentación de bulto cerrado" class="clay-input w-full text-xs font-bold bg-white">
            </div>
            <div class="flex items-center gap-2 p-2.5 bg-indigo-50/60 rounded-xl border border-indigo-100">
                <input type="checkbox" id="unidadModalEsBase" class="rounded border-slate-300 text-indigo-600">
                <label for="unidadModalEsBase" class="text-xs font-bold text-slate-700 cursor-pointer">
                    Establecer como la unidad de medida activa del producto (para visualizar en el sistema)
                </label>
            </div>
            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" id="btnCancelarEdicionUnidad" onclick="cancelarEdicionUnidad()" class="clay-btn-secondary px-3.5 py-1.5 text-xs font-bold hidden">
                    Cancelar Edición
                </button>
                <button type="submit" id="btnGuardarUnidad" class="clay-btn-primary px-4 py-1.5 text-xs font-black shadow-md flex items-center gap-1.5">
                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                    <span>Guardar Equivalencia</span>
                </button>
            </div>
        </form>

        <!-- Tabla de Unidades Existentes -->
        <div class="border-t border-slate-100 pt-3">
            <div class="flex items-center justify-between gap-2 mb-2">
                <h4 class="text-xs font-black text-slate-700 uppercase">Equivalencias Configuradas</h4>
                <button type="button" onclick="restablecerUnidadesIndividuales()" class="clay-btn-secondary px-2.5 py-1 text-[11px] font-bold text-slate-700 hover:text-slate-900 shadow-xs" title="Volver a mostrar el producto en unidades sueltas individuales">
                    Restablecer a Unidades Individuales (u.)
                </button>
            </div>
            <div class="overflow-y-auto max-h-56">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>Unidad</th>
                            <th class="text-center">Equivalencia Base</th>
                            <th class="text-center">Estado en Sistema</th>
                            <th>Descripción</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaUnidadesBody">
                        <tr><td colspan="5" class="text-center py-4 text-slate-400 font-bold">Cargando unidades...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-100">
            <button type="button" onclick="cerrarModalUnidades()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cerrar</button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: REGISTRAR DEVOLUCIÓN         -->
<!-- ========================================== -->
<div id="modalDevolucion" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalDevolucion()">
    <div class="clay-modal-content max-w-md p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-black text-slate-800">Registrar Devolución</h3>
            <button onclick="cerrarModalDevolucion()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form onsubmit="ejecutarDevolucion(event, 'modal')" class="space-y-4">
            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Producto</label>
                <select id="modalDevProductoSelect" onchange="onProductoDevolucionChange('modal')" required class="clay-input w-full text-xs font-bold"></select>
            </div>

            <div class="grid grid-cols-12 gap-3">
                <div class="col-span-12 sm:col-span-5">
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Tipo de Devolución</label>
                    <select id="modalDevTipo" onchange="onTipoDevolucionChange('modal')" class="clay-input w-full text-xs font-bold">
                        <option value="cliente">Devolución de Cliente</option>
                        <option value="proveedor">Devolución a Proveedor</option>
                    </select>
                </div>
                <div class="col-span-12 sm:col-span-3">
                    <label id="lblModalDevCantidad" class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Cantidad</label>
                    <input type="number" id="modalDevCantidad" min="1" value="1" oninput="actualizarEquivalenciaDevolucion('modal')" required class="clay-input w-full font-black text-sm">
                </div>
                <div class="col-span-12 sm:col-span-4">
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Unidad</label>
                    <select id="modalDevUnidadSelect" onchange="actualizarEquivalenciaDevolucion('modal')" class="clay-input w-full text-xs font-bold bg-white">
                        <option value="">Unidades (u.)</option>
                    </select>
                </div>
            </div>

            <!-- Banner de Equivalencia para Devolución -->
            <div id="modalDevEquivalenciaBox" class="hidden p-2.5 rounded-xl bg-indigo-50/80 border border-indigo-200/80 text-xs text-indigo-950 font-bold flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-indigo-600 shrink-0"></i>
                    <span id="modalDevEquivalenciaTexto">Equivalencia: 1 Caja x12 = 12 unidades individuales (u.)</span>
                </div>
                <span class="clay-badge clay-badge-success text-[10px] font-black shrink-0">✓ Unidad Activa</span>
            </div>


            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Motivo</label>
                <input type="text" id="modalDevMotivo" required placeholder="Producto vencido / dañado / error pedido" class="clay-input w-full text-sm font-bold">
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="cerrarModalDevolucion()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cancelar</button>
                <button type="submit" class="clay-btn-primary px-5 py-2 text-xs font-black shadow-md">Registrar Devolución</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const API_BASE = '{{ url("/api") }}';
    let productosCache = [];
    let categoriasCache = [];
    let marcasCache = [];
    let ubicacionesCache = [];
    let proveedoresCache = [];
    let lotesCache = [];
    let modoLoteActual = 'nuevo';

    document.addEventListener('DOMContentLoaded', () => {
        recargarTodo();
        if (new URLSearchParams(window.location.search).get('tab') === 'lotes') {
            cambiarPestana('lotes');
        }
    });

    async function recargarTodo() {
        await Promise.all([
            cargarCategorias(),
            cargarMarcas(),
            cargarUbicaciones(),
            cargarProveedores(),
            cargarProductos(),
            cargarMovimientos(),
            cargarMovimientosPrecios(),
            cargarHistorialConteosFisicos()
        ]);
        if (window.lucide) window.lucide.createIcons();
    }

    function cambiarPestana(id) {
        document.querySelectorAll('.pestana-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(btn => {
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
        if (id === 'movimientos') {
            recargarHistorialActual();
        }
        if (window.lucide) window.lucide.createIcons();
    }

    // --- CARGA DE DATOS DE LA API ---
    async function cargarCategorias() {
        try {
            const res = await fetch(`${API_BASE}/categorias`);
            const json = await res.json();
            if (json.status === 'success') {
                categoriasCache = json.data;
                const selectFiltro = document.getElementById('filtroCategoria');
                const selectModal = document.getElementById('prodCategoria');
                selectFiltro.innerHTML = '<option value="">Todas las Categorías</option>' + 
                    categoriasCache.map(c => `<option value="${c.id_categoria}">${c.nombre}</option>`).join('');
                selectModal.innerHTML = '<option value="">Sin categoría</option>' + 
                    categoriasCache.map(c => `<option value="${c.id_categoria}">${c.nombre}</option>`).join('');
            }
        } catch (e) { console.error('Error categorias', e); }
    }

    async function cargarMarcas() {
        try {
            const res = await fetch(`${API_BASE}/marcas`);
            const json = await res.json();
            if (json.status === 'success') {
                marcasCache = json.data;
                const selectFiltro = document.getElementById('filtroMarca');
                const selectModal = document.getElementById('prodMarca');
                selectFiltro.innerHTML = '<option value="">Todas las Marcas</option>' + 
                    marcasCache.map(m => `<option value="${m.id_marca}">${m.nombre}</option>`).join('');
                selectModal.innerHTML = '<option value="">Sin marca</option>' + 
                    marcasCache.map(m => `<option value="${m.id_marca}">${m.nombre}</option>`).join('');
            }
        } catch (e) { console.error('Error marcas', e); }
    }

    async function cargarUbicaciones() {
        try {
            const res = await fetch(`${API_BASE}/ubicaciones`);
            const json = await res.json();
            if (json.status === 'success') {
                ubicacionesCache = json.data;
                const select = document.getElementById('prodUbicacion');
                select.innerHTML = '<option value="">Sin asignar</option>' + 
                    ubicacionesCache.map(u => `<option value="${u.id_ubicacion}">${u.descripcion}</option>`).join('');
            }
        } catch (e) { console.error('Error ubicaciones', e); }
    }

    async function cargarProveedores() {
        try {
            const res = await fetch(`${API_BASE}/proveedores?estado=activo`);
            const json = await res.json();
            if (json.status === 'success') {
                proveedoresCache = (json.data || []).filter(p => p.estado === 'activo');
                const select = document.getElementById('ingresoProveedorSelect');
                if (select) {
                    select.innerHTML = '<option value="">Seleccione un proveedor activo...</option>' + 
                        proveedoresCache.map(p => `<option value="${p.id_proveedor}">${p.razon_social} (${p.cuit || p.CUIT || ''})</option>`).join('');
                }
            }
        } catch (e) { console.error('Error proveedores', e); }
    }

    async function cargarProductos() {
        try {
            const res = await fetch(`${API_BASE}/productos`);
            const json = await res.json();
            if (json.status === 'success') {
                productosCache = json.data;
                renderProductos();
                actualizarKPIs();
                poblarSelectsProductos();
            }
        } catch (e) { console.error('Error productos', e); }
    }

    function obtenerTextoPresentacion(p) {
        const eq = Number(p?.unidades_por_bulto || p?.equivalencia_unidad || 1);
        let formato = (p?.formato_bulto || p?.unidad_medida || 'Unidad').trim();

        if (!formato || formato === 'Unidad' || formato === 'u.') {
            return eq > 1 ? `Pack x ${eq} u.` : 'Unidad (u.)';
        }

        // Si el formato ya termina con el número de equivalencia (ej: "Caja x 12" o "Caja x12" o "Caja 12")
        const regexX = new RegExp(`[\\s_xX]+${eq}\\s*(u\\.?|unidades)?$`, 'i');
        if (regexX.test(formato)) {
            return `${formato.replace(/\s*u\.?$/i, '')} u.`;
        }

        if (eq > 1) {
            return `${formato} x ${eq} u.`;
        }

        return formato;
    }

    function renderColumnaFormatoVenta(p, cantStock) {
        const eq = Number(p?.unidades_por_bulto || p?.equivalencia_unidad || 1);
        const texto = obtenerTextoPresentacion(p);
        if (eq > 1) {
            const bultos = (cantStock / eq).toLocaleString('es-AR', { maximumFractionDigits: 1 });
            return `<div class="font-extrabold text-slate-800 text-xs">${texto}</div><div class="text-[10px] text-indigo-600 font-black tracking-tight">≈ ${bultos} bultos</div>`;
        }
        return `<span class="text-slate-400 font-bold text-xs">${texto}</span>`;
    }

    function formatoCantidadProducto(cant, p) {
        const num = Number(cant || 0);
        return `${num} u.`;
    }

    function formatoCantidadProductoCorta(cant, p) {
        const num = Number(cant || 0);
        return `${num} u.`;
    }

    function obtenerInfoUnidadProducto(idProducto) {
        const prod = productosCache.find(p => String(p.id_producto) === String(idProducto));
        if (!prod) return { id_unidad: null, nombre: 'u.', equivalencia: 1.0, tieneActiva: false, producto: null };
        const tieneActiva = Boolean(prod.id_unidad_activa && Number(prod.equivalencia_unidad) > 1 && prod.unidad_medida && prod.unidad_medida !== 'u.');
        return {
            id_unidad: prod.id_unidad_activa,
            nombre: prod.unidad_medida || 'u.',
            equivalencia: Number(prod.equivalencia_unidad || 1),
            tieneActiva: tieneActiva,
            producto: prod
        };
    }

    function renderProductos() {
        const search = document.getElementById('filtroSearch')?.value.toLowerCase().trim() || '';
        const catId = document.getElementById('filtroCategoria')?.value || '';
        const marcaId = document.getElementById('filtroMarca')?.value || '';
        const estado = document.getElementById('filtroEstado')?.value || 'todos';
        const nivelStock = document.getElementById('filtroNivelStock')?.value || 'todos';
        const stockMinCant = document.getElementById('filtroStockMinCant')?.value;
        const stockMaxCant = document.getElementById('filtroStockMaxCant')?.value;

        const filtrados = productosCache.filter(p => {
            const coincideTexto = !search || p.nombre.toLowerCase().includes(search) || p.codigo.toLowerCase().includes(search);
            const coincideCat = !catId || String(p.id_categoria) === catId;
            const coincideMarca = !marcaId || String(p.id_marca) === marcaId;
            const coincideEstado = estado === 'todos' || p.estado === estado;

            const cantStock = Number(p.stock ?? p.stock_disponible ?? 0);
            const stockMin = Number(p.stock_minimo || 5);

            let coincideNivel = true;
            if (nivelStock === 'suficiente') coincideNivel = cantStock > stockMin;
            else if (nivelStock === 'bajo') coincideNivel = cantStock > 0 && cantStock <= stockMin * 1.5;
            else if (nivelStock === 'critico') coincideNivel = cantStock > 0 && cantStock <= stockMin;
            else if (nivelStock === 'agotado') coincideNivel = cantStock === 0;

            let coincideRango = true;
            if (stockMinCant !== '' && stockMinCant !== undefined && stockMinCant !== null) {
                coincideRango = coincideRango && cantStock >= Number(stockMinCant);
            }
            if (stockMaxCant !== '' && stockMaxCant !== undefined && stockMaxCant !== null) {
                coincideRango = coincideRango && cantStock <= Number(stockMaxCant);
            }

            return coincideTexto && coincideCat && coincideMarca && coincideEstado && coincideNivel && coincideRango;
        });

        const tbody = document.getElementById('tablaProductosBody');
        if (!tbody) return;
        if (filtrados.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-slate-400 font-bold">No se encontraron productos coincidentes con los filtros aplicados.</td></tr>`;
            return;
        }

        tbody.innerHTML = filtrados.map(p => {
            const esActivo = p.estado === 'activo';
            const badgeClase = esActivo ? 'clay-badge-success' : 'clay-badge-danger';
            const precioUnitario = Number(p.precio_unitario ?? p.precio_minorista ?? 0).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
            const cantStock = Number(p.stock ?? p.stock_disponible ?? 0);
            const stockMin = Number(p.stock_minimo || 5);

            // Semáforo visual
            let semaforoBadge = '';
            if (cantStock === 0) {
                semaforoBadge = '<span class="clay-badge clay-badge-danger font-black"><span class="w-1.5 h-1.5 rounded-full bg-red-600 inline-block mr-1"></span>Agotado</span>';
            } else if (cantStock <= stockMin) {
                semaforoBadge = '<span class="clay-badge clay-badge-danger font-black"><span class="w-1.5 h-1.5 rounded-full bg-rose-500 inline-block mr-1"></span>Crítico</span>';
            } else if (cantStock <= stockMin * 1.5) {
                semaforoBadge = '<span class="clay-badge clay-badge-warning font-black"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 inline-block mr-1"></span>Bajo</span>';
            } else {
                semaforoBadge = '<span class="clay-badge clay-badge-success font-black"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block mr-1"></span>Suficiente</span>';
            }

            const ubicacionTexto = p.ubicacion?.descripcion || p.ubicacion_nombre || 'Sin asignar';

            return `
                <tr>
                    <td class="font-extrabold text-slate-800">${p.codigo}</td>
                    <td>
                        <div class="font-black text-slate-800 text-sm leading-tight">${p.nombre}</div>
                        <div class="text-[11px] text-slate-400 flex flex-wrap items-center gap-1.5 mt-0.5">
                            <span>${p.descripcion || '-'}</span>
                            <span class="text-slate-300">•</span>
                            <span class="text-slate-500 font-bold flex items-center gap-0.5" title="Ubicación en depósito"><i data-lucide="map-pin" class="w-3 h-3 text-slate-400"></i>${ubicacionTexto}</span>
                            ${Number(p.unidades_por_bulto || p.equivalencia_unidad || 1) > 1 ? `<span class="text-slate-300">•</span><span class="text-indigo-600 font-extrabold flex items-center gap-0.5" title="Presentación"><i data-lucide="package" class="w-3 h-3"></i>${obtenerTextoPresentacion(p)}</span>` : ''}
                        </div>
                    </td>
                    <td><span class="text-xs font-bold text-slate-600">${p.categoria?.nombre || p.categoria_nombre || '-'}</span></td>
                    <td><span class="text-xs font-bold text-slate-600">${p.marca?.nombre || p.marca_nombre || '-'}</span></td>
                    <td class="text-center font-black text-slate-800">${precioUnitario}</td>
                    <td class="text-center">
                        <div class="font-black text-slate-800 text-xs">${cantStock} u.</div>
                        <div class="mt-0.5">${semaforoBadge}</div>
                    </td>
                    <td class="text-center">
                        <span class="clay-badge ${badgeClase}">${p.estado}</span>
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <button onclick="abrirHistorialPrecios(${p.id_producto})" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg" title="Historial de Precios">
                                <i data-lucide="history" class="w-4 h-4"></i>
                            </button>
                            <button onclick="editarProducto(${p.id_producto})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg" title="Editar Producto">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <button onclick="cambiarEstadoProducto(${p.id_producto}, '${p.estado}')" class="p-1.5 ${esActivo ? 'text-rose-600 hover:bg-rose-50' : 'text-emerald-600 hover:bg-emerald-50'} rounded-lg" title="${esActivo ? 'Desactivar' : 'Activar'}">
                                <i data-lucide="${esActivo ? 'power' : 'check'}" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        if (window.lucide) window.lucide.createIcons();
    }

    let subtabStockActual = 'todos';

    function cambiarSubtabStock(subtab) {
        subtabStockActual = subtab;
        const contTodos = document.getElementById('contenedorStockCompleto');
        const contAlertas = document.getElementById('contenedorStockAlertas');
        const btnTodos = document.getElementById('btnSubtabStockTodos');
        const btnAlertas = document.getElementById('btnSubtabStockAlertas');

        if (subtab === 'alertas') {
            if (contTodos) contTodos.classList.add('hidden');
            if (contAlertas) contAlertas.classList.remove('hidden');
            if (btnTodos) btnTodos.className = 'px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition-all cursor-pointer font-bold';
            if (btnAlertas) btnAlertas.className = 'px-3 py-1.5 rounded-lg bg-white shadow-xs text-rose-700 transition-all cursor-pointer flex items-center gap-1.5 font-black';
        } else {
            if (contTodos) contTodos.classList.remove('hidden');
            if (contAlertas) contAlertas.classList.add('hidden');
            if (btnTodos) btnTodos.className = 'px-3 py-1.5 rounded-lg bg-white shadow-xs text-blue-700 transition-all cursor-pointer font-black';
            if (btnAlertas) btnAlertas.className = 'px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition-all cursor-pointer flex items-center gap-1.5 font-bold';
            renderTablaStockCompleto();
        }
        if (window.lucide) window.lucide.createIcons();
    }

    function renderTablaStockCompleto() {
        const search = document.getElementById('filtroStockSearch')?.value.toLowerCase().trim() || '';
        const nivel = document.getElementById('filtroStockNivel')?.value || 'todos';

        let lista = Array.isArray(productosCache) ? productosCache : [];

        if (search) {
            lista = lista.filter(p => p.nombre.toLowerCase().includes(search) || p.codigo.toLowerCase().includes(search));
        }

        if (nivel !== 'todos') {
            lista = lista.filter(p => {
                const s = Number(p.stock ?? p.stock_disponible ?? 0);
                const m = Number(p.stock_minimo || 5);
                if (nivel === 'suficiente') return s > m;
                if (nivel === 'bajo') return s > 0 && s <= m * 1.5;
                if (nivel === 'critico') return s > 0 && s <= m;
                if (nivel === 'agotado') return s === 0;
                return true;
            });
        }

        const tagTotal = document.getElementById('totalStockProductosTag');
        if (tagTotal) tagTotal.textContent = `${lista.length} artículos`;

        const tbody = document.getElementById('tablaStockCompletoBody');
        if (!tbody) return;

        if (lista.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center py-6 text-slate-400 font-bold">No se encontraron productos coincidentes en el inventario.</td></tr>`;
            return;
        }

        tbody.innerHTML = lista.map(p => {
            const stockFisico = Number(p.stock ?? p.stock_disponible ?? 0);
            const comprometido = Number(p.stock_comprometido || 0);
            const disponibleVenta = Math.max(0, stockFisico - comprometido);
            const stockMin = Number(p.stock_minimo || 5);
            const ubicacionTexto = p.ubicacion?.descripcion || p.ubicacion_nombre || 'Sin asignar';

            let semaforoBadge = '';
            if (disponibleVenta === 0) {
                semaforoBadge = '<span class="clay-badge clay-badge-danger font-black"><span class="w-1.5 h-1.5 rounded-full bg-red-600 inline-block mr-1"></span>Agotado</span>';
            } else if (disponibleVenta <= stockMin) {
                semaforoBadge = '<span class="clay-badge clay-badge-danger font-black"><span class="w-1.5 h-1.5 rounded-full bg-rose-500 inline-block mr-1"></span>Crítico</span>';
            } else if (disponibleVenta <= stockMin * 1.5) {
                semaforoBadge = '<span class="clay-badge clay-badge-warning font-black"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 inline-block mr-1"></span>Bajo</span>';
            } else {
                semaforoBadge = '<span class="clay-badge clay-badge-success font-black"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block mr-1"></span>Suficiente</span>';
            }

            return `
                <tr>
                    <td class="font-extrabold text-slate-800">${p.codigo}</td>
                    <td>
                        <div class="font-black text-slate-800 text-xs">${p.nombre}</div>
                        <div class="text-[11px] text-slate-400">${p.categoria?.nombre || p.categoria_nombre || '-'}</div>
                    </td>
                    <td>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-bold text-xs" title="Ubicación física">
                            <i data-lucide="map-pin" class="w-3 h-3 text-slate-500"></i>
                            ${ubicacionTexto}
                        </span>
                    </td>
                    <td class="text-center">${renderColumnaFormatoVenta(p, stockFisico)}</td>
                    <td class="text-center font-black text-slate-800 text-xs">${stockFisico} u.</td>
                    <td class="text-center font-bold text-slate-400 text-xs">${comprometido} u.</td>
                    <td class="text-center font-black text-blue-700 bg-blue-50/40 text-xs">${disponibleVenta} u.</td>
                    <td class="text-center font-bold text-slate-600 text-xs">${stockMin} u.</td>
                    <td class="text-center">${semaforoBadge}</td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-1">
                            <button onclick="abrirModalIngresoRapido(${p.id_producto})" class="clay-btn-primary px-2 py-1 text-[11px] font-bold" title="Reponer Stock">Reponer</button>
                            <button onclick="abrirModalAjuste(${p.id_producto})" class="p-1 text-amber-600 hover:bg-amber-50 rounded" title="Ajuste Manual de Inventario">
                                <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                            </button>
                            <button onclick="abrirModalDevolucion(${p.id_producto})" class="p-1 text-purple-600 hover:bg-purple-50 rounded" title="Registrar Devolución">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        if (window.lucide) window.lucide.createIcons();
    }

    function actualizarKPIs() {
        const total = productosCache.length;
        const activos = productosCache.filter(p => p.estado === 'activo').length;
        const criticos = productosCache.filter(p => Number(p.stock ?? p.stock_disponible ?? 0) <= Number(p.stock_minimo || 5)).length;

        const elTotalCat = document.getElementById('kpiTotalCat');
        const elActivosCat = document.getElementById('kpiActivosCat');
        const elCriticosCat = document.getElementById('kpiCriticosCat');
        if (elTotalCat) elTotalCat.textContent = total;
        if (elActivosCat) elActivosCat.textContent = activos;
        if (elCriticosCat) elCriticosCat.textContent = criticos;

        // Stock KPIs (Pestaña 2)
        const totalUnidades = productosCache.reduce((acc, p) => acc + Number(p.stock ?? p.stock_disponible ?? 0), 0);
        const sufCount = productosCache.filter(p => Number(p.stock ?? p.stock_disponible ?? 0) > Number(p.stock_minimo || 5)).length;
        const bajoCritCount = productosCache.filter(p => {
            const s = Number(p.stock ?? p.stock_disponible ?? 0);
            const m = Number(p.stock_minimo || 5);
            return s > 0 && s <= m;
        }).length;
        const agotCount = productosCache.filter(p => Number(p.stock ?? p.stock_disponible ?? 0) === 0).length;

        const elKpiUnidades = document.getElementById('kpiStockTotalUnidades');
        const elKpiSuf = document.getElementById('kpiStockSuficiente');
        const elKpiBajo = document.getElementById('kpiStockBajoCritico');
        const elKpiAgot = document.getElementById('kpiStockAgotado');
        const badgeContador = document.getElementById('badgeContadorAlertasStock');

        if (elKpiUnidades) elKpiUnidades.textContent = totalUnidades.toLocaleString('es-AR');
        if (elKpiSuf) elKpiSuf.textContent = sufCount;
        if (elKpiBajo) elKpiBajo.textContent = bajoCritCount;
        if (elKpiAgot) elKpiAgot.textContent = agotCount;
        if (badgeContador) badgeContador.textContent = bajoCritCount + agotCount;

        renderTablaStockCompleto();

        // Render alertas pestaña Stock
        const tbodyAlertas = document.getElementById('tablaStockAlertasBody');
        const alertas = productosCache.filter(p => Number(p.stock ?? p.stock_disponible ?? 0) <= Number(p.stock_minimo || 5));
        const totalAlertasTag = document.getElementById('totalAlertasTag');
        if (totalAlertasTag) totalAlertasTag.textContent = `${alertas.length} productos`;

        if (tbodyAlertas) {
            if (alertas.length === 0) {
                tbodyAlertas.innerHTML = `<tr><td colspan="8" class="text-center py-6 text-emerald-600 font-bold">¡Inventario en orden! No hay productos bajo stock mínimo.</td></tr>`;
            } else {
                tbodyAlertas.innerHTML = alertas.map(p => {
                    const stockVal = Number(p.stock ?? p.stock_disponible ?? 0);
                    const minVal = Number(p.stock_minimo || 5);
                    const semaforoClass = stockVal === 0 ? 'clay-badge-danger' : 'clay-badge-danger';
                    const semaforoTxt = stockVal === 0 ? 'Agotado (0 u.)' : 'Crítico (≤ Mín)';
                    return `
                    <tr>
                        <td class="font-extrabold text-slate-800">${p.codigo}</td>
                        <td class="font-bold text-slate-800">${p.nombre}</td>
                        <td>${p.categoria?.nombre || p.categoria_nombre || '-'}</td>
                        <td class="text-center font-black text-rose-700">${formatoCantidadProducto(stockVal, p)}</td>
                        <td class="text-center font-bold text-slate-600">${formatoCantidadProducto(minVal, p)}</td>
                        <td class="text-center text-xs font-bold text-rose-600">-${formatoCantidadProductoCorta(Math.max(0, minVal - stockVal), p)}</td>
                        <td class="text-center"><span class="clay-badge ${semaforoClass}">${semaforoTxt}</span></td>
                        <td class="text-center">
                            <button onclick="abrirModalIngresoRapido(${p.id_producto})" class="clay-btn-primary px-3 py-1 text-[11px] font-bold">Reponer</button>
                        </td>
                    </tr>
                `;
                }).join('');
            }
        }
    }

    function poblarSelectsProductos() {
        const selectConteo = document.getElementById('conteoProductoSelect');
        const selectDev = document.getElementById('devProductoSelect');
        const modalSelectDev = document.getElementById('modalDevProductoSelect');
        const selectIngreso = document.getElementById('ingresoProductoSelect');
        const selectAjuste = document.getElementById('ajusteProductoSelect');

        const options = '<option value="">Seleccione un producto...</option>' + 
            productosCache.filter(p => p.estado === 'activo').map(p => `<option value="${p.id_producto}">${p.codigo} - ${p.nombre} (Stock: ${formatoCantidadProductoCorta(p.stock ?? p.stock_disponible ?? 0, p)})</option>`).join('');

        if (selectConteo) selectConteo.innerHTML = options;
        if (selectDev) selectDev.innerHTML = options;
        if (modalSelectDev) modalSelectDev.innerHTML = options;
        if (selectIngreso) selectIngreso.innerHTML = options;
        if (selectAjuste) selectAjuste.innerHTML = options;
        actualizarStockConteo();
    }

    async function cargarLotes() {}
    function renderTablaLotes() {}
    function mostrarLotesEnAlerta() {}

    let subtabMovimientosActual = 'stock';

    function cambiarSubtabMovimientos(tipo) {
        subtabMovimientosActual = tipo;
        const contStock = document.getElementById('contenedorMovimientosStock');
        const contPrecios = document.getElementById('contenedorHistorialPrecios');
        const contAuditoria = document.getElementById('contenedorMovimientosAuditoria');
        const btnStock = document.getElementById('btnSubtabMovStock');
        const btnPrecios = document.getElementById('btnSubtabMovPrecios');
        const btnAuditoria = document.getElementById('btnSubtabMovAuditoria');

        if (contStock) contStock.classList.add('hidden');
        if (contPrecios) contPrecios.classList.add('hidden');
        if (contAuditoria) contAuditoria.classList.add('hidden');

        if (btnStock) btnStock.className = 'px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition-all cursor-pointer font-bold';
        if (btnPrecios) btnPrecios.className = 'px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition-all cursor-pointer font-bold';
        if (btnAuditoria) btnAuditoria.className = 'px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition-all cursor-pointer font-bold';

        if (tipo === 'auditoria') {
            if (contAuditoria) contAuditoria.classList.remove('hidden');
            if (btnAuditoria) btnAuditoria.className = 'px-3 py-1.5 rounded-lg bg-white shadow-xs text-indigo-700 transition-all cursor-pointer font-black';
            cargarMovimientosAuditoria();
        } else if (tipo === 'precios') {
            if (contPrecios) contPrecios.classList.remove('hidden');
            if (btnPrecios) btnPrecios.className = 'px-3 py-1.5 rounded-lg bg-white shadow-xs text-blue-700 transition-all cursor-pointer font-black';
            cargarMovimientosPrecios();
        } else {
            if (contStock) contStock.classList.remove('hidden');
            if (btnStock) btnStock.className = 'px-3 py-1.5 rounded-lg bg-white shadow-xs text-blue-700 transition-all cursor-pointer font-black';
            cargarMovimientos();
        }
        if (window.lucide) window.lucide.createIcons();
    }

    function recargarHistorialActual() {
        if (subtabMovimientosActual === 'auditoria') {
            cargarMovimientosAuditoria();
        } else if (subtabMovimientosActual === 'precios') {
            cargarMovimientosPrecios();
        } else {
            cargarMovimientos();
        }
    }

    async function cargarMovimientosAuditoria() {
        const tbody = document.getElementById('tablaMovimientosAuditoriaBody');
        const selectProd = document.getElementById('filtroProdMovAuditoria');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-6 text-slate-400 font-bold">Cargando modificaciones de productos...</td></tr>';
        const prodId = selectProd?.value || '';

        if (selectProd && selectProd.options.length <= 1 && Array.isArray(productosCache)) {
            selectProd.innerHTML = '<option value="">Todos los Productos Modificados</option>' +
                productosCache.map(p => `<option value="${p.id_producto}">${p.codigo} - ${p.nombre}</option>`).join('');
            if (prodId) selectProd.value = prodId;
        }

        const url = `${API_BASE}/productos/auditoria${prodId ? '?id_producto=' + encodeURIComponent(prodId) : ''}`;
        try {
            const res = await fetch(url);
            const json = await res.json();
            const registros = json.data || [];
            if (registros.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-6 text-slate-400 font-bold">No se registran modificaciones de productos en la auditoría.</td></tr>';
                return;
            }
            tbody.innerHTML = registros.map(log => {
                const fecha = log.fecha_hora ? log.fecha_hora.substring(0, 16).replace('T', ' ') : '-';
                const accionBadge = log.accion === 'INSERT'
                    ? '<span class="clay-badge clay-badge-success font-black">ALTA</span>'
                    : log.accion === 'DELETE'
                    ? '<span class="clay-badge clay-badge-danger font-black">BAJA</span>'
                    : '<span class="clay-badge clay-badge-info font-black">MODIFICACIÓN</span>';

                let detalleHtml = '';
                if (Array.isArray(log.cambios) && log.cambios.length > 0) {
                    detalleHtml = log.cambios.map(c => `<div class="text-xs font-bold text-slate-700 py-0.5">• ${c}</div>`).join('');
                } else {
                    detalleHtml = `<span class="text-xs text-slate-500 font-bold">${log.cambios_resumen || 'Modificación registrada'}</span>`;
                }

                return `
                    <tr>
                        <td class="font-bold text-slate-600 text-xs">${fecha}</td>
                        <td class="font-black text-slate-800 text-xs">${log.producto_nombre || ('Producto #' + log.id_producto)}</td>
                        <td class="text-center">${accionBadge}</td>
                        <td>${detalleHtml}</td>
                        <td class="text-xs font-bold text-slate-600">${log.usuario_nombre || 'Sistema'}</td>
                    </tr>
                `;
            }).join('');
        } catch (e) {
            console.error('Error cargando auditoría de productos:', e);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-6 text-rose-500 font-bold">Error al cargar historial de modificaciones.</td></tr>';
        }
    }

    async function cargarMovimientos() {
        const tipo = document.getElementById('filtroTipoMovStock')?.value || '';
        const prodId = document.getElementById('filtroProdMovStock')?.value || '';
        const selectProd = document.getElementById('filtroProdMovStock');

        if (selectProd && selectProd.options.length <= 1 && Array.isArray(productosCache)) {
            selectProd.innerHTML = '<option value="">Todos los Productos</option>' +
                productosCache.map(p => `<option value="${p.id_producto}">${p.codigo} - ${p.nombre}</option>`).join('');
            if (prodId) selectProd.value = prodId;
        }

        const params = new URLSearchParams();
        if (tipo) params.append('tipo', tipo);
        if (prodId) params.append('id_producto', prodId);
        const url = `${API_BASE}/stock/movimientos${params.toString() ? '?' + params.toString() : ''}`;

        try {
            const res = await fetch(url);
            const json = await res.json();
            const movs = json.data || [];
            const tbody = document.getElementById('tablaMovimientosBody');
            if (!tbody) return;
            if (movs.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-6 text-slate-400 font-bold">No se registran movimientos para el filtro seleccionado.</td></tr>`;
                return;
            }
            tbody.innerHTML = movs.map(m => {
                const tipoBadge = m.tipo === 'ingreso' ? 'clay-badge-success' : (m.tipo === 'venta' ? 'clay-badge-info' : (m.tipo === 'devolucion' ? 'clay-badge-warning' : 'clay-badge-danger'));
                const unidadInfo = m.unidad ? ` (${m.unidad})` : '';
                return `
                    <tr>
                        <td class="font-bold text-slate-600 text-xs">${m.fecha ? m.fecha.substring(0, 16).replace('T', ' ') : '-'}</td>
                        <td><span class="clay-badge ${tipoBadge} uppercase">${m.tipo}</span></td>
                        <td class="font-black text-slate-800">${m.producto_nombre || `Producto #${m.id_producto}`}</td>
                        <td class="text-center font-black text-slate-700">${m.cantidad} u.${unidadInfo}</td>
                        <td class="text-xs text-slate-500 font-bold">${m.motivo || '-'}</td>
                        <td class="text-xs font-bold text-slate-600">${m.usuario_nombre || 'Sistema'}</td>
                    </tr>
                `;
            }).join('');
        } catch (e) { 
            console.error('Error movimientos', e); 
        }
    }

    async function cargarMovimientosPrecios() {
        const tbody = document.getElementById('tablaMovimientosPreciosBody');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-6 text-slate-400 font-bold">Cargando historial de precios...</td></tr>';
        const filtroOrigen = document.getElementById('filtroOrigenMovPrecios')?.value || '';
        const url = `${API_BASE}/productos/historial${filtroOrigen ? '?origen=' + encodeURIComponent(filtroOrigen) : ''}`;
        try {
            const res = await fetch(url);
            const json = await res.json();
            const registros = json.data || [];
            if (registros.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-6 text-slate-400 font-bold">No se registran aumentos o variaciones de precios en el historial.</td></tr>';
                return;
            }
            tbody.innerHTML = registros.map(h => {
                const fecha = h.fecha_cambio ? h.fecha_cambio.substring(0, 16).replace('T', ' ') : '-';
                const prodNombre = h.producto_nombre || (h.codigo_producto ? `${h.codigo_producto} - Producto #${h.id_producto}` : `Producto #${h.id_producto}`);
                const precioAnt = (h.precio_anterior !== null && h.precio_anterior !== undefined) ? `$${Number(h.precio_anterior).toFixed(2)}` : '-';
                const precioNuevo = `$${Number(h.precio_nuevo ?? h.precio).toFixed(2)}`;
                const pct = (h.porcentaje_aumento !== null && h.porcentaje_aumento !== undefined) ? Number(h.porcentaje_aumento) : null;
                const pctBadge = pct !== null ? (pct >= 0 ? `<span class="clay-badge clay-badge-success font-black">+${pct.toFixed(2)}%</span>` : `<span class="clay-badge clay-badge-danger font-black">${pct.toFixed(2)}%</span>`) : '<span class="text-slate-400">-</span>';
                const origenBadge = h.origen === 'aumento_masivo' 
                    ? '<span class="clay-badge clay-badge-info font-black">Aumento Masivo</span>' 
                    : '<span class="clay-badge clay-badge-warning font-black">Manual</span>';
                const usuario = h.usuario_nombre || 'Sistema';

                return `
                    <tr>
                        <td class="font-bold text-slate-600 text-xs">${fecha}</td>
                        <td class="font-black text-slate-800">${prodNombre}</td>
                        <td class="text-right font-bold text-slate-500">${precioAnt}</td>
                        <td class="text-right font-black text-slate-800">${precioNuevo}</td>
                        <td class="text-center">${pctBadge}</td>
                        <td class="text-center">${origenBadge}</td>
                        <td class="text-xs font-bold text-slate-600">${usuario}</td>
                    </tr>
                `;
            }).join('');
        } catch (e) {
            console.error('Error cargando historial de precios:', e);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-6 text-rose-500 font-bold">Error al cargar historial de precios.</td></tr>';
        }
    }

    function actualizarPreviewBultoProducto() {
        const formato = document.getElementById('prodFormatoBulto')?.value || 'Unidad';
        const unidades = Math.max(1, parseInt(document.getElementById('prodUnidadesPorBulto')?.value || '1', 10));
        const preview = document.getElementById('prodBultoPreview');
        if (!preview) return;

        if (formato !== 'Unidad' && unidades > 1) {
            preview.textContent = `💡 Presentación comercial: ${formato} x ${unidades} unidades individuales.`;
            preview.classList.remove('hidden');
        } else {
            preview.textContent = '';
            preview.classList.add('hidden');
        }
    }

    // --- ACCIONES Y MODALES ---
    function abrirModalProducto() {
        document.getElementById('modalProductoTitulo').textContent = 'Nuevo Producto';
        document.getElementById('prodId').value = '';
        document.getElementById('prodCodigo').value = '';
        document.getElementById('prodNombre').value = '';
        document.getElementById('prodDescripcion').value = '';
        document.getElementById('prodCategoria').value = '';
        document.getElementById('prodMarca').value = '';
        document.getElementById('prodPrecioUnitario').value = '';
        document.getElementById('prodStockMin').value = '10';
        document.getElementById('prodUbicacion').value = '';
        document.getElementById('prodFormatoBulto').value = 'Unidad';
        document.getElementById('prodUnidadesPorBulto').value = '1';
        actualizarPreviewBultoProducto();
        const btnHist = document.getElementById('btnVerHistorialDesdeModalProd');
        if (btnHist) btnHist.classList.add('hidden');
        document.getElementById('modalProducto').classList.remove('hidden');
        if (window.lucide) window.lucide.createIcons();
    }

    function editarProducto(id) {
        const prod = productosCache.find(p => p.id_producto === id);
        if (!prod) return;
        document.getElementById('modalProductoTitulo').textContent = 'Editar Producto';
        document.getElementById('prodId').value = prod.id_producto;
        document.getElementById('prodCodigo').value = prod.codigo;
        document.getElementById('prodNombre').value = prod.nombre;
        document.getElementById('prodDescripcion').value = prod.descripcion || '';
        document.getElementById('prodCategoria').value = prod.id_categoria || '';
        document.getElementById('prodMarca').value = prod.id_marca || '';
        document.getElementById('prodPrecioUnitario').value = prod.precio_unitario ?? prod.precio_minorista ?? '';
        document.getElementById('prodStockMin').value = prod.stock_minimo || 10;
        document.getElementById('prodUbicacion').value = prod.id_ubicacion || '';
        document.getElementById('prodFormatoBulto').value = prod.formato_bulto || 'Unidad';
        document.getElementById('prodUnidadesPorBulto').value = prod.unidades_por_bulto || 1;
        actualizarPreviewBultoProducto();
        const btnHist = document.getElementById('btnVerHistorialDesdeModalProd');
        if (btnHist) btnHist.classList.remove('hidden');
        document.getElementById('modalProducto').classList.remove('hidden');
        if (window.lucide) window.lucide.createIcons();
    }

    function abrirHistorialDesdeProducto() {
        const id = document.getElementById('prodId').value;
        if (!id) return;
        abrirHistorialPrecios(id, 'auditoria');
    }

    function cerrarModalProducto() {
        document.getElementById('modalProducto').classList.add('hidden');
    }

    async function guardarProducto(e) {
        e.preventDefault();
        const id = document.getElementById('prodId').value;
        const precioUnitario = Number(document.getElementById('prodPrecioUnitario').value);
        const formatoBulto = document.getElementById('prodFormatoBulto')?.value || 'Unidad';
        const unidadesPorBulto = Math.max(1, parseInt(document.getElementById('prodUnidadesPorBulto')?.value || '1', 10));

        const payload = {
            codigo: document.getElementById('prodCodigo').value.trim(),
            nombre: document.getElementById('prodNombre').value.trim(),
            descripcion: document.getElementById('prodDescripcion').value.trim(),
            id_categoria: document.getElementById('prodCategoria').value || null,
            id_marca: document.getElementById('prodMarca').value || null,
            precio_unitario: precioUnitario,
            precio_minorista: precioUnitario,
            precio_mayorista: precioUnitario,
            stock_minimo: Number(document.getElementById('prodStockMin').value || 10),
            id_ubicacion: document.getElementById('prodUbicacion').value || null,
            formato_bulto: formatoBulto,
            unidades_por_bulto: unidadesPorBulto
        };

        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const url = id ? `${API_BASE}/productos/${id}` : `${API_BASE}/productos`;
        const method = id ? 'PUT' : 'POST';

        try {
            const res = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                cerrarModalProducto();
                await cargarProductos();
            } else {
                alert(data.message || 'Error al guardar producto');
            }
        } catch (err) {
            alert('Error de conexión al guardar el producto');
        }
    }

    async function cambiarEstadoProducto(id, estadoActual) {
        const nuevoEstado = estadoActual === 'activo' ? 'inactivo' : 'activo';
        if (!confirm(`¿Desea cambiar el estado del producto a "${nuevoEstado}"?`)) return;

        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const accion = nuevoEstado === 'activo' ? 'activar' : 'desactivar';

        try {
            const res = await fetch(`${API_BASE}/productos/${id}/${accion}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            });
            const data = await res.json();
            if (data.status === 'success') {
                await cargarProductos();
            } else {
                alert(data.message || 'Error al modificar estado');
            }
        } catch (err) {
            alert('Error de conexión');
        }
    }

    // Modal Ingreso
    async function abrirModalIngreso(idProducto = null) {
        poblarSelectsProductos();
        document.getElementById('ingresoCantidad').value = '10';
        if (idProducto) {
            document.getElementById('ingresoProductoSelect').value = String(idProducto);
        } else {
            const selectProd = document.getElementById('ingresoProductoSelect');
            if (selectProd && selectProd.options.length > 1 && !selectProd.value) {
                selectProd.selectedIndex = 1;
            }
        }
        poblarUnidadesIngreso();
        actualizarEquivalenciaIngreso();
        document.getElementById('modalIngreso').classList.remove('hidden');
        if (window.lucide) window.lucide.createIcons();
    }

    function onProductoIngresoChange() {
        poblarUnidadesIngreso();
        actualizarEquivalenciaIngreso();
    }

    function poblarUnidadesIngreso() {
        const prodId = document.getElementById('ingresoProductoSelect')?.value;
        const selectUnidad = document.getElementById('ingresoUnidadSelect');
        if (selectUnidad) {
            selectUnidad.value = '';
        }

        const prod = productosCache.find(p => String(p.id_producto) === String(prodId));
        const ayuda = document.getElementById('ingresoPresentacionAyuda');
        if (ayuda) {
            if (prod && Number(prod.unidades_por_bulto || prod.equivalencia_unidad || 1) > 1) {
                const presTexto = obtenerTextoPresentacion(prod);
                ayuda.innerHTML = `<i data-lucide="package" class="w-3.5 h-3.5"></i> Presentación: ${presTexto} (el ingreso se carga en unidades físicas)`;
                ayuda.classList.remove('hidden');
            } else {
                ayuda.classList.add('hidden');
            }
        }
    }

    function actualizarEquivalenciaIngreso() {
        const prodId = document.getElementById('ingresoProductoSelect')?.value;
        const cant = Number(document.getElementById('ingresoCantidad')?.value || 0);
        const prod = productosCache.find(p => String(p.id_producto) === String(prodId));
        const box = document.getElementById('ingresoEquivalenciaBox');
        const texto = document.getElementById('ingresoEquivalenciaTexto');
        const lblCant = document.getElementById('lblIngresoCantidad');

        if (lblCant) lblCant.textContent = 'Cantidad a Ingresar (u.) *';

        const eq = Number(prod?.unidades_por_bulto || prod?.equivalencia_unidad || 1);
        if (prod && eq > 1 && cant > 0) {
            const presTexto = obtenerTextoPresentacion(prod);
            const bultos = (cant / eq).toLocaleString('es-AR', { maximumFractionDigits: 1 });
            if (box && texto) {
                texto.innerHTML = `Presentación: <strong>${presTexto}</strong>. ${cant} unidades equivalen a aprox. <strong>${bultos} bultos</strong>.`;
                box.classList.remove('hidden');
            }
        } else {
            if (box) box.classList.add('hidden');
        }
        if (window.lucide) window.lucide.createIcons();
    }

    function abrirModalIngresoRapido(idProducto) {
        abrirModalIngreso(idProducto);
    }

    function cerrarModalIngreso() { 
        document.getElementById('modalIngreso').classList.add('hidden'); 
    }

    async function guardarIngresoMercaderia(e) {
        e.preventDefault();
        const prodId = document.getElementById('ingresoProductoSelect').value;
        const provId = document.getElementById('ingresoProveedorSelect').value;
        const cant = Number(document.getElementById('ingresoCantidad').value);

        if (!prodId) {
            alert('Debe seleccionar un producto.');
            return;
        }
        if (!provId) {
            alert('Debe seleccionar un proveedor activo.');
            return;
        }
        const provObj = proveedoresCache.find(p => String(p.id_proveedor) === String(provId));
        if (provObj && provObj.estado === 'inactivo') {
            alert('El proveedor seleccionado está dado de baja y no puede ser seleccionado para operaciones.');
            return;
        }
        if (!cant || cant <= 0) {
            alert('La cantidad ingresada debe ser mayor a cero.');
            return;
        }

        const rawUnidad = document.getElementById('ingresoUnidadSelect')?.value;
        const idUnidad = (rawUnidad && Number(rawUnidad) > 0) ? Number(rawUnidad) : null;
        const info = obtenerInfoUnidadProducto(prodId);
        const esUnidadActiva = Boolean(idUnidad && info.tieneActiva && String(idUnidad) === String(info.id_unidad));
        const cantBase = esUnidadActiva ? Math.round(cant * info.equivalencia) : cant;

        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const item = {
            id_producto: Number(prodId),
            cantidad: cant,
            id_unidad: idUnidad,
            motivo: 'Ingreso directo de mercadería'
        };

        const payload = {
            id_proveedor: Number(provId),
            items: [item],
            // Compatibilidad directa
            id_producto: Number(prodId),
            cantidad: cant,
            id_unidad: idUnidad,
            motivo: 'Ingreso directo de mercadería'
        };

        try {
            const res = await fetch(`${API_BASE}/stock/ingresos`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                cerrarModalIngreso();
                await recargarTodo();
                const aclaracionEquiv = esUnidadActiva 
                    ? ` (equivalente a ${cantBase} unidades individuales)` 
                    : '';
                const unidadNom = esUnidadActiva ? ` ${info.nombre}` : ' u.';
                alert(`¡Ingreso registrado exitosamente!\nSe ingresaron ${cant}${unidadNom}${aclaracionEquiv} y se registró el movimiento en el historial (Kardex).`);
            } else {
                alert(data.message || 'Error al registrar ingreso');
            }
        } catch (err) {
            console.error('Error ingreso mercadería:', err);
            alert('Error de conexión al registrar ingreso.');
        }
    }

    function abrirModalNuevoLote() {}
    function cerrarModalNuevoLote() {}
    async function guardarNuevoLote(e) { if (e) e.preventDefault(); }

    // Modal Ajuste
    function onTipoAjusteChange() {
        const tipo = document.getElementById('ajusteTipo')?.value || 'aumentar';
        const labelCant = document.getElementById('ajusteCantidadLabel');
        if (labelCant) {
            labelCant.textContent = tipo === 'fijar' ? 'Nueva Cantidad Exacta' : 'Cantidad';
        }
    }

    async function abrirModalAjuste(prodId = null) {
        document.getElementById('modalAjuste').classList.remove('hidden');
        const sel = document.getElementById('ajusteProductoSelect');
        if (sel) {
            sel.innerHTML = '<option value="">Seleccione un producto...</option>' + 
                productosCache.filter(p => p.estado === 'activo').map(p => `<option value="${p.id_producto}">${p.codigo} - ${p.nombre} (Stock: ${formatoCantidadProductoCorta(p.stock ?? p.stock_disponible ?? 0, p)})</option>`).join('');
            if (prodId) {
                sel.value = String(prodId);
            }
        }
        poblarUnidadesAjuste();
        actualizarEquivalenciaAjuste();
        onTipoAjusteChange();
        if (window.lucide) window.lucide.createIcons();
    }

    function onProductoAjusteChange() {
        poblarUnidadesAjuste();
        actualizarEquivalenciaAjuste();
    }

    function poblarUnidadesAjuste() {
        const prodId = document.getElementById('ajusteProductoSelect')?.value;
        const selectUnidad = document.getElementById('ajusteUnidadSelect');
        if (!selectUnidad) return;

        const info = obtenerInfoUnidadProducto(prodId);
        let options = '<option value="">Unidades Individuales (u.)</option>';

        if (info.tieneActiva) {
            options = `<option value="${info.id_unidad}">${info.nombre} (Activa - Eq: ${info.equivalencia} u.)</option>` + options;
        }

        selectUnidad.innerHTML = options;
        if (info.tieneActiva) {
            selectUnidad.value = String(info.id_unidad);
        } else {
            selectUnidad.value = '';
        }
    }

    function actualizarEquivalenciaAjuste() {
        const prodId = document.getElementById('ajusteProductoSelect')?.value;
        const cant = Number(document.getElementById('ajusteCantidad')?.value || 0);
        const selectUnidad = document.getElementById('ajusteUnidadSelect');
        const box = document.getElementById('ajusteEquivalenciaBox');
        const texto = document.getElementById('ajusteEquivalenciaTexto');
        const lblCant = document.getElementById('ajusteCantidadLabel');
        const tipo = document.getElementById('ajusteTipo')?.value || 'aumentar';

        const info = obtenerInfoUnidadProducto(prodId);
        const idUnidadSeleccionada = selectUnidad?.value;

        if (idUnidadSeleccionada && info.tieneActiva && String(idUnidadSeleccionada) === String(info.id_unidad)) {
            const cantBase = Math.round(cant * info.equivalencia);
            if (lblCant) lblCant.textContent = tipo === 'fijar' ? `Fijar en (${info.nombre})` : `Cantidad (${info.nombre})`;
            if (box && texto) {
                texto.innerHTML = `Equivalencia: <strong>${cant} ${info.nombre}</strong> equivalen a <strong>${cantBase} unidades individuales (u.)</strong> para el ajuste.`;
                box.classList.remove('hidden');
            }
        } else {
            if (lblCant) lblCant.textContent = tipo === 'fijar' ? 'Nueva Cantidad Exacta' : 'Cantidad';
            if (box) box.classList.add('hidden');
        }
        if (window.lucide) window.lucide.createIcons();
    }

    function cerrarModalAjuste() { 
        document.getElementById('modalAjuste').classList.add('hidden'); 
    }

    async function guardarAjusteManual(e) {
        e.preventDefault();
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const prodId = document.getElementById('ajusteProductoSelect').value;
        const tipo = document.getElementById('ajusteTipo').value;
        const cantidad = Number(document.getElementById('ajusteCantidad').value);
        const motivo = document.getElementById('ajusteMotivo').value.trim();

        if (!prodId) {
            alert('Debe seleccionar un producto.');
            return;
        }

        if (isNaN(cantidad) || cantidad < 0) {
            alert('Debe indicar una cantidad válida mayor o igual a cero.');
            return;
        }

        if (!motivo) {
            alert('Debe indicar el motivo del ajuste.');
            return;
        }

        const rawUnidad = document.getElementById('ajusteUnidadSelect')?.value;
        const idUnidad = (rawUnidad && Number(rawUnidad) > 0) ? Number(rawUnidad) : null;

        const payload = {
            id_producto: Number(prodId),
            tipo: tipo,
            cantidad: cantidad,
            id_unidad: idUnidad,
            motivo: motivo
        };

        try {
            const res = await fetch(`${API_BASE}/stock/ajustes`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                alert(data.message || 'Ajuste de inventario registrado exitosamente.');
                cerrarModalAjuste();
                await recargarTodo();
            } else {
                alert(data.message || 'Error al aplicar ajuste');
            }
        } catch (err) {
            console.error('Error ajuste manual:', err);
            alert('Error de conexión al aplicar ajuste de inventario');
        }
    }

    function actualizarStockConteo() {
        const id = document.getElementById('conteoProductoSelect')?.value;
        const prod = productosCache.find(p => String(p.id_producto) === String(id));
        const inputSis = document.getElementById('conteoStockSistema');
        const stockActual = prod ? Number(prod.stock ?? prod.stock_disponible ?? 0) : 0;
        if (inputSis) inputSis.value = prod ? `${stockActual} u.` : '-';
        actualizarPreviewConteo();
    }

    function actualizarPreviewConteo() {
        const id = document.getElementById('conteoProductoSelect')?.value;
        const cantFisicaInput = document.getElementById('conteoCantidadFisica')?.value;
        const box = document.getElementById('conteoPreviewBox');
        const difSpan = document.getElementById('conteoPreviewDif');
        const diagEl = document.getElementById('conteoPreviewDiagnostico');
        if (!box || !difSpan || !diagEl) return;

        if (!id || cantFisicaInput === '' || cantFisicaInput === null || cantFisicaInput === undefined) {
            box.classList.add('hidden');
            return;
        }

        const prod = productosCache.find(p => String(p.id_producto) === String(id));
        if (!prod) {
            box.classList.add('hidden');
            return;
        }

        const stockSis = Number(prod.stock ?? prod.stock_disponible ?? 0);
        const cantFisica = Number(cantFisicaInput);
        const dif = cantFisica - stockSis;

        box.classList.remove('hidden');
        if (dif === 0) {
            difSpan.className = 'font-black text-sm text-emerald-600';
            difSpan.textContent = '0 u. (Coincidencia)';
            diagEl.className = 'text-xs font-extrabold text-emerald-700 pt-0.5';
            diagEl.textContent = '✓ El conteo físico coincide con el stock del sistema. Inventario conciliado.';
        } else if (dif > 0) {
            difSpan.className = 'font-black text-sm text-blue-600';
            difSpan.textContent = `+${dif} u. (Sobrante)`;
            diagEl.className = 'text-xs font-extrabold text-blue-700 pt-0.5';
            diagEl.textContent = `▲ Se detectó un sobrante de ${dif} unidad(es) física(s) respecto al stock teórico en sistema.`;
        } else {
            difSpan.className = 'font-black text-sm text-rose-600';
            difSpan.textContent = `${dif} u. (Faltante)`;
            diagEl.className = 'text-xs font-extrabold text-rose-700 pt-0.5';
            diagEl.textContent = `▼ Se detectó un faltante de ${Math.abs(dif)} unidad(es) física(s) respecto al stock teórico (inconsistencia).`;
        }
    }

    async function ejecutarConteoFisico(e) {
        e.preventDefault();
        const id = document.getElementById('conteoProductoSelect').value;
        const cantFisica = Number(document.getElementById('conteoCantidadFisica').value);
        const prod = productosCache.find(p => String(p.id_producto) === String(id));
        if (!prod) {
            alert('Seleccione un producto antes de procesar el conteo físico.');
            return;
        }

        const stockSis = Number(prod.stock ?? prod.stock_disponible ?? 0);
        const dif = cantFisica - stockSis;
        if (dif === 0) {
            alert('¡El conteo físico coincide exactamente con el stock en sistema! No se requiere ajuste.');
            return;
        }

        const tipo = dif > 0 ? 'aumentar' : 'disminuir';
        const obs = document.getElementById('conteoObs').value.trim();
        const motivo = `Conteo Físico: Stock anterior: ${stockSis} u., contaron ${cantFisica} u. (Dif: ${dif > 0 ? '+' : ''}${dif} u.). ${obs ? 'Obs: ' + obs : ''}`;
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const payload = {
            id_producto: Number(id),
            tipo,
            cantidad: Math.abs(dif),
            motivo: motivo
        };

        try {
            const res = await fetch(`${API_BASE}/stock/ajustes`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                alert(`¡Conteo físico procesado exitosamente!\n${prod.nombre} conciliado a ${cantFisica} u. (Diferencia ajustada: ${dif > 0 ? '+' : ''}${dif} u.)`);
                document.getElementById('conteoCantidadFisica').value = '';
                document.getElementById('conteoObs').value = '';
                actualizarStockConteo();
                await recargarTodo();
                await cargarHistorialConteosFisicos();
            } else {
                alert(data.message || 'Error al aplicar ajuste por conteo');
            }
        } catch (err) { 
            console.error('Error al conciliar conteo:', err);
            alert('Error al conciliar conteo'); 
        }
    }

    async function cargarHistorialConteosFisicos() {
        const tbody = document.getElementById('tablaHistorialConteosBody');
        if (!tbody) return;

        try {
            const res = await fetch(`${API_BASE}/stock/movimientos?tipo=ajuste`);
            const json = await res.json();
            const movs = (json.data || []).filter(m => (m.motivo || '').toLowerCase().includes('conteo') || m.tipo === 'ajuste');

            if (movs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-6 text-slate-400 font-bold">No se han registrado discrepancias ni conteos físicos recientemente.</td></tr>';
                return;
            }

            tbody.innerHTML = movs.map(m => {
                const fecha = m.fecha ? m.fecha.substring(0, 16).replace('T', ' ') : '-';
                const esSobrante = (m.motivo || '').includes('aumentar') || (m.motivo || '').includes('+');
                const badgeResultado = esSobrante 
                    ? '<span class="clay-badge clay-badge-info font-black">Sobrante</span>' 
                    : '<span class="clay-badge clay-badge-danger font-black">Faltante</span>';

                return `
                    <tr>
                        <td class="font-bold text-slate-600 text-xs">${fecha}</td>
                        <td class="font-black text-slate-800 text-xs">${m.producto_nombre || ('Producto #' + m.id_producto)}</td>
                        <td class="text-center font-bold text-slate-500 text-xs">-</td>
                        <td class="text-center font-black text-slate-700 text-xs">${m.cantidad} u.</td>
                        <td class="text-center font-black ${esSobrante ? 'text-blue-700' : 'text-rose-700'} text-xs">${esSobrante ? '+' : '-'}${m.cantidad} u.</td>
                        <td class="text-center">${badgeResultado}</td>
                        <td class="text-xs text-slate-600 font-bold">${m.motivo || 'Conciliación de inventario'}</td>
                        <td class="text-xs font-bold text-slate-600">${m.usuario_nombre || 'Sistema'}</td>
                    </tr>
                `;
            }).join('');
        } catch (e) {
            console.error('Error historial conteos:', e);
        }
    }

    function onTipoDevolucionChange(contexto = 'tab') {
        actualizarEquivalenciaDevolucion(contexto);
    }

    function onProductoDevolucionChange(contexto = 'tab') {
        poblarUnidadesDevolucion(contexto);
        actualizarEquivalenciaDevolucion(contexto);
    }

    function poblarUnidadesDevolucion(contexto = 'tab') {
        const prodId = contexto === 'modal' 
            ? document.getElementById('modalDevProductoSelect')?.value 
            : document.getElementById('devProductoSelect')?.value;
        const selectUnidad = contexto === 'modal'
            ? document.getElementById('modalDevUnidadSelect')
            : document.getElementById('devUnidadSelect');
        if (!selectUnidad) return;

        const info = obtenerInfoUnidadProducto(prodId);
        let options = '<option value="">Unidades Individuales (u.)</option>';

        if (info.tieneActiva) {
            options = `<option value="${info.id_unidad}">${info.nombre} (Activa - Eq: ${info.equivalencia} u.)</option>` + options;
        }

        selectUnidad.innerHTML = options;
        if (info.tieneActiva) {
            selectUnidad.value = String(info.id_unidad);
        } else {
            selectUnidad.value = '';
        }
    }

    function actualizarEquivalenciaDevolucion(contexto = 'tab') {
        const prodId = contexto === 'modal' 
            ? document.getElementById('modalDevProductoSelect')?.value 
            : document.getElementById('devProductoSelect')?.value;
        const cant = Number(contexto === 'modal'
            ? document.getElementById('modalDevCantidad')?.value || 0
            : document.getElementById('devCantidad')?.value || 0);
        const selectUnidad = contexto === 'modal'
            ? document.getElementById('modalDevUnidadSelect')
            : document.getElementById('devUnidadSelect');
        const box = contexto === 'modal'
            ? document.getElementById('modalDevEquivalenciaBox')
            : document.getElementById('devEquivalenciaBox');
        const texto = contexto === 'modal'
            ? document.getElementById('modalDevEquivalenciaTexto')
            : document.getElementById('devEquivalenciaTexto');
        const lblCant = contexto === 'modal'
            ? document.getElementById('lblModalDevCantidad')
            : document.getElementById('lblDevCantidad');
        const tipoDev = contexto === 'modal'
            ? document.getElementById('modalDevTipo')?.value
            : document.getElementById('devTipo')?.value;

        const info = obtenerInfoUnidadProducto(prodId);
        const idUnidadSeleccionada = selectUnidad?.value;

        if (idUnidadSeleccionada && info.tieneActiva && String(idUnidadSeleccionada) === String(info.id_unidad)) {
            const cantBase = Math.round(cant * info.equivalencia);
            if (lblCant) lblCant.textContent = `Cantidad (${info.nombre})`;
            if (box && texto) {
                const accionTxt = tipoDev === 'proveedor' ? 'descontarán del' : 'repondrán al';
                texto.innerHTML = `Equivalencia: <strong>${cant} ${info.nombre}</strong> equivalen a <strong>${cantBase} unidades individuales (u.)</strong> que se ${accionTxt} inventario.`;
                box.classList.remove('hidden');
            }
        } else {
            if (lblCant) lblCant.textContent = contexto === 'modal' ? 'Cantidad' : 'Cantidad Devuelta';
            if (box) box.classList.add('hidden');
        }
        if (window.lucide) window.lucide.createIcons();
    }

    async function actualizarLotesDevolucion(contexto = 'tab') {}
    function onLoteDevolucionChange(contexto = 'tab') {}

    async function ejecutarDevolucion(e, contexto = 'tab') {
        e.preventDefault();
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        const prodId = contexto === 'modal'
            ? document.getElementById('modalDevProductoSelect')?.value
            : document.getElementById('devProductoSelect')?.value;
        const tipoDev = contexto === 'modal'
            ? document.getElementById('modalDevTipo')?.value
            : document.getElementById('devTipo')?.value;
        const cant = contexto === 'modal'
            ? Number(document.getElementById('modalDevCantidad')?.value)
            : Number(document.getElementById('devCantidad')?.value);
        const motivo = contexto === 'modal'
            ? document.getElementById('modalDevMotivo')?.value.trim()
            : document.getElementById('devMotivo')?.value.trim();

        if (!prodId) {
            alert('Debe seleccionar el producto devuelto.');
            return;
        }

        const rawUnidad = contexto === 'modal'
            ? document.getElementById('modalDevUnidadSelect')?.value
            : document.getElementById('devUnidadSelect')?.value;
        const idUnidad = (rawUnidad && Number(rawUnidad) > 0) ? Number(rawUnidad) : null;

        const payload = {
            id_producto: Number(prodId),
            tipo_devolucion: tipoDev,
            cantidad: cant,
            id_unidad: idUnidad,
            motivo: motivo
        };

        try {
            const res = await fetch(`${API_BASE}/stock/devoluciones`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                alert(data.message || 'Devolución registrada correctamente en el stock.');
                await recargarTodo();
                if (contexto === 'modal') {
                    cerrarModalDevolucion();
                } else {
                    document.getElementById('devMotivo').value = '';
                }
            } else {
                alert(data.message || 'Error en devolución');
            }
        } catch (err) { alert('Error de conexión'); }
    }

    // Modal Aumento Masivo
    function abrirModalAumentoMasivo() {
        document.getElementById('modalAumento').classList.remove('hidden');
        const selCat = document.getElementById('aumentoCategoriaSelect');
        const selMarca = document.getElementById('aumentoMarcaSelect');
        if (selCat) selCat.innerHTML = categoriasCache.map(c => `<option value="${c.id_categoria}">${c.nombre}</option>`).join('');
        if (selMarca) selMarca.innerHTML = marcasCache.map(m => `<option value="${m.id_marca}">${m.nombre}</option>`).join('');
        const inputPct = document.getElementById('aumentoPorcentaje');
        if (!inputPct.value) inputPct.value = '10.0';
        actualizarCriterioAumento();
        actualizarVistaPreviaAumento();
        if (window.lucide) window.lucide.createIcons();
    }

    function cerrarModalAumentoMasivo() { 
        document.getElementById('modalAumento').classList.add('hidden'); 
    }

    function actualizarCriterioAumento() {
        const crit = document.getElementById('aumentoCriterio').value;
        document.getElementById('aumentoCategoriaGrupo').classList.toggle('hidden', crit !== 'categoria');
        document.getElementById('aumentoMarcaGrupo').classList.toggle('hidden', crit !== 'marca');
        actualizarVistaPreviaAumento();
    }

    function actualizarVistaPreviaAumento() {
        const crit = document.getElementById('aumentoCriterio')?.value || 'todos';
        const pctInput = document.getElementById('aumentoPorcentaje')?.value;
        const pct = Number(pctInput) || 0;
        const tbody = document.getElementById('aumentoPreviewBody');
        const countSpan = document.getElementById('aumentoPreviewCount');
        if (!tbody) return;

        let prods = (Array.isArray(productosCache) ? productosCache : []).filter(p => p.estado === 'activo');
        if (crit === 'categoria') {
            const catId = document.getElementById('aumentoCategoriaSelect')?.value;
            prods = prods.filter(p => String(p.id_categoria) === String(catId));
        } else if (crit === 'marca') {
            const marcaId = document.getElementById('aumentoMarcaSelect')?.value;
            prods = prods.filter(p => String(p.id_marca) === String(marcaId));
        }

        if (countSpan) countSpan.textContent = `${prods.length} artículo(s)`;

        if (prods.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-slate-400 font-bold">No hay productos activos para el criterio seleccionado.</td></tr>';
            return;
        }

        tbody.innerHTML = prods.slice(0, 100).map(p => {
            const actual = Number(p.precio_unitario || 0);
            const nuevo = pct > 0 ? (Math.round((actual * (1 + pct / 100)) * 100) / 100).toFixed(2) : actual.toFixed(2);
            return `
                <tr>
                    <td class="font-extrabold text-slate-700 text-xs">${p.codigo}</td>
                    <td class="font-bold text-slate-800 text-xs">${p.nombre}</td>
                    <td class="text-right font-bold text-slate-500 text-xs">$${actual.toFixed(2)}</td>
                    <td class="text-right font-black text-emerald-700 text-xs">$${nuevo}</td>
                </tr>
            `;
        }).join('');
    }

    async function aplicarAumentoMasivo(e) {
        e.preventDefault();
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const crit = document.getElementById('aumentoCriterio').value;
        const porcentaje = Number(document.getElementById('aumentoPorcentaje').value);

        if (!porcentaje || porcentaje <= 0) {
            alert('Debe ingresar un porcentaje mayor a 0');
            return;
        }

        const payload = {
            tipo_criterio: crit,
            porcentaje: porcentaje,
            confirmar: true
        };
        if (crit === 'categoria') payload.id_categoria = document.getElementById('aumentoCategoriaSelect').value;
        if (crit === 'marca') payload.id_marca = document.getElementById('aumentoMarcaSelect').value;

        const btn = document.getElementById('btnConfirmarAumento');
        if (btn) btn.disabled = true;

        try {
            const res = await fetch(`${API_BASE}/productos/aumento-masivo`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                cerrarModalAumentoMasivo();
                alert(`¡Aumento masivo del ${porcentaje}% aplicado exitosamente!\nLos nuevos precios y la variación se registraron en el Historial de Precios y Auditoría.`);
                await cargarProductos();
                cargarMovimientosPrecios();
            } else {
                alert(data.message || 'Error al aplicar aumento masivo');
            }
        } catch (err) {
            console.error('Error aumento masivo:', err);
            alert('Error de conexión al aplicar el aumento masivo.');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    // Modal Historial de Precios y Modificaciones (P08 / P04)
    let modalHistTabActual = 'precios';

    function poblarSelectHistorialProductos(idSeleccionado = '') {
        const selectProd = document.getElementById('historialFiltroProducto');
        if (!selectProd) return;
        let html = '<option value="">Todos los productos (Aumentos Masivos y Cambios)</option>';
        html += (Array.isArray(productosCache) ? productosCache : []).map(p => `
            <option value="${p.id_producto}" ${String(p.id_producto) === String(idSeleccionado) ? 'selected' : ''}>
                ${p.codigo} - ${p.nombre}
            </option>
        `).join('');
        selectProd.innerHTML = html;
        if (idSeleccionado) selectProd.value = String(idSeleccionado);
    }

    function cambiarModalHistTab(tab) {
        modalHistTabActual = tab;
        const btnPrecios = document.getElementById('modalHistTabBtn-precios');
        const btnAuditoria = document.getElementById('modalHistTabBtn-auditoria');
        const contPrecios = document.getElementById('contenedorModalHistPrecios');
        const contAuditoria = document.getElementById('contenedorModalHistAuditoria');
        const filtroOrigenGrupo = document.getElementById('historialFiltroOrigenGrupo');
        const selectProd = document.getElementById('historialFiltroProducto');
        const prodId = selectProd ? selectProd.value : '';

        if (tab === 'auditoria') {
            if (btnPrecios) {
                btnPrecios.className = 'px-3.5 py-1.5 rounded-xl font-bold text-xs text-slate-600 hover:text-slate-900 transition-all cursor-pointer flex items-center gap-1.5';
            }
            if (btnAuditoria) {
                btnAuditoria.className = 'px-3.5 py-1.5 rounded-xl font-black text-xs bg-white text-blue-600 shadow-xs border border-slate-200/80 transition-all cursor-pointer flex items-center gap-1.5';
            }
            if (contPrecios) contPrecios.classList.add('hidden');
            if (contAuditoria) contAuditoria.classList.remove('hidden');
            if (filtroOrigenGrupo) filtroOrigenGrupo.classList.add('hidden');
            actualizarTituloHistorialModal(prodId);
            cargarHistorialAuditoriaModal();
        } else {
            if (btnPrecios) {
                btnPrecios.className = 'px-3.5 py-1.5 rounded-xl font-black text-xs bg-white text-blue-600 shadow-xs border border-slate-200/80 transition-all cursor-pointer flex items-center gap-1.5';
            }
            if (btnAuditoria) {
                btnAuditoria.className = 'px-3.5 py-1.5 rounded-xl font-bold text-xs text-slate-600 hover:text-slate-900 transition-all cursor-pointer flex items-center gap-1.5';
            }
            if (contPrecios) contPrecios.classList.remove('hidden');
            if (contAuditoria) contAuditoria.classList.add('hidden');
            if (filtroOrigenGrupo) filtroOrigenGrupo.classList.remove('hidden');
            actualizarTituloHistorialModal(prodId);
            cargarHistorialPreciosModal();
        }
        if (window.lucide) window.lucide.createIcons();
    }

    function actualizarTituloHistorialModal(prodId) {
        const prod = prodId ? (Array.isArray(productosCache) ? productosCache : []).find(p => String(p.id_producto) === String(prodId)) : null;
        const tituloEl = document.getElementById('historialProductoTitulo');
        if (!tituloEl) return;
        if (prod) {
            tituloEl.textContent = modalHistTabActual === 'auditoria'
                ? `Modificaciones y Auditoría: ${prod.codigo} - ${prod.nombre}`
                : `Historial de Precios: ${prod.codigo} - ${prod.nombre}`;
        } else {
            tituloEl.textContent = modalHistTabActual === 'auditoria'
                ? 'Historial Global de Modificaciones y Auditoría de Productos'
                : 'Historial Global de Precios y Aumentos Masivos';
        }
    }

    function abrirHistorialPreciosGlobal(tab = 'precios') {
        poblarSelectHistorialProductos('');
        const filtroOrigen = document.getElementById('historialFiltroOrigen');
        if (filtroOrigen) filtroOrigen.value = '';
        document.getElementById('modalHistorial').classList.remove('hidden');
        cambiarModalHistTab(tab);
    }

    async function abrirHistorialPrecios(id, tab = 'precios') {
        poblarSelectHistorialProductos(id);
        const filtroOrigen = document.getElementById('historialFiltroOrigen');
        if (filtroOrigen) filtroOrigen.value = '';
        document.getElementById('modalHistorial').classList.remove('hidden');
        cambiarModalHistTab(tab);
    }

    function cambiarFiltroHistorialModal() {
        const selectProd = document.getElementById('historialFiltroProducto');
        const prodId = selectProd ? selectProd.value : '';
        actualizarTituloHistorialModal(prodId);
        if (modalHistTabActual === 'auditoria') {
            cargarHistorialAuditoriaModal();
        } else {
            cargarHistorialPreciosModal();
        }
    }

    async function cargarHistorialAuditoriaModal() {
        const selectProd = document.getElementById('historialFiltroProducto');
        const prodId = selectProd ? selectProd.value : '';
        const tbody = document.getElementById('historialAuditoriaBody');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-slate-400 font-bold">Cargando modificaciones de productos...</td></tr>';

        let url = `${API_BASE}/productos/auditoria`;
        if (prodId) url += `?id_producto=${encodeURIComponent(prodId)}`;

        try {
            const res = await fetch(url);
            const json = await res.json();
            const registros = json.data || [];
            if (registros.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-slate-400 font-bold">Sin modificaciones registradas para la selección actual.</td></tr>';
                return;
            }
            tbody.innerHTML = registros.map(log => {
                const fecha = log.fecha_hora ? log.fecha_hora.substring(0, 16).replace('T', ' ') : '-';
                const accionBadge = log.accion === 'INSERT'
                    ? '<span class="clay-badge clay-badge-success font-black">ALTA</span>'
                    : log.accion === 'DELETE'
                    ? '<span class="clay-badge clay-badge-danger font-black">BAJA</span>'
                    : '<span class="clay-badge clay-badge-info font-black">MODIFICACIÓN</span>';

                let detalleHtml = '';
                if (Array.isArray(log.cambios) && log.cambios.length > 0) {
                    detalleHtml = log.cambios.map(c => `<div class="text-xs font-bold text-slate-700 py-0.5">• ${c}</div>`).join('');
                } else {
                    detalleHtml = `<span class="text-xs text-slate-500 font-bold">${log.cambios_resumen || 'Modificación registrada'}</span>`;
                }

                return `
                    <tr>
                        <td class="font-bold text-slate-600 text-xs">${fecha}</td>
                        <td class="font-black text-slate-800 text-xs">${log.producto_nombre || ('Producto #' + log.id_producto)}</td>
                        <td class="text-center">${accionBadge}</td>
                        <td>${detalleHtml}</td>
                        <td class="text-xs font-bold text-slate-600">${log.usuario_nombre || 'Sistema'}</td>
                    </tr>
                `;
            }).join('');
        } catch (e) {
            console.error('Error al cargar auditoría modal:', e);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-rose-500 font-bold">Error al cargar historial de modificaciones.</td></tr>';
        }
    }

    async function cargarHistorialPreciosModal() {
        const selectProd = document.getElementById('historialFiltroProducto');
        const selectOrigen = document.getElementById('historialFiltroOrigen');
        const prodId = selectProd ? selectProd.value : '';
        const origen = selectOrigen ? selectOrigen.value : '';

        const tbody = document.getElementById('historialPreciosBody');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-slate-400">Cargando historial de precios...</td></tr>';

        let url = `${API_BASE}/productos/historial`;
        const params = new URLSearchParams();
        if (prodId) params.append('id_producto', prodId);
        if (origen) params.append('origen', origen);
        if (params.toString()) url += `?${params.toString()}`;

        try {
            const res = await fetch(url);
            const json = await res.json();
            const lista = json.data || [];
            if (lista.length > 0) {
                tbody.innerHTML = lista.map(h => {
                    const fecha = h.fecha_cambio ? h.fecha_cambio.substring(0, 16).replace('T', ' ') : '-';
                    const prodNombre = h.producto_nombre || (h.codigo_producto ? `${h.codigo_producto} - Producto #${h.id_producto}` : `Producto #${h.id_producto}`);
                    const precioAnt = (h.precio_anterior !== null && h.precio_anterior !== undefined) ? `$${Number(h.precio_anterior).toFixed(2)}` : '-';
                    const precioNuevo = `$${Number(h.precio_nuevo ?? h.precio).toFixed(2)}`;
                    const pct = (h.porcentaje_aumento !== null && h.porcentaje_aumento !== undefined) ? Number(h.porcentaje_aumento) : null;
                    const pctBadge = pct !== null ? (pct >= 0 ? `<span class="clay-badge clay-badge-success font-black">+${pct.toFixed(2)}%</span>` : `<span class="clay-badge clay-badge-danger font-black">${pct.toFixed(2)}%</span>`) : '<span class="text-slate-400">-</span>';
                    const origenBadge = h.origen === 'aumento_masivo' 
                        ? '<span class="clay-badge clay-badge-info font-black">Aumento Masivo</span>' 
                        : '<span class="clay-badge clay-badge-warning font-black">Manual</span>';
                    const usuario = h.usuario_nombre || 'Sistema';

                    return `
                        <tr>
                            <td class="text-xs text-slate-600 font-bold">${fecha}</td>
                            <td class="font-extrabold text-slate-800 text-xs">${prodNombre}</td>
                            <td class="text-right font-bold text-slate-500">${precioAnt}</td>
                            <td class="text-right font-black text-slate-800">${precioNuevo}</td>
                            <td class="text-center">${pctBadge}</td>
                            <td class="text-center">${origenBadge}</td>
                            <td class="text-xs text-slate-600 font-bold">${usuario}</td>
                        </tr>
                    `;
                }).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-slate-400 font-bold">Sin historial de cambios registrado para la selección actual.</td></tr>';
            }
        } catch (e) {
            console.error('Error al cargar historial modal:', e);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-rose-500 font-bold">Error al cargar historial.</td></tr>';
        }
    }

    function cerrarModalHistorial() { 
        document.getElementById('modalHistorial').classList.add('hidden'); 
    }

    // Modal Categorías
    function abrirModalCategorias() {
        cancelarEdicionCategoria();
        document.getElementById('modalCategoria').classList.remove('hidden');
        renderListaCategoriasModal();
        if (window.lucide) window.lucide.createIcons();
    }
    function abrirModalCategoriasMarcas() {
        abrirModalCategorias();
    }
    function cerrarModalCategorias() { 
        document.getElementById('modalCategoria').classList.add('hidden'); 
    }
    function editarCategoria(id, nombre) {
        document.getElementById('catId').value = id;
        document.getElementById('catNombre').value = nombre;
        document.getElementById('btnGuardarCategoria').querySelector('span').textContent = 'Guardar Cambios';
        document.getElementById('btnCancelarEdicionCat').classList.remove('hidden');
        document.getElementById('catNombre').focus();
    }
    function cancelarEdicionCategoria() {
        document.getElementById('catId').value = '';
        document.getElementById('catNombre').value = '';
        document.getElementById('btnGuardarCategoria').querySelector('span').textContent = 'Guardar Categoría';
        document.getElementById('btnCancelarEdicionCat').classList.add('hidden');
    }
    function renderListaCategoriasModal() {
        const tbody = document.getElementById('tablaCategoriasBody');
        if (!categoriasCache || categoriasCache.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-slate-400 font-bold">No hay categorías registradas.</td></tr>';
            return;
        }
        tbody.innerHTML = categoriasCache.map(c => `
            <tr>
                <td class="font-bold text-slate-600">${c.id_categoria}</td>
                <td class="font-black text-slate-800">${c.nombre}</td>
                <td class="text-center"><span class="clay-badge clay-badge-info">${c.total_productos ?? 0} prod.</span></td>
                <td class="text-center">
                    <div class="flex items-center justify-center gap-1">
                        <button type="button" onclick="editarCategoria(${c.id_categoria}, '${c.nombre.replace(/'/g, "\\'")}')" class="p-1 text-blue-600 hover:bg-blue-50 rounded" title="Editar Categoría">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                        </button>
                        <button type="button" onclick="eliminarCategoria(${c.id_categoria}, '${c.nombre.replace(/'/g, "\\'")}', ${c.total_productos ?? 0})" class="p-1 text-rose-600 hover:bg-rose-50 rounded" title="Dar de Baja / Eliminar Categoría">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
        if (window.lucide) window.lucide.createIcons();
    }
    async function guardarCategoria(e) {
        e.preventDefault();
        const id = document.getElementById('catId').value;
        const nombre = document.getElementById('catNombre').value.trim();
        if (!nombre) { alert('El nombre de la categoría es obligatorio.'); return; }

        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const url = id ? `${API_BASE}/categorias/${id}` : `${API_BASE}/categorias`;
        const method = id ? 'PUT' : 'POST';

        try {
            const res = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ nombre })
            });
            const data = await res.json();
            if (data.status === 'success') {
                cancelarEdicionCategoria();
                await cargarCategorias();
                renderListaCategoriasModal();
                alert(id ? 'Categoría actualizada exitosamente.' : 'Categoría creada exitosamente.');
            } else { alert(data.message || 'Error al guardar categoría'); }
        } catch (e) { alert('Error de conexión al guardar categoría'); }
    }
    async function eliminarCategoria(id, nombre, totalProductos) {
        if (totalProductos > 0) {
            alert(`No se puede dar de baja la categoría "${nombre}" porque está siendo utilizada por ${totalProductos} producto(s).`);
            return;
        }
        if (!confirm(`¿Confirma dar de baja / eliminar la categoría "${nombre}"?`)) return;

        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        try {
            const res = await fetch(`${API_BASE}/categorias/${id}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            });
            const data = await res.json();
            if (data.status === 'success') {
                await cargarCategorias();
                renderListaCategoriasModal();
                alert(`Categoría "${nombre}" eliminada exitosamente.`);
            } else { alert(data.message || 'Error al eliminar categoría'); }
        } catch (e) { alert('Error de conexión al eliminar categoría'); }
    }

    // Modal Marcas
    function abrirModalMarcas() {
        cancelarEdicionMarca();
        document.getElementById('modalMarca').classList.remove('hidden');
        renderListaMarcasModal();
        if (window.lucide) window.lucide.createIcons();
    }
    function cerrarModalMarcas() { 
        document.getElementById('modalMarca').classList.add('hidden'); 
    }
    function editarMarca(id, nombre) {
        document.getElementById('marcaId').value = id;
        document.getElementById('marcaNombre').value = nombre;
        document.getElementById('btnGuardarMarca').querySelector('span').textContent = 'Guardar Cambios';
        document.getElementById('btnCancelarEdicionMarca').classList.remove('hidden');
        document.getElementById('marcaNombre').focus();
    }
    function cancelarEdicionMarca() {
        document.getElementById('marcaId').value = '';
        document.getElementById('marcaNombre').value = '';
        document.getElementById('btnGuardarMarca').querySelector('span').textContent = 'Guardar Marca';
        document.getElementById('btnCancelarEdicionMarca').classList.add('hidden');
    }
    function renderListaMarcasModal() {
        const tbody = document.getElementById('tablaMarcasBody');
        if (!marcasCache || marcasCache.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-slate-400 font-bold">No hay marcas registradas.</td></tr>';
            return;
        }
        tbody.innerHTML = marcasCache.map(m => `
            <tr>
                <td class="font-bold text-slate-600">${m.id_marca}</td>
                <td class="font-black text-slate-800">${m.nombre}</td>
                <td class="text-center"><span class="clay-badge clay-badge-info">${m.total_productos ?? 0} prod.</span></td>
                <td class="text-center">
                    <div class="flex items-center justify-center gap-1">
                        <button type="button" onclick="editarMarca(${m.id_marca}, '${m.nombre.replace(/'/g, "\\'")}')" class="p-1 text-blue-600 hover:bg-blue-50 rounded" title="Editar Marca">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                        </button>
                        <button type="button" onclick="eliminarMarca(${m.id_marca}, '${m.nombre.replace(/'/g, "\\'")}', ${m.total_productos ?? 0})" class="p-1 text-rose-600 hover:bg-rose-50 rounded" title="Dar de Baja / Eliminar Marca">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
        if (window.lucide) window.lucide.createIcons();
    }
    async function guardarMarca(e) {
        e.preventDefault();
        const id = document.getElementById('marcaId').value;
        const nombre = document.getElementById('marcaNombre').value.trim();
        if (!nombre) { alert('El nombre de la marca es obligatorio.'); return; }

        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const url = id ? `${API_BASE}/marcas/${id}` : `${API_BASE}/marcas`;
        const method = id ? 'PUT' : 'POST';

        try {
            const res = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ nombre })
            });
            const data = await res.json();
            if (data.status === 'success') {
                cancelarEdicionMarca();
                await cargarMarcas();
                renderListaMarcasModal();
                alert(id ? 'Marca actualizada exitosamente.' : 'Marca creada exitosamente.');
            } else { alert(data.message || 'Error al guardar marca'); }
        } catch (e) { alert('Error de conexión al guardar marca'); }
    }
    async function eliminarMarca(id, nombre, totalProductos) {
        if (totalProductos > 0) {
            alert(`No se puede dar de baja la marca "${nombre}" porque está siendo utilizada por ${totalProductos} producto(s).`);
            return;
        }
        if (!confirm(`¿Confirma dar de baja / eliminar la marca "${nombre}"?`)) return;

        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        try {
            const res = await fetch(`${API_BASE}/marcas/${id}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            });
            const data = await res.json();
            if (data.status === 'success') {
                await cargarMarcas();
                renderListaMarcasModal();
                alert(`Marca "${nombre}" eliminada exitosamente.`);
            } else { alert(data.message || 'Error al eliminar marca'); }
        } catch (e) { alert('Error de conexión al eliminar marca'); }
    }

    // ==========================================
    // MODAL: GESTIÓN DE UBICACIONES
    // ==========================================
    function abrirModalUbicaciones() {
        cancelarEdicionUbicacion();
        cerrarDetalleProductosUbicacion();
        document.getElementById('modalUbicaciones').classList.remove('hidden');
        renderListaUbicacionesModal();
        if (window.lucide) window.lucide.createIcons();
    }

    function cerrarModalUbicaciones() {
        document.getElementById('modalUbicaciones').classList.add('hidden');
    }

    function editarUbicacion(id, descripcion) {
        document.getElementById('ubicacionId').value = id;
        document.getElementById('ubicacionDescripcion').value = descripcion;
        document.getElementById('btnGuardarUbicacion').querySelector('span').textContent = 'Guardar Cambios';
        document.getElementById('btnCancelarEdicionUbicacion').classList.remove('hidden');
        document.getElementById('ubicacionDescripcion').focus();
    }

    function cancelarEdicionUbicacion() {
        document.getElementById('ubicacionId').value = '';
        document.getElementById('ubicacionDescripcion').value = '';
        document.getElementById('btnGuardarUbicacion').querySelector('span').textContent = 'Crear Ubicación';
        document.getElementById('btnCancelarEdicionUbicacion').classList.add('hidden');
    }

    function renderListaUbicacionesModal() {
        const tbody = document.getElementById('tablaUbicacionesBody');
        const tagTotal = document.getElementById('totalUbicacionesTag');
        if (tagTotal) tagTotal.textContent = `${ubicacionesCache.length} ubicaciones`;
        if (!tbody) return;

        if (!ubicacionesCache || ubicacionesCache.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-slate-400 font-bold">No hay ubicaciones configuradas actualmente.</td></tr>';
            return;
        }

        tbody.innerHTML = ubicacionesCache.map(u => {
            const count = Number(u.productos_count ?? 0);
            return `
                <tr>
                    <td class="font-bold text-slate-600 text-xs">${u.id_ubicacion}</td>
                    <td class="font-black text-slate-800 text-xs">${u.descripcion}</td>
                    <td class="text-center">
                        <span class="clay-badge ${count > 0 ? 'clay-badge-info' : 'clay-badge-warning'} font-bold text-[11px]">${count} prod.</span>
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-1">
                            <button type="button" onclick="verProductosUbicacion(${u.id_ubicacion}, '${u.descripcion.replace(/'/g, "\\'")}')" class="p-1 text-emerald-600 hover:bg-emerald-50 rounded" title="Ver productos en esta ubicación">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            </button>
                            <button type="button" onclick="editarUbicacion(${u.id_ubicacion}, '${u.descripcion.replace(/'/g, "\\'")}')" class="p-1 text-blue-600 hover:bg-blue-50 rounded" title="Editar Ubicación">
                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                            </button>
                            <button type="button" onclick="eliminarUbicacion(${u.id_ubicacion}, '${u.descripcion.replace(/'/g, "\\'")}', ${count})" class="p-1 text-rose-600 hover:bg-rose-50 rounded" title="Dar de baja / eliminar ubicación">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
        if (window.lucide) window.lucide.createIcons();
    }

    function verProductosUbicacion(idUbicacion, nombreUbicacion) {
        const box = document.getElementById('boxProductosUbicacion');
        const titulo = document.getElementById('tituloProductosUbicacion');
        const lista = document.getElementById('listaProductosUbicacion');
        if (!box || !titulo || !lista) return;

        const prods = (Array.isArray(productosCache) ? productosCache : []).filter(p => Number(p.id_ubicacion) === Number(idUbicacion));
        titulo.textContent = `Productos en "${nombreUbicacion}" (${prods.length} artículo(s)):`;
        if (prods.length === 0) {
            lista.innerHTML = '<li class="text-slate-400 py-1 font-bold">No hay productos asignados a esta ubicación.</li>';
        } else {
            lista.innerHTML = prods.map(p => `
                <li class="py-1 flex items-center justify-between">
                    <span class="font-black text-slate-800">${p.codigo} - ${p.nombre}</span>
                    <span class="clay-badge clay-badge-info text-[10px] font-bold">Stock: ${p.stock ?? p.stock_disponible ?? 0} u.</span>
                </li>
            `).join('');
        }
        box.classList.remove('hidden');
    }

    function cerrarDetalleProductosUbicacion() {
        const box = document.getElementById('boxProductosUbicacion');
        if (box) box.classList.add('hidden');
    }

    async function guardarUbicacion(e) {
        e.preventDefault();
        const id = document.getElementById('ubicacionId').value;
        const descripcion = document.getElementById('ubicacionDescripcion').value.trim();
        if (!descripcion) { alert('La descripción de la ubicación es obligatoria.'); return; }

        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const url = id ? `${API_BASE}/ubicaciones/${id}` : `${API_BASE}/ubicaciones`;
        const method = id ? 'PUT' : 'POST';

        try {
            const res = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ descripcion })
            });
            const data = await res.json();
            if (data.status === 'success') {
                cancelarEdicionUbicacion();
                await cargarUbicaciones();
                renderListaUbicacionesModal();
                alert(id ? 'Ubicación actualizada exitosamente.' : 'Ubicación creada exitosamente.');
            } else { alert(data.message || 'Error al guardar ubicación'); }
        } catch (e) { alert('Error de conexión al guardar ubicación'); }
    }

    async function eliminarUbicacion(id, descripcion, productosCount) {
        if (productosCount > 0) {
            alert(`No se puede eliminar la ubicación "${descripcion}" porque tiene ${productosCount} producto(s) asignados.`);
            return;
        }
        if (!confirm(`¿Confirma dar de baja / eliminar la ubicación "${descripcion}"?`)) return;

        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        try {
            const res = await fetch(`${API_BASE}/ubicaciones/${id}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            });
            const data = await res.json();
            if (data.status === 'success') {
                await cargarUbicaciones();
                renderListaUbicacionesModal();
                alert(`Ubicación "${descripcion}" eliminada exitosamente.`);
            } else { alert(data.message || 'Error al eliminar ubicación'); }
        } catch (e) { alert('Error de conexión al eliminar ubicación'); }
    }

    // ==========================================
    // MODAL: GESTIÓN DE UNIDADES Y EQUIVALENCIAS
    // ==========================================
    let productoUnidadSeleccionado = null;
    let unidadesProductoCache = [];

    async function abrirModalUnidades(idProducto) {
        productoUnidadSeleccionado = idProducto;
        cancelarEdicionUnidad();
        const prod = productosCache.find(p => p.id_producto === idProducto);
        const titulo = document.getElementById('modalUnidadProdTitulo');
        if (titulo && prod) {
            titulo.textContent = `${prod.codigo} - ${prod.nombre} (Stock actual: ${formatoCantidadProductoCorta(prod.stock ?? prod.stock_disponible ?? 0, prod)})`;
        }
        document.getElementById('unidadModalProdId').value = idProducto;
        document.getElementById('modalUnidades').classList.remove('hidden');
        await cargarUnidadesProductoModal(idProducto);
        if (window.lucide) window.lucide.createIcons();
    }

    function cerrarModalUnidades() {
        document.getElementById('modalUnidades').classList.add('hidden');
        productoUnidadSeleccionado = null;
    }

    async function cargarUnidadesProductoModal(idProducto) {
        const tbody = document.getElementById('tablaUnidadesBody');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-slate-400 font-bold">Cargando unidades...</td></tr>';

        try {
            const res = await fetch(`${API_BASE}/productos/${idProducto}/unidades`);
            const json = await res.json();
            if (json.status === 'success') {
                unidadesProductoCache = json.data.unidades || [];

                if (unidadesProductoCache.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-slate-400 font-bold">No hay equivalencias registradas (el producto se visualiza en unidades individuales "u." por defecto).</td></tr>';
                    return;
                }

                tbody.innerHTML = unidadesProductoCache.map(u => {
                    const badgeActivo = u.es_base
                        ? '<span class="clay-badge clay-badge-success font-black text-xs inline-flex items-center gap-1"><i data-lucide="check" class="w-3 h-3"></i>Unidad Activa</span>'
                        : `<button type="button" onclick="seleccionarUnidadMedidaProducto(${u.id_unidad})" class="clay-btn-secondary px-2.5 py-1 text-[11px] font-bold text-indigo-700 hover:text-indigo-900 border border-indigo-200 shadow-xs" title="Elegir como unidad de medida principal para visualizar en el sistema">Elegir como Unidad</button>`;

                    return `
                    <tr>
                        <td class="font-black text-slate-800 text-xs">${u.nombre_unidad}</td>
                        <td class="text-center font-extrabold text-indigo-700 text-xs">${u.equivalencia_base} u.</td>
                        <td class="text-center">${badgeActivo}</td>
                        <td class="text-xs text-slate-500 font-bold">${u.descripcion || '-'}</td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button type="button" onclick="editarUnidadMedida(${u.id_unidad}, '${u.nombre_unidad.replace(/'/g, "\\'")}', ${u.equivalencia_base}, '${(u.descripcion || '').replace(/'/g, "\\'")}', ${u.es_base ? 'true' : 'false'})" class="p-1 text-blue-600 hover:bg-blue-50 rounded" title="Editar unidad">
                                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                </button>
                                <button type="button" onclick="eliminarUnidadMedida(${u.id_unidad}, '${u.nombre_unidad.replace(/'/g, "\\'")}')" class="p-1 text-rose-600 hover:bg-rose-50 rounded" title="Eliminar unidad">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                }).join('');
                if (window.lucide) window.lucide.createIcons();
            } else {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-rose-500 font-bold">${json.message || 'Error al cargar unidades'}</td></tr>`;
            }
        } catch (e) {
            console.error('Error unidades producto:', e);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-rose-500 font-bold">Error de conexión al cargar unidades.</td></tr>';
        }
    }

    function editarUnidadMedida(idUnidad, nombre, equivalencia, descripcion, esBase = false) {
        document.getElementById('unidadModalId').value = idUnidad;
        document.getElementById('unidadModalNombre').value = nombre;
        document.getElementById('unidadModalEquiv').value = equivalencia;
        document.getElementById('unidadModalDesc').value = descripcion || '';
        const chk = document.getElementById('unidadModalEsBase');
        if (chk) chk.checked = Boolean(esBase);
        document.getElementById('btnGuardarUnidad').querySelector('span').textContent = 'Guardar Cambios';
        document.getElementById('btnCancelarEdicionUnidad').classList.remove('hidden');
        document.getElementById('unidadModalNombre').focus();
    }

    function cancelarEdicionUnidad() {
        document.getElementById('unidadModalId').value = '';
        document.getElementById('unidadModalNombre').value = '';
        document.getElementById('unidadModalEquiv').value = '';
        document.getElementById('unidadModalDesc').value = '';
        const chk = document.getElementById('unidadModalEsBase');
        if (chk) chk.checked = false;
        document.getElementById('btnGuardarUnidad').querySelector('span').textContent = 'Guardar Equivalencia';
        document.getElementById('btnCancelarEdicionUnidad').classList.add('hidden');
    }

    async function guardarUnidadMedida(e) {
        e.preventDefault();
        const idProd = document.getElementById('unidadModalProdId').value;
        const idUnidad = document.getElementById('unidadModalId').value;
        const nombre = document.getElementById('unidadModalNombre').value.trim();
        const equiv = Number(document.getElementById('unidadModalEquiv').value);
        const desc = document.getElementById('unidadModalDesc').value.trim();
        const esBase = document.getElementById('unidadModalEsBase')?.checked || false;

        if (!idProd) { alert('No se ha seleccionado producto.'); return; }
        if (!nombre) { alert('El nombre de la unidad es obligatorio.'); return; }
        if (!equiv || equiv <= 0) { alert('La equivalencia debe ser mayor a cero.'); return; }

        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const url = idUnidad 
            ? `${API_BASE}/productos/${idProd}/unidades/${idUnidad}` 
            : `${API_BASE}/productos/${idProd}/unidades`;
        const method = idUnidad ? 'PUT' : 'POST';

        try {
            const res = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ nombre_unidad: nombre, equivalencia_base: equiv, descripcion: desc, es_base: esBase })
            });
            const data = await res.json();
            if (data.status === 'success') {
                cancelarEdicionUnidad();
                await recargarTodo();
                await cargarUnidadesProductoModal(idProd);
                const prod = productosCache.find(p => p.id_producto == idProd);
                const titulo = document.getElementById('modalUnidadProdTitulo');
                if (titulo && prod) {
                    titulo.textContent = `${prod.codigo} - ${prod.nombre} (Stock actual: ${formatoCantidadProductoCorta(prod.stock ?? prod.stock_disponible ?? 0, prod)})`;
                }
                alert(idUnidad ? 'Unidad de medida actualizada exitosamente.' : 'Unidad de medida registrada exitosamente.');
            } else {
                alert(data.message || 'Error al guardar unidad de medida.');
            }
        } catch (err) {
            console.error('Error guardar unidad:', err);
            alert('Error de conexión al guardar unidad.');
        }
    }

    async function seleccionarUnidadMedidaProducto(idUnidad) {
        const idProd = document.getElementById('unidadModalProdId').value;
        if (!idProd) return;
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        try {
            const res = await fetch(`${API_BASE}/productos/${idProd}/unidades/${idUnidad}/seleccionar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            });
            const data = await res.json();
            if (data.status === 'success') {
                await recargarTodo();
                await cargarUnidadesProductoModal(idProd);
                const prod = productosCache.find(p => p.id_producto == idProd);
                const titulo = document.getElementById('modalUnidadProdTitulo');
                if (titulo && prod) {
                    titulo.textContent = `${prod.codigo} - ${prod.nombre} (Stock actual: ${formatoCantidadProductoCorta(prod.stock ?? prod.stock_disponible ?? 0, prod)})`;
                }
            } else {
                alert(data.message || 'Error al seleccionar unidad de medida.');
            }
        } catch (err) {
            console.error('Error al seleccionar unidad:', err);
            alert('Error de conexión al seleccionar unidad.');
        }
    }

    async function restablecerUnidadesIndividuales() {
        const idProd = document.getElementById('unidadModalProdId').value;
        if (!idProd) return;
        if (!confirm('¿Desea restablecer el producto para que se visualice en unidades individuales (u.) por defecto?')) return;
        await seleccionarUnidadMedidaProducto(0);
    }

    async function eliminarUnidadMedida(idUnidad, nombre) {
        if (!confirm(`¿Confirma eliminar la unidad de medida "${nombre}"?`)) return;
        const idProd = document.getElementById('unidadModalProdId').value;
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        try {
            const res = await fetch(`${API_BASE}/productos/${idProd}/unidades/${idUnidad}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            });
            const data = await res.json();
            if (data.status === 'success') {
                await recargarTodo();
                await cargarUnidadesProductoModal(idProd);
                const prod = productosCache.find(p => p.id_producto == idProd);
                const titulo = document.getElementById('modalUnidadProdTitulo');
                if (titulo && prod) {
                    titulo.textContent = `${prod.codigo} - ${prod.nombre} (Stock actual: ${formatoCantidadProductoCorta(prod.stock ?? prod.stock_disponible ?? 0, prod)})`;
                }
                alert(`Unidad "${nombre}" eliminada exitosamente.`);
            } else {
                alert(data.message || 'No se pudo eliminar la unidad.');
            }
        } catch (err) {
            alert('Error de conexión al eliminar unidad.');
        }
    }

    // Modal Devoluciones
    async function abrirModalDevolucion(prodId = null) {
        document.getElementById('modalDevolucion').classList.remove('hidden');
        const sel = document.getElementById('modalDevProductoSelect');
        if (sel) {
            sel.innerHTML = '<option value="">Seleccione el producto devuelto...</option>' + 
                productosCache.filter(p => p.estado === 'activo').map(p => `<option value="${p.id_producto}">${p.codigo} - ${p.nombre} (Stock: ${p.stock ?? p.stock_disponible ?? 0})</option>`).join('');
            if (prodId) {
                sel.value = String(prodId);
            }
        }
        poblarUnidadesDevolucion('modal');
        actualizarEquivalenciaDevolucion('modal');
        if (window.lucide) window.lucide.createIcons();
    }
    function cerrarModalDevolucion() { 
        document.getElementById('modalDevolucion').classList.add('hidden'); 
    }
</script>
@endsection
