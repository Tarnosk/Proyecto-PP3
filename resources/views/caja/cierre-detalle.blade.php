@extends('layouts.app')

@section('titulo', 'Detalle de cierre')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/g2-finanzas.css') }}">
@endpush

@section('contenido')
<div class="g2-page">
    @php
        $etiquetasMedio = ['efectivo' => 'Efectivo', 'cheque' => 'Cheque', 'transferencia' => 'Transferencia', 'otro' => 'Otro'];
        $etiquetasTipo = ['reversion' => 'Reversión de cobro', 'ingreso_manual' => 'Ingreso manual', 'pago_proveedor' => 'Pago a proveedor', 'extraccion' => 'Extracción'];
        // Cierre automático (72 h) sin el conteo cargado todavía.
        $pendiente = !empty($cierre['cierre_automatico']) && $cierre['monto_final'] === null;
    @endphp

    <a href="{{ route('caja.cierres.index') }}" class="clay-btn-secondary link-volver">← Volver al historial</a>

    @if (session('mensaje'))
        <div class="alerta">{{ session('mensaje') }}</div>
    @endif
    @if (session('error'))
        <div class="alerta alerta-error">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alerta alerta-error">{{ $errors->first() }}</div>
    @endif

    <div class="clay-card">
        <h2>Cierre del {{ \Illuminate\Support\Carbon::parse($cierre['fecha'])->format('d/m/Y') }}</h2>
        <p class="subtitulo">
            Abierta: {{ \Illuminate\Support\Carbon::parse($cierre['fecha_hora_apertura'])->format('d/m/Y H:i') }}
            @if ($cierre['fecha_hora_cierre'])
                · Cerrada: {{ \Illuminate\Support\Carbon::parse($cierre['fecha_hora_cierre'])->format('d/m/Y H:i') }}
            @endif
            @if (!empty($cierre['cierre_automatico']))
                · <span class="badge badge-cerrado">Cierre automático</span>
            @endif
        </p>

        <table>
            <tbody>
                <tr>
                    <th>Monto inicial</th>
                    <td>${{ number_format($cierre['monto_inicial'], 2, ',', '.') }}</td>
                </tr>
                @if ($cierre['monto_esperado'] !== null)
                    <tr>
                        <th>Monto esperado</th>
                        <td>${{ number_format($cierre['monto_esperado'], 2, ',', '.') }}</td>
                    </tr>
                @endif
                <tr>
                    <th>Monto final</th>
                    <td>
                        @if ($pendiente)
                            Pendiente de conteo
                        @else
                            ${{ number_format($cierre['monto_final'], 2, ',', '.') }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Diferencia</th>
                    <td>
                        @if ($pendiente)
                            Pendiente de conteo
                        @else
                            ${{ number_format($cierre['diferencia'], 2, ',', '.') }}
                        @endif
                    </td>
                </tr>
                @if (isset($cierre['subtotal_efectivo']))
                    <tr>
                        <th>Movimientos en efectivo</th>
                        <td>${{ number_format($cierre['subtotal_efectivo'], 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Movimientos en valores</th>
                        <td>${{ number_format($cierre['subtotal_valores'], 2, ',', '.') }}</td>
                    </tr>
                @endif
                @if (!empty($cierre['observaciones']))
                    <tr>
                        <th>Observaciones</th>
                        <td>{{ $cierre['observaciones'] }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    @if ($pendiente && !empty($puedeCargarConteo))
        <div class="clay-card">
            <h3>Cargar el conteo</h3>
            <p class="subtitulo">
                Esta caja de repartidor se cerró sola al vencer. Contá el efectivo e indicá el monto final:
                la diferencia se calcula contra el monto esperado.
            </p>

            <form method="POST" action="{{ route('caja.conteo', $cierre['id_caja']) }}">
                @csrf
                @method('PATCH')

                <label for="monto_final">Monto final contado</label>
                <input type="number" step="0.01" min="0" name="monto_final" id="monto_final" class="clay-input" inputmode="decimal" autocomplete="off" data-solo-numeros required>

                <label for="observaciones_conteo">Observaciones (opcional)</label>
                <input type="text" name="observaciones" id="observaciones_conteo" class="clay-input" maxlength="500" autocomplete="off">

                <div class="fila-acciones">
                    <button type="submit" class="clay-btn-primary">Guardar conteo</button>
                </div>
            </form>
        </div>
    @endif

    @if (!empty($cierre['movimientos']))
        <div class="clay-card">
            <h3>Movimientos de la caja</h3>
            <table>
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Medio</th>
                        <th>Concepto</th>
                        <th>Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cierre['movimientos'] as $movimiento)
                        <tr>
                            <td>{{ $etiquetasTipo[$movimiento['tipo']] ?? ucfirst(str_replace('_', ' ', $movimiento['tipo'])) }}</td>
                            <td>{{ $etiquetasMedio[$movimiento['medio_pago'] ?? 'efectivo'] ?? $movimiento['medio_pago'] }}</td>
                            <td>{{ $movimiento['concepto'] }}</td>
                            <td>${{ number_format($movimiento['monto'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

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
</div>
@endsection
