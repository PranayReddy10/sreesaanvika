@extends('layouts.shop')

@php use App\Support\Shop; @endphp

@section('title', 'Your account — ' . Shop::name())

@section('content')
<div class="od-wrap py-14 md:py-20 max-w-3xl">
    @auth
        <h1 class="font-display text-4xl">Hello, {{ auth()->user()->name }}</h1>

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
            You can order without one. To look up a parcel,
            <a href="{{ route('track') }}" class="text-gold hover:text-gold-light">track it here</a>.
        </p>
    @endauth
</div>
@endsection
