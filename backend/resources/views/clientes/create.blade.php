@extends('layouts.app')

@section('title', 'Nuevo cliente | Sistema de gestión')
@section('layout-class', 'narrow')

@section('content')
    <section class="page-heading">
        <p class="eyebrow">Clientes</p>
        <h1>Nuevo cliente</h1>
        <p>Complete los datos obligatorios para registrar un cliente activo.</p>
    </section>

    @include('clientes.form')
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush
