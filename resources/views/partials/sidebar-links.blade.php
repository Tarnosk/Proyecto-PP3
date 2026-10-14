@php
    $currentRoute = request()->path();
@endphp

<!-- INICIO / PANEL -->
<a href="{{ url('/') }}" title="Inicio / Panel"
   class="sidebar-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ (request()->is('/') || request()->is('dashboard*')) ? 'bg-white/20 text-white shadow-inner font-black' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
    <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i>
    <span class="sidebar-text truncate">Inicio / Panel</span>
</a>

<!-- INVENTARIO Y STOCK -->
<a href="{{ url('/inventario') }}" title="Inventario y Stock"
   class="sidebar-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ (request()->is('inventario*') || request()->is('productos*') || request()->is('stock*')) ? 'bg-white/20 text-white shadow-inner font-black' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
    <i data-lucide="package" class="w-4 h-4 shrink-0"></i>
    <span class="sidebar-text truncate">Inventario y Stock</span>
</a>

<!-- COMPRAS Y ÓRDENES -->
<a href="{{ url('/compras') }}" title="Compras y Órdenes"
   class="sidebar-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ (request()->is('compras*') || request()->is('pedidos*') || request()->is('ordenes*')) ? 'bg-white/20 text-white shadow-inner font-black' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
    <i data-lucide="shopping-cart" class="w-4 h-4 shrink-0"></i>
    <span class="sidebar-text truncate">Compras y Órdenes</span>
</a>

<!-- PROVEEDORES -->
<a href="{{ url('/proveedores') }}" title="Proveedores"
   class="sidebar-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ (request()->is('proveedores*') || request()->is('clientes*')) ? 'bg-white/20 text-white shadow-inner font-black' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
    <i data-lucide="users" class="w-4 h-4 shrink-0"></i>
    <span class="sidebar-text truncate">Proveedores</span>
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
