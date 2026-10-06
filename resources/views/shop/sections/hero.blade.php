@php $slides = $section->slides; @endphp

@if ($slides->isNotEmpty())
{{--
    The front page's slides.

    They changed themselves every six seconds and could be changed by pressing
    one of the bars underneath, and that was all: a shopper who did what anyone
    does with a picture on a phone — pushed it sideways — found it did not move.

    Pointer events rather than touch ones, so the same few lines cover a finger
    and a mouse drag. Vertical wins ties: the commonest gesture over a picture
    that fills the screen is scrolling past it, and a slider that steals that
    is worse than one that does not move at all.
--}}
<section
    x-data="{
        i: 0,
        n: {{ $slides->count() }},
        timer: null,
        fromX: null,
        fromY: null,
        go(to) { this.i = (to + this.n) % this.n; this.restart(); },
        restart() {
            clearInterval(this.timer);
            if (this.n > 1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.timer = setInterval(() => this.i = (this.i + 1) % this.n, 6500);
            }
        },
        grab(e) {
            if (this.n < 2) return;
            this.fromX = e.clientX;
            this.fromY = e.clientY;
            clearInterval(this.timer);
        },
        drop(e) {
            if (this.fromX === null) return;

            const x = e.clientX - this.fromX;
            const y = e.clientY - this.fromY;

            this.fromX = this.fromY = null;

            // Forty-five pixels, and more sideways than up: below that it is a
            // tap, and a tap on a slide is meant for the button on it.
            if (Math.abs(x) > 45 && Math.abs(x) > Math.abs(y)) {
                this.go(this.i + (x < 0 ? 1 : -1));

                return;
            }

            this.restart();
        },
    }"
    x-init="restart()"
    @mouseenter="clearInterval(timer)"
    @mouseleave="restart()"
    @pointerdown="grab($event)"
    @pointerup="drop($event)"
    @pointercancel="fromX = fromY = null; restart()"
    @keydown.window.arrow-left="go(i - 1)"
    @keydown.window.arrow-right="go(i + 1)"
    class="relative select-none"
    style="touch-action: pan-y"
>
    {{--
        Every slide in the same grid cell, rather than the first one in the
        flow of the page and the rest laid over it absolutely.

        That was the old arrangement, and it meant only the first slide held
        the section open: show the second and the first went display:none,
        the section collapsed to nothing, the arrows jumped up under the
        header and the sarees below rode up through the photograph. Stacked
        in one cell, whichever slide is showing gives the section its height,
        and they are all the same height anyway.
    --}}
    <div class="grid">
    @foreach ($slides as $k => $slide)
        <div x-show="i === {{ $k }}" x-transition.opacity.duration.600ms
             style="grid-area: 1 / 1">
            <div class="relative h-[72vh] min-h-[460px] md:h-[82vh]">
                @if ($slide->image)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($slide->image) }}"
                         alt="{{ $slide->heading }}"
                         class="absolute inset-0 w-full h-full object-cover"
                         loading="{{ $k === 0 ? 'eager' : 'lazy' }}"
                         fetchpriority="{{ $k === 0 ? 'high' : 'auto' }}">
                @endif

                {{-- The words sit on the photograph, so the photograph has to
                     be washed out under them or nothing is legible — and on a
                     cream page the wash is cream, lightening the silk rather
                     than darkening it. Strong where the words are, because a
                     deep indigo Kanjivaram is exactly the kind of photograph
                     that swallows dark lettering. --}}
                <div class="absolute inset-0 bg-gradient-to-t from-[color:var(--color-page)] via-[color:var(--color-page)]/75 to-[color:var(--color-page)]/25"></div>

                <div class="relative h-full od-wrap flex items-end md:items-center pb-16 md:pb-0">
                    <div class="max-w-xl
                        @if ($slide->align === 'centre') mx-auto text-center
                        @elseif ($slide->align === 'right') ml-auto text-right @endif">
                        @if ($slide->eyebrow)
                            <p class="od-eyebrow">{{ $slide->eyebrow }}</p>
                        @endif

                        @if ($slide->heading)
                            <h1 class="mt-3 font-display text-4xl md:text-6xl leading-[1.08]">{{ $slide->heading }}</h1>
                        @endif

                        @if ($slide->text)
                            <p class="mt-4 text-ink-soft leading-relaxed md:text-lg">{{ $slide->text }}</p>
                        @endif

                        @if ($slide->button_label)
                            <a href="{{ $slide->button_url ?: route('shop') }}" class="od-btn od-btn-gold mt-7">
                                {{ $slide->button_label }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    </div>

    @if ($slides->count() > 1)
        {{-- And something to press on a wide screen, where there is nothing to
             push and no reason to guess that the bars below are buttons. --}}
        <button type="button" @click="go(i - 1)" aria-label="The slide before"
                class="hidden md:grid place-items-center absolute left-4 top-1/2 -translate-y-1/2 w-11 h-11
                       rounded-full bg-[color:var(--color-page)]/70 border border-[color:var(--color-line)]
                       text-ink hover:bg-[color:var(--color-page)] transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
            </svg>
        </button>

        <button type="button" @click="go(i + 1)" aria-label="The next slide"
                class="hidden md:grid place-items-center absolute right-4 top-1/2 -translate-y-1/2 w-11 h-11
                       rounded-full bg-[color:var(--color-page)]/70 border border-[color:var(--color-line)]
                       text-ink hover:bg-[color:var(--color-page)] transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
            </svg>
        </button>

        <div class="absolute bottom-6 left-0 right-0 od-wrap flex items-center gap-2">
            @foreach ($slides as $k => $slide)
                <button type="button" @click="go({{ $k }})"
                        class="h-0.5 flex-1 max-w-16 transition"
                        :class="i === {{ $k }} ? 'bg-gold' : 'bg-[color:var(--color-line-soft)]'"
                        aria-label="Show slide {{ $k + 1 }}"></button>
            @endforeach
        </div>
    @endif
</section>
@endif
