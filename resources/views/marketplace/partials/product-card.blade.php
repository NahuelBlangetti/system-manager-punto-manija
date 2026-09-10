@php
    $productJson = json_encode([
        'id' => $product->id,
        'name' => $product->name,
        'price' => (float) $product->sale_price,
        'maxStock' => $product->stock,
        'image' => $product->image_url ?? '',
    ]);
@endphp
<div class="product-card pm-card overflow-hidden flex flex-col">
    <a href="{{ $product->public_url }}" class="product-card__media">
        @if($product->image_url)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                 class="product-img w-full h-full object-cover"
                 loading="lazy"
                 onerror="this.style.display='none'; this.parentElement.insertAdjacentHTML('beforeend','<div class=\'product-card__placeholder\'><svg class=\'w-12 h-12\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'/></svg></div>')">
        @else
            <div class="product-card__placeholder">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif
    </a>

    <div class="p-3 flex flex-col flex-1">
        @if($product->category)
            <span class="pm-chip pm-chip--category px-2 py-0.5 mb-1.5 self-start">{{ $product->category->name }}</span>
        @endif
        <h3 class="text-sm font-semibold text-on-surface leading-tight">
            <a href="{{ $product->public_url }}" class="hover:text-primary">{{ $product->name }}</a>
        </h3>
        @if($product->description)
            <p class="text-[11px] text-muted mt-1 line-clamp-2 leading-snug flex-1">{{ $product->description }}</p>
        @else
            <div class="flex-1"></div>
        @endif

        <div class="mt-2.5 flex items-center justify-between gap-2">
            <span class="product-card__price">
                ${{ number_format($product->sale_price, 0, ',', '.') }}
            </span>
            @if($product->stock <= 0)
                <span class="pm-chip pm-chip--out px-2 py-0.5">Sin stock</span>
            @else
                <span class="pm-chip pm-chip--stock px-2 py-0.5">En stock</span>
            @endif
        </div>

        @if($product->stock > 0)
            <button x-on:click="add({{ $productJson }})"
                    class="pm-btn-primary mt-3 w-full flex items-center justify-center gap-2 text-xs py-2.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Agregar
            </button>
        @else
            <button disabled class="mt-3 w-full text-xs font-label py-2.5 rounded-lg bg-disabled cursor-not-allowed border-2 border-muted">
                Sin stock
            </button>
        @endif
    </div>
</div>
