@extends('layouts.app')

@section('title', 'Clientes | Sistema de gestión')

@section('nav-links')
    @can('create', App\Modules\Clientes\Models\Cliente::class)
        <a href="{{ route('clientes.create') }}">Nuevo cliente</a>
    @endcan
@endsection

@section('content')
    <section class="page-heading">
        <p class="eyebrow">Administración</p>
        <h1>Clientes</h1>
        <p>Listado y búsqueda de clientes registrados.</p>
    </section>

    <form action="{{ route('clientes.index') }}" method="get" class="toolbar-form">
        <div class="field search-field">
            <label for="q">Buscar cliente</label>
            <input type="search" id="q" name="q" maxlength="160" value="{{ $search }}" placeholder="Nombre, DNI/CUIT, localidad o domicilio">
        </div>
        <div class="toolbar-actions">
            <button type="submit" class="button button-primary">Buscar</button>
            <a class="button button-secondary" href="{{ route('clientes.index') }}">Limpiar</a>
        </div>
    </form>

    <section class="table-panel" aria-label="Listado de clientes">
        @if ($clientes->isEmpty())
            <p class="empty-state">No se encontraron clientes.</p>
        @else
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>DNI/CUIT</th>
                            <th>Localidad</th>
                            <th>Zona</th>
                            <th>Domicilio</th>
                            <th>Contacto</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clientes as $cliente)
                            <tr>
                                <td>
                                    <strong>{{ $cliente->apellido_razon_social }}, {{ $cliente->nombre }}</strong>
                                    <span>{{ $cliente->email }}</span>
                                </td>
                                <td>{{ $cliente->dni_cuit }}</td>
                                <td>{{ $cliente->localidad ?: 'No informada' }}</td>
                                <td>{{ $cliente->zona?->nombre ?: 'No informada' }}</td>
                                <td>{{ $cliente->direccion }}</td>
                                <td>{{ $cliente->telefono }}</td>
                                <td><span class="type-badge type-{{ $cliente->tipo_cliente->value }}">{{ $cliente->tipo_cliente->value }}</span></td>
                                <td>{{ $cliente->estado->value }}</td>
                                <td>
                                    <div class="table-actions">
                                        <a class="button button-secondary button-small" href="{{ route('clientes.show', $cliente) }}">Ver historial</a>
                                        @can('update', $cliente)
                                            <a class="button button-secondary button-small" href="{{ route('clientes.edit', $cliente) }}">Editar</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
