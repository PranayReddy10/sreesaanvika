<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\OrderMailer;
use App\Services\OrderService;
use App\Services\Payments\Cashfree;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Cashfree's own word on what happened.
 *
 * This, not the browser, is what the shop believes. A shopper whose phone dies
 * on the bank's page has still paid; a browser that says "paid" has proved
 * nothing. Both paths end in the same markPaid(), which does nothing the
 * second time, so it does not matter which arrives first or how often.
 *
 * Answers 200 even for an event it ignores: a non-200 makes Cashfree retry for
 * hours over something that was never a problem.
 */
class CashfreeWebhookController extends Controller
{
    public function __construct(
        private Cashfree $cashfree,
        private OrderService $orders,
        private OrderMailer $mailer,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        // The body exactly as it arrived. Decoding and re-encoding it gives a
        // different string, and a different string has a different signature.
        $body = $request->getContent();

        $genuine = $this->cashfree->webhookIsGenuine($body, [
            'signature' => $request->header('x-webhook-signature'),
            'timestamp' => $request->header('x-webhook-timestamp'),
        ]);

        if (! $genuine) {
            Log::warning('A Cashfree webhook arrived with a signature that did not match.');

            return response('', 400);
        }

        $type = (string) $request->input('type');
        $reference = $request->input('data.order.order_id');

        if (! $reference) {
            return response('', 200);
        }

        $payment = Payment::where('gateway', 'cashfree')
            ->where('gateway_order_id', $reference)
            ->latest('id')
            ->first();

        if (! $payment) {
            // A payment for an order this shop has no record of. Worth a line
            // in the log and nothing more; retrying will not conjure one.
            Log::warning('Cashfree webhook for an unknown order', ['gateway_order' => $reference, 'event' => $type]);

            return response('', 200);
        }

        match ($type) {
            'PAYMENT_SUCCESS_WEBHOOK' => $this->captured($payment, (array) $request->input('data.payment', [])),
            'PAYMENT_FAILED_WEBHOOK',
            'PAYMENT_USER_DROPPED_WEBHOOK' => $this->failed($payment, (array) $request->input('data.payment', [])),
            'REFUND_STATUS_WEBHOOK'   => $this->refunded($payment, (array) $request->input('data.refund', [])),
            default                   => null,
        };

        return response('', 200);
    }

    /** @param array<string, mixed> $entity */
    private function captured(Payment $payment, array $entity): void
    {
        $this->cashfree->capture($payment, $entity);

        $order = $payment->order;

        if (! $order) {
            return;
        }

        /*
         * Only when this is the moment it became paid: the shopper's own
         * browser may have got here first, and nobody should be thanked twice.
         *
         * And it is the whole confirmation that goes out, not a bare receipt,
         * because this is the path a shopper whose phone died on the bank's
         * page takes — their order has been paid for and neither they nor the
         * shop has been told anything about it yet.
         */
        $wasUnpaid = $order->payment_status !== 'paid';

        $this->orders->markPaid($order, 'Payment confirmed by Cashfree');

        if ($wasUnpaid) {
            $this->mailer->placed($order);
        }
    }

    /** @param array<string, mixed> $entity */
    private function failed(Payment $payment, array $entity): void
    {
        /*
         * Never over the top of a payment that went through.
         *
         * An order can hold several attempts — a card refused, then a UPI that
         * worked — and they arrive in whatever order the network gives them.
         */
        if ($payment->status === 'captured') {
            return;
        }

        $payment->update([
            'gateway_payment_id' => isset($entity['cf_payment_id'])
                ? (string) $entity['cf_payment_id']
                : $payment->gateway_payment_id,
            'status'         => 'failed',
            'failure_reason' => $entity['payment_message'] ?? 'The payment failed.',
            'payload'        => array_merge((array) $payment->payload, ['payment' => $entity]),
        ]);

        // The order itself is left alone: the shopper may well try again with
        // another card, and cancelling it under them would lose their stock.
    }

    /** @param array<string, mixed> $entity */
    private function refunded(Payment $payment, array $entity): void
    {
        // Only a refund that has actually been sent on its way counts; the
        // same webhook arrives when one is first asked for.
        if (($entity['refund_status'] ?? null) !== 'SUCCESS') {
            return;
        }

        /*
         * Each refund counted once, however often Cashfree says it.
         *
         * Webhooks are retried, and two refunds on one order are ordinary —
         * so the test is whether this particular refund has been seen before,
         * not whether any refund has. Adding the same one twice would take an
         * order's books past what it was paid.
         */
        $id = (string) ($entity['cf_refund_id'] ?? $entity['refund_id'] ?? '');
        $seen = (array) (((array) $payment->payload)['refunds'] ?? []);

        if ($id !== '' && in_array($id, $seen, true)) {
            return;
        }

        // Cashfree speaks in rupees here, as it does everywhere else.
        $amount = isset($entity['refund_amount'])
            ? (float) $entity['refund_amount']
            : (float) $payment->amount;

        $payment->update([
            'status'  => 'refunded',
            'payload' => array_merge((array) $payment->payload, [
                'refund'  => $entity,
                'refunds' => $id === '' ? $seen : array_values(array_unique([...$seen, $id])),
            ]),
        ]);

        $order = $payment->order;

        if (! $order) {
            return;
        }

        $refunded = round((float) $order->refunded_total + $amount, 2);

        $order->forceFill([
            'refunded_total' => $refunded,
            'payment_status' => $refunded >= (float) $order->grand_total ? 'refunded' : 'partially_refunded',
        ])->save();

        if ($order->payment_status === 'refunded' && $order->status !== 'refunded') {
            $order->moveTo('refunded', 'Refunded through Cashfree');
        }
    }
}
