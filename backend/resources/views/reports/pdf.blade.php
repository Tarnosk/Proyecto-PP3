<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { margin: 0 0 6px; color: #075bd8; font-size: 18px; }
        p { margin: 0 0 18px; color: #5e6673; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #075bd8; color: #fff; }
        th, td { border: 1px solid #d7dce2; padding: 7px; text-align: left; }
        tr:nth-child(even) td { background: #f4f7fb; }
        .empty { padding: 18px; border: 1px solid #d7dce2; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p>Generado el {{ now()->format('d/m/Y H:i') }}</p>
    @if ($rows === [])
        <div class="empty">No hay datos para los filtros seleccionados.</div>
    @else
        <table>
            <thead><tr>@foreach ($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
            <tbody>@foreach ($rows as $row)<tr>@foreach ($row as $value)<td>{{ is_float($value) ? '$'.number_format($value, 2, ',', '.') : $value }}</td>@endforeach</tr>@endforeach</tbody>
        </table>
    @endif
</body>
</html>
