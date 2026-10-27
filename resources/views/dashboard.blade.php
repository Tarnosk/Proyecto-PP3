<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel principal | Sistema de gestión</title>
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modules.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/navigation.css') }}">
</head>
<body class="erp-body erp-shell">
    @include('partials.sidebar')

    <main class="erp-main">
        @if (session('permission_error'))
            <div class="alert alert-error" role="alert"><p>{{ session('permission_error') }}</p></div>
        @endif

        @if (auth()->user()->rol === App\Modules\Login\Enums\UserRole::ADMINISTRATIVO)
            <section class="erp-heading">
                <div>
                    <p class="eyebrow">Panel administrativo</p>
                    <h1>Fichas, Proveedores y Compras</h1>
                    <p>Gestión de clientes y tarifas diferenciadas, base fiscal de proveedores y compras con ingreso automático al inventario.</p>
                </div>
                <a class="button button-primary" href="{{ route('clientes.create') }}">+ Nueva Ficha Cliente</a>
            </section>

            <section class="module-tabs" aria-label="Módulos administrativos">
                <a class="active" href="{{ route('clientes.index') }}">Fichas de Clientes ({{ $totalClientes }})</a>
                <a href="{{ route('proveedores.index') }}">Proveedores / Fábricas</a>
                <a href="{{ route('compras.index') }}">Ingreso de Compras</a>
            </section>

            <section class="search-panel">
                <h2>Buscador y Ruteo de Clientes</h2>
                <form action="{{ route('clientes.index') }}" method="get" class="dashboard-search">
                    <div class="field">
                        <label for="q">Buscar por Razón Social, CUIT o Dirección</label>
                        <input type="search" id="q" name="q" maxlength="160" placeholder="Escriba nombre, CUIT o dirección de comercio...">
                    </div>
                    <div class="field">
                        <label for="zona">Filtrar por Zona de Reparto</label>
                        <select id="zona" disabled><option>Todas las Zonas</option></select>
                    </div>
                    <button type="submit" class="button button-primary">Buscar</button>
                </form>
            </section>

            <section class="summary-strip" aria-label="Resumen administrativo">
                <article><span>Clientes autorizados</span><strong>{{ $totalClientes }}</strong></article>
                <article><span>Mayoristas</span><strong>{{ $totalMayoristas }}</strong></article>
                <article><span>Minoristas</span><strong>{{ $totalMinoristas }}</strong></article>
                <article><span>Reclamos abiertos</span><strong>{{ $totalReclamos }}</strong></article>
            </section>

            <section class="client-section">
                <div class="section-title">
                    <h2>Base de Clientes Autorizados</h2>
                    <span>Mostrando {{ $clientes->count() }} de {{ $totalClientes }} fichas</span>
                </div>
                <div class="client-card-grid">
                    @forelse ($clientes as $cliente)
                        <article class="client-card">
                            <div class="client-card-head">
                                <h3>{{ $cliente->apellido_razon_social }} {{ $cliente->nombre }}</h3>
                                <span class="type-badge type-{{ $cliente->tipo_cliente->value }}">{{ $cliente->tipo_cliente->value }}</span>
                            </div>
                            <p>Identificación fiscal: {{ $cliente->dni_cuit }}</p>
                            <p>{{ $cliente->direccion }}</p>
                            <p>{{ $cliente->telefono }} · {{ $cliente->email }}</p>
                            <div class="tariff-box"><span>Tarifa asignada</span><strong>{{ $cliente->tipo_cliente->value }}</strong></div>
                            <a class="button button-secondary button-small" href="{{ route('clientes.edit', $cliente) }}">Editar Ficha</a>
                        </article>
                    @empty
                        <p class="empty-state">Todavía no hay clientes registrados.</p>
                    @endforelse
                </div>
            </section>

            <section class="panel-grid sprint-grid" aria-label="Accesos de sprint dos">
                <article class="action-panel"><div><h2>Reclamos</h2><p>Crear y consultar reclamos asociados a clientes.</p></div><a class="button button-primary" href="{{ route('reclamos.create') }}">Crear reclamo</a></article>
                <article class="action-panel"><div><h2>Seguridad de sesión</h2><p>Sesión única por cuenta y expiración automática por inactividad.</p></div></article>
            </section>
            <section class="panel-grid sprint-grid" aria-label="Accesos de sprint tres">
                <article class="action-panel"><div><h2>Pagos y saldos</h2><p>Consultar cuentas corrientes e historial informado por otros modulos.</p></div><a class="button button-primary" href="{{ route('clientes.index') }}">Ver cuentas</a></article>
                <article class="action-panel"><div><h2>Reportes</h2><p>Historial y análisis de clientes.</p></div><a class="button button-primary" href="{{ route('reports.index') }}">Abrir reportes</a></article>
            </section>
        @elseif (auth()->user()->rol === App\Modules\Login\Enums\UserRole::REPARTIDOR)
            <section class="erp-heading"><div><p class="eyebrow">Panel repartidor</p><h1>Bienvenido, {{ auth()->user()->nombre }}</h1><p>Accesos comerciales para consultar clientes, crear reclamos, revisar ventas y ver stock.</p></div></section>
            <section class="panel-grid" aria-label="Accesos de repartidor">
                <article class="action-panel"><div><h2>Clientes</h2><p>Buscar y consultar clientes registrados.</p></div><a class="button button-primary" href="{{ route('clientes.index') }}">Ver clientes</a></article>
                <article class="action-panel"><div><h2>Ventas y Pedidos</h2><p>Registrar ventas y consultar el catálogo comercial.</p></div><a class="button button-primary" href="{{ route('ventas.index') }}">Abrir</a></article>
                <article class="action-panel"><div><h2>Reclamos</h2><p>Cargar reclamos asociados a clientes registrados.</p></div><a class="button button-primary" href="{{ route('reclamos.create') }}">Crear reclamo</a></article>
                <article class="action-panel"><div><h2>Inventario y Stock</h2><p>Módulo de G3.</p></div><a class="button button-primary" href="{{ route('stock.index') }}">Abrir</a></article>
            </section>
        @else
            <section class="erp-heading"><div><p class="eyebrow">Panel contador</p><h1>Bienvenido, {{ auth()->user()->nombre }}</h1><p>Acceso contable preparado para reportes, finanzas y tesorería.</p></div></section>
            <section class="panel-grid" aria-label="Accesos de contador">
                <article class="action-panel"><div><h2>Reportes de clientes</h2><p>Consultar historial y compras por producto.</p></div><a class="button button-primary" href="{{ route('reports.index') }}">Abrir reportes</a></article>
                <article class="action-panel"><div><h2>Finanzas y Tesorería</h2><p>Consultar información operativa para el seguimiento contable.</p></div><a class="button button-secondary" href="{{ route('reports.client-history') }}">Consultar</a></article>
            </section>
        @endif
    </main>
    <script src="{{ asset('assets/js/navigation.js') }}" defer></script>
</body>
</html>
