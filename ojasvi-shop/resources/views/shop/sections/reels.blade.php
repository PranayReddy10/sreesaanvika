@php
    $limit = (int) ($section?->setting('limit', 6) ?: 6);

    $reels = \App\Models\Video::visible()
        ->forHome()
        ->with('product.images')
        ->take($limit)
        ->get();
@endphp

@if ($reels->isNotEmpty())
<section class="py-16 md:py-20 border-t border-[color:var(--color-line-soft)]">
    <div class="od-wrap">
        @if ($section?->eyebrow)
            <p class="od-eyebrow">{{ $section->eyebrow }}</p>
        @endif

        <h2 class="mt-2 font-display text-3xl md:text-4xl">
            {{ $section?->heading ?: 'Seen in motion' }}
        </h2>

        <p class="mt-3 text-ink-muted max-w-xl">
            {{ $section?->subheading ?: 'A photograph cannot show how a silk falls. These can.' }}
        </p>
    </div>

    {{-- A rail, because these are portrait films and a grid of them would be
         a wall. Scrolls with a thumb on a phone and a trackpad on a desktop. --}}
    <div class="mt-8 flex gap-4 overflow-x-auto od-scroll snap-x snap-mandatory px-4 md:px-8 pb-4">
        @foreach ($reels as $reel)
            <div class="snap-start shrink-0 w-[68vw] sm:w-[38vw] lg:w-[23vw] max-w-[320px]">
                <x-shop.video :video="$reel" :eager="$loop->first" />

                @if ($reel->product)
                    <a href="{{ route('product', $reel->product) }}"
                       class="mt-3 block font-head text-[1.0625rem] leading-snug hover:text-gold-light transition">
                        {{ $reel->title ?: $reel->product->name }}
                    </a>
                    <p class="text-sm text-ink-muted mt-0.5">
                        {{ \App\Support\Shop::money($reel->product->priceFor()) }}
                    </p>
                @elseif ($reel->title)
                    <p class="mt-3 font-head text-[1.0625rem]">{{ $reel->title }}</p>
                @endif
            </div>
        @endforeach
    </div>
</section>
@endif
