<?php

namespace App\Mail;

use App\Models\Order;
use App\Support\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * The email a shopper actually wants: what they bought, what it cost, where it
 * is going, and a link that works.
 *
 * Queued, because a shop must never make somebody wait on an SMTP server to
 * see their own confirmation page.
 */
class OrderPlaced extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your {$this->shopName()} order {$this->order->number}",
            replyTo: array_filter([Shop::email()]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.order-placed',
            with: [
                'order' => $this->order,
                'url'   => URL::temporarySignedRoute(
                    'order.confirmed',
                    now()->addDays(30),
                    ['order' => $this->order->number],
                ),
            ],
        );
    }

    private function shopName(): string
    {
        return Shop::name();
    }
}
