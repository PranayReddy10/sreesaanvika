@php use App\Support\Shop; @endphp

<x-mail::message>
# {{ $order->number }}

**{{ $order->isCod() ? 'Cash on delivery' : 'Paid online' }}** · {{ Shop::money($order->grand_total) }}

**Pack**

@foreach ($order->items as $item)
- {{ $item->quantity }} × {{ $item->name }}{{ $item->colourway_name ? ' — ' . $item->colourway_name : '' }}{{ $item->sku ? ' (' . $item->sku . ')' : '' }}
@endforeach

**Send to**

{{ $order->shipping_address['name'] ?? '' }}
{{ $order->shipping_address['line1'] ?? '' }}
@if (! empty($order->shipping_address['line2'])){{ $order->shipping_address['line2'] }}@endif
@if (! empty($order->shipping_address['landmark']))Near {{ $order->shipping_address['landmark'] }}@endif
{{ $order->shipping_address['city'] ?? '' }} {{ $order->shipping_address['pincode'] ?? '' }}
{{ $order->shipping_address['state'] ?? '' }}
{{ $order->phone }} · {{ $order->email }}

@if ($order->note)
**They asked:** {{ $order->note }}
@endif

<x-mail::button :url="url('/admin/orders/' . $order->id)">
Open it in the admin
</x-mail::button>
</x-mail::message>
