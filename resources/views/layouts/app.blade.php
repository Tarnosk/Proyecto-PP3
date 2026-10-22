<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'ERP Distribuidora — Juan XXIII')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/clay-conciliacion.css') }}">
</head>
<body>
    <!-- SIDEBAR UNIFICADO -->
    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Menú principal">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon">📦</div>
            <div>
                <h1>ERP Distribuidora</h1>
                <small>Juan XXIII — Pigüé</small>
            </div>
        </div>

        <p class="sidebar-section-label">Módulos Grupo 2</p>

        <ul class="sidebar-menu" role="list">
            <li class="sidebar-item">
                <a href="/entregas.html">
                    <span class="nav-icon" aria-hidden="true">🚚</span>
                    Entregas & Despacho
                </a>
            </li>
            <li class="sidebar-item">
                <a href="/facturacion.html">
                    <span class="nav-icon" aria-hidden="true">🧾</span>
                    Facturación
                </a>
            </li>
            <li class="sidebar-item">
                <a href="/rendiciones.html">
                    <span class="nav-icon" aria-hidden="true">📋</span>
                    Rendiciones & Cobros
                </a>
            </li>
        </ul>

        <p class="sidebar-section-label">Administración</p>
        <ul class="sidebar-menu" role="list">
            <li class="sidebar-item {{ request()->routeIs('conciliacion.*') ? 'active' : '' }}">
                <a href="{{ route('conciliacion.index') }}" {!! request()->routeIs('conciliacion.*') ? 'aria-current="page"' : '' !!}>
                    <span class="nav-icon" aria-hidden="true">🏦</span>
                    Conciliación Bancaria
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('caja.*') ? 'active' : '' }}">
                <a href="{{ route('caja.dia') }}" {!! request()->routeIs('caja.*') ? 'aria-current="page"' : '' !!}>
                    <span class="nav-icon" aria-hidden="true">🏠</span>
                    Caja & Tesoro
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-user-avatar">D</div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name">Diego García</div>
                    <div class="sidebar-user-role">Administrador</div>
                </div>
            </div>
        </div>
    </aside>
    <main>
        @if (session('mensaje'))
            <div class="alerta alerta-exito">{{ session('mensaje') }}</div>
        @endif
        @if (session('error'))
            <div class="alerta alerta-error">{{ session('error') }}</div>
        @endif

        @yield('contenido')
    </main>
</body>
</html>