@extends('layouts.shop')

@php use App\Support\Shop; @endphp

@section('title', 'Paying for ' . $order->number . ' — ' . Shop::name())

@section('content')
<div class="od-wrap py-20 max-w-md text-center">
    <h1 class="font-display text-3xl">Opening the payment page</h1>
    <p class="mt-3 text-ink-muted">
        Order {{ $order->number }} · {{ Shop::money($order->grand_total) }}
    </p>

    <p class="mt-8 text-sm text-ink-faint">
        If nothing happens, use the button below. Your order is held either way.
    </p>

    <button type="button" id="pay" class="od-btn od-btn-gold mt-5">Pay now</button>
    <a href="{{ $back }}" class="block mt-4 text-sm text-ink-muted hover:text-ink">Go back to checkout</a>

    {{-- Posted by JavaScript once Razorpay hands back a signature. The
         signature is checked on the server with the secret; nothing the
         browser says about the payment is believed on its own. --}}
    <form method="post" action="{{ $then }}" id="verify" class="hidden">
        @csrf
        <input type="hidden" name="order" value="{{ $order->number }}">
        <input type="hidden" name="razorpay_payment_id">
        <input type="hidden" name="razorpay_order_id">
        <input type="hidden" name="razorpay_signature">
    </form>

    <form method="post" action="{{ route('checkout.failed') }}" id="gaveup" class="hidden">
        @csrf
        <input type="hidden" name="order" value="{{ $order->number }}">
        <input type="hidden" name="reason">
    </form>
</div>
@endsection

@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    (function () {
        const verify = document.getElementById('verify');
        const gaveup = document.getElementById('gaveup');

        const options = {
            key: @json($key),
            amount: {{ (int) round($order->grand_total * 100) }},
            currency: @json($order->currency ?: 'INR'),
            name: @json(Shop::name()),
            description: @json('Order ' . $order->number),
            image: @json(asset('brand/icon-512.png')),
            order_id: @json($payment->gateway_order_id),
            prefill: {
                name: @json($order->shipping_address['name'] ?? ''),
                email: @json($order->email),
                contact: @json($order->phone),
            },
            notes: { order: @json($order->number) },
            theme: { color: '#a8781f' },
            handler: function (response) {
                verify.razorpay_payment_id.value = response.razorpay_payment_id;
                verify.razorpay_order_id.value = response.razorpay_order_id;
                verify.razorpay_signature.value = response.razorpay_signature;
                verify.submit();
            },
            modal: {
                ondismiss: function () {
                    gaveup.reason.value = 'The payment window was closed.';
                    gaveup.submit();
                },
            },
        };

        const razorpay = new Razorpay(options);

        razorpay.on('payment.failed', function (response) {
            gaveup.reason.value = (response.error && response.error.description) || 'The payment failed.';
            gaveup.submit();
        });

        document.getElementById('pay').addEventListener('click', () => razorpay.open());

        // Opened for them, so the usual case is one tap fewer.
        razorpay.open();
    })();
</script>
@endpush
