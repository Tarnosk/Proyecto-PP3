@extends('layouts.app')

@section('title', 'Reporte no disponible | Sistema de gestión')

@section('content')
    @include('reports.partials.navigation')
    <section class="page-heading">
        <p class="eyebrow">Reportes y Finanzas</p>
        <h1>No pudimos consultar este reporte</h1>
    </section>
    <section class="table-panel report-unavailable" role="alert">
        <p>El servicio de datos no está disponible o devolvió información que no podemos validar. Intentá nuevamente en unos minutos.</p>
        <p>El reporte queda sin datos disponibles hasta completar la consulta.</p>
        <div class="toolbar-actions">
            <a class="button button-primary" href="{{ request()->getRequestUri() }}">Reintentar consulta</a>
        </div>
    </section>
@endsection
