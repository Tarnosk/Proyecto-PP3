@extends('layouts.app')

@section('title', 'Clientes por producto | Reportes')

@section('content')
    <section class="page-heading">
        <p class="eyebrow">Reportes</p>
        <h1>Clientes por producto</h1>
    </section>

    <form class="toolbar-form report-filter" action="{{ route('reports.client-product-reach') }}" method="get">
        <div class="field client-filter">
            <label for="producto_id">Producto</label>
            <select id="producto_id" name="producto_id" required @disabled($productos->isEmpty())>
                <option value="">Seleccionar...</option>
                @foreach ($productos as $opcion)
                    <option value="{{ $opcion->id }}" @selected($producto?->id === $opcion->id)>{{ $opcion->nombre }} ({{ $opcion->codigo }})</option>
                @endforeach
            </select>
        </div>
        <div class="toolbar-actions">
            <button class="button button-primary" type="submit" @disabled($productos->isEmpty())>Consultar</button>
        </div>
    </form>

    @if ($productos->isEmpty())
        <section class="table-panel"><p class="empty-state">No hay productos registrados.</p></section>
    @elseif ($producto)
        <section class="product-reach-summary" aria-label="Porcentaje de clientes">
            <strong>{{ number_format($porcentaje, 2, ',', '.') }}%</strong>
            <span>{{ $clientes->total() }} de {{ $totalClientes }} clientes registrados compraron {{ $producto->nombre }}.</span>
        </section>

        <section class="table-panel" aria-label="Clientes que compraron el producto">
            @if ($clientes->isEmpty())
                <p class="empty-state">Ningún cliente registró una compra de este producto.</p>
            @else
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>Cliente</th><th>DNI/CUIT</th><th>Localidad</th><th>Domicilio</th></tr></thead>
                        <tbody>
                            @foreach ($clientes as $cliente)
                                <tr>
                                    <td><strong>{{ $cliente->apellido_razon_social }}, {{ $cliente->nombre }}</strong></td>
                                    <td>{{ $cliente->dni_cuit }}</td>
                                    <td>{{ $cliente->localidad ?: 'No informada' }}</td>
                                    <td>{{ $cliente->direccion }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($clientes->hasPages())
                    <nav class="report-pagination" aria-label="Páginas de clientes">
                        @if ($clientes->onFirstPage())
                            <span>Anterior</span>
                        @else
                            <a href="{{ $clientes->previousPageUrl() }}">Anterior</a>
                        @endif
                        <span>Página {{ $clientes->currentPage() }} de {{ $clientes->lastPage() }}</span>
                        @if ($clientes->hasMorePages())
                            <a href="{{ $clientes->nextPageUrl() }}">Siguiente</a>
                        @else
                            <span>Siguiente</span>
                        @endif
                    </nav>
                @endif
            @endif
        </section>
    @else
        <section class="table-panel"><p class="empty-state">Seleccione un producto para consultar el porcentaje de clientes.</p></section>
    @endif
@endsection
