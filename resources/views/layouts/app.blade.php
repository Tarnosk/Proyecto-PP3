<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sistema de gestión')</title>
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
</head>
<body class="@yield('body-class', 'module-page')">
    <header class="topbar">
        <a class="app-title" href="{{ route('dashboard') }}">Sistema de gestión</a>
        <nav class="top-nav" aria-label="Navegación principal">
            <a href="{{ route('dashboard') }}">Panel</a>
            @if (in_array(auth()->user()->rol, [App\Enums\UserRole::ADMINISTRATIVO, App\Enums\UserRole::CONTADOR], true))
                <a href="{{ route('reports.index') }}">Reportes</a>
            @endif
            @yield('nav-links')
            <form action="{{ route('logout') }}" method="post">
                @csrf
                <button class="nav-button" type="submit">Cerrar sesión</button>
            </form>
        </nav>
    </header>

    <main class="layout @yield('layout-class')">
        @include('partials.messages')
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
