@include('partials.navigation-controls')
<aside id="erp-sidebar" class="erp-sidebar" data-erp-sidebar aria-label="Menú principal">
    <a class="erp-sidebar-brand" href="{{ route('dashboard') }}">
        <strong>PyB Pigüé ERP</strong>
        <span>Distribuidora comercial</span>
    </a>
    @auth
        <div class="erp-sidebar-user">
            <span>Usuario activo</span>
            <strong>{{ auth()->user()->nombre }}</strong>
            <small>{{ auth()->user()->rol->value }}</small>
        </div>
    @endauth
    <nav class="erp-sidebar-nav" data-erp-sidebar-nav aria-label="Navegación principal">
        @include('partials.sidebar-links')
    </nav>
    @auth
        <form class="erp-sidebar-footer" action="{{ route('logout') }}" method="post">
            @csrf
            <button type="submit">Cerrar sesión</button>
        </form>
    @endauth
</aside>
