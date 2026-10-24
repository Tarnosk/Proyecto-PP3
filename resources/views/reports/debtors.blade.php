@extends('layouts.app')

@section('title', 'Clientes deudores | Sistema de gestion')

@section('content')
    @include('reports.partials.navigation')
    <section class="page-heading">
        <p class="eyebrow">Reportes</p>
        <h1>Clientes deudores</h1>
    </section>

    <form class="toolbar-form report-filter" method="get" action="{{ route('reports.debtors') }}">
        <div class="field">
            <label for="orden">Ordenar por importe</label>
            <select id="orden" name="orden">
                <option value="mayor" @selected($orden === 'mayor')>Mayor a menor</option>
                <option value="menor" @selected($orden === 'menor')>Menor a mayor</option>
            </select>
        </div>
        <div class="toolbar-actions"><button class="button button-primary" type="submit" @disabled(! $ready)>Consultar</button></div>
    </form>

    <section class="table-panel" aria-label="Clientes deudores">
        @if (! $ready)
            <p class="empty-state">Este reporte estara disponible cuando G2 publique facturas pendientes y G4 publique saldos de clientes.</p>
        @elseif ($debtors->total() > 0 && $debtors->isEmpty())
            <p class="empty-state">No hay resultados en esta pagina.</p>
        @elseif ($debtors->isEmpty())
            <p class="empty-state">No hay deudas registradas.</p>
        @else
            <div class="table-scroll">
                <table>
                    <thead><tr><th>Cliente</th><th>Importe adeudado</th><th>Facturas pendientes</th></tr></thead>
                    <tbody>
                    @foreach ($debtors as $debtor)
                        <tr>
                            <td><a href="{{ route('clientes.show', $debtor['cliente']) }}">{{ $debtor['cliente']->apellido_razon_social }}, {{ $debtor['cliente']->nombre }}</a></td>
                            <td><strong>${{ number_format((float) $debtor['saldo'], 2, ',', '.') }}</strong></td>
                            <td>
                                @forelse ($debtor['facturas'] as $factura)
                                    <div>{{ $factura['numero'] }} · {{ \Illuminate\Support\Carbon::parse($factura['vencimiento'])->format('d/m/Y') }} · ${{ number_format((float) $factura['importe_pendiente'], 2, ',', '.') }}</div>
                                @empty
                                    <span>Sin detalle de facturas pendientes</span>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($debtors->hasPages())
                <nav class="report-pagination" aria-label="Paginas de deudores">
                    @if ($debtors->onFirstPage())<span>Anterior</span>@else<a href="{{ $debtors->previousPageUrl() }}">Anterior</a>@endif
                    <span>Pagina {{ $debtors->currentPage() }} de {{ $debtors->lastPage() }}</span>
                    @if ($debtors->hasMorePages())<a href="{{ $debtors->nextPageUrl() }}">Siguiente</a>@else<span>Siguiente</span>@endif
                </nav>
            @endif
        @endif
    </section>
@endsection
