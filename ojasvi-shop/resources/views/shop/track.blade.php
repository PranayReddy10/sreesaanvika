@extends('layouts.shop')

@php use App\Support\Shop; @endphp

@section('title', 'Track an order — ' . Shop::name())

@section('content')
<div class="od-wrap py-14 md:py-20 max-w-xl">
    <h1 class="font-display text-4xl">Where is my parcel?</h1>
    <p class="mt-3 text-ink-muted">Your order number, and the email or phone you ordered with.</p>

    <form method="post" action="{{ route('track.find') }}" class="mt-8 space-y-4">
        @csrf
        <div>
            <label for="number" class="od-label">Order number</label>
            <input id="number" name="number" value="{{ old('number') }}" required
                   class="od-input" placeholder="OJ-2026-00041">
            @error('number')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="contact" class="od-label">Email or phone</label>
            <input id="contact" name="contact" value="{{ old('contact') }}" required class="od-input">
            @error('contact')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="od-btn od-btn-gold w-full">Find it</button>
    </form>

    @if ($looked)
        @if (! $order)
            <div class="od-card mt-8 p-6">
                <p class="font-head text-lg">We could not find that one</p>
                <p class="mt-2 text-sm text-ink-muted">
                    Check the number and that you used the same email or phone you ordered with.
                    @if (Shop::whatsapp())
                        Or <a href="https://wa.me/{{ Shop::whatsapp() }}" class="text-gold hover:text-gold-light">message us</a>.
                    @endif
                </p>
            </div>
        @else
            <div class="od-card mt-8 p-6">
                <div class="flex items-baseline justify-between gap-4">
                    <h2 class="font-head text-xl">{{ $order->number }}</h2>
                    <span class="text-sm text-gold">{{ \App\Models\Order::STATUSES[$order->status] ?? $order->status }}</span>
                </div>

                <p class="mt-1 text-sm text-ink-muted">Placed {{ $order->placed_at?->format('j F Y') }}</p>

                <ul class="mt-5 space-y-3 text-sm">
                    @foreach ($order->items as $item)
                        <li class="flex justify-between gap-4">
                            <span class="text-ink-soft">
                                {{ $item->name }}
                                @if ($item->colourway_name)<span class="text-ink-faint">· {{ $item->colourway_name }}</span>@endif
                                <span class="text-ink-faint">× {{ $item->quantity }}</span>
                            </span>
                            <span>{{ Shop::money($item->line_total) }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="od-rule my-5"></div>

                <div class="flex justify-between font-head text-lg">
                    <span>Total</span>
                    <span>{{ Shop::money($order->grand_total) }}</span>
                </div>

                @if ($order->shipment)
                    <div class="mt-6 rounded-[var(--radius-card)] border border-[color:var(--color-line)] p-4 text-sm">
                        <p class="text-ink-soft">{{ $order->shipment->status_label ?: 'On its way' }}</p>
                        @if ($order->shipment->awb)
                            <p class="mt-1 text-ink-muted">
                                {{ ucfirst($order->shipment->courier) }} · tracking {{ $order->shipment->awb }}
                            </p>
                        @endif
                        @if ($order->shipment->expected_on)
                            <p class="mt-1 text-ink-muted">Expected {{ $order->shipment->expected_on->format('j F') }}</p>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    @endif
</div>
@endsection
