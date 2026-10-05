@php use App\Support\Shop; @endphp

<x-mail::message>
# It is on its way

Order **{{ $order->number }}** left us today.

@if ($shipment?->awb)
**{{ ucfirst($shipment->courier) }}** is carrying it, tracking number **{{ $shipment->awb }}**.
@endif

@if ($shipment?->expected_on)
It should reach you around **{{ $shipment->expected_on->format('j F') }}**.
@endif

<x-mail::button :url="route('track')">
Track it
</x-mail::button>

When it arrives, do send us a photograph — we love seeing them worn.

{{ Shop::name() }}
</x-mail::message>
