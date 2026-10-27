<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Explorá el catálogo de PyB Pigüé: productos, marcas, presentaciones y disponibilidad.">
    <meta name="referrer" content="same-origin">
    <title>@yield('title', 'Catálogo de productos | PyB Pigüé')</title>
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/catalogo.css') }}">
</head>
<body class="catalogo-page">
    <a class="cat-skip" href="#catalogo-main">Saltar al contenido</a>
    <div class="cat-announcement"><span>Tu próxima compra empieza acá.</span><span>Catálogo de consulta · Sin registro</span></div>
    <header class="cat-header">
        <a class="cat-brand" href="{{ route('catalogo.index') }}" aria-label="PyB Pigüé, inicio del catálogo">
            <span class="cat-brand-mark">@include('catalogo.partials.icon', ['icon' => 'box'])</span>
            <span><strong>PyB Pigüé</strong><small>DISTRIBUIDORA COMERCIAL</small></span>
        </a>
        <nav class="cat-header-links" aria-label="Navegación del catálogo">
            <a href="{{ route('catalogo.index') }}" @if(request()->routeIs('catalogo.index')) aria-current="page" @endif>Catálogo</a>
            <a class="cat-internal-link" href="{{ route('login') }}">Acceso interno @include('catalogo.partials.icon', ['icon' => 'arrow'])</a>
        </nav>
    </header>
    <div class="cat-mobile-nav">
        <a href="{{ route('catalogo.index') }}" aria-label="Inicio del catálogo">@include('catalogo.partials.icon', ['icon' => 'home'])</a>
        <button type="button" data-cat-toggle aria-controls="catalogo-sidebar" aria-expanded="false">@include('catalogo.partials.icon', ['icon' => 'menu']) Categorías y filtros</button>
    </div>
    @yield('hero')
    <div class="cat-shell">
        @yield('sidebar')
        <main id="catalogo-main" class="cat-main" tabindex="-1">@yield('content')</main>
    </div>
    <footer class="cat-footer"><strong>PyB Pigüé <span>Distribuidora comercial</span></strong><p>Explorá productos. Compará presentaciones. Encontrá lo que necesitás.</p><small>Catálogo de consulta. Los precios y la disponibilidad corresponden a la última actualización informada; no reservan mercadería.</small></footer>
    <script src="{{ asset('assets/js/catalogo.js') }}" defer></script>
</body>
</html>
