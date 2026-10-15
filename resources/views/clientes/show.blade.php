@extends('layouts.app')

@section('title', 'Cuenta del cliente | Sistema de gestion')

@section('nav-links')
    <a href="{{ route('clientes.index') }}">Clientes</a>
@endsection

@section('content')
    <section class="page-heading heading-with-actions">
        <div>
            <p class="eyebrow">Cuenta corriente</p>
            <h1>{{ $cliente->apellido_razon_social }}, {{ $cliente->nombre }}</h1>
            <p>{{ $cliente->dni_cuit }} · {{ $cliente->email }}</p>
        </div>
    </section>

    <section class="finance-summary" aria-label="Resumen de cuenta">
        <article>
            <span>Saldo pendiente</span>
            @if ($saldoDisponible)
                <strong class="{{ (float) $saldo > 0 ? 'amount-due' : 'amount-clear' }}">${{ number_format((float) $saldo, 2, ',', '.') }}</strong>
                <small>{{ (float) $saldo > 0 ? 'Deuda vigente' : 'Cliente sin deuda' }}</small>
            @else
                <strong>--</strong>
                <small>Esperando API de saldos de G4</small>
            @endif
        </article>
        <article>
            <span>Pagos registrados</span>
            @if ($cobrosDisponibles)
                <strong>{{ count($cobros) }}</strong>
                <small>Informados por G2</small>
            @else
                <strong>--</strong>
                <small>Esperando API de cobros de G2</small>
            @endif
        </article>
        <article>
            <span>Ventas mostradas</span>
            <strong>{{ $ventas->count() }}</strong>
            <small>Segun el periodo seleccionado</small>
        </article>
    </section>

    <section class="section-block">
        <div class="section-title"><h2>Historial de pagos</h2></div>
        <div class="table-panel">
            @if (! $cobrosDisponibles)
                <p class="empty-state">El historial estara disponible cuando G2 publique su API de cobros.</p>
            @elseif (count($cobros) === 0)
                <p class="empty-state">Este cliente no tiene pagos registrados.</p>
            @else
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>Fecha</th><th>Monto</th><th>Medio</th><th>Comprobante</th><th>Notas</th></tr></thead>
                        <tbody>
                        @foreach ($cobros as $cobro)
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($cobro['fecha'])->format('d/m/Y H:i') }}</td>
                                <td><strong>${{ number_format((float) $cobro['monto_total'], 2, ',', '.') }}</strong></td>
                                <td>{{ ucfirst($cobro['medio_pago']) }}</td>
                                <td>{{ $cobro['comprobante_nro'] ?? '-' }}</td>
                                <td>{{ $cobro['observaciones'] ?? 'Sin observaciones' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    <section class="section-block">
        <div class="section-title"><h2>Compras y ventas del cliente</h2></div>
        <form class="toolbar-form" method="get" action="{{ route('clientes.show', $cliente) }}">
            <div class="field"><label for="desde">Desde</label><input id="desde" type="date" name="desde" value="{{ $desde }}"></div>
            <div class="field"><label for="hasta">Hasta</label><input id="hasta" type="date" name="hasta" value="{{ $hasta }}"></div>
            <div class="toolbar-actions"><button class="button button-primary" type="submit">Filtrar</button><a class="button button-secondary" href="{{ route('clientes.show', $cliente) }}">Limpiar</a></div>
        </form>
        @include('reports.partials.sales-table', ['ventas' => $ventas])
    </section>
@endsection
