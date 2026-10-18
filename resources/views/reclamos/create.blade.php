@extends('layouts.app')

@section('title', 'Nuevo reclamo | Sistema de gestión')
@section('layout-class', 'narrow')

@section('nav-links')
    <a href="{{ route('reclamos.index') }}">Reclamos</a>
@endsection

@section('content')
    <section class="page-heading">
        <p class="eyebrow">Atención al cliente</p>
        <h1>Nuevo reclamo</h1>
        <p>Seleccione el cliente y registre el detalle del reclamo.</p>
    </section>

    <form action="{{ route('reclamos.store') }}" method="post" class="form form-panel" data-validate="reclamo" novalidate>
        @csrf

        <div class="field">
            <label for="cliente_id">Cliente <span aria-hidden="true">*</span></label>
            <select id="cliente_id" name="cliente_id" required>
                <option value="">Seleccione un cliente</option>
                @foreach ($clientes as $cliente)
                    <option value="{{ $cliente->id }}" @selected((string) old('cliente_id') === (string) $cliente->id)>
                        {{ $cliente->apellido_razon_social }}, {{ $cliente->nombre }} - {{ $cliente->dni_cuit }} ({{ $cliente->tipo_cliente->value }})
                    </option>
                @endforeach
            </select>
            <small class="field-error" data-error-for="cliente_id"></small>
        </div>

        <div class="field">
            <label for="asunto">Asunto <span aria-hidden="true">*</span></label>
            <input type="text" id="asunto" name="asunto" maxlength="160" required value="{{ old('asunto') }}">
            <small class="field-error" data-error-for="asunto"></small>
        </div>

        <div class="field">
            <label for="prioridad">Prioridad <span aria-hidden="true">*</span></label>
            <select id="prioridad" name="prioridad" required>
                <option value="baja" @selected(old('prioridad') === 'baja')>Baja</option>
                <option value="media" @selected(old('prioridad', 'media') === 'media')>Media</option>
                <option value="alta" @selected(old('prioridad') === 'alta')>Alta</option>
            </select>
            <small class="field-error" data-error-for="prioridad"></small>
        </div>

        <div class="field">
            <label for="descripcion">Descripción <span aria-hidden="true">*</span></label>
            <textarea id="descripcion" name="descripcion" rows="5" required>{{ old('descripcion') }}</textarea>
            <small class="field-error" data-error-for="descripcion"></small>
        </div>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ route('reclamos.index') }}">Cancelar</a>
            <button type="submit" class="button button-primary">Crear reclamo</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush
