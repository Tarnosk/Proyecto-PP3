@extends('layouts.app')

@section('title', 'Editar cliente | Sistema de gestión')
@section('layout-class', 'narrow')

@section('nav-links')
    <a href="{{ route('clientes.index') }}">Clientes</a>
@endsection

@section('content')
    <section class="page-heading">
        <p class="eyebrow">Administración</p>
        <h1>Editar cliente</h1>
        <p>Actualice los datos comerciales y el estado del cliente.</p>
    </section>

    @include('clientes.form', ['cliente' => $cliente])
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush
