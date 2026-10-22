@extends('layouts.app')

@section('titulo', 'Detalle de período')

@section('contenido')
    <a href="{{ route('conciliacion.index') }}" class="clay-btn-secondary link-volver">← Volver al listado</a>

    <div class="clay-card">
        <div class="encabezado-flex-inicio">
            <div>
                <h2>
                    Período {{ \Illuminate\Support\Carbon::parse($periodo['fecha_desde'])->format('d/m/Y') }}
                    – {{ \Illuminate\Support\Carbon::parse($periodo['fecha_hasta'])->format('d/m/Y') }}
                </h2>
                <p class="subtitulo">
                    <span class="badge badge-{{ $periodo['estado'] }}">{{ ucfirst($periodo['estado']) }}</span>
                    · Abierto el {{ \Illuminate\Support\Carbon::parse($periodo['fecha_apertura'])->format('d/m/Y H:i') }}
                    @if ($periodo['fecha_cierre'])
                        · Cerrado el {{ \Illuminate\Support\Carbon::parse($periodo['fecha_cierre'])->format('d/m/Y H:i') }}
                    @endif
                </p>
            </div>

            @if ($periodo['estado'] === 'abierto')
                <div class="fila-acciones acciones-sin-margen">
                    <form method="POST" action="{{ route('conciliacion.conciliar', $periodo['id_periodo']) }}">
                        @csrf
                        <button type="submit" class="clay-btn-primary">Conciliar automáticamente</button>
                    </form>
                    <form method="POST" action="{{ route('conciliacion.cerrar', $periodo['id_periodo']) }}"
                          onsubmit="return confirm('¿Cerrar este período? No vas a poder conciliar más movimientos después.');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="clay-btn-secondary">Cerrar período</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    @if (isset($resultadoConciliacion))
        <div class="clay-card">
            <h3>Resultado de la última conciliación automática</h3>
            <p>
                Se procesaron <strong>{{ $resultadoConciliacion['total_pendientes_procesados'] }}</strong> movimientos pendientes.
                Se conciliaron automáticamente <strong>{{ $resultadoConciliacion['conciliados_automaticamente'] }}</strong>.
            </p>

            @if (count($resultadoConciliacion['ambiguos_requieren_revision_manual']) > 0)
                <div class="alerta alerta-error">
                    {{ count($resultadoConciliacion['ambiguos_requieren_revision_manual']) }} movimiento(s) tienen más de un candidato posible
                    y requieren revisión manual — no se conciliaron para evitar un cruce incorrecto.
                </div>
            @endif

            @if (count($resultadoConciliacion['sin_candidato']) > 0)
                <div class="alerta alerta-error">
                    {{ count($resultadoConciliacion['sin_candidato']) }} movimiento(s) no encontraron ningún candidato dentro del margen de 48hs.
                </div>
            @endif
        </div>
    @endif

    <div class="clay-card">
        <h3>Movimientos bancarios del período</h3>

        @if (empty($periodo['movimientos_bancarios']))
            <div class="vacio">
                <p>Todavía no hay movimientos bancarios cargados en este período.</p>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Tipo</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($periodo['movimientos_bancarios'] as $movimiento)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($movimiento['fecha_movimiento'])->format('d/m/Y') }}</td>
                            <td>{{ $movimiento['descripcion'] }}</td>
                            <td>{{ ucfirst($movimiento['tipo']) }}</td>
                            <td>${{ number_format($movimiento['monto'], 2, ',', '.') }}</td>
                            <td><span class="badge badge-{{ $movimiento['estado'] }}">{{ ucfirst($movimiento['estado']) }}</span></td>
                            <td>
                                @if ($movimiento['estado'] === 'pendiente')
                                    <button type="button" class="clay-btn-secondary"
                                            onclick="document.getElementById('modal-manual-{{ $movimiento['id_movimiento_bancario'] }}').classList.add('visible')">
                                        Conciliar manual
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Modales simples de conciliación manual, uno por movimiento pendiente --}}
    @foreach (($periodo['movimientos_bancarios'] ?? []) as $movimiento)
        @if ($movimiento['estado'] === 'pendiente')
            <div id="modal-manual-{{ $movimiento['id_movimiento_bancario'] }}" class="modal-overlay">
                <div class="clay-card modal-card">
                    <h3>Conciliar movimiento manualmente</h3>
                    <p class="subtitulo">
                        {{ $movimiento['descripcion'] }} — ${{ number_format($movimiento['monto'], 2, ',', '.') }}
                    </p>

                    <form method="POST" action="{{ route('conciliacion.conciliar-manual', $movimiento['id_movimiento_bancario']) }}">
                        @csrf

                        <label for="origen-{{ $movimiento['id_movimiento_bancario'] }}">Origen del movimiento</label>
                        <select name="tipo_origen" id="origen-{{ $movimiento['id_movimiento_bancario'] }}" class="clay-input" required>
                            <option value="">Seleccioná un tipo</option>
                            <option value="cobro">Cobro</option>
                            <option value="caja">Movimiento de caja</option>
                            <option value="ajuste">Ajuste bancario</option>
                            <option value="cheque">Cheque</option>
                        </select>

                        <label for="id-origen-{{ $movimiento['id_movimiento_bancario'] }}">ID del registro de origen</label>
                        <input type="number" name="id_origen" id="id-origen-{{ $movimiento['id_movimiento_bancario'] }}" class="clay-input" required>

                        <input type="hidden" name="id_periodo_redirect" value="{{ $periodo['id_periodo'] }}">

                        <div class="fila-acciones">
                            <button type="submit" class="clay-btn-primary">Confirmar conciliación</button>
                            <button type="button" class="clay-btn-secondary"
                                    onclick="document.getElementById('modal-manual-{{ $movimiento['id_movimiento_bancario'] }}').classList.remove('visible')">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach
@endsection