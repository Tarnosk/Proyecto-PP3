@extends('layouts.app')

@section('title', 'Ventas y Pedidos | Sistema de gestión')
@section('layout-class', 'narrow')

@section('content')
    <section class="page-heading">
        <p class="eyebrow">Módulo comercial</p>
        <h1>Ventas y Pedidos</h1>
        <p>Módulo preparado para la gestión de ventas y pedidos.</p>
    </section>
    <section class="form-panel">
        <p>Esta sección queda disponible como acceso operativo para el repartidor.</p>
        <div class="form-actions"><a class="button button-secondary" href="{{ route('dashboard') }}">Volver al panel</a></div>
    </section>
@endsection
