@extends('layouts.app')

@section('title', 'Inventario y Stock | Sistema de gestión')
@section('layout-class', 'narrow')

@section('content')
    <section class="page-heading">
        <p class="eyebrow">Módulo de inventario</p>
        <h1>Inventario y Stock</h1>
        <p>Módulo preparado para consulta y control de stock.</p>
    </section>
    <section class="form-panel">
        <p>Esta sección queda disponible como acceso operativo para el repartidor.</p>
        <div class="form-actions"><a class="button button-secondary" href="{{ route('dashboard') }}">Volver al panel</a></div>
    </section>
@endsection
