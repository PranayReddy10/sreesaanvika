@extends('layouts.shop')

@php use App\Support\Shop; @endphp

@section('title', 'Your account — ' . Shop::name())

@section('content')
<div class="od-wrap py-14 md:py-20 max-w-3xl">
    @auth
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <h1 class="font-display text-4xl">Hello, {{ auth()->user()->name }}</h1>

            <form method="post" action="{{ route('sign-out') }}">
                @csrf
                <button type="submit" class="text-sm text-ink-muted hover:text-ink transition">Sign out</button>
            </form>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('account.wishlist') }}" class="od-btn od-btn-ghost">Saved sarees</a>
            <a href="{{ route('track') }}" class="od-btn od-btn-ghost">Track an order</a>
        </div>

        <h2 class="mt-10 font-head text-2xl">Your orders</h2>

        @forelse ($orders as $order)
            <article class="od-card mt-4 p-5 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="font-head text-lg">{{ $order->number }}</p>
                    <p class="text-sm text-ink-muted">
                        {{ $order->placed_at?->format('j F Y') }} ·
                        {{ $order->items->sum('quantity') }} {{ \Illuminate\Support\Str::plural('piece', $order->items->sum('quantity')) }}
                    </p>
                </div>
                <div class="text-right">
                    <p>{{ Shop::money($order->grand_total) }}</p>
                    <p class="text-sm text-gold">{{ \App\Models\Order::STATUSES[$order->status] ?? $order->status }}</p>
                </div>
            </article>
        @empty
            <p class="mt-4 text-ink-muted">Nothing yet.</p>
        @endforelse
    @else
        <h1 class="font-display text-4xl">Your account</h1>
        <p class="mt-4 text-ink-muted">
            You can order without one — an account only keeps your orders and
            saved sarees for next time.
        </p>

        <div class="mt-7 flex flex-wrap gap-3">
            <a href="{{ route('sign-in') }}" class="od-btn od-btn-gold">Sign in</a>
            <a href="{{ route('join') }}" class="od-btn od-btn-ghost">Make an account</a>
        </div>

        <p class="mt-8 text-sm text-ink-muted">
            Looking for a parcel?
            <a href="{{ route('track') }}" class="text-gold hover:text-gold-light">Track it with your order number</a>.
        </p>
    @endauth
</div>
@endsection
