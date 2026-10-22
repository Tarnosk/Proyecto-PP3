@extends('layouts.app')

@section('titulo', 'Períodos de Conciliación')

@section('contenido')
    <div class="clay-card">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
            <div>
                <h2>Conciliación Bancaria</h2>
                <p class="subtitulo">Períodos registrados, del más reciente al más antiguo.</p>
            </div>
            <a href="{{ route('conciliacion.create') }}" class="clay-btn-primary">Nuevo período</a>
        </div>

        <form method="GET" action="{{ route('conciliacion.index') }}" style="max-width: 280px; margin-bottom: 1.5rem;">
            <label for="estado">Filtrar por estado</label>
            <select name="estado" id="estado" class="clay-input" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="abierto" @selected(request('estado') === 'abierto')>Abierto</option>
                <option value="cerrado" @selected(request('estado') === 'cerrado')>Cerrado</option>
            </select>
        </form>

        @if (count($periodos) === 0)
            <div class="vacio">
                <p>No hay períodos de conciliación para mostrar todavía.</p>
                <p>Creá el primero para empezar a registrar movimientos bancarios.</p>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Desde</th>
                        <th>Hasta</th>
                        <th>Estado</th>
                        <th>Apertura</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($periodos as $periodo)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($periodo['fecha_desde'])->format('d/m/Y') }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($periodo['fecha_hasta'])->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge badge-{{ $periodo['estado'] }}">{{ ucfirst($periodo['estado']) }}</span>
                            </td>
                            <td>{{ \Illuminate\Support\Carbon::parse($periodo['fecha_apertura'])->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('conciliacion.show', $periodo['id_periodo']) }}" class="clay-btn-secondary">Ver detalle</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection