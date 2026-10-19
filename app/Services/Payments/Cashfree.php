<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Cashfree.
 *
 * The same two rules the Razorpay class is built on, because they are not
 * Razorpay's rules — they are what makes a payment safe.
 *
 * The secret key never leaves this server. What goes to the browser is a
 * payment session id, which is good for one payment at one shop and tells
 * nobody anything.
 *
 * And nothing the browser says is believed. A shopper coming back from the
 * bank arrives with an order number in the address bar and no proof of
 * anything; the shop then asks Cashfree directly whether that order is paid.
 * The webhook does the same job for the shopper whose phone died on the bank's
 * page — they have still paid, and the shop must know it — so both paths end
 * in the same markPaid() and both are safe to run twice.
 *
 * One difference from Razorpay is worth saying out loud: Cashfree works in
 * rupees with two decimal places, where Razorpay works in paise. Send Razorpay
 * rupees and you undercharge a hundredfold; send Cashfree paise and you charge
 * a hundred times too much. Every amount here is in rupees, and nothing in
 * this file multiplies by 100.
 */
class Cashfree implements Gateway
{
    /**
     * The version of their API this code is written against. It is a date and
     * it is required on every call — Cashfree changes shapes between versions,
     * so pinning it is what keeps this working when they move on.
     */
    private const API_VERSION = '2023-08-01';

    public function name(): string
    {
        return 'cashfree';
    }

    public function label(): string
    {
        return 'Cashfree';
    }

    public function configured(): bool
    {
        return $this->appId() !== '' && $this->secret() !== '';
    }

    public function appId(): string
    {
        return trim((string) config('services.cashfree.app_id'));
    }

    private function secret(): string
    {
        return trim((string) config('services.cashfree.secret'));
    }

    /** Test keys and live keys are different accounts at different addresses. */
    public function sandbox(): bool
    {
        return config('services.cashfree.mode') === 'sandbox';
    }

    /** What the checkout script in the browser must be told to use. */
    public function mode(): string
    {
        return $this->sandbox() ? 'sandbox' : 'production';
    }

    public function base(): string
    {
        // Only ever set while the payment path is being tested against a
        // stand-in; a real shop has nothing in it and gets the address its
        // keys belong to.
        $override = trim((string) config('services.cashfree.base'));

        if ($override !== '') {
            return rtrim($override, '/');
        }

        return $this->sandbox()
            ? 'https://sandbox.cashfree.com/pg'
            : 'https://api.cashfree.com/pg';
    }

    public function http(): PendingRequest
    {
        return Http::withHeaders([
            'x-client-id'     => $this->appId(),
            'x-client-secret' => $this->secret(),
            'x-api-version'   => self::API_VERSION,
            'accept'          => 'application/json',
        ])->timeout(20)->asJson();
    }

    /**
     * Open a payment against an order and record the attempt.
     *
     * Cashfree wants an order id of our own and hands back a session id for
     * the browser. Ours is the order number, so a payment in their dashboard
     * can be read against an order in this shop without looking anything up —
     * and a second attempt at the same order gets a suffix, because Cashfree
     * will not take the same order id twice.
     */
    public function open(Order $order): Payment
    {
        $reference = $this->referenceFor($order);
        $address = $order->shipping_address ?? [];

        $response = $this->http()->post($this->base().'/orders', [
            'order_id'       => $reference,
            'order_amount'   => round((float) $order->grand_total, 2),
            'order_currency' => $order->currency ?: 'INR',
            'customer_details' => [
                // Their id, not ours: a guest has no account, and the phone
                // number is the one thing every shopper here has.
                'customer_id'    => 'ojasvi_'.preg_replace('/\D/', '', (string) $order->phone),
                'customer_name'  => $this->plain($address['name'] ?? '') ?: 'Customer',
                'customer_email' => (string) $order->email,
                'customer_phone' => (string) $order->phone,
            ],
            'order_meta' => [
                // Cashfree puts the order id into the address itself, which is
                // why the placeholder is left in it literally.
                'return_url' => str_replace(
                    'CFORDERID',
                    '{order_id}',
                    route('checkout.cashfree.return', ['reference' => 'CFORDERID']),
                ),
                'notify_url' => route('webhooks.cashfree'),
            ],
            'order_note' => 'Order '.$order->number,
        ]);

        $body = $this->sessionFrom($response, $reference);

        return $order->payments()->create([
            'gateway'          => 'cashfree',
            'gateway_order_id' => $reference,
            'amount'           => $order->grand_total,
            'currency'         => $order->currency ?: 'INR',
            'status'           => 'created',
            'payload'          => [
                'cf_order_id'        => $body['cf_order_id'] ?? null,
                'payment_session_id' => $body['payment_session_id'] ?? null,
            ],
        ]);
    }

    /**
     * What Cashfree itself says about an order.
     *
     * Returns the payment row when the money is really there, and null when it
     * is not. Called from the page the shopper lands on and from nothing else
     * that trusts them: the address bar is an input, not evidence.
     */
    public function settle(string $reference): ?Payment
    {
        $payment = Payment::where('gateway', 'cashfree')
            ->where('gateway_order_id', $reference)
            ->latest('id')
            ->first();

        if (! $payment) {
            return null;
        }

        $response = $this->http()->get($this->base().'/orders/'.urlencode($reference));

        if (! $response->successful()) {
            // Cashfree is unreachable or unhappy. Nothing is decided here —
            // the webhook will say what happened, and a shopper is not told
            // their payment failed because an API call timed out.
            return $payment->status === 'captured' ? $payment : null;
        }

        $order = $response->json();

        if (($order['order_status'] ?? null) !== 'PAID') {
            return null;
        }

        return $this->capture($payment, $this->successfulPaymentFor($reference));
    }

