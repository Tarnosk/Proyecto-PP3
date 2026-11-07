<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Historial de cierres de caja</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 4px 6px; text-align: left; }
        th { background: #eee; }
        td.num, th.num { text-align: right; }
        .pie { margin-top: 12px; color: #666; font-size: 9px; }
    </style>
</head>
<body>
@php
    $dinero = function ($v) {
        if ($v === null) { return '—'; }
        return ($v < 0 ? '-' : '') . '$' . number_format(abs((float) $v), 2, ',', '.');
    };
@endphp

<h1>Historial de cierres de caja</h1>
<div class="meta">
    @if ($desde || $hasta)
        Período: {{ $desde ? \Illuminate\Support\Carbon::parse($desde)->format('d/m/Y') : 'inicio' }}
        al {{ $hasta ? \Illuminate\Support\Carbon::parse($hasta)->format('d/m/Y') : 'hoy' }}
    @else
        Todos los cierres
    @endif
    · {{ count($cierres) }} {{ count($cierres) === 1 ? 'cierre' : 'cierres' }}
</div>

@if (count($cierres) === 0)
    <p>No hay cierres de caja para el filtro seleccionado.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Caja</th>
                <th>Dueño</th>
                <th class="num">Monto inicial</th>
                <th class="num">Monto esperado</th>
                <th class="num">Monto final</th>
                <th class="num">Diferencia</th>
                <th>Cierre</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cierres as $cierre)
                @php
                    $pendiente = !empty($cierre['cierre_automatico']) && ($cierre['monto_final'] ?? null) === null;
                @endphp
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($cierre['fecha'])->format('d/m/Y') }}</td>
                    <td>#{{ $cierre['id_caja'] }}</td>
                    <td>Usuario #{{ $cierre['id_usuario'] ?? '—' }}</td>
                    <td class="num">{{ $dinero($cierre['monto_inicial'] ?? null) }}</td>
                    <td class="num">{{ $dinero($cierre['monto_esperado'] ?? null) }}</td>
                    <td class="num">{{ $pendiente ? 'Pendiente de conteo' : $dinero($cierre['monto_final'] ?? null) }}</td>
                    <td class="num">{{ $pendiente ? 'Pendiente de conteo' : $dinero($cierre['diferencia'] ?? null) }}</td>
                    <td>
                        @if (!empty($cierre['cierre_automatico']))
                            Automático
                        @else
                            Manual (usuario #{{ $cierre['id_usuario_cierre'] ?? '—' }})
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="pie">
    Documento de solo lectura. Generado el {{ $generadoEn->format('d/m/Y H:i') }} por el usuario #{{ $generadoPor }}.
    La diferencia es monto final menos monto esperado (negativa = faltante).
</div>
</body>
</html>
