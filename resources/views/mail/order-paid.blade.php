@php use App\Support\Shop; @endphp

<x-mail::message>
# Payment received

We have **{{ Shop::money($order->grand_total) }}** against order **{{ $order->number }}**. Nothing more to do — we are packing it now.

You will hear from us again with the tracking number.

Thank you,<br>
{{ Shop::name() }}
</x-mail::message>
