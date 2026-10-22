@extends('layouts.app')

@section('titulo', 'Nuevo período de conciliación')

@section('contenido')
    <div class="clay-card card-angosta">
        <h2>Nuevo período de conciliación</h2>
        <p class="subtitulo">Definí el rango de fechas que vas a conciliar contra el extracto bancario.</p>

        @if ($errors->any())
            <div class="alerta alerta-error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('conciliacion.store') }}">
            @csrf

            <label for="fecha_desde">Desde</label>
            <input type="date" name="fecha_desde" id="fecha_desde" class="clay-input" value="{{ old('fecha_desde') }}" required>

            <label for="fecha_hasta">Hasta</label>
            <input type="date" name="fecha_hasta" id="fecha_hasta" class="clay-input" value="{{ old('fecha_hasta') }}" required>

            <div class="fila-acciones">
                <button type="submit" class="clay-btn-primary">Crear período</button>
                <a href="{{ route('conciliacion.index') }}" class="clay-btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection