@extends('layouts.app')

@section('titulo', 'Caja del dia')

@php
    // Etiquetas de los tipos de movimiento. Solo se muestran los que el rol puede usar
    // ($tiposPermitidos viene del controller).
    $etiquetasTipo = [
        'cobro' => 'Cobro',
        'ingreso_manual' => 'Ingreso manual',
        'egreso' => 'Egreso',
        'extraccion' => 'Extracción',
        'pago_proveedor' => 'Pago a proveedor',
        'impuesto' => 'Impuesto',
        'reversion' => 'Reversión de cobro',
    ];

    $etiquetasMedio = [
        'efectivo' => 'Efectivo',
        'cheque' => 'Cheque',
        'transferencia' => 'Transferencia',
        'otro' => 'Otro',
    ];

    $vencimiento = !empty($caja['fecha_hora_vencimiento'])
        ? \Illuminate\Support\Carbon::parse($caja['fecha_hora_vencimiento'])->timezone(config('app.timezone'))
        : null;
    $vencida = $caja['estado'] === 'abierta' && $vencimiento !== null && $vencimiento->isPast();

    // Recordatorio: faltan menos de 24 horas para el vencimiento y todavía no venció.
    $porVencer = $caja['estado'] === 'abierta'
        && $vencimiento !== null
        && !$vencida
        && $vencimiento->lte(now()->addHours(24));
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('css/g2-finanzas.css') }}">
@endpush

