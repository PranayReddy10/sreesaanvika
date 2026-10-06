@php $slides = $section->slides; @endphp

@if ($slides->isNotEmpty())
<section
    x-data="{
        i: 0,
        n: {{ $slides->count() }},
        timer: null,
        go(to) { this.i = (to + this.n) % this.n; this.restart(); },
        restart() {
            clearInterval(this.timer);
            if (this.n > 1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.timer = setInterval(() => this.i = (this.i + 1) % this.n, 6500);
            }
        },
    }"
    x-init="restart()"
    @mouseenter="clearInterval(timer)"
    @mouseleave="restart()"
    class="relative"
>
    @foreach ($slides as $k => $slide)
        <div x-show="i === {{ $k }}" x-transition.opacity.duration.600ms
             class="{{ $k === 0 ? '' : 'absolute inset-0' }}">
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

    @if ($slides->count() > 1)
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
