@extends('layouts.app')

@section('title', 'Reportes | Sistema de gestion')

@section('content')
    <section class="page-heading">
        <p class="eyebrow">Sprint 3</p>
        <h1>Reportes operativos</h1>
        <p>Consultas actualizadas de ventas, clientes e inventario.</p>
    </section>

    <section class="report-menu" aria-label="Reportes disponibles">
        <a href="{{ route('reports.daily-sales') }}"><strong>Ventas diarias</strong><span>Transacciones y total facturado por fecha.</span></a>
        <a href="{{ route('reports.low-stock') }}"><strong>Stock bajo</strong><span>Productos por debajo del mínimo definido.</span></a>
        <a href="{{ route('reports.client-history') }}"><strong>Historial de clientes</strong><span>Ventas, importes y productos por período.</span></a>
        <a href="{{ route('reports.client-product-reach') }}"><strong>Clientes por producto</strong><span>Porcentaje de clientes que compraron un producto.</span></a>
    </section>
@endsection
