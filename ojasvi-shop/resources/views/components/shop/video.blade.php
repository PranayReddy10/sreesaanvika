@props([
    'video',
    'ratio' => '9 / 16',
    'eager' => false,
])

@php
    $src = $video->src();
    $poster = $video->posterUrl();
@endphp

@if ($src)
    {{--
        Autoplay, honestly.

        Muted, because every browser refuses to start a film with sound and a
        shop whose videos silently do not play is worse off than one with none.
        Playing only while on screen, because a saree shop's customer is on a
        phone paying for her data by the gigabyte, and four films loading at
        once on the front page is a bill she did not agree to. Not at all for
        somebody who has asked their machine to stop moving things.

        The sound button is the point: she turns it on when she wants it, and
        the browser allows that because she asked.
    --}}
    <figure
        class="od-video group relative overflow-hidden rounded-[var(--radius-card)] bg-[color:var(--color-surface-2)]"
        style="aspect-ratio: {{ $ratio }}"
        data-od-video
    >
        <video
            class="w-full h-full object-cover"
            muted
            loop
            playsinline
            disablepictureinpicture
            preload="{{ $eager ? 'metadata' : 'none' }}"
            @if ($poster) poster="{{ $poster }}" @endif
            aria-label="{{ $video->label() }}"
        >
            <source src="{{ $src }}" type="{{ $video->mime() }}">
        </video>

        {{-- Shown until it is actually playing, so a film that never starts —
             a slow line, a browser that said no — still looks deliberate. --}}
        <button type="button"
                class="od-video-play absolute inset-0 grid place-items-center bg-[color:var(--color-page)]/35 transition"
                aria-label="Play {{ $video->label() }}">
            <span class="w-14 h-14 rounded-full bg-[color:var(--color-page)]/70 border border-[color:var(--color-line)]
                         grid place-items-center text-gold-light">
                <svg class="w-6 h-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </span>
        </button>

        <button type="button"
                class="od-video-sound absolute z-10 bottom-3 right-3 w-9 h-9 rounded-full
                       bg-[color:var(--color-page)]/70 border border-[color:var(--color-line)]
                       grid place-items-center text-ink-soft hover:text-gold-light transition"
                aria-label="Turn the sound on" aria-pressed="false">
            <svg class="od-video-muted w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.75 19.5 12m0 0 2.25 2.25M19.5 12l2.25-2.25M19.5 12l-2.25 2.25M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.5a1.5 1.5 0 0 1-1.5-1.5v-4.5a1.5 1.5 0 0 1 1.5-1.5h2.25Z"/>
            </svg>
            <svg class="od-video-loud w-4 h-4 hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.5a1.5 1.5 0 0 1-1.5-1.5v-4.5a1.5 1.5 0 0 1 1.5-1.5h2.25Z"/>
            </svg>
        </button>

        @if ($video->caption)
            {{-- pointer-events-none: the caption lies across the bottom of the
                 film, directly over the sound button, and without this it
                 swallows every press of it. --}}
            <figcaption class="pointer-events-none absolute bottom-0 inset-x-0 p-4 pr-16
                               bg-gradient-to-t from-[color:var(--color-page)] to-transparent
                               text-sm text-ink-soft">
                {{ $video->caption }}
            </figcaption>
        @endif
    </figure>
@endif
