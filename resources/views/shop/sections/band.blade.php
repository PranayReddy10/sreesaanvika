@php use App\Support\Shop; @endphp

<section class="border-y border-[color:var(--color-line-soft)] bg-[color:var(--color-page-alt)]">
    <div class="od-wrap py-14 md:py-20">
        <div class="max-w-2xl">
            @if ($section?->eyebrow)<p class="od-eyebrow">{{ $section->eyebrow }}</p>@endif
            <h2 class="mt-3 font-display text-3xl md:text-4xl">{{ $section?->heading ?: 'Woven, checked, folded, sent' }}</h2>
            @if ($section?->subheading)
                <p class="mt-4 text-ink-muted leading-relaxed">{{ $section->subheading }}</p>
            @endif
        </div>

        <div class="mt-10 grid gap-8 sm:grid-cols-3">
            @foreach ([
                ['Photographed in daylight', 'What you see is the piece you get — no filters, no borrowed stock photographs.'],
                ['Checked thread by thread', 'Every saree is looked over for a pulled thread before it is folded.'],
                ['Posted in ' . Shop::dispatchDays() . ' working days', 'Tracked all the way, and free over ' . Shop::money(Shop::freeShippingFrom()) . '.'],
            ] as [$title, $text])
                <div>
                    <div class="od-rule mb-5"></div>
                    <h3 class="font-head text-lg">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-ink-muted leading-relaxed">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
