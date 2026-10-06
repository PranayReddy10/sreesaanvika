@php
    $bar = \App\Support\Shop::bar();
    $bag = app(\App\Services\CartService::class);
@endphp

@if ($bar['on'] && $bar['text'] !== '')
    <div class="bg-[color:var(--color-maroon)] text-ink text-center text-[0.8125rem] tracking-wide">
        <div class="od-wrap py-2">
            @if ($bar['url'])
                <a href="{{ $bar['url'] }}" class="hover:text-gold-light transition">{{ $bar['text'] }}</a>
            @else
                {{ $bar['text'] }}
            @endif
        </div>
    </div>
@endif

<header
    x-data="{ menu: false, search: false }"
    class="sticky top-0 z-40 border-b border-[color:var(--color-line-soft)] bg-[color:var(--color-page)]/95 backdrop-blur"
>
    <div class="od-wrap flex items-center gap-4 py-4">
        {{-- On a phone the bag has to be reachable with a thumb, so the menu
             button sits left and the bag right, with the logo between. --}}
        <button
            type="button"
            class="md:hidden -ml-2 p-2 text-ink-soft"
            @click="menu = true"
            aria-label="Open the menu"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
            </svg>
        </button>

        <a href="{{ route('home') }}" class="shrink-0 md:mr-6" aria-label="{{ \App\Support\Shop::name() }}, home">
            <img src="{{ \App\Support\Shop::logo() }}" alt="{{ \App\Support\Shop::name() }}"
                 class="h-8 md:h-10 w-auto" width="280" height="70">
        </a>

        <nav class="hidden md:flex items-center gap-7 text-[0.8125rem] tracking-[0.14em] uppercase text-ink-soft">
            <a href="{{ route('shop') }}" class="hover:text-gold-light transition">All sarees</a>
            <a href="{{ route('shop', ['sort' => 'new']) }}" class="hover:text-gold-light transition">New in</a>
            <a href="{{ route('shop', ['on' => 'offer']) }}" class="hover:text-gold-light transition">Offers</a>
            <a href="{{ route('page', 'story') }}" class="hover:text-gold-light transition">Our story</a>
            <a href="{{ route('page', 'contact') }}" class="hover:text-gold-light transition">Contact</a>
        </nav>

        <div class="ml-auto flex items-center gap-1 md:gap-2">
            <button type="button" class="p-2 text-ink-soft hover:text-gold-light transition"
                    @click="search = true; $nextTick(() => $refs.q && $refs.q.focus())" aria-label="Search">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/>
                </svg>
            </button>

            <a href="{{ route('account.wishlist') }}" class="hidden sm:block p-2 text-ink-soft hover:text-gold-light transition"
               aria-label="Saved sarees">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M21 8.25c0-2.485-2.1-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                </svg>
            </a>

            {{-- In words on a wide screen: an outline of a head is not a
                 sign-in button to most people, and a shopper who cannot find
                 the way in orders as a guest or does not order at all. --}}
            <a href="{{ auth()->check() ? route('account') : route('sign-in') }}"
               class="hidden md:flex items-center gap-2 px-3 py-2 text-ink-soft hover:text-gold-light transition
                      text-[0.75rem] tracking-[0.12em] uppercase"
               aria-label="{{ auth()->check() ? 'Your account' : 'Sign in' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0"/>
                </svg>
                <span>{{ auth()->check() ? \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') : 'Sign in' }}</span>
            </a>

            <a href="{{ auth()->check() ? route('account') : route('sign-in') }}"
               class="sm:block md:hidden p-2 text-ink-soft hover:text-gold-light transition"
               aria-label="{{ auth()->check() ? 'Your account' : 'Sign in' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0"/>
                </svg>
            </a>

            <a href="{{ route('bag') }}" class="relative p-2 text-ink hover:text-gold-light transition" aria-label="Your bag">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12A1.125 1.125 0 0 1 19.75 21.75H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/>
                </svg>
                <span
                    x-data="{ n: {{ (int) $bag->count() }} }"
                    x-on:bag-changed.window="n = $event.detail.count"
                    x-show="n > 0"
                    x-cloak
                    x-text="n"
                    class="absolute -top-0.5 -right-0.5 min-w-[1.15rem] h-[1.15rem] grid place-items-center rounded-full
                           bg-gold text-[0.6875rem] font-semibold text-[color:var(--color-page)] px-1"
                >{{ $bag->count() }}</span>
            </a>
        </div>
    </div>

    {{-- Search, as a sheet rather than a page, so a shopper who was halfway
         down a listing is not thrown back to the top of it.

         Moved to the end of the body, because the header it is written inside
         is blurred — and backdrop-filter makes an element the containing block
         for everything fixed inside it. Left where it was written, "fixed
         inset-0" means the inside of the header bar rather than the screen,
         and the sheet opens as a strip 72 pixels tall. --}}
    <template x-teleport="body">
    <div x-show="search" x-cloak x-transition.opacity
         class="fixed inset-0 z-50 bg-[color:var(--color-page)]/95 backdrop-blur"
         @keydown.escape.window="search = false">
        <div class="od-wrap pt-24">
            <form action="{{ route('shop') }}" method="get" class="flex items-center gap-3 border-b border-[color:var(--color-line)] pb-4">
                <svg class="w-6 h-6 text-gold shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/>
                </svg>
                <input x-ref="q" name="q" type="search" value="{{ request('q') }}"
                       placeholder="Kanjivaram, indigo, under 10,000…"
                       class="w-full bg-transparent text-xl md:text-3xl font-head text-ink placeholder:text-ink-faint focus:outline-none py-2">
                <button type="button" @click="search = false" class="p-2 text-ink-muted hover:text-ink" aria-label="Close search">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </form>
            <p class="mt-4 text-sm text-ink-muted">Press enter to search {{ \App\Support\Shop::name() }}.</p>
        </div>
    </div>
    </template>

    {{-- The phone menu, moved to the end of the body for the same reason as
         the search sheet above: inside the blurred header it was a 72-pixel
         sliver with none of this in it. --}}
    <template x-teleport="body">
    <div x-show="menu" x-cloak x-transition.opacity class="fixed inset-0 z-50 md:hidden" @keydown.escape.window="menu = false">
        <div class="absolute inset-0 bg-black/60" @click="menu = false"></div>
        <nav class="absolute inset-y-0 left-0 w-[82%] max-w-sm bg-[color:var(--color-page-alt)] border-r border-[color:var(--color-line-soft)] p-6 overflow-y-auto od-scroll"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0">
            <div class="flex items-center justify-between mb-8">
                <img src="{{ \App\Support\Shop::logo() }}" alt="" class="h-8 w-auto">
                <button type="button" @click="menu = false" class="p-2 text-ink-muted" aria-label="Close the menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <ul class="space-y-1 text-lg font-head">
                <li><a href="{{ route('shop') }}" class="block py-3 border-b border-[color:var(--color-line-soft)]">All sarees</a></li>
                <li><a href="{{ route('shop', ['sort' => 'new']) }}" class="block py-3 border-b border-[color:var(--color-line-soft)]">New in</a></li>
                <li><a href="{{ route('shop', ['on' => 'offer']) }}" class="block py-3 border-b border-[color:var(--color-line-soft)]">Offers</a></li>
                <li><a href="{{ route('account.wishlist') }}" class="block py-3 border-b border-[color:var(--color-line-soft)]">Saved sarees</a></li>
                <li><a href="{{ auth()->check() ? route('account') : route('sign-in') }}" class="block py-3 border-b border-[color:var(--color-line-soft)]">{{ auth()->check() ? 'Your account' : 'Sign in' }}</a></li>
                <li><a href="{{ route('page', 'story') }}" class="block py-3 border-b border-[color:var(--color-line-soft)]">Our story</a></li>
                <li><a href="{{ route('page', 'contact') }}" class="block py-3">Contact</a></li>
            </ul>

            @if (\App\Support\Shop::whatsapp())
                <a href="https://wa.me/{{ \App\Support\Shop::whatsapp() }}" class="od-btn od-btn-ghost w-full mt-8">
                    Ask us on WhatsApp
                </a>
            @endif
        </nav>
    </div>
    </template>
</header>
