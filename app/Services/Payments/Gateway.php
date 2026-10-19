<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;

/**
 * What the shop needs of a payment gateway, and nothing more.
 *
 * Two of them are wired up — Razorpay and Cashfree — because a shop whose
 * gateway account is held up for a week, or whose rates change, should be able
 * to switch by choosing from a list rather than by waiting for somebody to
 * edit files. Everything past opening a payment differs enough between the two
 * that it stays on the classes themselves; this is only the part checkout, the
 * admin and the refund button all need to be able to ask for without knowing
 * which gateway answered.
 */
interface Gateway
{
    /** What the payment rows and the order are stamped with: 'razorpay', 'cashfree'. */
    public function name(): string;

    /** And how it is written when a person is going to read it. */
    public function label(): string;

    /** Has the shop been given working-looking credentials for it? */
    public function configured(): bool;

    /**
     * Open a payment against an order that already exists, and write down the
     * attempt. The order comes first so that whatever comes back later —
     * tonight's webhook, a dispute in March — has something to be matched to.
     */
    public function open(Order $order): Payment;

    /**
     * Is this webhook really from the gateway?
     *
     * The raw body, exactly as it arrived: a payload that has been decoded and
     * re-encoded is a different string and will not match.
     *
     * @param  array<string, string|null>  $headers  Whatever that gateway signs with.
     */
    public function webhookIsGenuine(string $body, array $headers): bool;

    /** Pay back, in full or in part. Amount in rupees. */
    public function refund(Payment $payment, ?float $amount = null): array;
}
