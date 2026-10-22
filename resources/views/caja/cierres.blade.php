@extends('layouts.app')

@section('titulo', 'Historial de Cierres')

@section('contenido')
    <div class="clay-card">
        <h2>Historial de cierres de caja</h2>
        <p class="subtitulo">Cajas cerradas, de la mas reciente a la mas antigua.</p>

        <form method="GET" action="{{ route('caja.cierres.index') }}" class="encabezado-flex" style="align-items: flex-end;">
            <div>
                <label for="desde">Desde</label>
                <input type="date" name="desde" id="desde" class="clay-input" value="{{ $desde }}">
            </div>
            <div>
                <label for="hasta">Hasta</label>
                <input type="date" name="hasta" id="hasta" class="clay-input" value="{{ $hasta }}">
            </div>
            <div>
                <button type="submit" class="clay-btn-primary">Filtrar</button>
            </div>
            @if ($desde || $hasta)
                <div>
                    <a href="{{ route('caja.cierres.index') }}" class="clay-btn-secondary">Limpiar filtro</a>
                </div>
            @endif
        </form>

        @if (count($cierres) === 0)
            <div class="vacio">
                <p>No hay cierres de caja para el filtro seleccionado.</p>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Monto inicial</th>
                        <th>Monto final</th>
                        <th>Diferencia</th>
                        <th>Usuario que cerró</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cierres as $cierre)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($cierre['fecha'])->format('d/m/Y') }}</td>
                            <td>${{ number_format($cierre['monto_inicial'], 2, ',', '.') }}</td>
                            <td>${{ number_format($cierre['monto_final'], 2, ',', '.') }}</td>
                            <td>${{ number_format($cierre['diferencia'], 2, ',', '.') }}</td>
                            {{-- TODO: mostrar nombre real cuando Login (G1) este integrado --}}
                            <td>Usuario #{{ $cierre['id_usuario_cierre'] }}</td>
                            <td>
                                <a href="{{ route('caja.cierres.show', $cierre['id_caja']) }}" class="clay-btn-secondary">Ver detalle</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection