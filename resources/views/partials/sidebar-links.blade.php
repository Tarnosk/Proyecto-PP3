@php
    $currentRoute = request()->path();
    $userRol = auth()->user()?->rol?->value ?? (auth()->user()?->rol ?? 'administrativo');
@endphp

<!-- INICIO / PANEL (Todos los roles) -->
<a href="{{ url('/dashboard') }}" title="Inicio / Panel"
   class="sidebar-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ (request()->is('/') || request()->is('dashboard*')) ? 'bg-white/20 text-white shadow-inner font-black' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
    <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i>
    <span class="sidebar-text truncate">Inicio / Panel</span>
</a>

@if ($userRol === 'contador')
    <!-- MÓDULOS DE CONTADOR -->
    <a href="{{ url('/reportes') }}" title="Reportes y Finanzas"
       class="sidebar-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->is('reportes*') ? 'bg-white/20 text-white shadow-inner font-black' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
        <i data-lucide="bar-chart-3" class="w-4 h-4 shrink-0"></i>
        <span class="sidebar-text truncate">Reportes y Finanzas</span>
    </a>
@else
    <!-- INVENTARIO Y STOCK (Administrativo y Repartidor) -->
    <a href="{{ url('/inventario') }}" title="Inventario y Stock"
       class="sidebar-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ (request()->is('inventario*') || request()->is('productos*') || request()->is('stock*')) ? 'bg-white/20 text-white shadow-inner font-black' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
        <i data-lucide="package" class="w-4 h-4 shrink-0"></i>
        <span class="sidebar-text truncate">Inventario y Stock</span>
    </a>

    <!-- COMPRAS Y ÓRDENES (Administrativo y Repartidor) -->
    <a href="{{ url('/compras') }}" title="Compras y Órdenes"
       class="sidebar-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ (request()->is('compras*') || request()->is('pedidos*') || request()->is('ordenes*')) ? 'bg-white/20 text-white shadow-inner font-black' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
        <i data-lucide="shopping-cart" class="w-4 h-4 shrink-0"></i>
        <span class="sidebar-text truncate">Compras y Órdenes</span>
    </a>

    <!-- PROVEEDORES (G3) -->
    <a href="{{ url('/proveedores') }}" title="Proveedores"
       class="sidebar-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->is('proveedores*') ? 'bg-white/20 text-white shadow-inner font-black' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
        <i data-lucide="truck" class="w-4 h-4 shrink-0"></i>
        <span class="sidebar-text truncate">Proveedores</span>
    </a>

    <!-- CLIENTES (G1) -->
    <a href="{{ url('/clientes') }}" title="Clientes"
       class="sidebar-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->is('clientes*') ? 'bg-white/20 text-white shadow-inner font-black' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
        <i data-lucide="users" class="w-4 h-4 shrink-0"></i>
        <span class="sidebar-text truncate">Clientes</span>
    </a>

    <!-- RECLAMOS (G1) -->
    <a href="{{ url('/reclamos') }}" title="Reclamos"
       class="sidebar-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->is('reclamos*') ? 'bg-white/20 text-white shadow-inner font-black' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
        <i data-lucide="message-square-warning" class="w-4 h-4 shrink-0"></i>
        <span class="sidebar-text truncate">Reclamos</span>
    </a>

    @if ($userRol === 'administrativo')
        <!-- REPORTES Y FINANZAS (Administrativo) -->
        <a href="{{ url('/reportes') }}" title="Reportes y Finanzas"
           class="sidebar-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->is('reportes*') ? 'bg-white/20 text-white shadow-inner font-black' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
            <i data-lucide="bar-chart-3" class="w-4 h-4 shrink-0"></i>
            <span class="sidebar-text truncate">Reportes y Finanzas</span>
        </a>

        <!-- USUARIOS (Administrativo) -->
        <a href="{{ url('/usuarios') }}" title="Lista de Usuarios"
           class="sidebar-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->is('usuarios*') ? 'bg-white/20 text-white shadow-inner font-black' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
            <i data-lucide="user-cog" class="w-4 h-4 shrink-0"></i>
            <span class="sidebar-text truncate">Lista de Usuarios</span>
        </a>

        <div class="sidebar-divider pt-4 pb-1">
            <div class="sidebar-text text-[9px] font-extrabold text-blue-200/60 uppercase tracking-widest px-3">
                Submódulos Especiales
            </div>
        </div>

        <!-- SUGERENCIAS REPOSICIÓN -->
        <a href="{{ url('/sugerencias-reposicion') }}" title="Sugerencias Reposición"
           class="sidebar-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->is('sugerencias-reposicion*') ? 'bg-white/20 text-white shadow-inner font-black' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
            <i data-lucide="sparkles" class="w-4 h-4 shrink-0"></i>
            <span class="sidebar-text truncate">Sugerencias Reposición</span>
        </a>

        <!-- DASHBOARD REPOSICIÓN -->
        <a href="{{ url('/dashboard-reposicion') }}" title="Métricas Reposición"
           class="sidebar-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->is('dashboard-reposicion*') ? 'bg-white/20 text-white shadow-inner font-black' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
            <i data-lucide="trending-up" class="w-4 h-4 shrink-0"></i>
            <span class="sidebar-text truncate">Métricas Reposición</span>
        </a>
    @endif
@endif
