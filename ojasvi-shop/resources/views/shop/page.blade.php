@extends('layouts.shop')

@php use App\Support\Shop; @endphp

@section('title', $heading . ' — ' . Shop::name())

@section('content')
<div class="od-wrap py-14 md:py-20 max-w-3xl">
    <h1 class="font-display text-4xl md:text-5xl">{{ $heading }}</h1>

    @if ($slug === 'contact')
        <div class="mt-8 grid gap-6 sm:grid-cols-2">
            @if (Shop::phone())
                <div class="od-card p-6">
                    <p class="od-eyebrow">Telephone</p>
                    <a href="tel:{{ preg_replace('/\s/', '', Shop::phone()) }}" class="mt-2 block text-lg hover:text-gold-light transition">{{ Shop::phone() }}</a>
                </div>
            @endif

            @if (Shop::email())
                <div class="od-card p-6">
                    <p class="od-eyebrow">Email</p>
                    <a href="mailto:{{ Shop::email() }}" class="mt-2 block text-lg hover:text-gold-light transition">{{ Shop::email() }}</a>
                </div>
            @endif

            @if (Shop::whatsapp())
                <div class="od-card p-6">
                    <p class="od-eyebrow">WhatsApp</p>
                    <a href="https://wa.me/{{ Shop::whatsapp() }}" target="_blank" rel="noopener"
                       class="mt-2 block text-lg hover:text-gold-light transition">Send us a message</a>
                </div>
            @endif

            @if (Shop::address())
                <div class="od-card p-6">
                    <p class="od-eyebrow">Where we are</p>
                    <p class="mt-2 whitespace-pre-line text-ink-soft leading-relaxed">{{ Shop::address() }}</p>
                </div>
            @endif
        </div>
    @elseif ($body)
        <div class="mt-8 text-ink-soft leading-relaxed whitespace-pre-line">{{ $body }}</div>
    @else
        <p class="mt-8 text-ink-muted">
            We have not written this one yet. {{ Shop::email() ? 'Email us at ' . Shop::email() . ' and we will answer properly.' : '' }}
        </p>
    @endif
</div>
@endsection
