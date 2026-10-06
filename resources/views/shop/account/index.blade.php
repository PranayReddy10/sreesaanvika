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

        {{--
            Where her orders have been sent.

            Kept from the orders themselves rather than asked for separately —
            nobody fills in an address book — so the second order is a matter
            of pressing the button, and the one the checkout will offer is
            marked here rather than being a surprise at the till.
        --}}
        @if ($addresses->isNotEmpty())
            <h2 class="mt-10 font-head text-2xl">Where we send things</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach ($addresses as $address)
                    <article class="od-card p-5 {{ $address->is_default ? 'border-[color:var(--color-brand)]' : '' }}">
                        @if ($address->is_default)
                            <p class="od-eyebrow mb-2">The next one goes here</p>
                        @endif

                        <p class="text-ink">{{ $address->name }}</p>
                        <p class="mt-1 text-sm text-ink-muted leading-relaxed">
                            {{ $address->line1 }}@if ($address->line2), {{ $address->line2 }}@endif<br>
                            @if ($address->landmark)Near {{ $address->landmark }}<br>@endif
                            {{ $address->city }} {{ $address->pincode }}<br>
                            {{ $address->state }}
                        </p>
                        @if ($address->phone)
                            <p class="mt-1 text-sm text-ink-faint">{{ $address->phone }}</p>
                        @endif

                        <div class="mt-4 flex items-center gap-4 text-sm">
                            @unless ($address->is_default)
                                <form method="post" action="{{ route('account.address.use', $address) }}">
                                    @csrf
                                    <button type="submit" class="text-gold hover:text-gold-light transition">Send the next one here</button>
                                </form>
                            @endunless

                            <form method="post" action="{{ route('account.address.forget', $address) }}"
                                  onsubmit="return confirm('Remove this address?')">
                                @csrf
                                @method('delete')
                                <button type="submit" class="text-ink-faint hover:text-ink-muted transition">Remove</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

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
