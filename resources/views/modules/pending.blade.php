@extends('layouts.app')

@section('title', $module.' | Sistema de gestión')
@section('layout-class', 'narrow')
@section('content')
    <section class="form-panel" style="padding: 2rem">
        <p class="eyebrow">Disponibilidad del módulo</p>
        <h1>{{ $module }}</h1>
        <p>Esta sección está pendiente de integración. Sus operaciones todavía no están habilitadas.</p>
        <p>Mientras tanto, podés seguir trabajando en las demás secciones del sistema.</p>
        <a class="button button-primary" href="{{ route('dashboard') }}">Volver al panel</a>
    </section>
@endsection
