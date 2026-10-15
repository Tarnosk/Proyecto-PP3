@extends('layouts.app')

@section('title', 'Reportes | Sistema de gestion')

@section('content')
    <section class="page-heading">
        <p class="eyebrow">Grupo 1</p>
        <h1>Reportes de clientes</h1>
    </section>

    <section class="report-menu" aria-label="Reportes disponibles">
        <a href="{{ route('reports.client-history') }}"><strong>Historial de clientes</strong><span>Ventas, importes y productos por período.</span></a>
        <a href="{{ route('reports.client-product-reach') }}"><strong>Clientes por producto</strong><span>Porcentaje de clientes que compraron un producto.</span></a>
        <a href="{{ route('reports.debtors') }}"><strong>Clientes deudores</strong><span>Saldos y facturas pendientes.</span></a>
        <a href="{{ route('reports.product-ranking') }}"><strong>Ranking comercial</strong><span>Productos vendidos por periodo.</span></a>
        <div class="report-menu-external"><strong>Reportes de ventas</strong><span>Corresponde a G4.</span></div>
        <div class="report-menu-external"><strong>Reportes de stock</strong><span>Corresponde a G3.</span></div>
    </section>
@endsection
