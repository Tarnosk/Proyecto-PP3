<article class="cat-product-card">
    <a class="cat-card-image-link" href="{{ route('catalogo.show', $product->id) }}" tabindex="-1" aria-hidden="true">@include('catalogo.partials.picture', ['large' => false])</a>
    <span class="cat-stock cat-stock-{{ $product->disponibilidad }}"><i></i>{{ $product->etiqueta() }}</span>
    <div class="cat-card-body"><div class="cat-card-meta"><span>{{ $product->marca }}</span><small>Cód. {{ $product->codigo }}</small></div>
        <h3><a href="{{ route('catalogo.show', $product->id) }}">{{ $product->nombre }}</a></h3><p class="cat-card-presentation">{{ $product->presentacion }}</p><p class="cat-card-category">{{ $product->categoria }}</p>
        <div class="cat-card-bottom"><div><small>{{ $product->precio === null ? 'Precio a confirmar' : 'Precio de referencia' }}</small><strong>{{ $product->precioVisible() }}</strong></div><a class="cat-card-detail" href="{{ route('catalogo.show', $product->id) }}" aria-label="Ver detalle de {{ $product->nombre }}">@include('catalogo.partials.icon', ['icon' => 'arrow'])</a></div>
    </div>
</article>
