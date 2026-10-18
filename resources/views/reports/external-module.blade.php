@extends('layouts.app')

@section('title', $module.' | Sistema de gestion')

@section('content')
    <section class="page-heading">
        <p class="eyebrow">{{ $group }}</p>
        <h1>{{ $module }}</h1>
        <p>Este módulo corresponde al {{ $group }}.</p>
    </section>
@endsection
