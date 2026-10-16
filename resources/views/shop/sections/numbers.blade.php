@php
    use App\Support\Shop;

    /*
     * The figures the shop keeps by hand: sarees sold across the counter as
     * well as on here, and the reviews people have written. Nothing is counted
     * at this end — a number the shop cannot correct is worse than no number,
     * because the shop is the only one who knows what the real total is.
     */
    $numbers = Shop::numbers();
@endphp

@if ($numbers !== [])
<section class="border-y border-[color:var(--color-line-soft)] bg-[color:var(--color-page-alt)]">
    <div class="od-wrap py-12 md:py-16">
        @if ($section?->eyebrow || $section?->heading || $section?->subheading)
            <div class="max-w-2xl">
                @if ($section?->eyebrow)<p class="od-eyebrow">{{ $section->eyebrow }}</p>@endif
                @if ($section?->heading)
                    <h2 class="mt-3 font-display text-3xl md:text-4xl">{{ $section->heading }}</h2>
                @endif
                @if ($section?->subheading)
                    <p class="mt-4 text-ink-muted leading-relaxed">{{ $section->subheading }}</p>
                @endif
            </div>
        @endif

        {{-- One figure fills the row on its own; two sit side by side, and on
             a phone they stack rather than shrink to nothing. --}}
        <div class="mt-9 grid gap-8 sm:gap-12 {{ count($numbers) > 1 ? 'sm:grid-cols-2' : '' }}">
            @foreach ($numbers as $number)
                <div>
                    <div class="od-rule mb-5"></div>
                    <p class="font-display text-4xl md:text-5xl leading-none">{{ $number['value'] }}</p>
                    <p class="mt-3 text-sm uppercase tracking-[0.16em] text-ink-muted">{{ $number['label'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
