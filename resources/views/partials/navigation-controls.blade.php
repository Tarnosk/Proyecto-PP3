<header class="erp-toolbar" aria-label="Accesos de navegación">
    <div class="erp-toolbar-controls">
        <button class="erp-icon-button" type="button" data-erp-sidebar-toggle aria-controls="erp-sidebar" aria-expanded="true" aria-label="Ocultar menú" title="Ocultar menú" hidden>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <a class="erp-icon-button" href="{{ route('dashboard') }}" aria-label="Ir al inicio" title="Ir al inicio">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10l9-7 9 7M5 9v12h5v-7h4v7h5V9"/></svg>
        </a>
    </div>
    <nav class="erp-toolbar-actions top-nav" aria-label="Accesos de la pantalla">@yield('nav-links')</nav>
</header>
