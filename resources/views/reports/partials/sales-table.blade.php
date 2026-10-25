<section class="table-panel">
    @if (isset($salesReady) && ! $salesReady)
        <p class="empty-state">El historial estara disponible cuando G4 publique su API de ventas.</p>
    @elseif ($ventas->isEmpty())
        <p class="empty-state">No hay ventas para los filtros seleccionados.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Fecha / Comprobante</th>@if (!isset($compact))<th>Productos</th>@else<th>Cliente</th>@endif<th>Estado</th><th>Total</th></tr></thead>
                <tbody>
                @foreach ($ventas as $venta)
                    <tr>
                        <td>{{ $venta->fecha->format('d/m/Y H:i') }}<span>{{ $venta->numero_factura ?: 'Sin comprobante' }}</span></td>
                        @if (!isset($compact))
                            <td>
                                @forelse ($venta->detalles as $detalle)
                                    <span>{{ $detalle->cantidad }} x {{ $detalle->producto->nombre }} · ${{ number_format((float) $detalle->subtotal, 2, ',', '.') }}</span>
                                @empty
                                    <span>Sin detalle de productos</span>
                                @endforelse
                            </td>
                        @else
                            <td>{{ $venta->cliente->nombre }}</td>
                        @endif
                        <td>{{ ucfirst($venta->estado->value) }}</td>
                        <td><strong>${{ number_format((float) $venta->total, 2, ',', '.') }}</strong></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
