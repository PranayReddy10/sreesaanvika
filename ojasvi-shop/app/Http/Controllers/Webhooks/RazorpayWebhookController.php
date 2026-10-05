<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderService;
use App\Services\Payments\Razorpay;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Razorpay's own word on what happened.
 *
 * This, not the browser, is what the shop believes. A shopper whose phone dies
 * on the bank's page has still paid; a browser that says "paid" has proved
 * nothing. Both paths end in the same markPaid(), which does nothing the
 * second time, so it does not matter which arrives first or how often.
 *
 * Answers 200 even for an event it ignores: a non-200 makes Razorpay retry
 * for hours over something that was never a problem.
 */
class RazorpayWebhookController extends Controller
{
    public function __construct(private Razorpay $razorpay, private OrderService $orders)
    {
    }

    public function __invoke(Request $request): Response
    {
        $body = $request->getContent();

        if (! $this->razorpay->webhookIsGenuine($body, $request->header('X-Razorpay-Signature'))) {
            Log::warning('A Razorpay webhook arrived with a signature that did not match.');

            return response('', 400);
        }

        $event = $request->input('event');
        $entity = $request->input('payload.payment.entity', []);
        $gatewayOrderId = $entity['order_id'] ?? null;

        if (! $gatewayOrderId) {
            return response('', 200);
        }

        $payment = Payment::where('gateway_order_id', $gatewayOrderId)->latest('id')->first();

        if (! $payment) {
            // A payment for an order this shop has no record of. Worth a line
            // in the log and nothing more; retrying will not conjure one.
            Log::warning('Razorpay webhook for an unknown order', ['gateway_order' => $gatewayOrderId, 'event' => $event]);

            return response('', 200);
        }

        match ($event) {
            'payment.captured', 'order.paid' => $this->captured($payment, $entity),
            'payment.failed'                 => $this->failed($payment, $entity),
            'refund.processed'               => $this->refunded($payment, $request->input('payload.refund.entity', [])),
            default                          => null,
        };

        return response('', 200);
    }

    private function captured(Payment $payment, array $entity): void
    {
        $payment->update([
            'gateway_payment_id' => $entity['id'] ?? $payment->gateway_payment_id,
            'method'             => $entity['method'] ?? $payment->method,
            'status'             => 'captured',
            'payload'            => $entity,
        ]);

        if ($payment->order) {
            $this->orders->markPaid($payment->order, 'Payment confirmed by Razorpay');
        }
    }

    private function failed(Payment $payment, array $entity): void
    {
        $payment->update([
            'gateway_payment_id' => $entity['id'] ?? $payment->gateway_payment_id,
            'status'             => 'failed',
            'failure_reason'     => $entity['error_description'] ?? 'The payment failed.',
            'payload'            => $entity,
        ]);

        // The order itself is left alone: the shopper may well try again with
        // another card, and cancelling it under them would lose their stock.
    }

    private function refunded(Payment $payment, array $entity): void
    {
        $amount = isset($entity['amount']) ? ((int) $entity['amount']) / 100 : (float) $payment->amount;

        $payment->update(['status' => 'refunded', 'payload' => $entity]);

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
            $order->moveTo('refunded', 'Refunded through Razorpay');
        }
    }
}
