@php
    use App\Support\Shop;
    $limit = (int) ($section?->setting('limit', 5) ?: 5);
    $picks = $featured->take($limit);
@endphp

@if ($picks->isNotEmpty())
<section class="od-wrap py-16 md:py-24">
    <header class="max-w-2xl">
        @if ($section?->eyebrow)
            <p class="od-eyebrow">{{ $section->eyebrow }}</p>
        @endif
        <h2 class="mt-3 font-display text-3xl md:text-5xl leading-tight">
            {{ $section?->heading ?: 'The pick of the loom' }}
        </h2>
        @if ($section?->subheading)
            <p class="mt-4 text-ink-muted leading-relaxed">{{ $section->subheading }}</p>
        @endif
    </header>

    {{-- Editorial rows rather than a grid: a handful of pieces shown large,
         alternating sides, so the photograph is the thing on the page and not
         one tile among twenty. --}}
    <div class="mt-12 space-y-16 md:space-y-24">
        @foreach ($picks as $i => $product)
            @php
                /*
                 * The same photographs the saree's own page opens with.
                 *
                 * That page starts on the first shade, and a shade with
                 * photographs of its own shows those rather than the saree's
                 * general ones — so taking the general ones here meant the
                 * thumbnail a shopper pressed was not among the photographs
                 * she then landed on.
                 */
                $opensWith = $product->colourways->where('is_visible', true)->first();
                $gallery = $product->imagesFor($opensWith);
                $image = $gallery->first();
                $extra = $gallery->slice(1, 2);
                $flip = $i % 2 === 1;
            @endphp

            <article class="grid gap-7 md:gap-12 md:grid-cols-12 items-center">
                <a href="{{ route('product', $product) }}"
                   class="md:col-span-7 block overflow-hidden rounded-[var(--radius-card)] group {{ $flip ? 'md:order-2' : '' }}">
                    @if ($image)
                        <img src="{{ $image->url }}" alt="{{ $image->alt ?: $product->name }}"
                             class="w-full aspect-[4/3] md:aspect-[16/11] object-cover transition duration-700 group-hover:scale-[1.03]"
                             loading="lazy" decoding="async" width="1200" height="800">
                    @endif
                </a>

                <div class="md:col-span-5 {{ $flip ? 'md:order-1 md:pr-6' : 'md:pl-6' }}">
                    <p class="od-eyebrow">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</p>

                    <h3 class="mt-3 font-display text-2xl md:text-4xl leading-tight">
                        <a href="{{ route('product', $product) }}" class="hover:text-gold-light transition">{{ $product->name }}</a>
                    </h3>

                    @if ($product->short_description)
                        <p class="mt-4 text-ink-muted leading-relaxed">{{ $product->short_description }}</p>
                    @endif

                    <p class="mt-5 flex items-baseline gap-3">
                        <span class="text-xl {{ $product->onSale() ? 'text-gold-light' : '' }}">{{ Shop::money($product->priceFor()) }}</span>
                        @if ($product->onSale())
                            <span class="text-ink-faint line-through">{{ Shop::money($product->fullPriceFor()) }}</span>
                            <span class="text-xs uppercase tracking-wider text-gold">{{ $product->discountPercent() }}% off</span>
                        @endif
                    </p>

                    @if ($product->colourways->count() > 1)
                        <div class="mt-4 flex items-center gap-2">
                            @foreach ($product->colourways->where('is_visible', true) as $shade)
                                <span class="w-4 h-4 rounded-full border border-[color:var(--color-line)]"
                                      style="background: {{ $shade->hex ?: '#555' }}" title="{{ $shade->name }}"></span>
                            @endforeach
                            <span class="text-xs text-ink-faint ml-1">
                                {{ $product->colourways->count() }} shades
                            </span>
                        </div>
                    @endif

                    <a href="{{ route('product', $product) }}" class="od-btn od-btn-ghost mt-7">See this saree</a>

                    {{-- The saree's other photographs, each one a way in to it.

                         They were decoration before — aria-hidden, dimmed, not
                         clickable — and a picture that looks like a thumbnail
                         and does nothing when pressed is worse than no picture
                         at all. Each opens the saree on that photograph. --}}
                    @if ($extra->isNotEmpty())
                        <div class="mt-7 hidden md:flex gap-3">
                            @foreach ($extra as $thumb)
                                <a href="{{ route('product', [$product, 'photo' => $thumb->id]) }}"
                                   class="block overflow-hidden rounded-md border border-transparent
                                          hover:border-[color:var(--color-brand)] transition">
                                    <img src="{{ $thumb->url }}"
                                         alt="{{ $product->name }}, another view"
                                         class="w-20 h-24 object-cover opacity-85 hover:opacity-100 transition"
                                         loading="lazy" decoding="async">
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
</section>
@endif
