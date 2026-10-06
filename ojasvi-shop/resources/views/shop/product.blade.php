@extends('layouts.shop')

@php
    use App\Support\Shop;

    $images = $product->imagesFor();
    $first = $images->first();

    // Everything the shade buttons need, worked out on the server so the page
    // never shows a price the server would not charge.
    $shades = $product->colourways->map(fn ($c) => [
        'id'     => $c->id,
        'name'   => $c->name,
        'hex'    => $c->hex ?: '#555555',
        'price'  => Shop::money($product->priceFor($c)),
        'full'   => $product->onSale($c) ? Shop::money($product->fullPriceFor($c)) : null,
        'off'    => $product->discountPercent($c),
        'stock'  => $product->stockFor($c),
        'order'  => $product->canOrder(1, $c),
        'images' => $product->imagesFor($c)->map(fn ($i) => ['url' => $i->url, 'alt' => $i->alt ?: $product->name])->values(),
    ])->values();

    $reviews = $product->approvedReviews;
    $rating = $reviews->avg('rating');

    $structured = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'image' => $images->map(fn ($i) => $i->url)->values(),
        'description' => strip_tags((string) ($product->short_description ?: $product->description)),
        'sku' => $product->sku,
        'brand' => ['@type' => 'Brand', 'name' => Shop::name()],
        'offers' => [
            '@type' => 'Offer',
            'url' => route('product', $product),
            'priceCurrency' => 'INR',
            'price' => number_format($product->priceFor(), 2, '.', ''),
            'availability' => $product->canOrder()
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
        ],
    ];

    if ($reviews->count()) {
        $structured['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => round((float) $rating, 1),
            'reviewCount' => $reviews->count(),
        ];
    }

    // Hex-escaped so a stray < or & in a saree's description cannot close the
    // script tag it is sitting inside.
    $jsonLd = json_encode($structured, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
@endphp

@section('title', $product->meta_title ?: $product->name . ' — ' . \App\Support\Seo::titleSuffix())
@section('description', $product->meta_description
    ?: \App\Support\Seo::productDescription($product->name, (string) $product->short_description))

{{-- Without the shade: every shade of one design is one page as far as a
     search engine is concerned, and three of them competing would be three
     weaker results instead of one strong one. --}}
@section('canonical', route('product', $product->slug))
@section('image', $first?->url ?? asset('brand/icon-512.png'))
@section('og_type', 'product')

@push('head')
    {{-- So the saree can appear in search with its price and whether it is in
         stock, rather than as a bare link. --}}
    <script type="application/ld+json">{!! $jsonLd !!}</script>
    <script type="application/ld+json">{!! \App\Support\Seo::json(\App\Support\Seo::breadcrumbs([
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Sarees', 'url' => route('shop')],
        ['name' => $product->name, 'url' => route('product', $product->slug)],
    ])) !!}</script>
@endpush

@push('tracking')
@php
    $track = [
        'item_id'   => $product->sku ?: (string) $product->id,
        'item_name' => $product->name,
        'price'     => round($product->priceFor(), 2),
        'quantity'  => 1,
    ];
@endphp
<script>
    window.odItem = {!! \App\Support\Seo::json($track) !!};
    odTrack('view_item', { value: window.odItem.price, items: [window.odItem] });
</script>
@endpush

@section('content')
<div
    x-data="saree({
        shades: {{ Illuminate\Support\Js::from($shades) }},
        fallback: {{ Illuminate\Support\Js::from($images->map(fn ($i) => ['url' => $i->url, 'alt' => $i->alt ?: $product->name])->values()) }},
        start: {{ (int) ($chosen?->id ?? 0) }},
    })"
    class="od-wrap py-8 md:py-12"
