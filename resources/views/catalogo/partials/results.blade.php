@if($unavailable)
    <section class="cat-empty cat-service-error" role="status">@include('catalogo.partials.icon', ['icon' => 'refresh'])<span class="cat-eyebrow">VOLVÉ A INTENTARLO</span><h3>No pudimos consultar el catálogo</h3><p>{{ $message ?? 'La información de productos no está disponible en este momento. Volvé a actualizar en unos instantes.' }}</p><p class="cat-empty-hint">La falta de conexión no significa que no haya productos o stock.</p><a class="cat-button" href="{{ route('catalogo.index', $filters) }}">Reintentar consulta @include('catalogo.partials.icon', ['icon' => 'refresh'])</a></section>
@else
    <div class="cat-results-summary"><p><strong>{{ $products->total() }}</strong> {{ $products->total() === 1 ? 'producto encontrado' : 'productos encontrados' }} @if(!empty($filters['q']))<span>para “{{ $filters['q'] }}”</span>@endif</p><span>Consulta pública · Sin compra online</span></div>
    @if($approximate)<p class="cat-notice" role="status">No encontramos una coincidencia exacta. Te mostramos resultados con nombres similares.</p>@endif
    @if($products->isEmpty())
        <section class="cat-empty">@include('catalogo.partials.icon', ['icon' => 'search'])<h3>{{ $totalCatalog === 0 ? 'El catálogo todavía no tiene productos publicados' : 'No encontramos productos con esos filtros' }}</h3><p>{{ $totalCatalog === 0 ? 'Volvé a consultar más adelante para conocer las novedades.' : 'Probá con otra palabra, el código del producto o una categoría diferente.' }}</p><a class="cat-button" href="{{ route('catalogo.index') }}">Ver todo el catálogo @include('catalogo.partials.icon', ['icon' => 'arrow'])</a></section>
    @else
        <div class="cat-products-grid">@foreach($products as $product)@include('catalogo.partials.card')@endforeach</div>
        @if($products->hasPages())<nav class="cat-pagination" aria-label="Páginas del catálogo">@if(!$products->onFirstPage())<a href="{{ $products->previousPageUrl() }}" rel="prev">← Anterior</a>@endif<span>Página {{ $products->currentPage() }} de {{ $products->lastPage() }}</span>@if($products->hasMorePages())<a href="{{ $products->nextPageUrl() }}" rel="next">Siguiente →</a>@endif</nav>@endif
    @endif
@endif
