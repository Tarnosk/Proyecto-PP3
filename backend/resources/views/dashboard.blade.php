@extends('layouts.app')

@section('title', 'Distribuidora PyB - Panel Principal')
@section('page_title', 'Inicio / Panel')
@section('page_subtitle', 'Distribuidora PyB • Sistema de Gestión')

@section('content')
<div class="max-w-6xl mx-auto w-full py-2 flex flex-col justify-between gap-8">

    <!-- CABECERA PRINCIPAL (Claymorphism Header idéntica a DashboardMainView) -->
    <div class="bg-white/80 backdrop-blur-md border border-white/80 rounded-2xl px-6 py-4 flex items-center justify-between shadow-[inset_3px_3px_6px_rgba(255,255,255,0.9),0_10px_30px_rgba(13,110,253,0.04)]">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-black shadow-md shadow-blue-500/15 shrink-0">
                <i data-lucide="package" class="w-6 h-6 text-white"></i>
            </div>
            <div class="text-left">
                <h1 class="text-lg md:text-xl font-black text-slate-800 tracking-tight leading-none">DISTRIBUIDORA PyB</h1>
                <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mt-1">Sistema de Gestión Comercial</p>
            </div>
        </div>

        <div class="flex items-center gap-3 md:gap-4">
            <!-- Botón Refrescar -->
            <button onclick="window.location.reload()" 
                    class="flex items-center gap-2 px-3.5 py-2 bg-white/95 hover:bg-slate-50 text-slate-700 hover:text-blue-600 rounded-full border border-slate-200/60 shadow-sm text-xs font-bold cursor-pointer transition-all"
                    title="Refrescar la página">
                <i data-lucide="rotate-cw" class="w-4 h-4 text-blue-600"></i>
                <span class="hidden sm:inline">Refrescar</span>
            </button>

            <!-- Notificaciones de Stock -->
            <a href="{{ url('/inventario') }}" class="relative p-2.5 bg-white/95 rounded-full border border-slate-200/60 shadow-sm hover:bg-slate-50 transition-colors cursor-pointer group" title="Alertas de Stock">
                <i data-lucide="bell" class="w-5 h-5 text-slate-600 group-hover:scale-110 transition-transform"></i>
                <span id="alertCountBadge" class="absolute top-1 right-1 w-5 h-5 bg-red-500 border-2 border-white rounded-full text-[10px] font-black text-white flex items-center justify-center">!</span>
            </a>
            
            <!-- Iniciales / Rol -->
            <div class="flex items-center gap-2.5 bg-white/95 border border-slate-200/60 p-1.5 pr-4 rounded-full shadow-sm">
                <div class="w-9 h-9 rounded-full bg-blue-600 text-white font-black flex items-center justify-center text-sm shadow-md shadow-blue-500/10 shrink-0">
                    AD
                </div>
                <div class="hidden sm:block text-left">
                    <div class="text-xs font-black text-slate-800 leading-none">Administrador</div>
                    <div class="text-[9px] font-extrabold text-blue-500 uppercase tracking-wider mt-1">Inventario & Compras</div>
                </div>
            </div>
        </div>
    </div>

    <!-- GRIDS DE MÓDULOS DE ACCESO - 3 columnas idénticas a DashboardMainView.tsx -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8 w-full max-w-6xl mx-auto">

        <!-- 1. STOCK E INVENTARIO -->
        <a href="{{ url('/inventario') }}" 
           class="clay-card clay-card-hover p-6 md:p-8 flex flex-col justify-between items-center text-center cursor-pointer relative overflow-hidden group select-none min-h-[230px] md:min-h-[260px] hover:shadow-blue-500/5">
            <div class="absolute -right-10 -bottom-10 w-32 h-32 rounded-full bg-slate-100/10 group-hover:scale-150 transition-transform duration-500"></div>
            
            <div class="flex flex-col items-center">
                <div class="w-14 h-14 md:w-16 md:h-16 rounded-2xl flex items-center justify-center shadow-[inset_2px_2px_4px_rgba(255,255,255,0.8)] transition-transform duration-300 group-hover:scale-110 bg-blue-50 text-blue-600 border border-blue-100">
                    <i data-lucide="package" class="w-7 h-7 md:w-8 md:h-8"></i>
                </div>
                <h3 class="text-lg md:text-xl font-black text-slate-800 mt-4 md:mt-5 leading-tight tracking-tight">
                    Stock e Inventario
                </h3>
                <p class="text-xs md:text-sm text-slate-500 font-bold mt-2 leading-relaxed px-1 max-w-[260px]">
                    Gestión de productos, catálogo, movimientos y alertas de stock.
                </p>
            </div>

            <div class="mt-4 md:mt-6 w-10 h-10 md:w-12 md:h-12 rounded-full border border-slate-200/60 bg-white/95 flex items-center justify-center shadow-[2px_2px_5px_rgba(0,0,0,0.05),-2px_-2px_5px_rgba(255,255,255,0.8)] text-slate-500 group-hover:bg-slate-50 group-hover:border-slate-300 group-hover:text-slate-800 transition-all shrink-0">
                <i data-lucide="chevron-right" class="w-5 h-5 md:w-6 md:h-6 group-hover:translate-x-0.5 transition-transform"></i>
            </div>
        </a>

        <!-- 2. COMPRAS Y ÓRDENES -->
        <a href="{{ url('/compras') }}" 
           class="clay-card clay-card-hover p-6 md:p-8 flex flex-col justify-between items-center text-center cursor-pointer relative overflow-hidden group select-none min-h-[230px] md:min-h-[260px] hover:shadow-indigo-500/5">
            <div class="absolute -right-10 -bottom-10 w-32 h-32 rounded-full bg-slate-100/10 group-hover:scale-150 transition-transform duration-500"></div>
            
            <div class="flex flex-col items-center">
                <div class="w-14 h-14 md:w-16 md:h-16 rounded-2xl flex items-center justify-center shadow-[inset_2px_2px_4px_rgba(255,255,255,0.8)] transition-transform duration-300 group-hover:scale-110 bg-indigo-50 text-indigo-600 border border-indigo-100">
                    <i data-lucide="shopping-cart" class="w-7 h-7 md:w-8 md:h-8"></i>
                </div>
                <h3 class="text-lg md:text-xl font-black text-slate-800 mt-4 md:mt-5 leading-tight tracking-tight">
                    Compras y Órdenes
                </h3>
                <p class="text-xs md:text-sm text-slate-500 font-bold mt-2 leading-relaxed px-1 max-w-[260px]">
                    Órdenes de compra a proveedores, recepciones parciales y pagos.
                </p>
            </div>

            <div class="mt-4 md:mt-6 w-10 h-10 md:w-12 md:h-12 rounded-full border border-slate-200/60 bg-white/95 flex items-center justify-center shadow-[2px_2px_5px_rgba(0,0,0,0.05),-2px_-2px_5px_rgba(255,255,255,0.8)] text-slate-500 group-hover:bg-slate-50 group-hover:border-slate-300 group-hover:text-slate-800 transition-all shrink-0">
                <i data-lucide="chevron-right" class="w-5 h-5 md:w-6 md:h-6 group-hover:translate-x-0.5 transition-transform"></i>
            </div>
        </a>

        <!-- 3. PROVEEDORES -->
        <a href="{{ url('/proveedores') }}" 
           class="clay-card clay-card-hover p-6 md:p-8 flex flex-col justify-between items-center text-center cursor-pointer relative overflow-hidden group select-none min-h-[230px] md:min-h-[260px] hover:shadow-sky-500/5">
            <div class="absolute -right-10 -bottom-10 w-32 h-32 rounded-full bg-slate-100/10 group-hover:scale-150 transition-transform duration-500"></div>
            
            <div class="flex flex-col items-center">
                <div class="w-14 h-14 md:w-16 md:h-16 rounded-2xl flex items-center justify-center shadow-[inset_2px_2px_4px_rgba(255,255,255,0.8)] transition-transform duration-300 group-hover:scale-110 bg-sky-50 text-sky-600 border border-sky-100">
                    <i data-lucide="users" class="w-7 h-7 md:w-8 md:h-8"></i>
                </div>
                <h3 class="text-lg md:text-xl font-black text-slate-800 mt-4 md:mt-5 leading-tight tracking-tight">
                    Proveedores
                </h3>
                <p class="text-xs md:text-sm text-slate-500 font-bold mt-2 leading-relaxed px-1 max-w-[260px]">
                    Administración de proveedores, plazos de entrega y costos.
                </p>
            </div>

            <div class="mt-4 md:mt-6 w-10 h-10 md:w-12 md:h-12 rounded-full border border-slate-200/60 bg-white/95 flex items-center justify-center shadow-[2px_2px_5px_rgba(0,0,0,0.05),-2px_-2px_5px_rgba(255,255,255,0.8)] text-slate-500 group-hover:bg-slate-50 group-hover:border-slate-300 group-hover:text-slate-800 transition-all shrink-0">
                <i data-lucide="chevron-right" class="w-5 h-5 md:w-6 md:h-6 group-hover:translate-x-0.5 transition-transform"></i>
            </div>
        </a>

        <!-- 5. SUGERENCIAS DE REPOSICIÓN -->
        <a href="{{ url('/sugerencias-reposicion') }}" 
           class="clay-card clay-card-hover p-6 md:p-8 flex flex-col justify-between items-center text-center cursor-pointer relative overflow-hidden group select-none min-h-[230px] md:min-h-[260px] hover:shadow-violet-500/5">
            <div class="absolute -right-10 -bottom-10 w-32 h-32 rounded-full bg-slate-100/10 group-hover:scale-150 transition-transform duration-500"></div>
            
            <div class="flex flex-col items-center">
                <div class="w-14 h-14 md:w-16 md:h-16 rounded-2xl flex items-center justify-center shadow-[inset_2px_2px_4px_rgba(255,255,255,0.8)] transition-transform duration-300 group-hover:scale-110 bg-violet-50 text-violet-600 border border-violet-100">
                    <i data-lucide="sparkles" class="w-7 h-7 md:w-8 md:h-8"></i>
                </div>
                <h3 class="text-lg md:text-xl font-black text-slate-800 mt-4 md:mt-5 leading-tight tracking-tight">
                    Sugerencias Reposición
                </h3>
                <p class="text-xs md:text-sm text-slate-500 font-bold mt-2 leading-relaxed px-1 max-w-[260px]">
                    Motor inteligente de cálculo para reorden automático de stock.
                </p>
            </div>

            <div class="mt-4 md:mt-6 w-10 h-10 md:w-12 md:h-12 rounded-full border border-slate-200/60 bg-white/95 flex items-center justify-center shadow-[2px_2px_5px_rgba(0,0,0,0.05),-2px_-2px_5px_rgba(255,255,255,0.8)] text-slate-500 group-hover:bg-slate-50 group-hover:border-slate-300 group-hover:text-slate-800 transition-all shrink-0">
                <i data-lucide="chevron-right" class="w-5 h-5 md:w-6 md:h-6 group-hover:translate-x-0.5 transition-transform"></i>
            </div>
        </a>

        <!-- 6. MÉTRICAS Y DASHBOARD -->
        <a href="{{ url('/dashboard-reposicion') }}" 
           class="clay-card clay-card-hover p-6 md:p-8 flex flex-col justify-between items-center text-center cursor-pointer relative overflow-hidden group select-none min-h-[230px] md:min-h-[260px] hover:shadow-emerald-500/5">
            <div class="absolute -right-10 -bottom-10 w-32 h-32 rounded-full bg-slate-100/10 group-hover:scale-150 transition-transform duration-500"></div>
            
            <div class="flex flex-col items-center">
                <div class="w-14 h-14 md:w-16 md:h-16 rounded-2xl flex items-center justify-center shadow-[inset_2px_2px_4px_rgba(255,255,255,0.8)] transition-transform duration-300 group-hover:scale-110 bg-emerald-50 text-emerald-600 border border-emerald-100">
                    <i data-lucide="trending-up" class="w-7 h-7 md:w-8 md:h-8"></i>
                </div>
                <h3 class="text-lg md:text-xl font-black text-slate-800 mt-4 md:mt-5 leading-tight tracking-tight">
                    Métricas de Reposición
                </h3>
                <p class="text-xs md:text-sm text-slate-500 font-bold mt-2 leading-relaxed px-1 max-w-[260px]">
                    Gráficos de motivos, criticidad y evolución del inventario.
                </p>
            </div>

            <div class="mt-4 md:mt-6 w-10 h-10 md:w-12 md:h-12 rounded-full border border-slate-200/60 bg-white/95 flex items-center justify-center shadow-[2px_2px_5px_rgba(0,0,0,0.05),-2px_-2px_5px_rgba(255,255,255,0.8)] text-slate-500 group-hover:bg-slate-50 group-hover:border-slate-300 group-hover:text-slate-800 transition-all shrink-0">
                <i data-lucide="chevron-right" class="w-5 h-5 md:w-6 md:h-6 group-hover:translate-x-0.5 transition-transform"></i>
            </div>
        </a>

    </div>

    <!-- FOOTER -->
    <div class="text-center py-4">
        <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">
            Distribuidora PyB © 2026 • Sistema de Gestión Comercial
        </p>
    </div>

</div>
@endsection

@section('scripts')
<script>
    // Carga rápida del contador de alertas de stock
    fetch('{{ url("/api/stock/alertas") }}')
        .then(r => r.json())
        .then(res => {
            const count = res.data ? res.data.length : 0;
            const badge = document.getElementById('alertCountBadge');
            if (badge) {
                badge.textContent = count > 0 ? count : '0';
                if (count === 0) badge.classList.replace('bg-red-500', 'bg-emerald-500');
            }
        })
        .catch(() => {});
</script>
@endsection
