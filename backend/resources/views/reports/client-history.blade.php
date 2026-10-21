@extends('layouts.app')

@section('title', 'Historial de clientes | Reportes')

@section('content')
    <section class="page-heading"><p class="eyebrow">Reportes</p><h1>Historial de clientes</h1><p>Compras y ventas con sus productos e importes.</p></section>
    <form class="toolbar-form report-filter" action="{{ route('reports.client-history') }}" method="get">
        <div class="field client-filter"><label for="cliente_id">Cliente</label><select id="cliente_id" name="cliente_id" required><option value="">Seleccionar...</option>@foreach ($clientes as $option)<option value="{{ $option->id }}" @selected($cliente?->id === $option->id)>{{ $option->apellido_razon_social }}, {{ $option->nombre }} · {{ $option->dni_cuit }}</option>@endforeach</select></div>
        <div class="field"><label for="desde">Desde</label><input id="desde" type="date" name="desde" value="{{ $desde }}"></div>
        <div class="field"><label for="hasta">Hasta</label><input id="hasta" type="date" name="hasta" value="{{ $hasta }}"></div>
        <div class="toolbar-actions"><button class="button button-primary" type="submit">Consultar</button>@if ($cliente && $salesReady)<a class="button button-secondary" href="{{ route('reports.client-history.export', ['format' => 'pdf', 'cliente_id' => $cliente->id, 'desde' => $desde, 'hasta' => $hasta]) }}">PDF</a><a class="button button-secondary" href="{{ route('reports.client-history.export', ['format' => 'xlsx', 'cliente_id' => $cliente->id, 'desde' => $desde, 'hasta' => $hasta]) }}">Excel</a>@endif</div>
    </form>
    @if ($cliente)
        <div class="section-title"><h2>{{ $cliente->apellido_razon_social }}, {{ $cliente->nombre }}</h2>@if ($salesReady)<span>{{ $ventas->count() }} operaciones</span>@endif</div>
        @include('reports.partials.sales-table', ['ventas' => $ventas])
    @else
        <section class="table-panel"><p class="empty-state">Seleccione un cliente para consultar su historial.</p></section>
    @endif
@endsection
