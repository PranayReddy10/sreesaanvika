@props([
    'product',
    'eager' => false,
])

@php
    use App\Support\Shop;

    /*
     * A card is of one shade, and shows that shade's own photographs.
     *
     * A saree woven in two colours is two quite different things to look at,
     * and a listing that shows the design in general shows neither. The first
     * shade is the one the card is of — its photographs, its price, its stock
     * — and the link says so, so the page it opens is showing the same thing
     * the card was.
     *
     * A shade without photographs of its own falls back to the saree's, which
     * is what imagesFor does, so a shop that has not photographed every colour
     * loses nothing.
     */
    $shades = $product->colourways->where('is_visible', true);
    $of = $shades->first();

    $gallery = $product->imagesFor($of);
    $image = $gallery->first();
    $second = $gallery->get(1);

    $url = $of ? route('product', [$product, 'shade' => $of->name]) : route('product', $product);

    $sale = $product->onSale($of);
    $off = $product->discountPercent($of);
    $stock = $product->stockFor($of);
@endphp

<article class="group">
    <a href="{{ $url }}" class="block relative overflow-hidden rounded-[var(--radius-card)] bg-[color:var(--color-surface-2)]">
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
                <span class="rounded-full bg-[color:var(--color-brand)] px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-wider text-white">
                    {{ $off }}% off
                </span>
            @endif

            @if ($stock !== null && $stock <= 0)
                <span class="rounded-full bg-[color:var(--color-page)]/85 px-2.5 py-1 text-[0.65rem] uppercase tracking-wider text-ink-soft">
                    Sold out
                </span>
            @elseif ($product->isLowStock())
                {{-- Deep brown with cream on it, like everything else that is
                     a filled shape here. It was maroon with the page's dark
                     ink on it, which read as a smudge on a photograph. --}}
                <span class="rounded-full bg-[color:var(--color-gold-deep)] px-2.5 py-1 text-[0.65rem] uppercase tracking-wider text-[color:var(--color-page)]">
                    {{ $stock }} left
                </span>
            @endif
        </div>
    </a>

    <div class="pt-3.5">
        <h3 class="font-head text-[1.0625rem] leading-snug">
            <a href="{{ $url }}" class="hover:text-gold-light transition">{{ $product->name }}</a>
        </h3>

        <p class="mt-1.5 flex items-baseline gap-2">
            <span class="text-[0.9375rem] {{ $sale ? 'text-gold-light' : 'text-ink-soft' }}">{{ Shop::money($product->priceFor($of)) }}</span>
            @if ($sale)
                <span class="text-sm text-ink-faint line-through">{{ Shop::money($product->fullPriceFor($of)) }}</span>
            @endif
        </p>

        @if ($shades->count() > 1)
            <div class="mt-2.5 flex items-center gap-1.5">
                @foreach ($shades->take(5) as $shade)
                    {{-- The one the card is of is ringed, so the row of dots
                         says which colour is in the photograph rather than
                         leaving it to be guessed. --}}
                    <a href="{{ route('product', [$product, 'shade' => $shade->name]) }}"
                       class="w-3.5 h-3.5 rounded-full border transition
                              {{ $of && $shade->id === $of->id
                                  ? 'border-[color:var(--color-ink)] ring-1 ring-offset-1 ring-[color:var(--color-ink)] ring-offset-[color:var(--color-page)]'
                                  : 'border-[color:var(--color-line)] hover:border-[color:var(--color-ink-muted)]' }}"
                       style="background: {{ $shade->hex ?: '#555' }}"
                       title="{{ $shade->name }}"
                       aria-label="{{ $product->name }} in {{ $shade->name }}"></a>
                @endforeach
                @if ($shades->count() > 5)
                    <span class="text-xs text-ink-faint">+{{ $shades->count() - 5 }}</span>
                @endif
            </div>
        @endif
    </div>
</article>
