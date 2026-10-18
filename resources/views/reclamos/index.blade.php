@extends('layouts.app')

@section('title', 'Reclamos | Sistema de gestión')

@section('nav-links')
    <a href="{{ route('reclamos.create') }}">Nuevo reclamo</a>
@endsection

@section('content')
    <section class="page-heading">
        <p class="eyebrow">Atención al cliente</p>
        <h1>Reclamos</h1>
        <p>Registro y seguimiento inicial de reclamos comerciales.</p>
    </section>

    <section class="table-panel" aria-label="Listado de reclamos">
        @if ($reclamos->isEmpty())
            <p class="empty-state">Todavía no hay reclamos cargados.</p>
        @else
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Reclamo</th>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reclamos as $reclamo)
                            <tr>
                                <td><strong>#{{ $reclamo->id }} {{ $reclamo->asunto }}</strong></td>
                                <td>{{ $reclamo->cliente->apellido_razon_social }}, {{ $reclamo->cliente->nombre }}</td>
                                <td><span class="type-badge type-{{ $reclamo->cliente->tipo_cliente->value }}">{{ $reclamo->cliente->tipo_cliente->value }}</span></td>
                                <td>{{ $reclamo->prioridad->value }}</td>
                                <td>{{ $reclamo->estado->value }}</td>
                                <td>{{ $reclamo->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
