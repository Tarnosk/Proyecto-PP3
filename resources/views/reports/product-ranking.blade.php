@extends('layouts.app')

@section('title', 'Ranking comercial | Sistema de gestion')

@section('content')
    @include('reports.partials.navigation')
    <section class="page-heading">
        <p class="eyebrow">Reportes</p>
        <h1>Ranking comercial</h1>
        <p>Ventas confirmadas, preparadas, entregadas, pagadas y facturadas. Excluye pendientes y canceladas. Importes netos con descuentos por producto y descuento global proporcional.</p>
    </section>

    <form class="toolbar-form report-filter" method="get" action="{{ route('reports.product-ranking') }}">
        <div class="field"><label for="desde">Desde</label><input id="desde" type="date" name="desde" value="{{ $desde }}"></div>
        <div class="field"><label for="hasta">Hasta</label><input id="hasta" type="date" name="hasta" value="{{ $hasta }}"></div>
        <div class="toolbar-actions"><button class="button button-primary" type="submit" @disabled(! $ready)>Consultar</button><a class="button button-secondary" href="{{ route('reports.product-ranking') }}">Limpiar</a></div>
    </form>

    <section class="table-panel" aria-label="Ranking de productos">
        @if (! $ready)
            <p class="empty-state">Este reporte estara disponible al conectar la API de Ventas.</p>
        @elseif ($ranking->total() > 0 && $ranking->isEmpty())
            <p class="empty-state">No hay resultados en esta pagina.</p>
        @elseif ($ranking->isEmpty())
            <p class="empty-state">No existen ventas confirmadas o posteriores para el periodo seleccionado.</p>
        @else
            <div class="table-scroll">
                <table>
                    <thead><tr><th>Producto</th><th>Unidades vendidas</th><th>Importe neto vendido</th></tr></thead>
                    <tbody>
                    @foreach ($ranking as $item)
                        <tr>
                            <td><strong>{{ $item['producto_nombre'] }}</strong></td>
                            <td>{{ $item['cantidad_vendida'] }}</td>
                            <td>${{ number_format((float) $item['monto_facturado'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($ranking->hasPages())
                <nav class="report-pagination" aria-label="Paginas del ranking">
                    @if ($ranking->onFirstPage())<span>Anterior</span>@else<a href="{{ $ranking->previousPageUrl() }}">Anterior</a>@endif
                    <span>Pagina {{ $ranking->currentPage() }} de {{ $ranking->lastPage() }}</span>
                    @if ($ranking->hasMorePages())<a href="{{ $ranking->nextPageUrl() }}">Siguiente</a>@else<span>Siguiente</span>@endif
                </nav>
            @endif
        @endif
    </section>
@endsection
