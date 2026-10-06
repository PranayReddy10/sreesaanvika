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
