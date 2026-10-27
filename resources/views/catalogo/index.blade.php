@extends('catalogo.layout')

@section('hero')
<section class="cat-hero" aria-labelledby="catalogo-title">
    <div><span class="cat-eyebrow">EL CATÁLOGO DE TU DISTRIBUIDORA</span><h1 id="catalogo-title">Todo para tu negocio.<br><em>Más fácil de encontrar.</em></h1><p>Explorá nuestras marcas, conocé las presentaciones y consultá la disponibilidad de cada producto.</p>
        <div class="cat-hero-tags"><span>@include('catalogo.partials.icon', ['icon' => 'check']) Consulta sin registro</span><span>@include('catalogo.partials.icon', ['icon' => 'refresh']) Información actualizada al consultar</span></div>
    </div>
    <div class="cat-hero-art" aria-hidden="true"><div class="cat-hero-orbit"></div><div class="cat-art-package">@include('catalogo.partials.icon', ['icon' => 'box'])</div><div class="cat-art-label"><i></i> Elegí. Compará. Encontrá.</div><span class="cat-art-spark">+</span></div>
</section>
@endsection

@section('sidebar')
<aside id="catalogo-sidebar" class="cat-sidebar" aria-label="Categorías y filtros">
    <form id="catalogo-filters" action="{{ route('catalogo.index') }}" method="get" data-endpoint="{{ route('catalogo.data') }}" data-refresh="{{ config('catalogo.refresh_seconds', 60) }}">
        <div class="cat-sidebar-heading"><h2>Explorá el catálogo</h2><button type="button" class="cat-close-menu" data-cat-close aria-label="Cerrar categorías">×</button></div>
        <label class="cat-field-label" for="cat-query">¿Qué estás buscando?</label>
        <div class="cat-search">@include('catalogo.partials.icon', ['icon' => 'search'])<input id="cat-query" type="search" name="q" maxlength="120" value="{{ $filters['q'] ?? '' }}" placeholder="Producto, marca o código" autocomplete="off"></div>
        <fieldset class="cat-categories"><legend>Categorías</legend><div id="cat-category-options">
            <label class="cat-category"><input type="radio" name="categoria" value="" @checked(empty($filters['categoria']))><span>Todos los productos</span><small>{{ $totalCatalog ?? '—' }}</small></label>
            @foreach($categories as $category)
                <label class="cat-category"><input type="radio" name="categoria" value="{{ $category['id'] }}" @checked(($filters['categoria'] ?? '') === $category['id'])><span>{{ $category['nombre'] }}</span><small>{{ $category['cantidad'] }}</small></label>
            @endforeach
            @if(!empty($filters['categoria']) && !collect($categories)->contains('id', $filters['categoria']))
                <label class="cat-category"><input type="radio" name="categoria" value="{{ $filters['categoria'] }}" checked><span>Categoría seleccionada</span><small>0</small></label>
            @endif
        </div></fieldset>
        <label class="cat-field-label" for="cat-brand">Marca</label>
        <select id="cat-brand" name="marca"><option value="">Todas las marcas</option>
            @foreach($brands as $brand)<option value="{{ $brand['id'] }}" @selected(($filters['marca'] ?? '') === $brand['id'])>{{ $brand['nombre'] }} ({{ $brand['cantidad'] }})</option>@endforeach
            @if(!empty($filters['marca']) && !collect($brands)->contains('id', $filters['marca']))<option value="{{ $filters['marca'] }}" selected>Marca seleccionada (0)</option>@endif
        </select>
        <label class="cat-field-label" for="cat-availability">Disponibilidad</label>
        <select id="cat-availability" name="disponibilidad"><option value="">Todos los estados</option>@foreach(['disponible' => 'Con disponibilidad', 'sin-stock' => 'Sin stock', 'consultar' => 'A consultar'] as $key => $label)<option value="{{ $key }}" @selected(($filters['disponibilidad'] ?? '') === $key)>{{ $label }}</option>@endforeach</select>
        <label class="cat-field-label" for="cat-sort">Ordenar por</label>
        <select id="cat-sort" name="orden">@foreach(['nombre' => 'Nombre: A a Z', 'codigo' => 'Código de producto', 'precio-asc' => 'Menor precio', 'precio-desc' => 'Mayor precio'] as $key => $label)<option value="{{ $key }}" @selected(($filters['orden'] ?? 'nombre') === $key)>{{ $label }}</option>@endforeach</select>
        <button class="cat-button cat-apply" type="submit">Aplicar filtros @include('catalogo.partials.icon', ['icon' => 'arrow'])</button>
        <a class="cat-reset" href="{{ route('catalogo.index') }}">Limpiar filtros</a>
    </form>
    <div class="cat-sidebar-note">@include('catalogo.partials.icon', ['icon' => 'box'])<p><strong>Tu producto, bien identificado.</strong>Usá el código para consultar una presentación específica.</p></div>
</aside>
@endsection

@section('content')
    <div class="cat-section-heading"><div><span class="cat-eyebrow">ENCONTRÁ LO QUE NECESITÁS</span><h2>Nuestros productos</h2></div><button type="button" class="cat-refresh" data-cat-refresh>@include('catalogo.partials.icon', ['icon' => 'refresh']) Actualizar</button></div>
    <div class="cat-freshness" role="status" aria-live="polite" id="cat-freshness">@if($checkedAt)<i></i> Consultado a las {{ $checkedAt->format('H:i') }} · Argentina @else Sin actualización disponible @endif</div>
    <div id="cat-results" aria-busy="false">@include('catalogo.partials.results')</div>
    <noscript><p class="cat-notice">Podés buscar y filtrar con “Aplicar filtros”. Para consultar cambios de disponibilidad, recargá la página.</p></noscript>
@endsection
