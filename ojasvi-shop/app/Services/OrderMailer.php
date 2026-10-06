<?php

namespace App\Services;

use App\Mail\NewOrderForShop;
use App\Mail\OrderPaid;
use App\Mail\OrderPlaced;
use App\Mail\OrderShipped;
use App\Models\Order;
use App\Support\Shop;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Every email the shop sends about an order, in one place.
 *
 * Nothing here is allowed to break a checkout. A misconfigured SMTP password
 * must cost the shop an email, never a sale, so each send is wrapped and its
 * failure logged rather than thrown — the order is already written and the
 * money is already taken by the time any of this runs.
 */
class OrderMailer
{
    public function placed(Order $order): void
    {
        $this->send(fn () => Mail::to($order->email)->send(new OrderPlaced($order)), $order, 'placed');

        // One email to everybody who runs the shop, sent separately so one bad
        // address cannot stop the others arriving.
        foreach (Shop::orderRecipients() as $recipient) {
            $this->send(
                fn () => Mail::to($recipient)->send(new NewOrderForShop($order)),
                $order,
                "shop copy to {$recipient}",
            );
        }
    }

    public function paid(Order $order): void
    {
        // Cash on delivery is confirmed at the door, not by an email saying
        // the money arrived — sending one would be a lie.
        if ($order->isCod()) {
            return;
        }

        $this->send(fn () => Mail::to($order->email)->send(new OrderPaid($order)), $order, 'paid');
    }

    public function shipped(Order $order): void
    {
        $this->send(fn () => Mail::to($order->email)->send(new OrderShipped($order)), $order, 'shipped');
    }

    private function send(callable $send, Order $order, string $what): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            Log::error("Could not send the '{$what}' email", [
                'order' => $order->number,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
