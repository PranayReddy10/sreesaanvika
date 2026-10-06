@php use App\Support\Shop; @endphp

<section class="mt-24 border-t border-[color:var(--color-line-soft)] bg-[color:var(--color-page-alt)]">
    <div class="od-wrap py-12 md:py-16 grid gap-6 md:grid-cols-2 md:items-center">
        <div>
            <p class="od-eyebrow">Keep in touch</p>
            <h2 class="mt-3 font-display text-2xl md:text-3xl">When something worth seeing comes off the loom</h2>
            <p class="mt-3 text-sm text-ink-muted leading-relaxed max-w-md">
                A few pieces at a time, once or twice a month. No daily nonsense, and one click to stop.
            </p>
        </div>

        <form method="post" action="{{ route('newsletter') }}" class="md:justify-self-end w-full max-w-md">
            @csrf
            {{-- Seen by nobody with eyes; filled in by most of what is not a
                 person. Cheaper and quieter than a puzzle. Positioned with an
                 inline style rather than a utility class, because this must be
                 off the page whether or not the stylesheet was rebuilt. --}}
            <div style="position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;overflow:hidden"
                 aria-hidden="true">
                <label for="website">Leave this empty</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <label for="newsletter-email" class="od-label">Your email</label>
            <div class="flex gap-2">
                <input id="newsletter-email" name="email" type="email" required
                       placeholder="you@example.in" class="od-input">
                <button type="submit" class="od-btn od-btn-gold px-6 shrink-0">Join</button>
            </div>

            @error('email')
                <p class="mt-2 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>
            @enderror

            <p class="mt-2 text-xs text-ink-faint">
                We never pass your address on. See our
                <a href="{{ route('page', 'privacy') }}" class="text-gold hover:text-gold-light">privacy notice</a>.
            </p>
        </form>
    </div>
</section>

<footer class="border-t border-[color:var(--color-line-soft)] bg-[color:var(--color-page-alt)]">
    <div class="od-wrap py-14 grid gap-10 md:grid-cols-4">
        <div class="md:col-span-1">
            <img src="{{ Shop::logo() }}" alt="{{ Shop::name() }}" class="h-9 w-auto" width="280" height="70">
            <p class="mt-4 text-sm text-ink-muted leading-relaxed max-w-xs">{{ Shop::tagline() }}</p>

            @if (Shop::social())
                <div class="mt-5 flex gap-3">
                    @foreach (Shop::social() as $label => $url)
                        <a href="{{ $url }}" rel="noopener" target="_blank"
                           class="text-xs uppercase tracking-[0.14em] text-ink-muted hover:text-gold-light transition">{{ $label }}</a>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <h2 class="od-eyebrow mb-4">Shop</h2>
            <ul class="space-y-2.5 text-sm text-ink-soft">
                <li><a href="{{ route('shop') }}" class="hover:text-gold-light transition">All sarees</a></li>
                <li><a href="{{ route('shop', ['sort' => 'new']) }}" class="hover:text-gold-light transition">New in</a></li>
                <li><a href="{{ route('shop', ['on' => 'offer']) }}" class="hover:text-gold-light transition">On offer</a></li>
                <li><a href="{{ route('account.wishlist') }}" class="hover:text-gold-light transition">Saved sarees</a></li>
            </ul>
        </div>

        <div>
            <h2 class="od-eyebrow mb-4">Help</h2>
            <ul class="space-y-2.5 text-sm text-ink-soft">
                {{-- Whatever the shop has ticked for the footer, in its own
                     order — so a page written next month appears here without
                     anyone editing this file. --}}
                @foreach (\App\Models\Page::inFooter()->get() as $page)
                    <li><a href="{{ route('page', $page->slug) }}" class="hover:text-gold-light transition">{{ $page->title }}</a></li>
                @endforeach
                <li><a href="{{ route('track') }}" class="hover:text-gold-light transition">Track an order</a></li>
            </ul>
        </div>

        <div>
            <h2 class="od-eyebrow mb-4">Reach us</h2>
            <ul class="space-y-2.5 text-sm text-ink-soft">
                @if (Shop::phone())
                    <li><a href="tel:{{ preg_replace('/\s/', '', Shop::phone()) }}" class="hover:text-gold-light transition">{{ Shop::phone() }}</a></li>
                @endif
                @if (Shop::email())
                    <li><a href="mailto:{{ Shop::email() }}" class="hover:text-gold-light transition">{{ Shop::email() }}</a></li>
                @endif
                @if (Shop::address())
                    <li class="text-ink-muted whitespace-pre-line leading-relaxed">{{ Shop::address() }}</li>
                @endif
            </ul>

            @if (Shop::whatsapp())
                <a href="https://wa.me/{{ Shop::whatsapp() }}" target="_blank" rel="noopener"
                   class="od-btn od-btn-ghost mt-5 text-[0.7rem] py-2.5 px-5">WhatsApp us</a>
            @endif
        </div>
    </div>

    <div class="od-wrap border-t border-[color:var(--color-line-soft)] py-6 flex flex-col sm:flex-row gap-3 items-center justify-between text-xs text-ink-faint">
        <p>&copy; {{ now()->year }} {{ Shop::name() }}. All rights reserved.</p>
        <nav class="flex gap-5">
            <a href="{{ route('page', 'terms') }}" class="hover:text-ink-muted transition">Terms</a>
            <a href="{{ route('page', 'privacy') }}" class="hover:text-ink-muted transition">Privacy</a>
        </nav>
    </div>
</footer>
