@php use App\Support\Shop; @endphp

<x-mail::message>
# Thank you, {{ $order->shipping_address['name'] ?? 'and welcome' }}

We have your order **{{ $order->number }}**.

@if ($order->isCod())
Pay the courier **{{ Shop::money($order->grand_total) }}** when it arrives.
@else
Your payment of **{{ Shop::money($order->grand_total) }}** has gone through.
@endif

It goes out within {{ Shop::dispatchDays() }} working days, and we will email you the tracking number as soon as it does.

<x-mail::table>
| What | | Price |
|:-----|:--|------:|
@foreach ($order->items as $item)
| {{ $item->name }}{{ $item->colourway_name ? ' — ' . $item->colourway_name : '' }} | × {{ $item->quantity }} | {{ Shop::money($item->line_total) }} |
@endforeach
@if ($order->offer_total > 0)
| Offers | | −{{ Shop::money($order->offer_total) }} |
@endif
@if ($order->discount_total > 0)
| {{ $order->coupon_code }} | | −{{ Shop::money($order->discount_total) }} |
@endif
| Delivery | | {{ $order->shipping_total > 0 ? Shop::money($order->shipping_total) : 'Free' }} |
| **Total** | | **{{ Shop::money($order->grand_total) }}** |
</x-mail::table>

**Going to**

{{ collect([
    $order->shipping_address['name'] ?? null,
    $order->shipping_address['line1'] ?? null,
    $order->shipping_address['line2'] ?? null,
    $order->shipping_address['landmark'] ?? null,
    trim(($order->shipping_address['city'] ?? '') . ' ' . ($order->shipping_address['pincode'] ?? '')),
    $order->shipping_address['state'] ?? null,
])->filter()->implode(', ') }}

<x-mail::button :url="$url">
See your order
</x-mail::button>

{{-- A directive written hard against a word is not a directive: Blade leaves @if attached to "email" as text and compiles the @endif on its own, which is a view that will not render at all. --}}
Anything at all, just reply to this email{{ Shop::phone() ? ' or ring us on '.Shop::phone() : '' }}.

Thank you,<br>
{{ Shop::name() }}
</x-mail::message>
