@extends('layouts.g3')

@section('title', 'Distribuidora PyB - Panel Principal')
@section('page_title', 'Inicio / Panel')
@section('page_subtitle', 'Distribuidora PyB – Sistema de Gestión Comercial')

@section('content')
@php
    $user = auth()->user();
    $rol = $user?->rol;
    $rolValue = is_object($rol) ? $rol->value : (string) ($rol ?? 'administrativo');
@endphp

<div class="max-w-6xl mx-auto w-full py-2 flex flex-col justify-between gap-8">

    <!-- CABECERA PRINCIPAL (Claymorphism Header) -->
    <div class="bg-white/80 backdrop-blur-md border border-white/80 rounded-2xl px-6 py-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-[inset_3px_3px_6px_rgba(255,255,255,0.9),0_10px_30px_rgba(13,110,253,0.04)]">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-black shadow-md shadow-blue-500/15 shrink-0">
                <i data-lucide="package" class="w-6 h-6 text-white"></i>
            </div>
            <div class="text-left">
                <div class="flex items-center gap-2">
                    <h1 class="text-lg md:text-xl font-black text-slate-800 tracking-tight leading-none">DISTRIBUIDORA PyB</h1>
                    @if ($rolValue === 'administrativo')
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-blue-100 text-blue-700">Panel administrativo</span>
                    @elseif ($rolValue === 'repartidor')
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700">Panel repartidor</span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-purple-100 text-purple-700">Panel contador</span>
                    @endif
                </div>
                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mt-1">
                    Bienvenido, {{ $user?->nombre ?? 'Usuario' }} &bull; Rol: {{ ucfirst($rolValue) }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 md:gap-4 self-end md:self-auto">
            <!-- Botón Refrescar -->
            <button onclick="window.location.reload()" 
                    class="flex items-center gap-2 px-3.5 py-2 bg-white/95 hover:bg-slate-50 text-slate-700 hover:text-blue-600 rounded-full border border-slate-200/60 shadow-sm text-xs font-bold cursor-pointer transition-all"
                    title="Refrescar la página">
                <i data-lucide="rotate-cw" class="w-4 h-4 text-blue-600"></i>
                <span class="hidden sm:inline">Refrescar</span>
            </button>

            @if ($rolValue !== 'contador')
                <!-- Notificaciones de Stock -->
                <a href="{{ url('/inventario') }}" class="relative p-2.5 bg-white/95 rounded-full border border-slate-200/60 shadow-sm hover:bg-slate-50 transition-colors cursor-pointer group" title="Alertas de Stock">
                    <i data-lucide="bell" class="w-5 h-5 text-slate-600 group-hover:scale-110 transition-transform"></i>
                    <span id="alertCountBadge" class="absolute top-1 right-1 w-5 h-5 bg-red-500 border-2 border-white rounded-full text-[10px] font-black text-white flex items-center justify-center">!</span>
                </a>
            @endif

            <!-- Iniciales / Rol -->
            <div class="flex items-center gap-2.5 bg-white/95 border border-slate-200/60 p-1.5 pr-4 rounded-full shadow-sm">
                <div class="w-9 h-9 rounded-full bg-blue-600 text-white font-black flex items-center justify-center text-sm shadow-md shadow-blue-500/10 shrink-0 uppercase">
                    {{ strtoupper(substr($user?->nombre ?? 'US', 0, 2)) }}
                </div>
                <div class="hidden sm:block text-left">
                    <div class="text-xs font-black text-slate-800 leading-none">{{ $user?->nombre ?? 'Usuario' }}</div>
                    <div class="text-[9px] font-extrabold text-blue-500 uppercase tracking-wider mt-1 capitalize">{{ $rolValue }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN ESPECÍFICA SEGÚN ROL -->

    @if ($rolValue === 'administrativo')
        <!-- ========================================== -->
        <!-- VISTA DE ROL: ADMINISTRATIVO               -->
        <!-- ========================================== -->
        <div class="flex items-center justify-between pb-1 border-b border-slate-200/60">
            <div>
                <h2 class="text-base font-black text-slate-800">Panel administrativo – Control Total</h2>
                <p class="text-xs font-semibold text-slate-500">Acceso integral a operaciones de compras, stock, catálogo, auditoría y accesos</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('users.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 transition-all flex items-center gap-1.5">
                    <i data-lucide="users" class="w-3.5 h-3.5"></i>
                    <span>Lista de Usuarios</span>
                </a>
                <a href="{{ route('reports.index') }}" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-xl border border-indigo-200 transition-all flex items-center gap-1.5">
                    <i data-lucide="bar-chart-2" class="w-3.5 h-3.5"></i>
                    <span>Reportes y Finanzas</span>
                </a>
            </div>
        </div>

        <!-- GRIDS DE MÓDULOS DE ACCESO - 3 columnas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- 1. STOCK E INVENTARIO -->
            <div class="clay-card p-6 flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200 border border-white/60">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-black">
                            <i data-lucide="boxes" class="w-6 h-6"></i>
                        </div>
                        <span class="text-xs font-extrabold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full border border-blue-100">
                            Inventario & Stock
                        </span>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800">Stock e Inventario</h2>
                        <p class="text-xs text-slate-500 font-bold mt-1">
                            Catálogo general de productos, conteo físico, movimientos Kardex y formatos de bulto.
                        </p>
                    </div>
                </div>
                <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ url('/inventario') }}" class="text-xs font-extrabold text-blue-600 hover:text-blue-800 flex items-center gap-1 transition-colors">
                        <span>Gestionar inventario</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>

            <!-- 2. COMPRAS Y ÓRDENES -->
            <div class="clay-card p-6 flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200 border border-white/60">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-black">
                            <i data-lucide="shopping-bag" class="w-6 h-6"></i>
                        </div>
                        <span class="text-xs font-extrabold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full border border-amber-100">
                            Abastecimiento
                        </span>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800">Compras y Órdenes</h2>
                        <p class="text-xs text-slate-500 font-bold mt-1">
                            Emisión de órdenes de compra, recepción directa de mercadería y seguimiento de estados.
                        </p>
                    </div>
                </div>
                <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ url('/compras') }}" class="text-xs font-extrabold text-amber-600 hover:text-amber-800 flex items-center gap-1 transition-colors">
                        <span>Gestionar compras</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>

            <!-- 3. PROVEEDORES Y TARIFAS -->
            <div class="clay-card p-6 flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200 border border-white/60">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-black">
                            <i data-lucide="truck" class="w-6 h-6"></i>
                        </div>
                        <span class="text-xs font-extrabold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full border border-indigo-100">
                            Comercial
                        </span>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800">Proveedores</h2>
                        <p class="text-xs text-slate-500 font-bold mt-1">
                            Padrón de proveedores, historial comparativo de aumentos de precios y plazos acordados.
                        </p>
                    </div>
                </div>
                <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ url('/proveedores') }}" class="text-xs font-extrabold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 transition-colors">
                        <span>Gestionar proveedores</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        </div>

    @elseif ($rolValue === 'repartidor')
        <!-- ========================================== -->
        <!-- VISTA DE ROL: REPARTIDOR                   -->
        <!-- ========================================== -->
        <div class="pb-1 border-b border-slate-200/60">
            <h2 class="text-base font-black text-slate-800">Panel repartidor – Operaciones de Entrega</h2>
            <p class="text-xs font-semibold text-slate-500">Módulos habilitados para logística de reparto, consulta de pedidos y disponibilidad de stock</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- VENTAS Y PEDIDOS -->
            <div class="clay-card p-6 flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200 border border-white/60">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-black">
                            <i data-lucide="shopping-cart" class="w-6 h-6"></i>
                        </div>
                        <span class="text-xs font-extrabold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-100">
                            Logística
                        </span>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800">Ventas y Pedidos</h2>
                        <p class="text-xs text-slate-500 font-bold mt-1">
                            Consulta de pedidos asignados, ruteo de entregas y estado de cobranzas en destino.
                        </p>
                    </div>
                </div>
                <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ route('ventas.index') }}" class="text-xs font-extrabold text-emerald-600 hover:text-emerald-800 flex items-center gap-1 transition-colors">
                        <span>Abrir Ventas y Pedidos</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>

            <!-- INVENTARIO Y STOCK -->
            <div class="clay-card p-6 flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200 border border-white/60">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-black">
                            <i data-lucide="package-search" class="w-6 h-6"></i>
                        </div>
                        <span class="text-xs font-extrabold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full border border-blue-100">
                            Consulta
                        </span>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800">Inventario y Stock</h2>
                        <p class="text-xs text-slate-500 font-bold mt-1">
                            Consulta de unidades disponibles para venta y formatos de presentación por bulto.
                        </p>
                    </div>
                </div>
                <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ route('stock.index') }}" class="text-xs font-extrabold text-blue-600 hover:text-blue-800 flex items-center gap-1 transition-colors">
                        <span>Consultar Inventario y Stock</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        </div>

    @else
        <!-- ========================================== -->
        <!-- VISTA DE ROL: CONTADOR                     -->
        <!-- ========================================== -->
        <div class="pb-1 border-b border-slate-200/60">
            <h2 class="text-base font-black text-slate-800">Panel contador – Contabilidad y Finanzas</h2>
            <p class="text-xs font-semibold text-slate-500">Módulos habilitados para informes contables, cuentas corrientes y balance financiero</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- REPORTES CONTABLES -->
            <div class="clay-card p-6 flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200 border border-white/60">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center font-black">
                            <i data-lucide="file-spreadsheet" class="w-6 h-6"></i>
                        </div>
                        <span class="text-xs font-extrabold text-purple-600 bg-purple-50 px-2.5 py-1 rounded-full border border-purple-100">
                            Balances
                        </span>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800">Reportes contables</h2>
                        <p class="text-xs text-slate-500 font-bold mt-1">
                            Emisión de balances contables, auditoría de operaciones e informe impositivo.
                        </p>
                    </div>
                </div>
                <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ route('reports.index') }}" class="text-xs font-extrabold text-purple-600 hover:text-purple-800 flex items-center gap-1 transition-colors">
                        <span>Consultar Reportes contables</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>

            <!-- FINANZAS Y TESORERÍA -->
            <div class="clay-card p-6 flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200 border border-white/60">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-black">
                            <i data-lucide="landmark" class="w-6 h-6"></i>
                        </div>
                        <span class="text-xs font-extrabold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full border border-indigo-100">
                            Tesorería
                        </span>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800">Finanzas y Tesorería</h2>
                        <p class="text-xs text-slate-500 font-bold mt-1">
                            Seguimiento de cobros realizados, vencimientos y flujo de caja de la distribuidora.
                        </p>
                    </div>
                </div>
                <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ route('reports.index') }}" class="text-xs font-extrabold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 transition-colors">
                        <span>Abrir Finanzas y Tesorería</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
