@extends('layouts.app')

@section('titulo', 'Abrir Caja')

@section('contenido')
    <div class="clay-card card-angosta">
        <h2>Abrir caja del dia</h2>

        @if (!empty($cajaPendienteConteo))
            <div class="alerta alerta-error">
                Tu caja del {{ \Illuminate\Support\Carbon::parse($cajaPendienteConteo['fecha'])->format('d/m/Y') }}
                se cerró automáticamente al vencer. Abrí una caja nueva y cargá en ella lo que haya quedado pendiente.
                El conteo de la caja anterior lo carga el administrador.
            </div>
        @endif

        @if ($errors->any())
            <div class="alerta alerta-error">{{ $errors->first() }}</div>
        @endif

        @if (session('id_caja_pendiente'))
            <div class="alerta alerta-error">
                {{ session('error') }}
                <br>
                <a href="{{ route('caja.pendiente', session('id_caja_pendiente')) }}" class="clay-btn-secondary" style="margin-top: 0.75rem;">
                    Ir a cerrar esa caja
                </a>
            </div>
        @elseif (session('error'))
            <div class="alerta alerta-error">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('caja.abrir') }}">
            @csrf

            <label for="monto_inicial">Monto inicial</label>
            <input type="number" step="0.01" min="0" name="monto_inicial" id="monto_inicial" class="clay-input" value="{{ old('monto_inicial') }}" inputmode="decimal" autocomplete="off" data-solo-numeros required>

            <label for="observaciones">Observaciones (opcional)</label>
            <input type="text" name="observaciones" id="observaciones" class="clay-input" maxlength="500" autocomplete="off">

            <div class="fila-acciones">
                <button type="submit" class="clay-btn-primary">Abrir caja</button>
            </div>
        </form>
    </div>

    <script>
        // Campos de monto: solo dígitos y separador decimal (se bloquean e, E, + y -).
        document.querySelectorAll('[data-solo-numeros]').forEach((campo) => {
            campo.addEventListener('keydown', (e) => {
                if (['e', 'E', '+', '-'].includes(e.key)) {
                    e.preventDefault();
                }
            });
            campo.addEventListener('paste', (e) => {
                const texto = (e.clipboardData || window.clipboardData).getData('text').trim();
                if (!/^\d*[.,]?\d*$/.test(texto)) {
                    e.preventDefault();
                }
            });
        });
    </script>
@endsection