@section('contenido')
<div class="g2-page">
    @if ($porVencer)
        <div class="alerta" style="background:#FFF4D6;color:#6B4E00;border:1px solid #F0D98A;margin-bottom:16px;">
            <strong>Recordatorio:</strong> esta caja vence el {{ $vencimiento->format('d/m/Y H:i') }} (faltan menos de 24 horas).
            Cerrala antes de esa hora{{ !empty($cierreAutomatico) ? ': de lo contrario se cerrará automáticamente.' : '.' }}
        </div>
    @endif

    <div class="clay-card">
        <div class="encabezado-flex-inicio">
            <div>
                <h2>Caja del {{ \Illuminate\Support\Carbon::parse($caja['fecha'])->format('d/m/Y') }}</h2>
                <p class="subtitulo">
                    <span class="badge badge-{{ $caja['estado'] === 'abierta' ? 'abierto' : 'cerrado' }}">{{ ucfirst($caja['estado']) }}</span>
                    @if ($vencida)
                        <span class="badge badge-cerrado">Vencida</span>
                    @endif
                    · Monto inicial: ${{ number_format($caja['monto_inicial'], 2, ',', '.') }}
                    @if ($caja['estado'] === 'abierta')
                        · Monto actual: <strong id="monto-actual">${{ number_format($montoActual, 2, ',', '.') }}</strong>
                    @endif
                </p>
                @if ($caja['estado'] === 'abierta' && $vencimiento !== null)
                    <p class="subtitulo">
                        {{ $vencida ? 'Venció el' : 'Vence el' }} {{ $vencimiento->format('d/m/Y H:i') }}.
                        @if ($vencida)
                            @if (!empty($cierreAutomatico))
                                Se cerrará automáticamente en los próximos minutos.
                            @else
                                Cerrala cuando puedas: igual podés seguir cargando movimientos.
                            @endif
                        @endif
                    </p>
                @endif
            </div>

            @if ($caja['estado'] === 'abierta')
                <div class="fila-acciones acciones-sin-margen">
                    <button type="button" class="clay-btn-primary" onclick="document.getElementById('modal-movimiento').classList.add('visible')">
                        Registrar movimiento
                    </button>
                    <button type="button" class="clay-btn-secondary" onclick="document.getElementById('modal-cerrar').classList.add('visible')">
                        Cerrar caja
                    </button>
                </div>
            @endif
        </div>
    </div>

    <div class="clay-card">
        <h3>Movimientos de esta caja</h3>

        <div id="sin-movimientos" class="vacio" @if(!empty($caja['movimientos'])) style="display:none" @endif>
            <p>Todavia no registraste movimientos en esta caja.</p>
        </div>

        <table id="tabla-movimientos" @if(empty($caja['movimientos'])) style="display:none" @endif>
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Medio</th>
                    <th>Concepto</th>
                    <th>Monto</th>
                </tr>
            </thead>
            <tbody id="tabla-movimientos-body">
                @foreach ($caja['movimientos'] ?? [] as $movimiento)
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

    <div id="modal-movimiento" class="modal-overlay">
        <div class="clay-card modal-card">
            <h3>Registrar movimiento</h3>

            <div id="error-movimiento" class="alerta alerta-error" style="display:none"></div>

            <form id="form-movimiento" method="POST" action="{{ route('caja.movimiento', $caja['id_caja']) }}">
                @csrf

                <label for="tipo">Tipo</label>
                <select name="tipo" id="tipo" class="clay-input" required>
                    @foreach ($tiposPermitidos as $tipo)
                        <option value="{{ $tipo }}">{{ $etiquetasTipo[$tipo] ?? $tipo }}</option>
                    @endforeach
                </select>

                <label for="medio_pago">Medio de pago</label>
                <select name="medio_pago" id="medio_pago" class="clay-input" required>
                    @foreach ($etiquetasMedio as $valor => $etiqueta)
                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>

                <label for="concepto">Concepto</label>
                <input type="text" name="concepto" id="concepto" class="clay-input" maxlength="200" autocomplete="off" required>

                <label for="monto">Monto</label>
                <input type="number" step="0.01" min="0.01" name="monto" id="monto" class="clay-input" inputmode="decimal" autocomplete="off" data-solo-numeros required>

                <div class="fila-acciones">
                    <button type="submit" class="clay-btn-primary">Guardar movimiento</button>
                    <button type="button" class="clay-btn-secondary" onclick="cerrarModalMovimiento()">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-cerrar" class="modal-overlay">
        <div class="clay-card modal-card">
            <h3>Cerrar caja</h3>
            <p class="subtitulo">Contá el efectivo e indicá el monto final.</p>

            @if ($caja['estado'] === 'abierta')
                <p class="subtitulo">
                    Monto actual según el sistema:
                    <strong id="monto-actual-cierre">${{ number_format($montoActual, 2, ',', '.') }}</strong>
                </p>
            @endif

            <form method="POST" action="{{ route('caja.cerrar', $caja['id_caja']) }}">
                @csrf
                @method('PATCH')

                <label for="monto_final">Monto final</label>
                <input type="number" step="0.01" min="0" name="monto_final" id="monto_final" class="clay-input" inputmode="decimal" autocomplete="off" data-solo-numeros required>

                <label for="observaciones_cierre">Observaciones (opcional)</label>
                <input type="text" name="observaciones" id="observaciones_cierre" class="clay-input" autocomplete="off">

                <div class="fila-acciones">
                    <button type="submit" class="clay-btn-primary">Confirmar cierre</button>
                    <button type="button" class="clay-btn-secondary" onclick="document.getElementById('modal-cerrar').classList.remove('visible')">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const etiquetasTipo = @json($etiquetasTipo);
        const etiquetasMedio = @json($etiquetasMedio);

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

        function cerrarModalMovimiento() {
            document.getElementById('modal-movimiento').classList.remove('visible');
            document.getElementById('error-movimiento').style.display = 'none';
            document.getElementById('form-movimiento').reset();
        }

        function formatearMonto(numero) {
            return '$' + Number(numero).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        document.getElementById('form-movimiento').addEventListener('submit', function (evento) {
            evento.preventDefault();

            const form = evento.target;
            const datos = new FormData(form);
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const errorDiv = document.getElementById('error-movimiento');

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
                body: datos,
            })
                .then(async (respuesta) => {
                    const cuerpo = await respuesta.json();

                    if (!respuesta.ok || cuerpo.error) {
                        errorDiv.textContent = cuerpo.mensaje || cuerpo.message || 'Ocurrio un error al registrar el movimiento.';
                        errorDiv.style.display = 'block';
                        return;
                    }

                    // Actualizar el monto actual (arriba de todo y en el modal de cierre).
                    ['monto-actual', 'monto-actual-cierre'].forEach((id) => {
                        const el = document.getElementById(id);
                        if (el) {
                            el.textContent = formatearMonto(cuerpo.monto_actual);
                        }
                    });

                    // Agregar la fila nueva a la tabla, y mostrar la tabla si estaba oculta.
                    const tabla = document.getElementById('tabla-movimientos');
                    const cuerpoTabla = document.getElementById('tabla-movimientos-body');
                    const sinMovimientos = document.getElementById('sin-movimientos');

                    tabla.style.display = 'table';
                    sinMovimientos.style.display = 'none';

                    const m = cuerpo.movimiento;
                    const fila = document.createElement('tr');
                    const celdas = [
                        etiquetasTipo[m.tipo] || m.tipo.replace(/_/g, ' '),
                        etiquetasMedio[m.medio_pago] || etiquetasMedio.efectivo,
                        m.concepto,
                        formatearMonto(m.monto),
                    ];

                    celdas.forEach((texto) => {
                        const td = document.createElement('td');
                        td.textContent = texto; // textContent: el concepto no se interpreta como HTML
                        fila.appendChild(td);
                    });

                    cuerpoTabla.appendChild(fila);

                    cerrarModalMovimiento();
                })
                .catch(() => {
                    errorDiv.textContent = 'No se pudo conectar con el servidor.';
                    errorDiv.style.display = 'block';
                });
        });
    </script>
</div>
@endsection
