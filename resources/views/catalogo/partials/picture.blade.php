<div class="cat-product-picture {{ $large ?? false ? 'cat-picture-large' : '' }}">
    @if($product->imagen)<img src="{{ $product->imagen }}" alt="{{ $product->nombre }}" loading="lazy" decoding="async" referrerpolicy="no-referrer" data-cat-image>@endif
    <div class="cat-picture-fallback" @if($product->imagen) hidden @endif>@include('catalogo.partials.icon', ['icon' => 'box'])<span>Imagen no disponible</span></div>
</div>
