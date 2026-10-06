@php
    $limit = (int) ($section?->setting('limit', 12) ?: 12);
    $items = $pool->take($limit);
@endphp

@if ($items->isNotEmpty())
<section class="od-wrap py-16 md:py-20 border-t border-[color:var(--color-line-soft)]">
    <header class="flex flex-wrap items-end justify-between gap-4 mb-10">
        <div class="max-w-xl">
            @if ($section?->eyebrow)
                <p class="od-eyebrow">{{ $section->eyebrow }}</p>
            @endif
            <h2 class="mt-2 font-display text-3xl md:text-4xl">
                {{ $section?->heading ?: 'Every saree we have' }}
            </h2>
            @if ($section?->subheading)
                <p class="mt-3 text-ink-muted">{{ $section->subheading }}</p>
            @endif
        </div>

        <a href="{{ route('shop') }}" class="text-[0.8125rem] uppercase tracking-[0.14em] text-gold hover:text-gold-light transition">
            See all →
        </a>
    </header>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-5 gap-y-10">
        @foreach ($items as $product)
            <x-shop.product-card :product="$product" />
        @endforeach
    </div>
</section>
@endif
