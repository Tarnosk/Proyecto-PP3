<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $__env->yieldContent('titulo', 'Sistema de gestión'))</title>
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modules.css') }}">
    @yield('styles')
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/navigation.css') }}">
</head>
<body class="erp-body erp-shell">
    @include('partials.sidebar')
    <div class="app-workspace">
        <main class="layout @yield('layout-class')">
            @foreach (['mensaje' => 'success', 'error' => 'error', 'permission_error' => 'error'] as $flash => $kind)
                @if (session($flash))
                    <div class="alert alert-{{ $kind }}" role="alert">{{ session($flash) }}</div>
                @endif
            @endforeach
            @if (request()->routeIs('rendiciones.index'))
                <aside class="alert" role="status" id="g1-rendiciones-status" aria-live="polite">
                    {{ ($g1RendicionesUnavailable ?? false) ? 'Sin datos disponibles: el servicio de G2 no está operativo en este entorno. Las operaciones permanecen deshabilitadas.' : 'Comprobando disponibilidad de G2. Las operaciones se habilitarán al recibir datos válidos.' }}
                </aside>
                <button type="button" class="button button-secondary" id="g1-rendiciones-retry" onclick="window.location.reload()">Reintentar conexión con G2</button>
                <noscript><p>Activá JavaScript para consultar las rendiciones. Las operaciones están deshabilitadas.</p></noscript>
                <fieldset id="g1-rendiciones-controls" disabled style="border:0;padding:0;margin:0;min-width:0">
            @endif
            @hasSection('content')
                @yield('content')
            @else
                @yield('contenido')
            @endif
            @if (request()->routeIs('rendiciones.index'))
                </fieldset>
            @endif
        </main>
    </div>
    @yield('modals')
    @yield('scripts')
    @stack('scripts')
    @if (request()->routeIs('rendiciones.index'))
        <script src="{{ asset('assets/js/g1-rendiciones.js') }}" defer></script>
    @endif
    <script src="{{ asset('assets/js/navigation.js') }}" defer></script>
</body>
</html>
