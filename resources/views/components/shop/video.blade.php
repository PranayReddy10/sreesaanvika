@props([
    'video',
    'ratio' => '9 / 16',
    'eager' => false,
])

@php
    $src = $video->src();
    $embed = $video->embedUrl();
    $poster = $video->posterUrl();
@endphp

@if ($src)
    {{--
        A film the shop holds, played by the shop.

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
        {{--
            Behind the film, always. A <video> with nothing decoded yet paints
            flat black — there is no way to style that away — so an uploaded
            film with no cover frame, or one still arriving over a slow line,
            or one the browser cannot decode, is a black rectangle in the
            middle of the page. This is what is underneath instead, and the
            film is only faded in once there is actually a frame to show.
        --}}
        @if ($poster)
            <img src="{{ $poster }}" alt="" aria-hidden="true"
                 class="od-video-rest absolute inset-0 w-full h-full object-cover"
                 loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async">
        @else
            <div class="od-video-rest absolute inset-0 grid place-items-center
                        bg-gradient-to-br from-[color:var(--color-surface-2)] to-[color:var(--color-surface-3)]">
                <span class="od-eyebrow text-gold-light/50">OJASVI</span>
            </div>
        @endif

        <video
            class="od-video-film relative w-full h-full object-cover"
            muted
            loop
            playsinline
            disablepictureinpicture
            preload="{{ $eager ? 'metadata' : 'none' }}"
            aria-label="{{ $video->label() }}"
        >
            <source src="{{ $src }}" type="{{ $video->mime() }}">
        </video>

        {{-- Shown until it is actually playing, so a film that never starts —
             a slow line, a browser that said no — still looks deliberate. Taken
             away altogether if the film turns out not to be playable at all: a
             play button that does nothing when pressed is worse than none. --}}
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

@elseif ($embed)
    {{--
        A reel that Instagram plays.

        Instagram does not let a website start one by itself, so there is no
        pretending otherwise — this is a still with a play badge, and one tap
        hands over to Instagram's own player.

        Nothing of Instagram's is fetched until that tap. Their embed brings
        its own scripts and its own cookies, and loading four of them as the
        front page opens would undo what the shop promises about not calling
        on anybody else. It also costs a shopper on a slow line more than the
        whole rest of the page.
    --}}
    <figure
        class="od-reel group relative overflow-hidden rounded-[var(--radius-card)] bg-[color:var(--color-surface-2)]"
        style="aspect-ratio: {{ $ratio }}"
        data-od-reel
        data-embed="{{ $embed }}"
        data-label="{{ $video->label() }}"
    >
        @if ($poster)
            <img src="{{ $poster }}" alt="{{ $video->label() }}"
                 class="od-reel-poster w-full h-full object-cover"
                 loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async">
        @else
            <div class="od-reel-poster w-full h-full grid place-items-center
                        bg-gradient-to-br from-[color:var(--color-surface-2)] to-[color:var(--color-surface-3)]">
                <span class="od-eyebrow">On Instagram</span>
            </div>
        @endif

        <button type="button"
                class="od-reel-play absolute inset-0 grid place-items-center bg-[color:var(--color-page)]/35
                       transition hover:bg-[color:var(--color-page)]/20"
                aria-label="Play {{ $video->label() }} on Instagram">
            <span class="w-14 h-14 rounded-full bg-[color:var(--color-page)]/75 border border-[color:var(--color-line)]
                         grid place-items-center text-gold-light">
                <svg class="w-6 h-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </span>
        </button>

        {{-- Said plainly, so a tap is never a surprise: this one goes to
             Instagram, and Instagram will know she watched it. --}}
        <span class="od-reel-mark pointer-events-none absolute top-3 left-3 flex items-center gap-1.5
                     rounded-full bg-[color:var(--color-page)]/75 border border-[color:var(--color-line)]
                     px-2.5 py-1 text-[0.65rem] uppercase tracking-[0.12em] text-ink-soft">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <rect x="3" y="3" width="18" height="18" rx="5"/>
                <circle cx="12" cy="12" r="4"/>
                <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>
            </svg>
            Instagram
        </span>

        @if ($video->caption)
            <figcaption class="od-reel-caption pointer-events-none absolute bottom-0 inset-x-0 p-4
                               bg-gradient-to-t from-[color:var(--color-page)] to-transparent
                               text-sm text-ink-soft">
                {{ $video->caption }}
            </figcaption>
        @endif

        <noscript>
            <a href="{{ $video->watchUrl() }}" target="_blank" rel="noopener"
               class="absolute inset-0 grid place-items-end p-4 text-sm text-gold-light">
                Watch on Instagram
            </a>
        </noscript>
    </figure>
@endif