>
    <nav class="text-xs text-ink-faint mb-6 flex items-center gap-2">
        <a href="{{ route('home') }}" class="hover:text-ink-muted">Home</a>
        <span>/</span>
        <a href="{{ route('shop') }}" class="hover:text-ink-muted">Sarees</a>
        <span>/</span>
        <span class="text-ink-muted">{{ $product->name }}</span>
    </nav>

    <div class="grid gap-10 lg:grid-cols-2 lg:gap-16">
        {{-- The photographs. On a phone this is a swipe rail, because that is
             how a phone looks at pictures; on a desktop it is a column with a
             strip of thumbnails. --}}
        {{-- min-w-0: a grid item will not shrink below its content by default,
             so without this the swipe rail makes the whole page as wide as the
             photographs inside it. --}}
        <div class="min-w-0">
            <div class="lg:hidden -mx-4">
                <div class="flex gap-3 overflow-x-auto od-scroll snap-x snap-mandatory px-4 pb-3">
                    <template x-for="(img, k) in gallery" :key="k">
                        <img :src="img.url" :alt="img.alt"
                             class="snap-center shrink-0 w-[86vw] od-shot rounded-[var(--radius-card)]"
                             width="600" height="800" decoding="async">
                    </template>
                </div>
            </div>

            <div class="hidden lg:block">
                <img :src="gallery[active]?.url" :alt="gallery[active]?.alt"
                     class="od-shot rounded-[var(--radius-card)]" width="900" height="1200" fetchpriority="high">

                <div class="mt-3 grid grid-cols-5 gap-3" x-show="gallery.length > 1">
                    <template x-for="(img, k) in gallery" :key="k">
                        <button type="button" @click="active = k"
                                class="rounded-md overflow-hidden border transition"
                                :class="active === k ? 'border-[color:var(--color-gold)]' : 'border-transparent opacity-70 hover:opacity-100'">
                            <img :src="img.url" alt="" class="od-shot" width="150" height="200" loading="lazy">
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <div>
            <h1 class="font-display text-3xl md:text-5xl leading-tight">{{ $product->name }}</h1>

            @if ($product->sku)
                <p class="mt-2 text-xs uppercase tracking-[0.16em] text-ink-faint">Design {{ $product->sku }}</p>
            @endif

            @if ($reviews->count())
                <a href="#reviews" class="mt-3 inline-flex items-center gap-2 text-sm text-ink-muted hover:text-gold-light transition">
                    <span class="text-gold">{{ str_repeat('★', (int) round($rating)) }}{{ str_repeat('☆', 5 - (int) round($rating)) }}</span>
                    {{ number_format((float) $rating, 1) }} from {{ $reviews->count() }}
                    {{ \Illuminate\Support\Str::plural('review', $reviews->count()) }}
                </a>
            @endif

            <div class="mt-5 flex items-baseline gap-3 flex-wrap">
                <span class="font-head text-3xl" :class="shade?.full ? 'text-gold-light' : ''" x-text="shade?.price"></span>
                <template x-if="shade?.full">
                    <span class="text-ink-faint line-through" x-text="shade.full"></span>
                </template>
                <template x-if="shade?.off > 0">
                    <span class="text-xs uppercase tracking-[0.14em] text-gold" x-text="shade.off + '% off'"></span>
                </template>
            </div>

            <p class="mt-1 text-xs text-ink-faint">Inclusive of all taxes</p>

            @if ($product->short_description)
                <p class="mt-6 text-ink-soft leading-relaxed">{{ $product->short_description }}</p>
            @endif

            @foreach ($offers as $offer)
                <div class="mt-5 flex items-start gap-3 rounded-[var(--radius-card)] border border-[color:var(--color-line)] bg-[color:var(--color-surface)] p-4">
                    <svg class="w-5 h-5 text-gold shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c.251.023.501.05.75.082M9.75 3.104a24.3 24.3 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-gold-light">{{ $offer->headlineText() }}</p>
                        <p class="text-xs text-ink-muted mt-0.5">Added in your bag — no code to type.</p>
                    </div>
                </div>
            @endforeach

            @if ($shades->count() > 1)
                <div class="mt-7">
                    <p class="od-label">Shade — <span class="text-ink normal-case tracking-normal" x-text="shade?.name"></span></p>
                    <div class="flex flex-wrap gap-2.5">
                        <template x-for="s in shades" :key="s.id">
                            <button type="button" @click="pick(s.id)"
                                    class="relative w-11 h-11 rounded-full border-2 transition"
                                    :class="s.id === chosen
                                        ? 'border-[color:var(--color-gold)] scale-105'
                                        : 'border-[color:var(--color-line-soft)] hover:border-[color:var(--color-gold-deep)]'"
                                    :style="`background: ${s.hex}`"
                                    :title="s.name"
                                    :aria-label="s.name">
                                <template x-if="s.stock !== null && s.stock <= 0">
                                    <span class="absolute inset-0 grid place-items-center text-[color:var(--color-page)] text-lg">×</span>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>
            @endif

            <template x-if="shade && shade.stock !== null && shade.stock > 0 && shade.stock <= 3">
                <p class="mt-5 text-sm text-[color:var(--color-marigold)]">
                    Only <span x-text="shade.stock"></span> left in this shade.
                </p>
            </template>

            <form method="post" action="{{ route('bag.add') }}" class="mt-7" @submit.prevent="addToBag($el)">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="colourway_id" :value="chosen || ''">

                <div class="flex items-stretch gap-3">
                    <div class="flex items-center rounded-full border border-[color:var(--color-line-soft)] shrink-0">
                        <button type="button" @click="qty = Math.max(1, qty - 1)" class="px-4 py-3 text-ink-muted hover:text-ink" aria-label="One fewer">−</button>
                        <input type="number" name="quantity" x-model.number="qty" min="1" max="10"
                               class="w-10 bg-transparent text-center focus:outline-none" aria-label="How many">
                        <button type="button" @click="qty = Math.min(10, qty + 1)" class="px-4 py-3 text-ink-muted hover:text-ink" aria-label="One more">+</button>
                    </div>

                    <button type="submit" class="od-btn od-btn-gold flex-1" :disabled="busy || (shade && !shade.order)">
                        <span x-show="!busy" x-text="shade && !shade.order ? 'Sold out' : 'Add to bag'"></span>
                        <span x-show="busy" x-cloak>Adding…</span>
                    </button>
                </div>

                <button type="submit" name="then" value="checkout"
                        class="od-btn od-btn-ghost w-full mt-3" :disabled="busy || (shade && !shade.order)">
                    Buy it now
                </button>
            </form>

            @auth
                @php $saved = auth()->user()->wishlistItems()->where('product_id', $product->id)->exists(); @endphp
                <form method="post" action="{{ route('account.save', $product) }}" class="mt-3">
                    @csrf
                    <button type="submit" class="text-sm text-ink-muted hover:text-gold-light transition inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="{{ $saved ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M21 8.25c0-2.485-2.1-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                        </svg>
                        {{ $saved ? 'Saved — tap to remove' : 'Save for later' }}
                    </button>
                </form>
            @else
                <p class="mt-3 text-sm text-ink-faint">
                    <a href="{{ route('sign-in') }}" class="text-gold hover:text-gold-light">Sign in</a> to save this for later.
                </p>
            @endauth

            <div class="mt-7 grid gap-3 text-sm text-ink-muted">
                <p class="flex items-center gap-2.5">
                    <span class="text-gold">✦</span>
                    Free delivery over {{ Shop::money(Shop::freeShippingFrom()) }}, posted within {{ Shop::dispatchDays() }} working days.
                </p>
                @if (Shop::codOn())
                    <p class="flex items-center gap-2.5"><span class="text-gold">✦</span> Cash on delivery available.</p>
                @endif
                <p class="flex items-center gap-2.5"><span class="text-gold">✦</span> Unstitched blouse piece included.</p>
            </div>

            @if ($specs->isNotEmpty() || $product->description)
                <div class="mt-9 divide-y divide-[color:var(--color-line-soft)] border-y border-[color:var(--color-line-soft)]">
                    @if ($product->description)
                        <details class="group" open>
                            <summary class="flex items-center justify-between py-4 cursor-pointer list-none">
                                <span class="font-head text-lg">About this saree</span>
                                <span class="text-gold transition group-open:rotate-45">+</span>
                            </summary>
                            <div class="pb-5 prose-sm text-ink-muted leading-relaxed [&_p]:mb-3">{!! $product->description !!}</div>
                        </details>
                    @endif

                    @if ($specs->isNotEmpty())
                        <details class="group">
                            <summary class="flex items-center justify-between py-4 cursor-pointer list-none">
                                <span class="font-head text-lg">The details</span>
                                <span class="text-gold transition group-open:rotate-45">+</span>
                            </summary>
                            <dl class="pb-5 grid grid-cols-[auto_1fr] gap-x-6 gap-y-2.5 text-sm">
                                @foreach ($specs as $heading => $value)
                                    <dt class="text-ink-faint">{{ $heading }}</dt>
                                    <dd class="text-ink-soft">{{ $value }}</dd>
                                @endforeach
                                @if ($product->weight_g)
                                    <dt class="text-ink-faint">Weight</dt>
                                    <dd class="text-ink-soft">{{ $product->weight_g }} g</dd>
                                @endif
                            </dl>
                        </details>
                    @endif

                    <details class="group">
                        <summary class="flex items-center justify-between py-4 cursor-pointer list-none">
                            <span class="font-head text-lg">Delivery &amp; returns</span>
                            <span class="text-gold transition group-open:rotate-45">+</span>
                        </summary>
                        <div class="pb-5 text-sm text-ink-muted leading-relaxed whitespace-pre-line">{{ Shop::policy('returns') ?: 'Seven days from delivery, unworn and with tags.' }}</div>
                    </details>
                </div>
            @endif
        </div>
    </div>

    @if ($product->videos->isNotEmpty())
        <section class="mt-20">
            <h2 class="font-display text-2xl md:text-3xl">See it worn</h2>
            <p class="mt-2 text-ink-muted">Sound is off until you turn it on.</p>

            <div class="mt-7 flex gap-4 overflow-x-auto od-scroll snap-x snap-mandatory pb-4 -mx-4 px-4 md:mx-0 md:px-0">
                @foreach ($product->videos as $film)
                    <div class="snap-start shrink-0 w-[68vw] sm:w-[40vw] lg:w-[24vw] max-w-[320px]">
                        <x-shop.video :video="$film" :eager="$loop->first" />
                        @if ($film->title)
                            <p class="mt-3 text-sm text-ink-soft">{{ $film->title }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($product->matches->isNotEmpty())
        <section class="mt-20">
            <h2 class="font-display text-2xl md:text-3xl">Goes with</h2>
            <div class="mt-7 grid grid-cols-2 md:grid-cols-4 gap-x-5 gap-y-10">
                @foreach ($product->matches as $match)
                    <x-shop.product-card :product="$match" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($reviews->isNotEmpty())
        <section id="reviews" class="mt-20 scroll-mt-28">
            <h2 class="font-display text-2xl md:text-3xl">What people said</h2>
            <div class="mt-7 grid gap-5 md:grid-cols-2">
                @foreach ($reviews as $review)
                    <article class="od-card p-6">
                        <div class="flex items-center justify-between">
                            <span class="text-gold text-sm">{{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', 5 - (int) $review->rating) }}</span>
                            <span class="text-xs text-ink-faint">{{ $review->created_at?->format('F Y') }}</span>
                        </div>
                        @if ($review->title)
                            <h3 class="mt-3 font-head text-lg">{{ $review->title }}</h3>
                        @endif
                        @if ($review->body)
                            <p class="mt-2 text-sm text-ink-muted leading-relaxed">{{ $review->body }}</p>
                        @endif
                        <p class="mt-4 text-xs text-ink-faint">
                            {{ $review->name }}@if ($review->is_verified) · <span class="text-gold-deep">Verified purchase</span>@endif
                        </p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if ($alsoLike->isNotEmpty())
        <section class="mt-20">
            <h2 class="font-display text-2xl md:text-3xl">You may also like</h2>
            <div class="mt-7 grid grid-cols-2 md:grid-cols-4 gap-x-5 gap-y-10">
                @foreach ($alsoLike as $other)
                    <x-shop.product-card :product="$other" />
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
