<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cierre de caja #{{ $cierre['id_caja'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 17px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 16px 0 6px; }
        .meta { color: #555; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 5px 7px; text-align: left; }
        th { background: #eee; }
        td.num, th.num { text-align: right; }
        .resumen th { width: 38%; }
        .pie { margin-top: 14px; color: #666; font-size: 9px; }
    </style>
</head>
<body>
@php
    $dinero = function ($v) {
        if ($v === null) { return '—'; }
        return ($v < 0 ? '-' : '') . '$' . number_format(abs((float) $v), 2, ',', '.');
    };
    $etiquetasMedio = ['efectivo' => 'Efectivo', 'cheque' => 'Cheque', 'transferencia' => 'Transferencia', 'otro' => 'Otro'];
    $etiquetasTipo = [
        'cobro' => 'Cobro', 'reversion' => 'Reversión de cobro', 'ingreso_manual' => 'Ingreso manual',
        'egreso' => 'Egreso', 'pago_proveedor' => 'Pago a proveedor', 'extraccion' => 'Extracción', 'impuesto' => 'Impuesto',
    ];
    $ingresos = ['cobro', 'ingreso_manual'];
    $pendiente = !empty($cierre['cierre_automatico']) && ($cierre['monto_final'] ?? null) === null;
    $fmtFecha = fn ($f) => $f ? \Illuminate\Support\Carbon::parse($f)->timezone(config('app.timezone'))->format('d/m/Y H:i') : '—';
@endphp

<h1>Cierre de caja #{{ $cierre['id_caja'] }}</h1>
<div class="meta">
    Fecha {{ \Illuminate\Support\Carbon::parse($cierre['fecha'])->format('d/m/Y') }}
    · Dueño: usuario #{{ $cierre['id_usuario'] ?? '—' }}
    · {{ !empty($cierre['cierre_automatico']) ? 'Cierre automático (72 h)' : 'Cierre manual' }}
</div>

<table class="resumen">
    <tbody>
        <tr><th>Apertura</th><td>{{ $fmtFecha($cierre['fecha_hora_apertura'] ?? null) }}</td></tr>
        <tr><th>Cierre</th><td>{{ $fmtFecha($cierre['fecha_hora_cierre'] ?? null) }}</td></tr>
        <tr><th>Monto inicial</th><td>{{ $dinero($cierre['monto_inicial'] ?? null) }}</td></tr>
        <tr><th>Monto esperado</th><td>{{ $dinero($cierre['monto_esperado'] ?? null) }}</td></tr>
        <tr><th>Monto final contado</th><td>{{ $pendiente ? 'Pendiente de conteo' : $dinero($cierre['monto_final'] ?? null) }}</td></tr>
        <tr><th>Diferencia (final − esperado)</th><td>{{ $pendiente ? 'Pendiente de conteo' : $dinero($cierre['diferencia'] ?? null) }}</td></tr>
        @if (isset($cierre['subtotal_efectivo']) || isset($cierre['subtotal_valores']))
            <tr><th>Movimientos en efectivo</th><td>{{ $dinero($cierre['subtotal_efectivo'] ?? null) }}</td></tr>
            <tr><th>Movimientos en valores (cheques y otros)</th><td>{{ $dinero($cierre['subtotal_valores'] ?? null) }}</td></tr>
        @endif
        @if (!empty($cierre['observaciones']))
            <tr><th>Observaciones</th><td>{{ $cierre['observaciones'] }}</td></tr>
        @endif
    </tbody>
</table>

<h2>Movimientos de la caja</h2>
@if (empty($cierre['movimientos']))
    <p>La caja no tiene movimientos.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Medio</th>
                <th>Concepto</th>
                <th class="num">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cierre['movimientos'] as $mov)
                @php
                    $esIngreso = in_array($mov['tipo'], $ingresos, true);
                    $monto = (float) $mov['monto'];
                @endphp
                <tr>
                    <td>{{ $etiquetasTipo[$mov['tipo']] ?? ucfirst(str_replace('_', ' ', $mov['tipo'])) }}</td>
                    <td>{{ $etiquetasMedio[$mov['medio_pago'] ?? 'efectivo'] ?? ucfirst($mov['medio_pago'] ?? 'efectivo') }}</td>
                    <td>{{ $mov['concepto'] }}</td>
                    <td class="num">{{ $dinero($esIngreso ? $monto : -$monto) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="pie">
    Documento de solo lectura. Generado el {{ $generadoEn->format('d/m/Y H:i') }} por el usuario #{{ $generadoPor }}.
</div>
</body>
</html>
