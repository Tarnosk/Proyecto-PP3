@php
    $role = auth()->user()?->rol?->value;
    $operational = ['administrativo', 'repartidor'];
    $accounting = ['administrativo', 'contador'];
    $links = [
        ['dashboard', 'Inicio / Panel', null],
        ['catalogo.index', 'Catálogo de productos', null],
        ['clientes.index', 'Clientes', $operational],
        ['reclamos.index', 'Reclamos', $operational],
        ['ventas.index', 'Ventas y Pedidos', $operational],
        ['ventas.historial', 'Historial de ventas', $operational],
        ['promociones.index', 'Promociones', ['administrativo']],
        ['saldo', 'Cuentas corrientes', $accounting],
        ['inventario.index', 'Inventario y Stock', $operational],
        ['compras.index', 'Compras y Órdenes', $operational],
        ['proveedores.index', 'Proveedores', $operational],
        ['entregas.index', 'Entregas y Despacho', $operational],
        ['rendiciones.index', 'Rendiciones y Cobros', $operational],
        ['caja.dia', 'Caja y Tesorería', $operational],
        ['conciliacion.index', 'Conciliación Bancaria', $accounting],
        ['reports.index', 'Reportes y Finanzas', $accounting],
        ['users.index', 'Lista de Usuarios', ['administrativo']],
    ];
    $activePatterns = [
        'dashboard' => ['dashboard', 'g3.dashboard'],
        'catalogo.index' => ['catalogo.*'],
        'clientes.index' => ['clientes.*'],
        'reclamos.index' => ['reclamos.*'],
        'reports.index' => ['reports.*'],
        'users.index' => ['users.*'],
        'inventario.index' => ['inventario.*', 'productos.*', 'stock.*', 'vencimientos.*'],
        'compras.index' => ['compras.*', 'pedidos-compra.*'],
        'proveedores.index' => ['proveedores.*'],
        'promociones.index' => ['promociones.*'],
        'saldo' => ['saldo', 'saldo.*'],
        'caja.dia' => ['caja.*'],
        'conciliacion.index' => ['conciliacion.*'],
        'entregas.index' => ['entregas.*'],
        'rendiciones.index' => ['rendiciones.*'],
    ];
@endphp
@foreach ($links as [$routeName, $label, $roles])
    @if (($roles === null || in_array($role, $roles, true)) && Route::has($routeName))
        @php
            $active = request()->routeIs(...($activePatterns[$routeName] ?? [$routeName]));
        @endphp
        <a href="{{ route($routeName) }}" class="erp-nav-link" title="{{ $label }}" @if($active) aria-current="page" @endif><span>{{ $label }}</span></a>
    @endif
@endforeach