    /**
     * Which payment on that order actually went through.
     *
     * An order can carry several attempts — a card that was refused and then a
     * UPI that worked — and the one worth writing down is the successful one.
     *
     * @return array<string, mixed>
     */
    public function successfulPaymentFor(string $reference): array
    {
        $response = $this->http()->get($this->base().'/orders/'.urlencode($reference).'/payments');

        if (! $response->successful()) {
            return [];
        }

        foreach ((array) $response->json() as $attempt) {
            if (is_array($attempt) && ($attempt['payment_status'] ?? null) === 'SUCCESS') {
                return $attempt;
            }
        }

        return [];
    }

    /**
     * Write a successful payment down, once.
     *
     * @param  array<string, mixed>  $entity  Cashfree's own record of the payment, where there is one.
     */
    public function capture(Payment $payment, array $entity = []): Payment
    {
        $payment->update([
            'gateway_payment_id' => isset($entity['cf_payment_id'])
                ? (string) $entity['cf_payment_id']
                : $payment->gateway_payment_id,
            'method'  => $this->methodFrom($entity) ?: $payment->method,
            'status'  => 'captured',
            'payload' => array_merge((array) $payment->payload, $entity ? ['payment' => $entity] : []),
        ]);

        return $payment->refresh();
    }

    /**
     * Is this webhook really from Cashfree?
     *
     * Their signature is over the timestamp and the raw body together, so a
     * payload somebody kept from last week cannot be sent again tonight. The
     * comparison is hash_equals rather than ===, so it takes the same time
     * whatever the input and tells an attacker nothing.
     *
     * @param  array<string, string|null>  $headers
     */
    public function webhookIsGenuine(string $body, array $headers): bool
    {
        $secret = $this->secret();
        $signature = $headers['signature'] ?? null;
        $timestamp = $headers['timestamp'] ?? null;

        if ($secret === '' || ! $signature || ! $timestamp) {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $timestamp.$body, $secret, true));

        return hash_equals($expected, $signature);
    }

    /** Pay back, in full or in part. Amount in rupees, as everywhere here. */
    public function refund(Payment $payment, ?float $amount = null): array
    {
        $amount = round($amount ?? (float) $payment->amount, 2);

        $response = $this->http()->post(
            $this->base().'/orders/'.urlencode((string) $payment->gateway_order_id).'/refunds',
            [
                'refund_amount' => $amount,
                // Unique, and readable in their dashboard beside the order.
                'refund_id'     => substr('rf_'.$payment->gateway_order_id.'_'.now()->format('ymdHis'), 0, 40),
                'refund_note'   => 'Refunded from the shop',
            ],
        );

        if (! $response->successful()) {
            throw new \RuntimeException($this->complaint($response->json(), $response->status()));
        }

        return (array) $response->json();
    }

    /* ------------------------------------------------------------ the small print */

    /**
     * A new order id for each attempt at the same order.
     *
     * Cashfree refuses an order id it has seen before, and a shopper who comes
     * back to pay for an order they abandoned is the ordinary case, not a
     * strange one.
     */
    private function referenceFor(Order $order): string
    {
        $attempts = $order->payments()->where('gateway', 'cashfree')->count();

        return $attempts === 0 ? $order->number : $order->number.'-'.($attempts + 1);
    }

    /**
     * The created order, or the one that was already there.
     *
     * A retry after a timeout is the case this exists for: the order was made,
     * the answer was lost, and refusing to carry on would leave a shopper who
     * cannot pay for an order that does exist.
     *
     * @return array<string, mixed>
     */
    private function sessionFrom(\Illuminate\Http\Client\Response $response, string $reference): array
    {
        if ($response->successful() && ($response->json('payment_session_id'))) {
            return (array) $response->json();
        }

        if ($response->status() === 409) {
            $existing = $this->http()->get($this->base().'/orders/'.urlencode($reference));

            if ($existing->successful() && $existing->json('payment_session_id')) {
                return (array) $existing->json();
            }
        }

        throw new \RuntimeException($this->complaint($response->json(), $response->status()));
    }

    /**
     * What Cashfree said went wrong, in one line fit for a log.
     *
     * @param  mixed  $body
     */
    private function complaint($body, int $status): string
    {
        $message = is_array($body) ? ($body['message'] ?? null) : null;
        $code = is_array($body) ? ($body['code'] ?? null) : null;

        return trim(sprintf(
            'Cashfree answered %d%s%s',
            $status,
            $code ? ' ('.$code.')' : '',
            $message ? ': '.$message : '',
        ));
    }

    /**
     * 'upi', 'card', 'netbanking' — Cashfree sends the method as an object
     * with one key, and the key is the part worth keeping.
     *
     * @param  array<string, mixed>  $entity
     */
    private function methodFrom(array $entity): ?string
    {
        $method = $entity['payment_method'] ?? null;

        if (is_string($method)) {
            return $method;
        }

        if (is_array($method) && $method !== []) {
            return (string) array_key_first($method);
        }

        return null;
    }

    /** Cashfree refuses a customer name with punctuation in it. */
    private function plain(string $name): string
    {
        return trim(substr(preg_replace('/[^\p{L}\p{N} ]+/u', ' ', $name) ?? '', 0, 100));
    }
}
