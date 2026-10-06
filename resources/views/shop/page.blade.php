@extends('layouts.shop')

@php
    use App\Support\Seo;
    use App\Support\Shop;
@endphp

@section('title', $heading . ' — ' . Seo::titleSuffix())
@section('description', $blurb ?: Shop::tagline())

@push('head')
    <script type="application/ld+json">{!! Seo::json(Seo::breadcrumbs([
        ['name' => 'Home', 'url' => route('home')],
        ['name' => $heading, 'url' => route('page', $slug)],
    ])) !!}</script>
@endpush

@section('content')
<div class="od-wrap py-14 md:py-20 max-w-3xl">
    <nav class="text-xs text-ink-faint mb-6 flex items-center gap-2">
        <a href="{{ route('home') }}" class="hover:text-ink-muted">Home</a>
        <span>/</span>
        <span class="text-ink-muted">{{ $heading }}</span>
    </nav>

    <h1 class="font-display text-4xl md:text-5xl">{{ $heading }}</h1>

    @if ($blurb)
        <p class="mt-3 text-ink-muted">{{ $blurb }}</p>
    @endif

    @if ($slug === 'contact')
        <div class="mt-9 grid gap-5 sm:grid-cols-2">
            @if (Shop::phone())
                <div class="od-card p-6">
                    <p class="od-eyebrow">Telephone</p>
                    <a href="tel:{{ preg_replace('/\s/', '', Shop::phone()) }}"
                       class="mt-2 block text-lg hover:text-gold-light transition">{{ Shop::phone() }}</a>
                    <p class="mt-2 text-sm text-ink-faint">Ten to seven, Monday to Saturday.</p>
                </div>
            @endif

            @if (Shop::whatsapp())
                <div class="od-card p-6">
                    <p class="od-eyebrow">WhatsApp</p>
                    <a href="https://wa.me/{{ Shop::whatsapp() }}" target="_blank" rel="noopener"
                       class="mt-2 block text-lg hover:text-gold-light transition">Send us a message</a>
                    <p class="mt-2 text-sm text-ink-faint">The quickest way to reach us.</p>
                </div>
            @endif

            @if (Shop::email())
                <div class="od-card p-6">
                    <p class="od-eyebrow">Email</p>
                    <a href="mailto:{{ Shop::email() }}"
                       class="mt-2 block text-lg break-all hover:text-gold-light transition">{{ Shop::email() }}</a>
                    <p class="mt-2 text-sm text-ink-faint">Answered within two working days.</p>
                </div>
            @endif

            @if (Shop::address())
                <div class="od-card p-6">
                    <p class="od-eyebrow">Where we are</p>
                    <p class="mt-2 whitespace-pre-line text-ink-soft leading-relaxed">{{ Shop::address() }}</p>
                </div>
            @endif
        </div>

        {{--
            And a box to type in.

            The cards above suit a shopper who is ready to pick up the phone.
            This catches the rest — the question at eleven at night about
            whether the blouse piece is included, which is not worth a
            telephone call and is a sale either way.
        --}}
        <div class="od-card mt-5 p-6 md:p-8" id="write">
            <h2 class="font-head text-xl">Or write to us</h2>

            @if (session('enquiry') || session('enquiry_error'))
                <p class="mt-4 rounded-[var(--radius-card)] border px-4 py-3 text-sm
                          {{ session('enquiry_error')
                              ? 'border-[color:var(--color-maroon)] text-ink'
                              : 'border-[color:var(--color-line)] text-gold' }}">
                    {{ session('enquiry_error') ?: session('enquiry') }}
                </p>
            @endif

            <form method="post" action="{{ route('enquiry.store') }}" class="mt-5">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="od-label" for="enquiry-name">Your name</label>
                        <input id="enquiry-name" name="name" class="od-input" maxlength="80" required
                               value="{{ old('name', auth()->user()?->name) }}">
                        @error('name')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="od-label" for="enquiry-email">Your email</label>
                        <input id="enquiry-email" name="email" type="email" class="od-input" required
                               value="{{ old('email', auth()->user()?->email) }}">
                        @error('email')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="od-label" for="enquiry-phone">Telephone <span class="normal-case tracking-normal">(if you would rather be rung)</span></label>
                        <input id="enquiry-phone" name="phone" class="od-input" maxlength="20" value="{{ old('phone') }}">
                        @error('phone')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="od-label" for="enquiry-order">Order number <span class="normal-case tracking-normal">(if it is about one)</span></label>
                        <input id="enquiry-order" name="order_number" class="od-input" maxlength="40"
                               placeholder="OJ-2026-00041" value="{{ old('order_number') }}">
                        @error('order_number')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label class="od-label" for="enquiry-message">What would you like to ask?</label>
                    <textarea id="enquiry-message" name="message" rows="5" class="od-input" required
                              placeholder="Is the blouse piece included with the indigo Kanjivaram?">{{ old('message') }}</textarea>
                    @error('message')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
                </div>

                {{-- Not for people. --}}
                <div class="hidden" aria-hidden="true">
                    <label>Website<input name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <button type="submit" class="od-btn od-btn-gold mt-6">Send it</button>

                <p class="mt-4 text-xs text-ink-faint">
                    A person answers, within two working days. We never pass your address on.
                </p>
            </form>
        </div>

        <div class="od-card mt-5 p-6">
            <p class="od-eyebrow">About an order you have placed</p>
            <p class="mt-2 text-ink-muted text-sm leading-relaxed">
                Have your order number to hand — it looks like OJ-2026-00041 and is in your
                confirmation email. You can also
                <a href="{{ route('track') }}" class="text-gold hover:text-gold-light">look it up yourself</a>.
            </p>
        </div>
    @elseif ($body)
        {{-- The shop writes plain text with the odd **bold** line; rendered
             here rather than stored as HTML, so nothing typed into Settings
             can put markup on the page. --}}
        <div class="mt-9 space-y-4 text-ink-soft leading-relaxed">
            @foreach (preg_split('/\n\s*\n/', trim($body)) as $para)
                @php $para = trim($para); @endphp
                @continue($para === '')
                @if (preg_match('/^\*\*(.+)\*\*$/u', $para, $m))
                    <h2 class="font-head text-xl text-ink pt-4">{{ $m[1] }}</h2>
                @else
                    <p>{!! preg_replace('/\*\*(.+?)\*\*/u', '<strong class="text-ink">$1</strong>', e($para)) !!}</p>
                @endif
            @endforeach
        </div>

        <p class="mt-10 text-xs text-ink-faint">
            @if ($updated)
                Last changed {{ \Illuminate\Support\Carbon::parse($updated)->format('j F Y') }}.
            @endif
            Anything unclear, <a href="{{ route('page', 'contact') }}" class="text-gold hover:text-gold-light">ask us</a> —
            we would rather explain it than have you guess.
        </p>
    @else
        <p class="mt-8 text-ink-muted">
            We have not written this one yet.
            @if (Shop::email())
                Email us at <a href="mailto:{{ Shop::email() }}" class="text-gold hover:text-gold-light">{{ Shop::email() }}</a>
                and we will answer properly.
            @endif
        </p>
    @endif
</div>
@endsection
