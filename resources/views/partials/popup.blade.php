@php $popup = \App\Support\Shop::popup(); @endphp

@if ($popup['on'])
    {{--
        One message, over the front of the shop.

        Three rules, all of them about not being the thing people close without
        reading. It waits until she has seen the shop. It remembers being
        closed, in her own browser, for as long as the shop says. And it can be
        got out of with Escape, the cross, or the page behind it — because the
        commonest reason a popup is hated is that it is hard to leave.

        Kept in localStorage rather than a cookie: nothing about it needs to
        reach the server, and a cookie would be sent with every request for
        every photograph.
    --}}
    <div
        x-data="{
            show: false,
            key: 'od-popup-{{ substr(md5($popup['heading'].$popup['text']), 0, 8) }}',
            init() {
                let closed = 0;

                try { closed = Number(localStorage.getItem(this.key)) || 0; } catch (e) {}

                if (Date.now() - closed < {{ $popup['again'] }} * 86400000) return;

                setTimeout(() => this.show = true, {{ $popup['after'] }} * 1000);
            },
            close() {
                this.show = false;

                try { localStorage.setItem(this.key, String(Date.now())); } catch (e) {}
            },
        }"
        x-show="show"
        x-cloak
        x-transition.opacity.duration.300ms
        @keydown.escape.window="close()"
        class="fixed inset-0 z-[70] grid place-items-center p-4"
        role="dialog"
        aria-modal="true"
        aria-label="{{ $popup['heading'] ?: 'A message from the shop' }}"
    >
        <div class="absolute inset-0 bg-[color:var(--color-ink)]/45" @click="close()"></div>

        <div class="od-card relative w-full max-w-lg overflow-hidden grid {{ $popup['image'] ? 'sm:grid-cols-2' : '' }}"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0">
            @if ($popup['image'])
                <img src="{{ $popup['image'] }}" alt="" class="h-40 sm:h-full w-full object-cover" loading="lazy" decoding="async">
            @endif

            <div class="p-6 sm:p-7">
                @if ($popup['heading'])
                    <h2 class="font-display text-2xl leading-tight">{{ $popup['heading'] }}</h2>
                @endif

                @if ($popup['text'])
                    <p class="mt-3 text-sm text-ink-muted leading-relaxed">{{ $popup['text'] }}</p>
                @endif

                @if ($popup['ask'])
                    <form method="post" action="{{ route('newsletter') }}" class="mt-5">
                        @csrf

                        {{-- A field no human sees and no human fills in. --}}
                        <div style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden" aria-hidden="true">
                            <label for="popup-website">Leave this empty</label>
                            <input type="text" id="popup-website" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="flex gap-2">
                            <label for="popup-email" class="sr-only">Your email</label>
                            <input id="popup-email" name="email" type="email" required
                                   placeholder="you@example.in" class="od-input">
                            <button type="submit" class="od-btn od-btn-gold px-5 shrink-0">
                                {{ $popup['label'] ?: 'Join' }}
                            </button>
                        </div>

                        <p class="mt-2 text-xs text-ink-faint">
                            We never pass your address on. See our
                            <a href="{{ route('page', 'privacy') }}" class="text-gold hover:text-gold-light">privacy notice</a>.
                        </p>
                    </form>
                @elseif ($popup['label'] && $popup['url'])
                    <a href="{{ $popup['url'] }}" class="od-btn od-btn-gold mt-6" @click="close()">
                        {{ $popup['label'] }}
                    </a>
                @endif
            </div>

            <button type="button" @click="close()"
                    class="absolute top-3 right-3 w-8 h-8 grid place-items-center rounded-full
                           bg-[color:var(--color-page)]/80 text-ink-muted hover:text-ink transition"
                    aria-label="Close">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
@endif
