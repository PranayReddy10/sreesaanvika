@extends('layouts.shop')

@php use App\Support\Shop; @endphp

@section('title', 'Thank you — ' . Shop::name())

@section('content')
<div class="od-wrap py-14 md:py-20 max-w-2xl">
    <p class="od-eyebrow">Order {{ $order->number }}</p>
    <h1 class="mt-3 font-display text-4xl md:text-5xl">Thank you</h1>

    <p class="mt-4 text-ink-soft leading-relaxed">
        @if ($order->isCod())
            We have it. Pay the courier {{ Shop::money($order->grand_total) }} when it arrives.
        @else
            We have your payment of {{ Shop::money($order->grand_total) }}.
        @endif
        It goes out within {{ Shop::dispatchDays() }} working days, and we will email
        {{ $order->email }} with the tracking number.
    </p>

    <div class="od-card mt-8 p-6">
        <h2 class="font-head text-xl">What you bought</h2>

        <ul class="mt-5 space-y-4">
            @foreach ($order->items as $item)
                <li class="flex gap-3">
                    @if ($item->image)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item->image) }}"
                             alt="" class="w-16 h-[84px] object-cover rounded" width="64" height="84">
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="leading-snug">{{ $item->name }}</p>
                        <p class="text-sm text-ink-faint mt-0.5">
                            @if ($item->colourway_name){{ $item->colourway_name }} · @endif
                            @if ($item->sku){{ $item->sku }} · @endif
                            × {{ $item->quantity }}
                        </p>
                        @if ($item->offer_name)
                            <p class="text-xs text-gold mt-1">{{ $item->offer_name }}</p>
                        @endif
                    </div>
                    <p>{{ Shop::money($item->line_total) }}</p>
                </li>
            @endforeach
        </ul>

        <div class="od-rule my-5"></div>

        <dl class="space-y-2.5 text-sm">
            <div class="flex justify-between"><dt class="text-ink-muted">Sarees</dt><dd>{{ Shop::money($order->items_total) }}</dd></div>
            @if ($order->offer_total > 0)
                <div class="flex justify-between text-gold-light"><dt>Offers</dt><dd>−{{ Shop::money($order->offer_total) }}</dd></div>
            @endif
            @if ($order->discount_total > 0)
                <div class="flex justify-between text-gold-light"><dt>{{ $order->coupon_code }}</dt><dd>−{{ Shop::money($order->discount_total) }}</dd></div>
            @endif
            <div class="flex justify-between"><dt class="text-ink-muted">Delivery</dt><dd>{{ $order->shipping_total > 0 ? Shop::money($order->shipping_total) : 'Free' }}</dd></div>
        </dl>

        <div class="od-rule my-5"></div>

        <div class="flex items-baseline justify-between">
            <span class="font-head text-lg">{{ $order->isPaid() ? 'Paid' : 'To pay on delivery' }}</span>
            <span class="font-head text-2xl">{{ Shop::money($order->grand_total) }}</span>
        </div>
    </div>

    <div class="od-card mt-5 p-6">
        <h2 class="font-head text-xl">Going to</h2>
        <p class="mt-3 text-sm text-ink-soft leading-relaxed whitespace-pre-line">{{ collect([
            $order->shipping_address['name'] ?? null,
            $order->shipping_address['line1'] ?? null,
            $order->shipping_address['line2'] ?? null,
            $order->shipping_address['landmark'] ?? null,
            trim(($order->shipping_address['city'] ?? '') . ' ' . ($order->shipping_address['pincode'] ?? '')),
            $order->shipping_address['state'] ?? null,
            $order->shipping_address['phone'] ?? null,
        ])->filter()->implode("\n") }}</p>
    </div>

    <div class="mt-8 flex flex-wrap gap-3">
        <a href="{{ route('shop') }}" class="od-btn od-btn-gold">Keep looking</a>
        <a href="{{ route('track') }}" class="od-btn od-btn-ghost">Track this order</a>
    </div>
</div>
@endsection
