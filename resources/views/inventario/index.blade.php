@extends('layouts.app')

@section('title', 'Inventario y Stock - Distribuidora Pigüé')
@section('page_title', 'Inventario y Stock')
@section('page_subtitle', 'Catálogo de Productos, Control de Stock, Lotes y Movimientos')

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
            <button onclick="cambiarPestana('lotes')" id="tabBtn-lotes" 
                    class="tab-btn px-4 py-2.5 rounded-xl font-black text-xs flex items-center gap-2 transition-all cursor-pointer text-slate-600 hover:text-slate-900">
                <i data-lucide="calendar" class="w-4 h-4"></i>
                <span>Lotes y Vencimientos</span>
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
            <button onclick="cambiarPestana('devoluciones')" id="tabBtn-devoluciones" 
                    class="tab-btn px-4 py-2.5 rounded-xl font-black text-xs flex items-center gap-2 transition-all cursor-pointer text-slate-600 hover:text-slate-900">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                <span>Devoluciones</span>
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
                <select id="filtroNivelStock" onchange="renderProductos()" class="clay-input py-2 text-xs font-bold text-slate-700 min-w-[145px]" title="Filtrar por Nivel de Stock (S15/S16)">
                    <option value="todos">Todos los Niveles</option>
                    <option value="suficiente">🟢 Stock Suficiente</option>
                    <option value="bajo">🟡 Stock Bajo</option>
                    <option value="critico">🔴 Stock Crítico (≤ Mín)</option>
                    <option value="agotado">⚫ Sin Stock (0 u.)</option>
                </select>

                <!-- Rango de Cantidad de Stock (S15) -->
                <div class="flex items-center gap-1.5 bg-slate-50 px-2.5 py-1 rounded-xl border border-slate-200 text-xs font-bold" title="Filtrar por Rango de Cantidad de Stock (S15)">
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
                <button onclick="abrirModalAumentoMasivo()" class="clay-btn-secondary px-3.5 py-2.5 text-xs font-bold flex items-center gap-2" title="Aumento Porcentual Masivo (P07)">
                    <i data-lucide="trending-up" class="w-4 h-4 text-blue-600"></i>
                    <span class="hidden sm:inline">Aumento %</span>
                </button>
                <button onclick="abrirHistorialPreciosGlobal()" class="clay-btn-secondary px-3.5 py-2.5 text-xs font-bold flex items-center gap-2" title="Historial de Precios y Aumentos Masivos (P08)">
                    <i data-lucide="history" class="w-4 h-4 text-emerald-600"></i>
                    <span class="hidden sm:inline">Historial Precios</span>
                </button>
                <button onclick="abrirModalCategorias()" class="clay-btn-secondary px-3.5 py-2.5 text-xs font-bold flex items-center gap-2" title="Administrar Categorías (P02)">
                    <i data-lucide="layers" class="w-4 h-4 text-indigo-600"></i>
                    <span>Categorías</span>
                </button>
                <button onclick="abrirModalMarcas()" class="clay-btn-secondary px-3.5 py-2.5 text-xs font-bold flex items-center gap-2" title="Administrar Marcas (P03)">
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
                            <th class="text-right">Precio Unitario</th>
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
                    <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Bajo / Crítico (S07)</span>
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

                <!-- Selector de Vista: Todos los Productos vs Solo Alertas Críticas (S07) -->
                <div class="bg-slate-100 p-1 rounded-xl flex items-center border border-slate-200/80 text-xs font-black">
                    <button type="button" id="btnSubtabStockTodos" onclick="cambiarSubtabStock('todos')" class="px-3 py-1.5 rounded-lg bg-white shadow-xs text-blue-700 transition-all cursor-pointer">
                        Todos los Productos (S01/S02)
                    </button>
                    <button type="button" id="btnSubtabStockAlertas" onclick="cambiarSubtabStock('alertas')" class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition-all cursor-pointer flex items-center gap-1.5">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-amber-500"></i>
                        <span>Solo Críticos (S07)</span>
                        <span id="badgeContadorAlertasStock" class="clay-badge clay-badge-danger text-[10px] px-1.5 py-0">0</span>
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap shrink-0">
                <button onclick="abrirModalIngreso()" class="clay-btn-primary px-3.5 py-2 text-xs font-bold flex items-center gap-1.5 shadow-md" title="Registrar Ingreso de Mercadería (S03)">
                    <i data-lucide="arrow-down-right" class="w-4 h-4"></i>
                    <span>Ingreso Mercadería</span>
                </button>
                <button onclick="abrirModalDevolucion()" class="clay-btn-secondary px-3 py-2 text-xs font-bold flex items-center gap-1.5" title="Registrar Devolución (S05)">
                    <i data-lucide="rotate-ccw" class="w-4 h-4 text-purple-600"></i>
                    <span>Devolución</span>
                </button>
                <button onclick="abrirModalAjuste()" class="clay-btn-secondary px-3 py-2 text-xs font-bold flex items-center gap-1.5" title="Ajuste Manual de Inventario (S06)">
                    <i data-lucide="sliders" class="w-4 h-4 text-amber-600"></i>
                    <span>Ajuste Manual</span>
                </button>
                <button onclick="abrirModalUbicaciones()" class="clay-btn-secondary px-3 py-2 text-xs font-bold flex items-center gap-1.5" title="Gestión de Ubicaciones Físicas (S14)">
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
                    Inventario Completo y Disponibilidad para Venta en Tiempo Real (S01 / S02)
                </span>
                <span id="totalStockProductosTag" class="text-xs font-bold text-slate-500">0 artículos</span>
            </div>
            <div class="overflow-x-auto">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto / Descripción</th>
                            <th>Ubicación Física (S14)</th>
                            <th class="text-center">Stock Físico</th>
                            <th class="text-center">Comprometido</th>
                            <th class="text-center bg-blue-50/50">Disponible Venta (S02)</th>
                            <th class="text-center">Stock Mínimo (S07)</th>
                            <th class="text-center">Nivel Semáforo (S16)</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaStockCompletoBody">
                        <tr>
                            <td colspan="9" class="text-center py-6 text-slate-400 font-bold">Cargando inventario de stock...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- VISTA 2: TABLA DE ALERTAS DE STOCK MÍNIMO Y CRÍTICO (S07) -->
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
    <!-- PESTAÑA 3: LOTES Y VENCIMIENTOS (S09, S10, S11)           -->
    <!-- ========================================================= -->
    <div id="pestana-lotes" class="pestana-content hidden space-y-4">
        <div class="clay-card p-5 flex items-center justify-between flex-wrap gap-4">
            <div>
                <h3 class="text-base font-black text-slate-800">Lotes y Vencimientos de Mercadería (S09 / S11)</h3>
                <p class="text-xs text-slate-500 font-bold">Monitoreo de lotes individuales, cantidades remanentes y fechas de expiración</p>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="abrirModalNuevoLote()" class="clay-btn-primary px-4 py-2 text-xs font-black flex items-center gap-2 shadow-md">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Nuevo Lote</span>
                </button>
                <a href="{{ url('/vencimientos') }}" class="clay-btn-secondary px-4 py-2 text-xs font-bold flex items-center gap-2" title="Semáforo de vencimientos (S10/S11)">
                    <i data-lucide="calendar" class="w-4 h-4 text-blue-600"></i>
                    <span>Ver Semáforo Completo (S11)</span>
                </a>
            </div>
        </div>

        <!-- BANNER DE ALERTA: PRODUCTOS PRÓXIMOS A VENCER (S10) -->
        <div id="bannerAlertaVencimientos" class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200/90 flex items-center justify-between flex-wrap gap-3 hidden">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-amber-100 text-amber-700 rounded-xl">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                </div>
                <div>
                    <h4 class="text-xs font-black text-amber-900">Alerta de Lotes Próximos a Vencer (S10)</h4>
                    <p id="textoAlertaVencimientos" class="text-xs text-amber-800 font-bold">Se detectaron lotes con fecha de vencimiento crítica o cercana a 30 días.</p>
                </div>
            </div>
            <a href="{{ url('/vencimientos') }}" class="clay-btn-primary px-3.5 py-1.5 text-xs font-black shadow-xs">
                Gestionar Vencimientos
            </a>
        </div>

        <!-- BARRA DE FILTROS Y ORDENAMIENTO DE LOTES (S11) -->
        <div class="clay-card p-4 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <div class="flex items-center flex-1 gap-2.5 flex-wrap">
                <div class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
                    <input type="text" id="filtroLotesSearch" placeholder="Buscar por N° lote o producto..." 
                           class="clay-input pl-9 pr-3 py-1.5 w-full text-xs font-bold text-slate-700"
                           oninput="renderTablaLotes()">
                </div>
                <select id="filtroLotesEstado" onchange="renderTablaLotes()" class="clay-input py-1.5 px-3 text-xs font-bold text-slate-700 min-w-[150px]">
                    <option value="todos">Todos los Estados</option>
                    <option value="vigente">🟢 Vigentes (> 30 días)</option>
                    <option value="proximo">🟡 Próximos a Vencer (≤ 30 días)</option>
                    <option value="vencido">🔴 Vencidos</option>
                    <option value="consumido">⚪ Consumidos (Stock 0)</option>
                </select>
                <select id="filtroLotesOrden" onchange="renderTablaLotes()" class="clay-input py-1.5 px-3 text-xs font-bold text-slate-700 min-w-[170px]">
                    <option value="venc_asc">📅 Vencimiento: Más Próximo</option>
                    <option value="venc_desc">📅 Vencimiento: Más Lejano</option>
                    <option value="stock_desc">📦 Mayor Stock Actual</option>
                </select>
            </div>
            <span id="totalLotesTag" class="text-xs font-extrabold text-slate-500">0 lotes</span>
        </div>

        <div class="clay-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="clay-table">
                    <thead>
                        <tr>
                            <th>N° Lote</th>
                            <th>Producto Asociado</th>
                            <th class="text-center">Cantidad Inicial</th>
                            <th class="text-center">Stock en Lote</th>
                            <th class="text-center">Fecha Vencimiento</th>
                            <th class="text-center">Plazo / Días</th>
                            <th class="text-center">Estado (S10)</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tablaLotesBody">
                        <tr>
                            <td colspan="8" class="text-center py-6 text-slate-400 font-bold">Cargando lotes...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- PESTAÑA 4: MOVIMIENTOS E HISTORIALES                      -->
    <!-- ========================================================= -->
    <div id="pestana-movimientos" class="pestana-content hidden space-y-4">
        <div class="clay-card p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-black text-slate-800">Historiales y Auditoría del Sistema</h3>
                <p class="text-xs text-slate-500 font-bold">Registro cronológico de movimientos de stock (Kardex - S12), variaciones de precios (P08) y modificaciones de productos (P04)</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Segmented Control para elegir entre Stock, Precios y Auditoría -->
                <div class="bg-slate-100 p-1 rounded-xl flex items-center border border-slate-200/80 text-xs font-black">
                    <button type="button" id="btnSubtabMovStock" onclick="cambiarSubtabMovimientos('stock')" class="px-3 py-1.5 rounded-lg bg-white shadow-xs text-blue-700 transition-all cursor-pointer">
                        Movimientos Stock
                    </button>
                    <button type="button" id="btnSubtabMovPrecios" onclick="cambiarSubtabMovimientos('precios')" class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition-all cursor-pointer">
                        Historial Precios (P08)
                    </button>
                    <button type="button" id="btnSubtabMovAuditoria" onclick="cambiarSubtabMovimientos('auditoria')" class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition-all cursor-pointer">
                        Modificaciones Productos (P04)
                    </button>
                </div>
                <button type="button" onclick="recargarHistorialActual()" class="clay-btn-secondary px-3 py-1.5 text-xs font-bold flex items-center gap-1.5">
                    <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i>
                    <span>Actualizar</span>
                </button>
            </div>
        </div>

        <!-- SUBTAB 1: MOVIMIENTOS DE STOCK (Kardex - S12) -->
        <div id="contenedorMovimientosStock" class="clay-card overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                <span class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="arrow-left-right" class="w-4 h-4 text-blue-600"></i>
                    Libro Diario de Movimientos de Inventario (Kardex - S12)
                </span>
                <div class="flex items-center gap-2 flex-wrap">
                    <select id="filtroTipoMovStock" onchange="cargarMovimientos()" class="clay-input py-1 px-2.5 text-xs font-bold">
                        <option value="">Todos los Tipos</option>
                        <option value="ingreso">🟢 Ingresos (S03)</option>
                        <option value="venta">🔵 Ventas (S04)</option>
                        <option value="ajuste">🟠 Ajustes Manuales (S06)</option>
                        <option value="devolucion">🟣 Devoluciones (S05)</option>
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

        <!-- SUBTAB 2: HISTORIAL DE PRECIOS Y AUMENTOS MASIVOS (P08) -->
        <div id="contenedorHistorialPrecios" class="clay-card overflow-hidden hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                <span class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="trending-up" class="w-4 h-4 text-emerald-600"></i>
                    Registro de Modificaciones y Aumentos Masivos de Precios
                </span>
                <div class="flex items-center gap-2">
                    <select id="filtroOrigenMovPrecios" onchange="cargarMovimientosPrecios()" class="clay-input py-1 px-2.5 text-xs font-bold">
                        <option value="">Todos los Orígenes</option>
                        <option value="aumento_masivo">Solo Aumentos Masivos (P07)</option>
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

        <!-- SUBTAB 3: AUDITORÍA DE MODIFICACIONES DE PRODUCTOS (P04) -->
        <div id="contenedorMovimientosAuditoria" class="clay-card overflow-hidden hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                <span class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="clipboard-check" class="w-4 h-4 text-indigo-600"></i>
                    Historial de Modificaciones y Auditoría de Productos (P04)
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
    <!-- PESTAÑA 5: CONTEO FÍSICO E INCONSISTENCIAS (S13)          -->
    <!-- ========================================================= -->
    <div id="pestana-conteo" class="pestana-content hidden space-y-4">
        <div class="clay-card p-6 max-w-2xl mx-auto space-y-4">
            <div>
                <h3 class="text-base font-black text-slate-800">Sesión de Conteo Físico e Inventario (S13)</h3>
                <p class="text-xs text-slate-500 font-bold">Ingresá la cantidad real contada en depósito para detectar diferencias y conciliar inconsistencias</p>
            </div>

            <form onsubmit="ejecutarConteoFisico(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-extrabold text-slate-600 uppercase mb-1">Producto a Conciliar *</label>
                    <select id="conteoProductoSelect" onchange="actualizarInfoConteo()" required class="clay-input w-full text-sm font-bold">
                        <option value="">Seleccione un producto...</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-extrabold text-slate-500 uppercase mb-1">Stock Registrado en Sistema</label>
                        <input type="text" id="conteoStockSistema" readonly value="-" class="clay-input w-full bg-slate-50 font-black text-slate-600">
                    </div>
                    <div>
                        <label class="block text-xs font-extrabold text-blue-600 uppercase mb-1">Cantidad Contada en Físico *</label>
                        <input type="number" id="conteoCantidadFisica" min="0" oninput="actualizarPreviewConteo()" required class="clay-input w-full font-black text-blue-700" placeholder="0">
                    </div>
                </div>

                <!-- Diagnóstico y Comparativa en Vivo (S13) -->
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
                    Registrar y Conciliar Diferencia (Ajuste S06)
                </button>
            </form>
        </div>

        <!-- TABLA: RESUMEN DE INCONSISTENCIAS Y CONTEOS FÍSICOS (S13) -->
        <div class="clay-card overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <span class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="clipboard-check" class="w-4 h-4 text-emerald-600"></i>
                    Resumen de Inconsistencias y Conteos Físicos Realizados (S13)
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

    <!-- ========================================================= -->
    <!-- PESTAÑA 6: DEVOLUCIONES                                  -->
    <!-- ========================================================= -->
    <div id="pestana-devoluciones" class="pestana-content hidden space-y-4">
        <div class="clay-card p-6 max-w-2xl mx-auto space-y-4">
            <div>
                <h3 class="text-base font-black text-slate-800">Registro de Devoluciones</h3>
                <p class="text-xs text-slate-500 font-bold">Ingreso de mercadería devuelta por clientes o egreso por devolución a proveedores</p>
            </div>

            <form onsubmit="ejecutarDevolucion(event, 'tab')" class="space-y-4">
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 sm:col-span-4">
                        <label class="block text-xs font-extrabold text-slate-600 uppercase mb-1">Tipo de Devolución</label>
                        <select id="devTipo" onchange="onTipoDevolucionChange('tab')" class="clay-input w-full text-sm font-bold">
                            <option value="cliente">Cliente (Suma stock)</option>
                            <option value="proveedor">Proveedor (Resta stock)</option>
                        </select>
                    </div>
                    <div class="col-span-12 sm:col-span-4">
                        <label id="lblDevCantidad" class="block text-xs font-extrabold text-slate-600 uppercase mb-1">Cantidad Devuelta</label>
                        <input type="number" id="devCantidad" min="1" value="1" oninput="actualizarEquivalenciaDevolucion('tab')" required class="clay-input w-full font-black text-slate-800">
                    </div>
                    <div class="col-span-12 sm:col-span-4">
                        <label class="block text-xs font-extrabold text-slate-600 uppercase mb-1">Unidad</label>
                        <select id="devUnidadSelect" onchange="actualizarEquivalenciaDevolucion('tab')" class="clay-input w-full text-sm font-bold bg-white">
                            <option value="">Unidades (u.)</option>
                        </select>
                    </div>
                </div>

                <!-- Banner de Equivalencia para Devolución en Tab -->
                <div id="devEquivalenciaBox" class="hidden p-2.5 rounded-xl bg-indigo-50/80 border border-indigo-200/80 text-xs text-indigo-950 font-bold flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2">
                        <i data-lucide="info" class="w-4 h-4 text-indigo-600 shrink-0"></i>
                        <span id="devEquivalenciaTexto">Equivalencia: 1 Caja x12 = 12 unidades individuales (u.)</span>
                    </div>
                    <span class="clay-badge clay-badge-success text-[10px] font-black shrink-0">✓ Unidad Activa</span>
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-slate-600 uppercase mb-1">Producto</label>
                    <select id="devProductoSelect" onchange="onProductoDevolucionChange('tab')" required class="clay-input w-full text-sm font-bold">
                        <option value="">Seleccione el producto devuelto...</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-slate-600 uppercase mb-1">Lote del Producto (Asignación de Lote)</label>
                    <select id="devLoteSelect" onchange="onLoteDevolucionChange('tab')" class="clay-input w-full text-sm font-bold">
                        <option value="">(Sin lote específico - Stock general)</option>
                    </select>
                    <div id="devLoteInfo" class="mt-1 hidden"></div>
                    <p class="text-[11px] text-slate-500 font-medium mt-1">Si selecciona un lote, se actualizará directamente el stock del lote además del producto.</p>
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-slate-600 uppercase mb-1">Motivo / Causa</label>
                    <input type="text" id="devMotivo" required placeholder="Ej: Envase dañado en transporte / Vencido" class="clay-input w-full text-sm">
                </div>

                <button type="submit" class="clay-btn-primary w-full py-3 text-sm font-black shadow-lg">
                    Asentar Devolución en Stock
                </button>
            </form>
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
                <p class="text-[11px] text-slate-500 font-bold">Ingreso de stock con asignación y creación de lote (S03/S09)</p>
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
                    <div class="col-span-12 sm:col-span-6">
                        <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Producto</label>
                        <select id="ingresoProductoSelect" onchange="onProductoIngresoChange()" required class="clay-input w-full text-xs font-bold"></select>
                    </div>
                    <div class="col-span-12 sm:col-span-3">
                        <label id="lblIngresoCantidad" class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Cantidad Ingresada</label>
                        <input type="number" id="ingresoCantidad" min="1" value="10" oninput="actualizarEquivalenciaIngreso()" required class="clay-input w-full font-black text-sm">
                    </div>
                    <div class="col-span-12 sm:col-span-3">
                        <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Unidad de Medida</label>
                        <select id="ingresoUnidadSelect" onchange="actualizarEquivalenciaIngreso()" class="clay-input w-full text-xs font-bold bg-white">
                            <option value="">Unidades (u.)</option>
                        </select>
                    </div>
                </div>

                <!-- Banner de Equivalencia a Unidades Individuales -->
                <div id="ingresoEquivalenciaBox" class="hidden p-2.5 rounded-xl bg-indigo-50/80 border border-indigo-200/80 text-xs text-indigo-950 font-bold flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2">
                        <i data-lucide="info" class="w-4 h-4 text-indigo-600 shrink-0"></i>
                        <span id="ingresoEquivalenciaTexto">Equivalencia: 10 Cajas x12 = 120 unidades individuales (u.)</span>
                    </div>
                    <span class="clay-badge clay-badge-success text-[10px] font-black shrink-0" id="ingresoEquivalenciaBadge">✓ Unidad Activa</span>
                </div>
            </div>

            <div class="bg-blue-50/60 p-3.5 rounded-2xl border border-blue-100/80 space-y-2.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-blue-900 font-extrabold text-xs">
                        <i data-lucide="calendar" class="w-4 h-4 text-blue-600"></i>
                        <span>Trazabilidad de Lote y Vencimiento</span>
                    </div>
                    <div class="inline-flex p-0.5 bg-white/90 rounded-xl border border-blue-100 text-[11px] font-bold">
                        <button type="button" id="btnModoLoteNuevo" onclick="cambiarModoLoteIngreso('nuevo')" class="px-2.5 py-1 rounded-lg bg-blue-600 text-white font-extrabold transition-all shadow-xs">
                            Nuevo Lote
                        </button>
                        <button type="button" id="btnModoLoteExistente" onclick="cambiarModoLoteIngreso('existente')" class="px-2.5 py-1 rounded-lg text-slate-600 hover:text-blue-600 font-bold transition-all">
                            Elegir Lote Existente
                        </button>
                    </div>
                </div>

                <!-- Selector de Lote Existente (visible si modo == existente) -->
                <div id="contenedorLoteExistente" class="hidden space-y-1">
                    <label class="block text-[11px] font-extrabold text-blue-900 uppercase">Lote Registrado en el Sistema</label>
                    <select id="ingresoLoteExistenteSelect" onchange="seleccionarLoteExistente()" class="clay-input w-full text-xs font-bold bg-white">
                        <option value="">Seleccione un lote existente...</option>
                    </select>
                    <p id="msgSinLotesExistentes" class="hidden text-[11px] text-amber-700 font-bold">Este producto no posee lotes registrados aún. Podés crear uno con la opción "Nuevo Lote".</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label id="lblNroLote" class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">N° de Lote</label>
                        <input type="text" id="ingresoNroLote" placeholder="LOTE-001" required class="clay-input w-full uppercase font-bold text-sm bg-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Fecha de Vencimiento</label>
                        <input type="date" id="ingresoVencimiento" required class="clay-input w-full font-bold text-sm bg-white">
                    </div>
                </div>
                <p id="ingresoLoteAyuda" class="text-[11px] text-slate-500 font-medium">Al confirmar, se incrementa el stock actual del producto, se asigna al lote seleccionado y queda registrado en el historial de Movimientos (Kardex).</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="cerrarModalIngreso()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cancelar</button>
                <button type="submit" class="clay-btn-primary px-5 py-2 text-xs font-black shadow-md">Registrar Ingreso</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: REGISTRAR NUEVO LOTE               -->
