@extends('layouts.app')
@section('title', 'G2 — Instalación pendiente')
@section('content')
    <section class="card">
        <h1>{{ ['caja' => 'Caja y Tesorería', 'conciliacion' => 'Conciliación Bancaria', 'entregas' => 'Entregas y Despacho', 'rendiciones' => 'Rendiciones y Cobros'][$module] }}</h1>
        <p role="status">{{ $message }}</p>
        <a href="{{ route('dashboard') }}">Volver al panel</a>
    </section>
@endsection
