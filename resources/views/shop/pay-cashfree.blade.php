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
</div>
@endsection

@push('scripts')
<script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
<script>
    (function () {
        /*
         * The session id is all the browser is given, and all it needs: it is
         * good for this one payment at this one shop. Nothing is sent back
         * here afterwards — Cashfree returns the shopper to an address of
         * ours, and the shop asks Cashfree itself what happened.
         */
        const cashfree = Cashfree({ mode: @json($mode) });

        const open = () => cashfree.checkout({
            paymentSessionId: @json($payment->payload['payment_session_id'] ?? ''),
            redirectTarget: '_self',
        });

        document.getElementById('pay').addEventListener('click', open);

        // Opened for them, so the usual case is one tap fewer.
        open();
    })();
</script>
@endpush