<!-- ========================================== -->
<div id="modalNuevoLote" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalNuevoLote()">
    <div class="clay-modal-content max-w-lg p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-800">Registrar Nuevo Lote</h3>
                <p class="text-[11px] text-slate-500 font-bold">Creación directa de lote con control de vencimiento (S09)</p>
            </div>
            <button onclick="cerrarModalNuevoLote()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form onsubmit="guardarNuevoLote(event)" class="space-y-4">
            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Producto</label>
                <select id="nuevoLoteProductoSelect" onchange="onProductoNuevoLoteChange()" required class="clay-input w-full text-xs font-bold"></select>
            </div>

            <div class="grid grid-cols-12 gap-3">
                <div class="col-span-12 sm:col-span-5">
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">N° de Lote</label>
                    <input type="text" id="nuevoLoteNro" placeholder="LOTE-001" required class="clay-input w-full uppercase font-bold text-sm">
                </div>
                <div class="col-span-12 sm:col-span-3">
                    <label id="lblNuevoLoteCantidad" class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Cantidad del Lote</label>
                    <input type="number" id="nuevoLoteCantidad" min="1" value="10" oninput="actualizarEquivalenciaNuevoLote()" required class="clay-input w-full font-black text-sm">
                </div>
                <div class="col-span-12 sm:col-span-4">
                    <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Unidad</label>
                    <select id="nuevoLoteUnidadSelect" onchange="actualizarEquivalenciaNuevoLote()" class="clay-input w-full text-xs font-bold bg-white">
                        <option value="">Unidades (u.)</option>
                    </select>
                </div>
            </div>

            <!-- Banner de Equivalencia para Nuevo Lote -->
            <div id="nuevoLoteEquivalenciaBox" class="hidden p-2.5 rounded-xl bg-indigo-50/80 border border-indigo-200/80 text-xs text-indigo-950 font-bold flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-indigo-600 shrink-0"></i>
                    <span id="nuevoLoteEquivalenciaTexto">Equivalencia: 10 Cajas x12 = 120 unidades individuales (u.)</span>
                </div>
                <span class="clay-badge clay-badge-success text-[10px] font-black shrink-0">✓ Unidad Activa</span>
            </div>

            <div>
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Fecha de Vencimiento</label>
                <input type="date" id="nuevoLoteVencimiento" required class="clay-input w-full font-bold text-sm">
            </div>

            <div class="flex items-center gap-2 p-3 bg-blue-50/50 rounded-xl border border-blue-100/60">
                <input type="checkbox" id="nuevoLoteAsociarIngreso" checked class="rounded border-slate-300 text-blue-600">
                <label for="nuevoLoteAsociarIngreso" class="text-xs font-bold text-slate-700 cursor-pointer">
                    Sumar esta cantidad al stock actual del producto y asentar el movimiento (Kardex)
                </label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="cerrarModalNuevoLote()" class="clay-btn-secondary px-4 py-2 text-xs font-bold">Cancelar</button>
                <button type="submit" class="clay-btn-primary px-5 py-2 text-xs font-black shadow-md">Crear Lote</button>
            </div>
        </form>
    </div>
