@php
    $limit = (int) ($section?->setting('limit', 6) ?: 6);
    $items = $pool->shuffle()->take($limit);
@endphp

@if ($items->isNotEmpty())
<section class="py-16 md:py-20">
    <div class="od-wrap mb-8">
        @if ($section?->eyebrow)<p class="od-eyebrow">{{ $section->eyebrow }}</p>@endif
        <h2 class="mt-2 font-display text-3xl md:text-4xl">{{ $section?->heading ?: 'Seen on the loom' }}</h2>
    </div>

    {{-- A rail rather than a grid, because a lookbook is for looking along. --}}
    <div class="flex gap-4 overflow-x-auto od-scroll snap-x snap-mandatory px-4 md:px-8 pb-4">
        @foreach ($items as $product)
            @php $image = $product->firstImage(); @endphp
            <a href="{{ route('product', $product) }}"
               class="snap-start shrink-0 w-[72vw] sm:w-[42vw] lg:w-[28vw] group">
                @if ($image)
                    <img src="{{ $image->url }}" alt="{{ $product->name }}"
                         class="od-shot rounded-[var(--radius-card)] transition duration-700 group-hover:opacity-90"
                         loading="lazy" decoding="async" width="600" height="800">
                @endif
                <p class="mt-3 font-head">{{ $product->name }}</p>
            </a>
        @endforeach
    </div>
</section>
@endif
