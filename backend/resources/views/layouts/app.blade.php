<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Distribuidora ERP')</title>
    {{-- Las vistas del paquete S11/PC07 usan Bootstrap 5 + Font Awesome (clases d-flex, modal, fas fa-*). --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/claymorfismo.css') }}">
</head>
<body>
    <nav class="p-2 px-4">
        <a href="{{ url('/Interfaz/index.html') }}">&larr; Volver al ERP</a>
        &nbsp;|&nbsp; <a href="{{ url('/vencimientos') }}">Vencimientos</a>
        &nbsp;|&nbsp; <a href="{{ url('/sugerencias-reposicion') }}">Sugerencias de reposición</a>
        &nbsp;|&nbsp; <a href="{{ url('/dashboard-reposicion') }}">Dashboard</a>
    </nav>

    @yield('content')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