</div>

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

            <!-- Asignación Obligatoria de Lote -->
            <div class="space-y-2 p-3 bg-blue-50/60 rounded-xl border border-blue-100">
                <div class="flex items-center justify-between">
                    <label class="block text-[11px] font-extrabold text-blue-950 uppercase">Lote para el ajuste</label>
                    <div id="ajusteModoTabs" class="inline-flex p-0.5 bg-white/90 rounded-lg border border-blue-200 text-[10px] font-bold">
                        <button type="button" id="btnAjusteModoExistente" onclick="cambiarModoLoteAjuste('existente')" class="px-2 py-0.5 rounded bg-blue-600 text-white font-extrabold shadow-xs transition-all">
                            Lote Existente
                        </button>
                        <button type="button" id="btnAjusteModoNuevo" onclick="cambiarModoLoteAjuste('nuevo')" class="px-2 py-0.5 rounded text-slate-600 hover:text-blue-600 font-bold transition-all">
                            Nuevo Lote
                        </button>
                    </div>
                </div>

                <!-- Selector de Lote Existente -->
                <div id="contenedorAjusteLoteExistente" class="space-y-1">
                    <select id="ajusteLoteSelect" onchange="onLoteAjusteChange()" class="clay-input w-full text-xs font-bold bg-white">
                        <option value="">-- Seleccione un lote si corresponde --</option>
                    </select>
                    <div id="ajusteLoteInfo" class="mt-1 hidden"></div>
                    <p id="msgSinLotesAjuste" class="hidden text-[11px] text-amber-700 font-bold"></p>
                </div>

                <!-- Formulario Nuevo Lote (sólo disponible al aumentar) -->
                <div id="contenedorAjusteLoteNuevo" class="hidden space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase mb-0.5">N° de Lote *</label>
                            <input type="text" id="ajusteNroLoteNuevo" placeholder="LOT-001" class="clay-input w-full uppercase font-bold text-xs bg-white">
                        </div>
                        <div>
                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase mb-0.5">Fecha de Vencimiento</label>
                            <input type="date" id="ajusteVencimientoNuevo" class="clay-input w-full font-bold text-xs bg-white">
                        </div>
                    </div>
                    <p class="text-[10px] text-blue-700 font-medium">Se creará este nuevo lote y se incrementará simultáneamente el stock general del producto.</p>
                </div>

                <p class="text-[10px] text-slate-500 font-bold">Si el producto tiene lotes, seleccione uno. Si no tiene lotes, puede disminuir directamente del stock general.</p>
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
<!-- MODAL: AUMENTO MASIVO DE PRECIOS (P07)     -->
<!-- ========================================== -->
<div id="modalAumento" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalAumentoMasivo()">
    <div class="clay-modal-content max-w-xl p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-800">Aumento Masivo de Precios (P07)</h3>
                <p class="text-[11px] font-bold text-slate-500">Configurá cómo redondear los nuevos precios</p>
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

            <div>
                <label for="aumentoRedondeo" class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Regla de Redondeo</label>
                <select id="aumentoRedondeo" class="clay-input w-full text-xs font-bold" onchange="actualizarVistaPreviaAumento()">
                    <option value="sin_redondeo">Sin redondeo (mantener centavos)</option>
                    <option value="peso_mas_cercano">Al peso más cercano</option>
                    <option value="peso_superior">Siempre hacia arriba al peso</option>
                    <option value="terminar_90">Terminar en .90 (hacia arriba)</option>
                    <option value="terminar_99">Terminar en .99 (hacia arriba)</option>
                </select>
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
                <h3 class="text-base font-black text-slate-800">Historial y Auditoría de Productos (P08 / P04)</h3>
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
                <span>Historial de Precios (P08)</span>
            </button>
            <button type="button" id="modalHistTabBtn-auditoria" onclick="cambiarModalHistTab('auditoria')" class="px-3.5 py-1.5 rounded-xl font-bold text-xs text-slate-600 hover:text-slate-900 transition-all cursor-pointer flex items-center gap-1.5">
                <i data-lucide="clipboard-check" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Historial de Modificaciones / Auditoría (P04)</span>
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
                    <option value="aumento_masivo">Aumentos Masivos (P07)</option>
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

        <!-- VISTA 2: TABLA MODIFICACIONES Y AUDITORÍA (P04) -->
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
<!-- MODAL: GESTIÓN DE CATEGORÍAS (P02)        -->
<!-- ========================================== -->
<div id="modalCategoria" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalCategorias()">
    <div class="clay-modal-content max-w-lg p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-800">Administrar Categorías (P02)</h3>
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
<!-- MODAL: GESTIÓN DE MARCAS (P03)            -->
<!-- ========================================== -->
<div id="modalMarca" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalMarcas()">
    <div class="clay-modal-content max-w-lg p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-800">Administrar Marcas (P03)</h3>
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
<!-- MODAL: GESTIÓN DE UBICACIONES (S14)       -->
<!-- ========================================== -->
<div id="modalUbicaciones" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalUbicaciones()">
    <div class="clay-modal-content max-w-xl p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-800">Ubicaciones de Almacenamiento (S14)</h3>
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
<!-- MODAL: GESTIÓN DE UNIDADES Y EQUIVALENCIAS (S08) -->
<!-- ========================================== -->
<div id="modalUnidades" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalUnidades()">
    <div class="clay-modal-content max-w-xl p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-800">Unidades de Medida y Equivalencias (S08)</h3>
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
<!-- MODAL: REGISTRAR DEVOLUCIÓN (S05)         -->
<!-- ========================================== -->
<div id="modalDevolucion" class="clay-modal-backdrop hidden" onclick="if(event.target === this) cerrarModalDevolucion()">
    <div class="clay-modal-content max-w-md p-6 space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-black text-slate-800">Registrar Devolución (S05)</h3>
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
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">Lote del Producto (Asignación de Lote)</label>
                <select id="modalDevLoteSelect" onchange="onLoteDevolucionChange('modal')" class="clay-input w-full text-xs font-bold">
                    <option value="">(Sin lote específico - Stock general)</option>
                </select>
                <div id="modalDevLoteInfo" class="mt-1 hidden"></div>
                <p class="text-[10px] text-slate-500 font-medium mt-1">Si selecciona un lote, se actualizará directamente el stock del lote además del producto.</p>
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
    });

    async function recargarTodo() {
        await Promise.all([
            cargarCategorias(),
            cargarMarcas(),
            cargarUbicaciones(),
            cargarProveedores(),
            cargarProductos(),
            cargarLotes(),
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
        } else if (id === 'devoluciones') {
            actualizarLotesDevolucion('tab');
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

    function formatoCantidadProducto(cant, p) {
        const num = Number(cant || 0);
        const eq = Number(p?.equivalencia_unidad || 1);
        const nom = p?.unidad_medida || 'u.';
        if (eq > 1 && nom !== 'u.') {
            const cantConvertida = (num / eq).toLocaleString('es-AR', { maximumFractionDigits: 2 });
            return `<span title="${num} u. individuales">${cantConvertida} ${nom}</span><span class="block text-[10px] text-slate-400 font-semibold">(${num} u.)</span>`;
        }
        return `${num} u.`;
    }

    function formatoCantidadProductoCorta(cant, p) {
        const num = Number(cant || 0);
        const eq = Number(p?.equivalencia_unidad || 1);
        const nom = p?.unidad_medida || 'u.';
        if (eq > 1 && nom !== 'u.') {
            const cantConvertida = (num / eq).toLocaleString('es-AR', { maximumFractionDigits: 2 });
            return `${cantConvertida} ${nom} (${num} u.)`;
        }
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

            // Semáforo visual (S16)
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
                        <div class="text-[11px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                            <span>${p.descripcion || '-'}</span>
                            <span class="text-slate-300">•</span>
                            <span class="text-slate-500 font-bold flex items-center gap-0.5" title="Ubicación en depósito (S14)"><i data-lucide="map-pin" class="w-3 h-3 text-slate-400"></i>${ubicacionTexto}</span>
                        </div>
                    </td>
                    <td><span class="text-xs font-bold text-slate-600">${p.categoria?.nombre || p.categoria_nombre || '-'}</span></td>
                    <td><span class="text-xs font-bold text-slate-600">${p.marca?.nombre || p.marca_nombre || '-'}</span></td>
                    <td class="text-right font-black text-slate-800">${precioUnitario}</td>
                    <td class="text-center">
                        <div class="font-black text-slate-800 text-xs">${formatoCantidadProducto(cantStock, p)}</div>
                        <div class="mt-0.5">${semaforoBadge}</div>
                    </td>
                    <td class="text-center">
                        <span class="clay-badge ${badgeClase}">${p.estado}</span>
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <button onclick="abrirModalUnidades(${p.id_producto})" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg" title="Unidades y Equivalencias (S08)">
                                <i data-lucide="layers" class="w-4 h-4"></i>
                            </button>
                            <button onclick="abrirHistorialPrecios(${p.id_producto})" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg" title="Historial de Precios (P08)">
                                <i data-lucide="history" class="w-4 h-4"></i>
                            </button>
                            <button onclick="editarProducto(${p.id_producto})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg" title="Editar Producto (P04)">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <button onclick="cambiarEstadoProducto(${p.id_producto}, '${p.estado}')" class="p-1.5 ${esActivo ? 'text-rose-600 hover:bg-rose-50' : 'text-emerald-600 hover:bg-emerald-50'} rounded-lg" title="${esActivo ? 'Desactivar (P06)' : 'Activar (P06)'}">
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
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-bold text-xs" title="Ubicación física (S14)">
                            <i data-lucide="map-pin" class="w-3 h-3 text-slate-500"></i>
                            ${ubicacionTexto}
                        </span>
                    </td>
                    <td class="text-center font-bold text-slate-700 text-xs">${formatoCantidadProducto(stockFisico, p)}</td>
                    <td class="text-center font-bold text-slate-400 text-xs">${formatoCantidadProducto(comprometido, p)}</td>
                    <td class="text-center font-black text-blue-700 bg-blue-50/40 text-xs">${formatoCantidadProducto(disponibleVenta, p)}</td>
                    <td class="text-center font-bold text-slate-600 text-xs">${formatoCantidadProducto(stockMin, p)}</td>
                    <td class="text-center">${semaforoBadge}</td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-1">
                            <button onclick="abrirModalIngresoRapido(${p.id_producto})" class="clay-btn-primary px-2 py-1 text-[11px] font-bold" title="Reponer Stock (S03)">Reponer</button>
                            <button onclick="abrirModalAjuste(${p.id_producto})" class="p-1 text-amber-600 hover:bg-amber-50 rounded" title="Ajuste Manual de Inventario (S06)">
                                <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                            </button>
                            <button onclick="abrirModalDevolucion(${p.id_producto})" class="p-1 text-purple-600 hover:bg-purple-50 rounded" title="Registrar Devolución (S05)">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                            </button>
                            <button onclick="abrirModalUnidades(${p.id_producto})" class="p-1 text-indigo-600 hover:bg-indigo-50 rounded" title="Unidades de Medida (S08)">
                                <i data-lucide="layers" class="w-3.5 h-3.5"></i>
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
        const selectNuevoLote = document.getElementById('nuevoLoteProductoSelect');

        const options = '<option value="">Seleccione un producto...</option>' + 
            productosCache.filter(p => p.estado === 'activo').map(p => `<option value="${p.id_producto}">${p.codigo} - ${p.nombre} (Stock: ${formatoCantidadProductoCorta(p.stock ?? p.stock_disponible ?? 0, p)})</option>`).join('');

        if (selectConteo) selectConteo.innerHTML = options;
        if (selectDev) selectDev.innerHTML = options;
        if (modalSelectDev) modalSelectDev.innerHTML = options;
        if (selectIngreso) selectIngreso.innerHTML = options;
        if (selectAjuste) selectAjuste.innerHTML = options;
        if (selectNuevoLote) selectNuevoLote.innerHTML = options;
    }

    async function cargarLotes() {
        try {
            const res = await fetch(`${API_BASE}/stock/lotes`);
            const json = await res.json();
            lotesCache = json.data || [];
            renderTablaLotes();
        } catch (e) { console.error('Error lotes', e); }
    }

    function renderTablaLotes() {
        const search = document.getElementById('filtroLotesSearch')?.value.toLowerCase().trim() || '';
        const estadoFiltro = document.getElementById('filtroLotesEstado')?.value || 'todos';
        const orden = document.getElementById('filtroLotesOrden')?.value || 'venc_asc';

        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);

        let lotes = Array.isArray(lotesCache) ? [...lotesCache] : [];

        // Evaluar días restantes y clasificación
        lotes = lotes.map(l => {
            const cant = Number(l.cantidad_actual ?? 0);
            let diasRestantes = null;
            let estadoCalculado = 'vigente';

            if (l.fecha_vencimiento) {
                const fVenc = new Date(l.fecha_vencimiento.substring(0, 10));
                fVenc.setHours(0, 0, 0, 0);
                const diffTime = fVenc.getTime() - hoy.getTime();
                diasRestantes = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                if (cant === 0) {
                    estadoCalculado = 'consumido';
                } else if (diasRestantes < 0) {
                    estadoCalculado = 'vencido';
                } else if (diasRestantes <= 30) {
                    estadoCalculado = 'proximo';
                } else {
                    estadoCalculado = 'vigente';
                }
            } else if (cant === 0) {
                estadoCalculado = 'consumido';
            }

            return { ...l, diasRestantes, estadoCalculado };
        });

        // Banner de Alerta S10 (próximos a vencer ≤ 30 días con stock > 0)
        const lotesCriticos = lotes.filter(l => (l.estadoCalculado === 'proximo' || l.estadoCalculado === 'vencido') && Number(l.cantidad_actual) > 0);
        const banner = document.getElementById('bannerAlertaVencimientos');
        const txtBanner = document.getElementById('textoAlertaVencimientos');
        if (banner && txtBanner) {
            if (lotesCriticos.length > 0) {
                banner.classList.remove('hidden');
                txtBanner.textContent = `Se detectaron ${lotesCriticos.length} lote(s) con vencimiento crítico (≤ 30 días o vencidos) con existencia física en depósito.`;
            } else {
                banner.classList.add('hidden');
            }
        }

        // Filtro texto
        if (search) {
            lotes = lotes.filter(l => {
                const nro = (l.nro_lote || '').toLowerCase();
                const prod = (l.producto?.nombre || l.producto_nombre || '').toLowerCase();
                return nro.includes(search) || prod.includes(search);
            });
        }

        // Filtro estado
        if (estadoFiltro !== 'todos') {
            lotes = lotes.filter(l => l.estadoCalculado === estadoFiltro);
        }

        // Ordenamiento (S11)
        if (orden === 'venc_asc') {
            lotes.sort((a, b) => (a.diasRestantes ?? 99999) - (b.diasRestantes ?? 99999));
        } else if (orden === 'venc_desc') {
            lotes.sort((a, b) => (b.diasRestantes ?? -99999) - (a.diasRestantes ?? -99999));
        } else if (orden === 'stock_desc') {
            lotes.sort((a, b) => Number(b.cantidad_actual) - Number(a.cantidad_actual));
        }

        const tagTotal = document.getElementById('totalLotesTag');
        if (tagTotal) tagTotal.textContent = `${lotes.length} lotes`;

        const tbody = document.getElementById('tablaLotesBody');
        if (!tbody) return;

        if (lotes.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-6 text-slate-400 font-bold">No se encontraron lotes que coincidan con la búsqueda.</td></tr>`;
            return;
        }

        tbody.innerHTML = lotes.map(l => {
            const vence = l.fecha_vencimiento ? l.fecha_vencimiento.substring(0, 10) : '-';
            const estado = l.estadoCalculado;
            let badgeClass = 'clay-badge-success';
            let badgeTexto = 'Vigente';
            let plazoTexto = '';

            if (estado === 'vencido') {
                badgeClass = 'clay-badge-danger font-black';
                badgeTexto = 'Vencido';
                plazoTexto = `<span class="text-rose-600 font-black text-xs">Venció hace ${Math.abs(l.diasRestantes)} d.</span>`;
            } else if (estado === 'proximo') {
                badgeClass = 'clay-badge-warning font-black';
                badgeTexto = 'Próximo a Vencer';
                plazoTexto = `<span class="text-amber-600 font-black text-xs">${l.diasRestantes} días rest.</span>`;
            } else if (estado === 'consumido') {
                badgeClass = 'clay-badge-info';
                badgeTexto = 'Consumido';
                plazoTexto = '<span class="text-slate-400 text-xs font-bold">Sin stock</span>';
            } else {
                badgeClass = 'clay-badge-success font-bold';
                badgeTexto = 'Vigente';
                plazoTexto = `<span class="text-emerald-600 font-bold text-xs">${l.diasRestantes !== null ? `${l.diasRestantes} días` : '-'}</span>`;
            }

            return `
                <tr>
                    <td class="font-black text-slate-800">${l.nro_lote}</td>
                    <td class="font-bold text-slate-700">${l.producto?.nombre || l.producto_nombre || `Producto #${l.id_producto}`}</td>
                    <td class="text-center font-bold text-slate-500">${l.cantidad_inicial || l.cantidad_actual} u.</td>
                    <td class="text-center font-black text-blue-700">${l.cantidad_actual} u.</td>
                    <td class="text-center font-bold text-slate-700">${vence}</td>
                    <td class="text-center">${plazoTexto}</td>
                    <td class="text-center"><span class="clay-badge ${badgeClass} uppercase">${badgeTexto}</span></td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-1">
                            <button onclick="abrirModalAjuste(${l.id_producto}, ${l.id_lote})" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded" title="Ajustar o corregir este lote (S06)">
                                <i data-lucide="sliders" class="w-4 h-4"></i>
                            </button>
                            <button onclick="abrirModalIngresoRapido(${l.id_producto})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded" title="Ingresar mercadería con este producto">
                                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        if (window.lucide) window.lucide.createIcons();
    }

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
                    ? '<span class="clay-badge clay-badge-info font-black">Aumento Masivo (P07)</span>' 
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
            id_ubicacion: document.getElementById('prodUbicacion').value || null
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
        if (!lotesCache || lotesCache.length === 0) {
            await cargarLotes();
        }
        if (idProducto) {
            document.getElementById('ingresoProductoSelect').value = String(idProducto);
        } else {
            const selectProd = document.getElementById('ingresoProductoSelect');
            if (selectProd && selectProd.options.length > 1 && !selectProd.value) {
                selectProd.selectedIndex = 1;
            }
        }
        await cambiarModoLoteIngreso('nuevo');
        await actualizarLotesExistentesIngreso();
        poblarUnidadesIngreso();
        actualizarEquivalenciaIngreso();
        document.getElementById('modalIngreso').classList.remove('hidden');
        if (window.lucide) window.lucide.createIcons();
    }

    function onProductoIngresoChange() {
        actualizarLotesExistentesIngreso();
        poblarUnidadesIngreso();
        actualizarEquivalenciaIngreso();
    }

    function poblarUnidadesIngreso() {
        const prodId = document.getElementById('ingresoProductoSelect')?.value;
        const selectUnidad = document.getElementById('ingresoUnidadSelect');
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

    function actualizarEquivalenciaIngreso() {
        const prodId = document.getElementById('ingresoProductoSelect')?.value;
        const cant = Number(document.getElementById('ingresoCantidad')?.value || 0);
        const selectUnidad = document.getElementById('ingresoUnidadSelect');
        const box = document.getElementById('ingresoEquivalenciaBox');
        const texto = document.getElementById('ingresoEquivalenciaTexto');
        const badge = document.getElementById('ingresoEquivalenciaBadge');
        const lblCant = document.getElementById('lblIngresoCantidad');

        const info = obtenerInfoUnidadProducto(prodId);
        const idUnidadSeleccionada = selectUnidad?.value;

        if (idUnidadSeleccionada && info.tieneActiva && String(idUnidadSeleccionada) === String(info.id_unidad)) {
            const cantBase = Math.round(cant * info.equivalencia);
            if (lblCant) lblCant.textContent = `Cantidad (${info.nombre})`;
            if (box && texto) {
                texto.innerHTML = `Equivalencia: <strong>${cant} ${info.nombre}</strong> equivalen a <strong>${cantBase} unidades individuales (u.)</strong> que se sumarán al lote y al stock.`;
                if (badge) badge.textContent = '✓ Unidad Activa';
                box.classList.remove('hidden');
            }
        } else {
            if (lblCant) lblCant.textContent = 'Cantidad Ingresada (u.)';
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

    async function cambiarModoLoteIngreso(modo) {
        modoLoteActual = modo;
        const btnNuevo = document.getElementById('btnModoLoteNuevo');
        const btnExistente = document.getElementById('btnModoLoteExistente');
        const contExistente = document.getElementById('contenedorLoteExistente');
        const inputNro = document.getElementById('ingresoNroLote');
        const inputVenc = document.getElementById('ingresoVencimiento');

        if (modo === 'nuevo') {
            btnNuevo.className = 'px-2.5 py-1 rounded-lg bg-blue-600 text-white font-extrabold transition-all shadow-xs';
            btnExistente.className = 'px-2.5 py-1 rounded-lg text-slate-600 hover:text-blue-600 font-bold transition-all';
            contExistente.classList.add('hidden');
            inputNro.readOnly = false;
            inputNro.value = `LOTE-${Date.now().toString().slice(-4)}`;
            inputVenc.readOnly = false;
            inputVenc.value = new Date(Date.now() + 180*24*60*60*1000).toISOString().split('T')[0];
        } else {
            btnExistente.className = 'px-2.5 py-1 rounded-lg bg-blue-600 text-white font-extrabold transition-all shadow-xs';
            btnNuevo.className = 'px-2.5 py-1 rounded-lg text-slate-600 hover:text-blue-600 font-bold transition-all';
            contExistente.classList.remove('hidden');
            inputNro.readOnly = true;
            await actualizarLotesExistentesIngreso();
        }
    }

    async function actualizarLotesExistentesIngreso() {
        const prodId = document.getElementById('ingresoProductoSelect').value;
        const selectLote = document.getElementById('ingresoLoteExistenteSelect');
        const msgSinLotes = document.getElementById('msgSinLotesExistentes');
        if (!selectLote) return;

        // Aseguramos tener los lotes globales del sistema en caché
        if (!lotesCache || lotesCache.length === 0) {
            try {
                const resLotes = await fetch(`${API_BASE}/stock/lotes`);
                const jsonLotes = await resLotes.json();
                if (jsonLotes.status === 'success' && Array.isArray(jsonLotes.data)) {
                    lotesCache = jsonLotes.data;
                }
            } catch (e) {
                console.error('Error cargando lotes del sistema:', e);
            }
        }

        let lotesDelProducto = [];
        if (prodId) {
            try {
                const res = await fetch(`${API_BASE}/stock/productos/${prodId}/lotes`);
                const json = await res.json();
                if (json.status === 'success' && json.data) {
                    if (Array.isArray(json.data)) {
                        lotesDelProducto = json.data;
                    } else if (Array.isArray(json.data.lotes)) {
                        lotesDelProducto = json.data.lotes;
                    }
                }
            } catch (e) {
                console.error('Error cargando lotes del producto:', e);
            }

            // Respaldo defensivo con lotesCache
            if (lotesDelProducto.length === 0 && Array.isArray(lotesCache)) {
                lotesDelProducto = lotesCache.filter(l => String(l.id_producto) === String(prodId));
            }
        }

        const otrosLotes = (Array.isArray(lotesCache) ? lotesCache : []).filter(l => !prodId || String(l.id_producto) !== String(prodId));

        if (lotesDelProducto.length === 0 && otrosLotes.length === 0) {
            selectLote.innerHTML = '<option value="">No hay lotes registrados aún en el sistema</option>';
            if (msgSinLotes) {
                msgSinLotes.textContent = 'No hay lotes creados en el sistema. Podés crear uno con la opción "Nuevo Lote".';
                msgSinLotes.classList.remove('hidden');
            }
            if (modoLoteActual === 'existente') {
                document.getElementById('ingresoNroLote').value = '';
                document.getElementById('ingresoVencimiento').value = '';
            }
            return;
        }

        if (msgSinLotes) msgSinLotes.classList.add('hidden');

        let html = '<option value="">Seleccione un lote existente...</option>';

        if (lotesDelProducto.length > 0) {
            const labelGrupo = prodId ? 'Lotes del producto seleccionado' : 'Lotes disponibles';
            html += `<optgroup label="${labelGrupo}">`;
            html += lotesDelProducto.map(l => {
                const venceStr = l.fecha_vencimiento ? l.fecha_vencimiento.substring(0, 10) : '';
                return `<option value="${l.id_lote}" data-nro="${l.nro_lote}" data-venc="${venceStr}" data-id-producto="${l.id_producto}">
                    ${l.nro_lote} (Stock en lote: ${l.cantidad_actual} u. ${venceStr ? '- Vence: ' + venceStr : ''})
                </option>`;
            }).join('');
            html += '</optgroup>';
        } else if (prodId) {
            html += '<option value="" disabled>⚠️ Este producto no tiene lotes previos aún</option>';
        }

        if (otrosLotes.length > 0) {
            html += '<optgroup label="Lotes existentes de otros artículos (asigna el producto)">';
            html += otrosLotes.map(l => {
                const venceStr = l.fecha_vencimiento ? l.fecha_vencimiento.substring(0, 10) : '';
                const prodNombre = l.producto_nombre || (l.producto ? (l.producto.codigo + ' - ' + l.producto.nombre) : `Producto #${l.id_producto}`);
                return `<option value="${l.id_lote}" data-nro="${l.nro_lote}" data-venc="${venceStr}" data-id-producto="${l.id_producto}">
                    ${l.nro_lote} [${prodNombre}] (Stock: ${l.cantidad_actual} u. ${venceStr ? '- Vence: ' + venceStr : ''})
                </option>`;
            }).join('');
            html += '</optgroup>';
        }

        selectLote.innerHTML = html;

        if (modoLoteActual === 'existente') {
            const primerValido = Array.from(selectLote.options).find(opt => opt.value && !opt.disabled);
            if (primerValido) {
                selectLote.value = primerValido.value;
                seleccionarLoteExistente();
            }
        }
    }

    function seleccionarLoteExistente() {
        const selectLote = document.getElementById('ingresoLoteExistenteSelect');
        if (!selectLote) return;
        const opt = selectLote.selectedOptions[0];
        if (!opt || !opt.value) {
            if (modoLoteActual === 'existente') {
                document.getElementById('ingresoNroLote').value = '';
                document.getElementById('ingresoVencimiento').value = '';
            }
            return;
        }

        const nro = opt.getAttribute('data-nro');
        const venc = opt.getAttribute('data-venc');
        const idProd = opt.getAttribute('data-id-producto');

        if (idProd) {
            const selectProd = document.getElementById('ingresoProductoSelect');
            if (selectProd && selectProd.value !== String(idProd)) {
                selectProd.value = String(idProd);
            }
        }
        if (nro) document.getElementById('ingresoNroLote').value = nro;
        if (venc) document.getElementById('ingresoVencimiento').value = venc;
    }

    async function guardarIngresoMercaderia(e) {
        e.preventDefault();
        const prodId = document.getElementById('ingresoProductoSelect').value;
        const provId = document.getElementById('ingresoProveedorSelect').value;
        const cant = Number(document.getElementById('ingresoCantidad').value);
        const nroLote = document.getElementById('ingresoNroLote').value.trim();
        const fechaVenc = document.getElementById('ingresoVencimiento').value;
        const selectLote = document.getElementById('ingresoLoteExistenteSelect');
        const idLote = (modoLoteActual === 'existente' && selectLote && selectLote.value) ? Number(selectLote.value) : null;

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
        if (modoLoteActual === 'existente' && !idLote && !nroLote) {
            alert('Debe seleccionar un lote existente o cambiar a la opción "Nuevo Lote".');
            return;
        }
        if (!nroLote) {
            alert('Debe ingresar un número de lote.');
            return;
        }
        if (!fechaVenc) {
            alert('Debe seleccionar la fecha de vencimiento del lote.');
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
            nro_lote: nroLote,
            fecha_vencimiento: fechaVenc,
            motivo: modoLoteActual === 'existente' ? `Ingreso a lote existente ${nroLote}` : `Ingreso nuevo lote ${nroLote}`
        };
        if (idLote) item.id_lote = idLote;

        const payload = {
            id_proveedor: Number(provId),
            items: [item],
            // Compatibilidad directa
            id_producto: Number(prodId),
            cantidad: cant,
            id_unidad: idUnidad,
            nro_lote: nroLote,
            fecha_vencimiento: fechaVenc,
            id_lote: idLote
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
                alert(`¡Ingreso registrado exitosamente!\nSe ingresaron ${cant}${unidadNom}${aclaracionEquiv} asociadas al lote ${nroLote} y se registró el movimiento en el historial (Kardex).`);
            } else {
                alert(data.message || 'Error al registrar ingreso');
            }
        } catch (err) {
            console.error('Error ingreso mercadería:', err);
            alert('Error de conexión al registrar ingreso.');
        }
    }

    // Modal Nuevo Lote Directo
    function abrirModalNuevoLote() {
        poblarSelectsProductos();
        document.getElementById('nuevoLoteNro').value = `LOTE-${Date.now().toString().slice(-4)}`;
        document.getElementById('nuevoLoteCantidad').value = '10';
        document.getElementById('nuevoLoteVencimiento').value = new Date(Date.now() + 180*24*60*60*1000).toISOString().split('T')[0];
        document.getElementById('nuevoLoteAsociarIngreso').checked = true;
        poblarUnidadesNuevoLote();
        actualizarEquivalenciaNuevoLote();
        document.getElementById('modalNuevoLote').classList.remove('hidden');
        if (window.lucide) window.lucide.createIcons();
    }

    function onProductoNuevoLoteChange() {
        poblarUnidadesNuevoLote();
        actualizarEquivalenciaNuevoLote();
    }

    function poblarUnidadesNuevoLote() {
        const prodId = document.getElementById('nuevoLoteProductoSelect')?.value;
        const selectUnidad = document.getElementById('nuevoLoteUnidadSelect');
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

    function actualizarEquivalenciaNuevoLote() {
        const prodId = document.getElementById('nuevoLoteProductoSelect')?.value;
        const cant = Number(document.getElementById('nuevoLoteCantidad')?.value || 0);
        const selectUnidad = document.getElementById('nuevoLoteUnidadSelect');
        const box = document.getElementById('nuevoLoteEquivalenciaBox');
        const texto = document.getElementById('nuevoLoteEquivalenciaTexto');
        const lblCant = document.getElementById('lblNuevoLoteCantidad');

        const info = obtenerInfoUnidadProducto(prodId);
        const idUnidadSeleccionada = selectUnidad?.value;

        if (idUnidadSeleccionada && info.tieneActiva && String(idUnidadSeleccionada) === String(info.id_unidad)) {
            const cantBase = Math.round(cant * info.equivalencia);
            if (lblCant) lblCant.textContent = `Cantidad (${info.nombre})`;
            if (box && texto) {
                texto.innerHTML = `Equivalencia: <strong>${cant} ${info.nombre}</strong> equivalen a <strong>${cantBase} unidades individuales (u.)</strong> que conformarán el lote.`;
                box.classList.remove('hidden');
            }
        } else {
            if (lblCant) lblCant.textContent = 'Cantidad del Lote';
            if (box) box.classList.add('hidden');
        }
        if (window.lucide) window.lucide.createIcons();
    }

    function cerrarModalNuevoLote() {
        document.getElementById('modalNuevoLote').classList.add('hidden');
    }

    async function guardarNuevoLote(e) {
        e.preventDefault();
        const prodId = document.getElementById('nuevoLoteProductoSelect').value;
        if (!prodId) {
            alert('Debe seleccionar un producto.');
            return;
        }
        const cant = Number(document.getElementById('nuevoLoteCantidad').value);
        const nroLote = document.getElementById('nuevoLoteNro').value.trim();
        const fechaVenc = document.getElementById('nuevoLoteVencimiento').value;

        if (!cant || cant <= 0) {
            alert('La cantidad debe ser mayor a cero.');
            return;
        }
        if (!nroLote) {
            alert('El número de lote es obligatorio.');
            return;
        }
        if (!fechaVenc) {
            alert('La fecha de vencimiento es obligatoria.');
            return;
        }

        const rawUnidad = document.getElementById('nuevoLoteUnidadSelect')?.value;
        const idUnidad = (rawUnidad && Number(rawUnidad) > 0) ? Number(rawUnidad) : null;
        const info = obtenerInfoUnidadProducto(prodId);
        const esUnidadActiva = Boolean(idUnidad && info.tieneActiva && String(idUnidad) === String(info.id_unidad));
        const cantBase = esUnidadActiva ? Math.round(cant * info.equivalencia) : cant;

        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const payload = {
            id_producto: Number(prodId),
            nro_lote: nroLote,
            cantidad: cant,
            id_unidad: idUnidad,
            fecha_vencimiento: fechaVenc,
            asociar_ingreso: document.getElementById('nuevoLoteAsociarIngreso').checked,
            motivo: `Alta de lote ${nroLote}`
        };

        try {
            const res = await fetch(`${API_BASE}/stock/lotes`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                cerrarModalNuevoLote();
                await recargarTodo();
                const aclaracionEquiv = esUnidadActiva ? ` (${cant} ${info.nombre} = ${cantBase} u. individuales)` : '';
                alert(`¡Lote ${nroLote} creado exitosamente!${aclaracionEquiv}`);
            } else {
                alert(data.message || 'Error al registrar lote');
            }
        } catch (err) {
            console.error('Error nuevo lote:', err);
            alert('Error de conexión al registrar lote');
        }
    }

    // Modal Ajuste (S06)
    let modoLoteAjuste = 'existente';

    function cambiarModoLoteAjuste(modo) {
        modoLoteAjuste = modo;
        const btnExistente = document.getElementById('btnAjusteModoExistente');
        const btnNuevo = document.getElementById('btnAjusteModoNuevo');
        const contExistente = document.getElementById('contenedorAjusteLoteExistente');
        const contNuevo = document.getElementById('contenedorAjusteLoteNuevo');

        if (modo === 'nuevo') {
            btnNuevo?.classList.add('bg-blue-600', 'text-white', 'font-extrabold', 'shadow-xs');
            btnNuevo?.classList.remove('text-slate-600');
            btnExistente?.classList.remove('bg-blue-600', 'text-white', 'font-extrabold', 'shadow-xs');
            btnExistente?.classList.add('text-slate-600');
            contExistente?.classList.add('hidden');
            contNuevo?.classList.remove('hidden');
        } else {
            btnExistente?.classList.add('bg-blue-600', 'text-white', 'font-extrabold', 'shadow-xs');
            btnExistente?.classList.remove('text-slate-600');
            btnNuevo?.classList.remove('bg-blue-600', 'text-white', 'font-extrabold', 'shadow-xs');
            btnNuevo?.classList.add('text-slate-600');
            contExistente?.classList.remove('hidden');
            contNuevo?.classList.add('hidden');
        }
        onLoteAjusteChange();
    }

    function onTipoAjusteChange() {
        const tipo = document.getElementById('ajusteTipo')?.value || 'aumentar';
        const tabs = document.getElementById('ajusteModoTabs');
        const labelCant = document.getElementById('ajusteCantidadLabel');
        const msgSinLotes = document.getElementById('msgSinLotesAjuste');

        if (labelCant) {
            labelCant.textContent = tipo === 'fijar' ? 'Nueva Cantidad Exacta del Lote' : 'Cantidad';
        }

        if (msgSinLotes && !msgSinLotes.classList.contains('hidden')) {
            const prodId = document.getElementById('ajusteProductoSelect')?.value;
            const producto = productosCache.find(p => String(p.id_producto) === String(prodId));
            const stockActual = Number(producto?.stock ?? producto?.stock_disponible ?? 0);
            msgSinLotes.textContent = tipo === 'disminuir'
                ? `No hay lotes registrados. La disminución se aplicará al stock general (${stockActual} u.), sin asociarse a un lote.`
                : tipo === 'aumentar'
                    ? 'Este producto no posee lotes. Para aumentar el stock, registre un Nuevo Lote.'
                    : 'Este producto no posee lotes registrados para fijar una cantidad exacta.';
        }

        if (tipo === 'disminuir' || tipo === 'fijar') {
            if (tabs) tabs.classList.add('hidden');
            cambiarModoLoteAjuste('existente');
        } else {
            if (tabs) tabs.classList.remove('hidden');
        }
        onLoteAjusteChange();
    }

    async function abrirModalAjuste(prodId = null, loteId = null) {
        document.getElementById('modalAjuste').classList.remove('hidden');
        cambiarModoLoteAjuste('existente');
        const inputNroNuevo = document.getElementById('ajusteNroLoteNuevo');
        const inputVencNuevo = document.getElementById('ajusteVencimientoNuevo');
        if (inputNroNuevo) inputNroNuevo.value = '';
        if (inputVencNuevo) inputVencNuevo.value = '';

        const sel = document.getElementById('ajusteProductoSelect');
        if (sel) {
            sel.innerHTML = '<option value="">Seleccione un producto...</option>' + 
                productosCache.filter(p => p.estado === 'activo').map(p => `<option value="${p.id_producto}">${p.codigo} - ${p.nombre} (Stock: ${formatoCantidadProductoCorta(p.stock ?? p.stock_disponible ?? 0, p)})</option>`).join('');
            if (prodId) {
                sel.value = String(prodId);
            }
        }
        await actualizarLotesAjuste(loteId);
        poblarUnidadesAjuste();
        actualizarEquivalenciaAjuste();
        onTipoAjusteChange();
        if (window.lucide) window.lucide.createIcons();
    }

    function onProductoAjusteChange() {
        actualizarLotesAjuste();
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
            if (lblCant) lblCant.textContent = tipo === 'fijar' ? 'Nueva Cantidad Exacta del Lote' : 'Cantidad';
            if (box) box.classList.add('hidden');
        }
        if (window.lucide) window.lucide.createIcons();
    }

    function cerrarModalAjuste() { 
        document.getElementById('modalAjuste').classList.add('hidden'); 
    }

    async function actualizarLotesAjuste(preselectedLoteId = null) {
        const prodId = document.getElementById('ajusteProductoSelect')?.value;
        const selectLote = document.getElementById('ajusteLoteSelect');
        const infoDiv = document.getElementById('ajusteLoteInfo');
        const resumenDescuadre = document.getElementById('ajusteResumenDescuadre');
        const msgSinLotes = document.getElementById('msgSinLotesAjuste');

        if (!selectLote) return;
        if (infoDiv) infoDiv.classList.add('hidden');
        if (resumenDescuadre) resumenDescuadre.classList.add('hidden');
        if (msgSinLotes) msgSinLotes.classList.add('hidden');

        if (!prodId) {
            selectLote.innerHTML = '<option value="">-- Primero seleccione un producto --</option>';
            return;
        }

        const prod = productosCache.find(p => String(p.id_producto) === String(prodId));
        const stockActual = Number(prod?.stock ?? prod?.stock_disponible ?? 0);

        selectLote.innerHTML = '<option value="">Cargando lotes del producto...</option>';

        let lotes = [];
        try {
            const res = await fetch(`${API_BASE}/stock/productos/${prodId}/lotes`);
            const json = await res.json();
            if (json.status === 'success' && json.data) {
                if (Array.isArray(json.data)) {
                    lotes = json.data;
                } else if (Array.isArray(json.data.lotes)) {
                    lotes = json.data.lotes;
                }
            }
        } catch (e) {
            console.error('Error al cargar lotes para ajuste:', e);
        }

        // Respaldo defensivo con lotesCache
        if (lotes.length === 0 && Array.isArray(lotesCache)) {
            lotes = lotesCache.filter(l => String(l.id_producto) === String(prodId));
        }

        // Evaluar consistencia entre Stock del Producto y Suma de Lotes
        const sumaLotes = lotes.reduce((acc, l) => acc + Number(l.cantidad_actual ?? 0), 0);
        if (resumenDescuadre && lotes.length > 0) {
            if (stockActual !== sumaLotes) {
                const dif = Math.abs(stockActual - sumaLotes);
                resumenDescuadre.innerHTML = `
                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200/90 text-xs space-y-2">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <span class="font-black text-amber-900 flex items-center gap-1.5">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                                Descuadre Lotes vs Stock General
                            </span>
                            <button type="button" onclick="sincronizarStockConLotesDirecto(${prodId})" class="clay-btn-primary px-2.5 py-1 text-[11px] font-black shrink-0 shadow-xs" title="Iguala el stock general a la suma de lotes">
                                Sincronizar Stock con Lotes
                            </button>
                        </div>
                        <p class="text-amber-800 text-[11px] font-bold">
                            Stock General: <span class="font-black text-slate-800">${stockActual} u.</span> | Suma de Lotes: <span class="font-black text-slate-800">${sumaLotes} u.</span> (Diferencia: <span class="text-rose-700 font-black">${dif} u.</span>). Si tenés diferencias históricas, podés sincronizarlas con el botón arriba.
                        </p>
                    </div>
                `;
                resumenDescuadre.classList.remove('hidden');
            } else {
                resumenDescuadre.innerHTML = `
                    <div class="p-2 bg-emerald-50 rounded-lg border border-emerald-200/80 text-[11px] text-emerald-800 font-bold flex items-center justify-between">
                        <span>✓ Stock general (${stockActual} u.) en perfecta coincidencia con la suma de lotes.</span>
                    </div>
                `;
                resumenDescuadre.classList.remove('hidden');
            }
        }

        if (lotes.length === 0) {
            selectLote.innerHTML = '<option value="">-- No hay lotes registrados para este producto --</option>';
            const tipo = document.getElementById('ajusteTipo')?.value;
            if (msgSinLotes) {
                msgSinLotes.textContent = tipo === 'disminuir'
                    ? `No hay lotes registrados. La disminución se aplicará al stock general (${stockActual} u.), sin asociarse a un lote.`
                    : tipo === 'aumentar'
                        ? 'Este producto no posee lotes. Para aumentar el stock, registre un Nuevo Lote.'
                        : 'Este producto no posee lotes registrados para fijar una cantidad exacta.';
                msgSinLotes.classList.remove('hidden');
            }
            if (tipo === 'aumentar') {
                cambiarModoLoteAjuste('nuevo');
            }
        } else {
            let html = '<option value="">-- Seleccione un Lote Existente Obligatorio --</option>';
            html += lotes.map(l => {
                const venc = l.fecha_vencimiento ? ` | Vence: ${l.fecha_vencimiento}` : '';
                return `<option value="${l.id_lote}" data-stock="${l.cantidad_actual}">Lote: ${l.nro_lote} (Stock en lote: ${l.cantidad_actual} u.${venc})</option>`;
            }).join('');
            selectLote.innerHTML = html;
        }

        if (preselectedLoteId) {
            selectLote.value = String(preselectedLoteId);
            cambiarModoLoteAjuste('existente');
        }

        onTipoAjusteChange();
        if (window.lucide) window.lucide.createIcons();
    }

    function onLoteAjusteChange() {
        const selectLote = document.getElementById('ajusteLoteSelect');
        const infoDiv = document.getElementById('ajusteLoteInfo');
        const tipoAjuste = document.getElementById('ajusteTipo')?.value;

        if (!selectLote || !infoDiv) return;

        if (modoLoteAjuste === 'nuevo') {
            infoDiv.classList.add('hidden');
            return;
        }

        const selectedOption = selectLote.options[selectLote.selectedIndex];
        if (selectedOption && selectedOption.dataset.stock !== undefined && selectLote.value) {
            const stockLote = Number(selectedOption.dataset.stock);
            const badgeClass = stockLote > 0 ? 'clay-badge-info' : 'clay-badge-danger';
            
            let accionTxt = 'Se sumará al lote y obligatoriamente al producto';
            if (tipoAjuste === 'disminuir') accionTxt = 'Se descontará del lote y obligatoriamente del producto';
            else if (tipoAjuste === 'fijar') accionTxt = 'Se fijará en el lote y el producto reflejará el cambio neto exacto';

            infoDiv.innerHTML = `<span class="clay-badge ${badgeClass} font-black text-xs">Stock actual en lote: ${stockLote} u. (${accionTxt})</span>`;
            infoDiv.classList.remove('hidden');
        } else {
            infoDiv.classList.add('hidden');
        }
    }

    async function sincronizarStockConLotesDirecto(prodId) {
        if (!confirm('¿Desea sincronizar automáticamente el stock general del producto para que iguale exactamente a la suma de sus lotes físicos?')) {
            return;
        }
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        try {
            const res = await fetch(`${API_BASE}/stock/ajustes`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    id_producto: Number(prodId),
                    alcance: 'sincronizar_producto',
                    motivo: 'Sincronización de stock general con la suma de lotes físicos'
                })
            });
            const data = await res.json();
            if (data.status === 'success') {
                alert(data.message || 'Stock sincronizado correctamente.');
                await recargarTodo();
                await actualizarLotesAjuste();
            } else {
                alert(data.message || 'Error al sincronizar stock');
            }
        } catch (e) {
            alert('Error de conexión al sincronizar');
        }
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
        const info = obtenerInfoUnidadProducto(prodId);
        const esUnidadActiva = Boolean(idUnidad && info.tieneActiva && String(idUnidad) === String(info.id_unidad));
        const cantBase = esUnidadActiva ? Math.round(cantidad * info.equivalencia) : cantidad;

        const payload = {
            id_producto: Number(prodId),
            tipo: tipo,
            cantidad: cantidad,
            id_unidad: idUnidad,
            motivo: motivo
        };

        if (modoLoteAjuste === 'existente') {
            const loteId = document.getElementById('ajusteLoteSelect')?.value;
            if (!loteId) {
                const selectLote = document.getElementById('ajusteLoteSelect');
                const hayLotes = Array.from(selectLote?.options || []).some(opcion => opcion.value !== '');
                if (tipo !== 'disminuir' || hayLotes) {
                    alert(hayLotes
                        ? 'Seleccione el lote del que desea descontar.'
                        : 'Para este ajuste debe seleccionar o crear un lote.');
                    return;
                }
            } else {
                payload.id_lote = Number(loteId);
            }
        } else {
            const nroLote = document.getElementById('ajusteNroLoteNuevo')?.value.trim();
            if (!nroLote) {
                alert('Debe ingresar el N° de lote para registrar el nuevo lote.');
                return;
            }
            payload.nro_lote = nroLote;
            const venc = document.getElementById('ajusteVencimientoNuevo')?.value;
            if (venc) {
                payload.fecha_vencimiento = venc;
            }
        }

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

    function actualizarInfoConteo() {
        const id = document.getElementById('conteoProductoSelect')?.value;
        const prod = productosCache.find(p => String(p.id_producto) === id);
        const inputSis = document.getElementById('conteoStockSistema');
        if (inputSis) inputSis.value = prod ? `${prod.stock ?? prod.stock_disponible ?? 0} u.` : '-';
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

        const prod = productosCache.find(p => String(p.id_producto) === id);
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
            diagEl.textContent = `▲ Se detectó un sobrante de ${dif} unidad(es) física(s) respecto al stock teórico del sistema.`;
        } else {
            difSpan.className = 'font-black text-sm text-rose-600';
            difSpan.textContent = `${dif} u. (Faltante)`;
            diagEl.className = 'text-xs font-extrabold text-rose-700 pt-0.5';
            diagEl.textContent = `▼ Se detectó un faltante de ${Math.abs(dif)} unidad(es) física(s) respecto al sistema (inconsistencia).`;
        }
    }

    async function ejecutarConteoFisico(e) {
        e.preventDefault();
        const id = document.getElementById('conteoProductoSelect').value;
        const cantFisica = Number(document.getElementById('conteoCantidadFisica').value);
        const prod = productosCache.find(p => String(p.id_producto) === id);
        if (!prod) return;

        const stockSis = Number(prod.stock ?? prod.stock_disponible ?? 0);
        const dif = cantFisica - stockSis;
        if (dif === 0) {
            alert('¡El conteo físico coincide exactamente con el stock del sistema! No se requiere ajuste.');
            return;
        }

        const tipo = dif > 0 ? 'aumentar' : 'disminuir';
        const obs = document.getElementById('conteoObs').value.trim();
        const motivo = `Conteo Físico (S13): Stock anterior ${stockSis} u., contaron ${cantFisica} u. (Dif: ${dif > 0 ? '+' : ''}${dif} u.). ${obs ? 'Obs: ' + obs : ''}`;
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const payload = {
            id_producto: id,
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
                alert(`¡Conteo físico procesado exitosamente!\nStock de ${prod.nombre} conciliado a ${cantFisica} u. (Diferencia ajustada: ${dif > 0 ? '+' : ''}${dif} u.)`);
                document.getElementById('conteoCantidadFisica').value = '';
                document.getElementById('conteoObs').value = '';
                actualizarInfoConteo();
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
        onLoteDevolucionChange(contexto);
        actualizarEquivalenciaDevolucion(contexto);
    }

    function onProductoDevolucionChange(contexto = 'tab') {
        actualizarLotesDevolucion(contexto);
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

    async function actualizarLotesDevolucion(contexto = 'tab') {
        const prodId = contexto === 'modal' 
            ? document.getElementById('modalDevProductoSelect')?.value 
            : document.getElementById('devProductoSelect')?.value;
        const selectLote = contexto === 'modal'
            ? document.getElementById('modalDevLoteSelect')
            : document.getElementById('devLoteSelect');
        const infoDiv = contexto === 'modal'
            ? document.getElementById('modalDevLoteInfo')
            : document.getElementById('devLoteInfo');

        if (!selectLote) return;
        if (infoDiv) infoDiv.classList.add('hidden');

        if (!prodId) {
            selectLote.innerHTML = '<option value="">(Primero seleccione un producto)</option>';
            return;
        }

        selectLote.innerHTML = '<option value="">Cargando lotes del producto...</option>';

        let lotes = [];
        try {
            const res = await fetch(`${API_BASE}/stock/productos/${prodId}/lotes`);
            const json = await res.json();
            if (json.status === 'success' && json.data) {
                if (Array.isArray(json.data)) {
                    lotes = json.data;
                } else if (Array.isArray(json.data.lotes)) {
                    lotes = json.data.lotes;
                }
            }
        } catch (e) {
            console.error('Error al cargar lotes para devolución:', e);
        }

        // Respaldo defensivo con lotesCache
        if (lotes.length === 0 && Array.isArray(lotesCache)) {
            lotes = lotesCache.filter(l => String(l.id_producto) === String(prodId));
        }

        if (lotes.length === 0) {
            selectLote.innerHTML = '<option value="">(Sin lotes registrados para este producto - Stock general)</option>';
        } else {
            let html = '<option value="">(Sin lote específico - Stock general)</option>';
            html += lotes.map(l => {
                const venc = l.fecha_vencimiento ? ` | Vence: ${l.fecha_vencimiento}` : '';
                return `<option value="${l.id_lote}" data-stock="${l.cantidad_actual}">Lote: ${l.nro_lote} (Stock en lote: ${l.cantidad_actual} u.${venc})</option>`;
            }).join('');
            selectLote.innerHTML = html;
        }
        onLoteDevolucionChange(contexto);
    }

    function onLoteDevolucionChange(contexto = 'tab') {
        const selectLote = contexto === 'modal' ? document.getElementById('modalDevLoteSelect') : document.getElementById('devLoteSelect');
        const infoDiv = contexto === 'modal' ? document.getElementById('modalDevLoteInfo') : document.getElementById('devLoteInfo');
        const tipoDev = contexto === 'modal' ? document.getElementById('modalDevTipo')?.value : document.getElementById('devTipo')?.value;
        if (!selectLote || !infoDiv) return;

        const selectedOption = selectLote.options[selectLote.selectedIndex];
        if (selectedOption && selectedOption.dataset.stock !== undefined && selectLote.value) {
            const stockLote = Number(selectedOption.dataset.stock);
            const badgeClass = stockLote > 0 ? 'clay-badge-info' : 'clay-badge-danger';
            const accionTxt = tipoDev === 'proveedor' ? 'Se descontará del lote' : 'Se repondrá al lote';
            infoDiv.innerHTML = `<span class="clay-badge ${badgeClass} font-black text-xs">Stock actual en lote: ${stockLote} u. (${accionTxt})</span>`;
            infoDiv.classList.remove('hidden');
        } else {
            infoDiv.classList.add('hidden');
        }
    }

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
        const loteId = contexto === 'modal'
            ? document.getElementById('modalDevLoteSelect')?.value
            : document.getElementById('devLoteSelect')?.value;

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
        if (loteId) {
            payload.id_lote = Number(loteId);
        }

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
                    await actualizarLotesDevolucion('tab');
                }
            } else {
                alert(data.message || 'Error en devolución');
            }
        } catch (err) { alert('Error de conexión'); }
    }

    // Modal Aumento Masivo (P07)
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
        const reglaRedondeo = document.getElementById('aumentoRedondeo')?.value || 'sin_redondeo';
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
            const calculado = pct > 0 ? Math.round(actual * (1 + pct / 100) * 100) / 100 : actual;
            const precioEnCentavos = Math.round(calculado * 100);
            const unidadPeso = Math.floor(precioEnCentavos / 100);
            let nuevoEnCentavos = precioEnCentavos;

            if (reglaRedondeo === 'peso_mas_cercano') {
                nuevoEnCentavos = Math.round(calculado) * 100;
            } else if (reglaRedondeo === 'peso_superior') {
                nuevoEnCentavos = Math.ceil(calculado) * 100;
            } else if (reglaRedondeo === 'terminar_90' || reglaRedondeo === 'terminar_99') {
                const terminacion = reglaRedondeo === 'terminar_90' ? 90 : 99;
                const candidato = unidadPeso * 100 + terminacion;
                nuevoEnCentavos = precioEnCentavos <= candidato ? candidato : (unidadPeso + 1) * 100 + terminacion;
            }

            const nuevo = (nuevoEnCentavos / 100).toFixed(2);
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
        const reglaRedondeo = document.getElementById('aumentoRedondeo').value;

        if (!porcentaje || porcentaje <= 0) {
            alert('Debe ingresar un porcentaje mayor a 0');
            return;
        }

        const payload = {
            tipo_criterio: crit,
            porcentaje: porcentaje,
            regla_redondeo: reglaRedondeo,
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
                ? 'Historial Global de Modificaciones y Auditoría de Productos (P04)'
                : 'Historial Global de Precios y Aumentos Masivos (P08)';
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
                        ? '<span class="clay-badge clay-badge-info font-black">Aumento Masivo (P07)</span>' 
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

    // Modal Categorías (P02)
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
                        <button type="button" onclick="editarCategoria(${c.id_categoria}, '${c.nombre.replace(/'/g, "\\'")}')" class="p-1 text-blue-600 hover:bg-blue-50 rounded" title="Editar Categoría (P02)">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                        </button>
                        <button type="button" onclick="eliminarCategoria(${c.id_categoria}, '${c.nombre.replace(/'/g, "\\'")}', ${c.total_productos ?? 0})" class="p-1 text-rose-600 hover:bg-rose-50 rounded" title="Dar de Baja / Eliminar Categoría (P02)">
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
            alert(`No se puede dar de baja la categoría "${nombre}" porque está siendo utilizada por ${totalProductos} producto(s). (Criterio P02)`);
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

    // Modal Marcas (P03)
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
                        <button type="button" onclick="editarMarca(${m.id_marca}, '${m.nombre.replace(/'/g, "\\'")}')" class="p-1 text-blue-600 hover:bg-blue-50 rounded" title="Editar Marca (P03)">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                        </button>
                        <button type="button" onclick="eliminarMarca(${m.id_marca}, '${m.nombre.replace(/'/g, "\\'")}', ${m.total_productos ?? 0})" class="p-1 text-rose-600 hover:bg-rose-50 rounded" title="Dar de Baja / Eliminar Marca (P03)">
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
            alert(`No se puede dar de baja la marca "${nombre}" porque está siendo utilizada por ${totalProductos} producto(s). (Criterio P03)`);
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
    // MODAL: GESTIÓN DE UBICACIONES (S14)
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
                            <button type="button" onclick="verProductosUbicacion(${u.id_ubicacion}, '${u.descripcion.replace(/'/g, "\\'")}')" class="p-1 text-emerald-600 hover:bg-emerald-50 rounded" title="Ver productos en esta ubicación (S14 Criterio 5)">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            </button>
                            <button type="button" onclick="editarUbicacion(${u.id_ubicacion}, '${u.descripcion.replace(/'/g, "\\'")}')" class="p-1 text-blue-600 hover:bg-blue-50 rounded" title="Editar Ubicación (S14)">
                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                            </button>
                            <button type="button" onclick="eliminarUbicacion(${u.id_ubicacion}, '${u.descripcion.replace(/'/g, "\\'")}', ${count})" class="p-1 text-rose-600 hover:bg-rose-50 rounded" title="Dar de baja / eliminar ubicación (S14 Criterio 6)">
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
            alert(`No se puede eliminar la ubicación "${descripcion}" porque tiene ${productosCount} producto(s) asignados. (S14 Criterio 6)`);
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
    // MODAL: GESTIÓN DE UNIDADES Y EQUIVALENCIAS (S08)
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

    // Modal Devoluciones (S05)
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
        await actualizarLotesDevolucion('modal');
        poblarUnidadesDevolucion('modal');
        actualizarEquivalenciaDevolucion('modal');
        if (window.lucide) window.lucide.createIcons();
    }
    function cerrarModalDevolucion() { 
        document.getElementById('modalDevolucion').classList.add('hidden'); 
    }
</script>
@endsection
