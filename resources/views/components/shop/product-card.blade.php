@props([
    'product',
    'eager' => false,
])

@php
    use App\Support\Shop;

    $image = $product->firstImage();
    $second = $product->imagesFor()->get(1);
    $sale = $product->onSale();
    $off = $product->discountPercent();
    $stock = $product->stockFor();
    $shades = $product->colourways->where('is_visible', true);
@endphp

<article class="group">
    <a href="{{ route('product', $product) }}" class="block relative overflow-hidden rounded-[var(--radius-card)] bg-[color:var(--color-surface-2)]">
        @if ($image)
            <img src="{{ $image->url }}" alt="{{ $image->alt ?: $product->name }}"
                 class="od-shot transition duration-700 group-hover:scale-[1.04]"
                 width="600" height="800"
                 loading="{{ $eager ? 'eager' : 'lazy' }}"
                 fetchpriority="{{ $eager ? 'high' : 'auto' }}"
                 decoding="async">

            {{-- The second photograph on hover, which is how a shopper sees the
                 pallu without opening the page. Desktop only: on a phone there
                 is no hover, and preloading it would cost data for nothing. --}}
            @if ($second)
                <img src="{{ $second->url }}" alt=""
                     class="od-shot absolute inset-0 hidden md:block opacity-0 transition duration-500 group-hover:opacity-100"
                     width="600" height="800" loading="lazy" decoding="async" aria-hidden="true">
            @endif
        @else
            <div class="od-shot grid place-items-center text-ink-faint text-sm">No photograph yet</div>
        @endif

        <div class="absolute top-3 left-3 flex flex-col gap-1.5 items-start">
            @if ($sale && $off > 0)
                <span class="rounded-full bg-gold px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-wider text-[color:var(--color-page)]">
                    {{ $off }}% off
                </span>
            @endif

            @if ($stock !== null && $stock <= 0)
                <span class="rounded-full bg-[color:var(--color-page)]/85 px-2.5 py-1 text-[0.65rem] uppercase tracking-wider text-ink-soft">
                    Sold out
                </span>
            @elseif ($product->isLowStock())
                <span class="rounded-full bg-[color:var(--color-maroon)] px-2.5 py-1 text-[0.65rem] uppercase tracking-wider text-ink">
                    {{ $stock }} left
                </span>
            @endif
        </div>
    </a>

    <div class="pt-3.5">
        <h3 class="font-head text-[1.0625rem] leading-snug">
            <a href="{{ route('product', $product) }}" class="hover:text-gold-light transition">{{ $product->name }}</a>
        </h3>

        <p class="mt-1.5 flex items-baseline gap-2">
            <span class="text-[0.9375rem] {{ $sale ? 'text-gold-light' : 'text-ink-soft' }}">{{ Shop::money($product->priceFor()) }}</span>
            @if ($sale)
                <span class="text-sm text-ink-faint line-through">{{ Shop::money($product->fullPriceFor()) }}</span>
            @endif
        </p>

        @if ($shades->count() > 1)
            <div class="mt-2.5 flex items-center gap-1.5">
                @foreach ($shades->take(5) as $shade)
                    <span class="w-3.5 h-3.5 rounded-full border border-[color:var(--color-line)]"
                          style="background: {{ $shade->hex ?: '#555' }}"
                          title="{{ $shade->name }}"></span>
                @endforeach
                @if ($shades->count() > 5)
                    <span class="text-xs text-ink-faint">+{{ $shades->count() - 5 }}</span>
                @endif
            </div>
        @endif
    </div>
</article>
