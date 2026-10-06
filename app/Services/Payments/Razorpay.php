<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Support\Shop;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

/**
 * Razorpay.
 *
 * Two things matter here and nothing else does.
 *
 * The secret never leaves the server. Only the key id is put on a page, and
 * every signature is checked here with the secret, so a browser cannot tell us
 * a payment succeeded.
 *
 * The webhook is the authority, not the browser. A shopper whose phone dies on
 * the bank's page has still paid, and the shop must know it — so the webhook
 * can mark an order paid on its own, and both paths are safe to run twice.
 */
class Razorpay
{
    public function configured(): bool
    {
        return (bool) (config('services.razorpay.key') && config('services.razorpay.secret'));
    }

    public function key(): string
    {
        return (string) config('services.razorpay.key');
    }

    private function api(): Api
    {
        return new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
    }

    /**
     * Open a payment against an order and record the attempt.
     *
     * Razorpay works in paise, so every amount is multiplied here, once, where
     * it can be seen — a rupees/paise mix-up is a hundredfold error.
     */
    public function open(Order $order): Payment
    {
        $amount = (int) round($order->grand_total * 100);

        $created = $this->api()->order->create([
            'amount'   => $amount,
            'currency' => $order->currency ?: 'INR',
            'receipt'  => $order->number,
            'notes'    => [
                'order'  => $order->number,
                'shop'   => Shop::name(),
            ],
        ]);

        return $order->payments()->create([
            'gateway'          => 'razorpay',
            'gateway_order_id' => $created['id'],
            'amount'           => $order->grand_total,
            'currency'         => $order->currency ?: 'INR',
            'status'           => 'created',
        ]);
    }

    /**
     * What the browser hands back after the modal closes.
     *
     * Returns the payment row when the signature is genuine, null when it is
     * not. A false signature is not an error to show a shopper — it is an
     * attempt to be paid for nothing.
     */
    public function verify(Order $order, array $fields): ?Payment
    {
        $paymentId = $fields['razorpay_payment_id'] ?? null;
        $orderId = $fields['razorpay_order_id'] ?? null;
        $signature = $fields['razorpay_signature'] ?? null;

        if (! $paymentId || ! $orderId || ! $signature) {
            return null;
        }

        $payment = $order->payments()
            ->where('gateway_order_id', $orderId)
            ->latest('id')
            ->first();

        if (! $payment) {
            return null;
        }

        try {
            $this->api()->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature'  => $signature,
            ]);
        } catch (SignatureVerificationError) {
            $payment->update([
                'status'         => 'failed',
                'failure_reason' => 'The signature did not match.',
            ]);

            return null;
        }

        $payment->update([
            'gateway_payment_id' => $paymentId,
            'gateway_signature'  => $signature,
            'status'             => 'captured',
        ]);

        return $payment;
    }

    /**
     * Is this webhook really from Razorpay?
     *
     * hash_equals rather than ===, so the comparison takes the same time
     * whatever the input and tells an attacker nothing.
     */
    public function webhookIsGenuine(string $body, ?string $signature): bool
    {
        $secret = (string) config('services.razorpay.webhook_secret');

        if ($secret === '' || ! $signature) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $body, $secret), $signature);
    }

    /** Pay back, in full or in part. Amount in rupees. */
    public function refund(Payment $payment, ?float $amount = null): array
    {
        $options = $amount !== null ? ['amount' => (int) round($amount * 100)] : [];

        $refund = $this->api()->payment->fetch($payment->gateway_payment_id)->refund($options);

        return $refund->toArray();
    }
}
