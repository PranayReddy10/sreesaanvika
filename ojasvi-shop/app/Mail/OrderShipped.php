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

/** The one email every shopper opens: the tracking number. */
class OrderShipped extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your saree is on its way — {$this->order->number}",
            replyTo: array_filter([Shop::email()]),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-shipped', with: [
            'order'    => $this->order,
            'shipment' => $this->order->shipment,
        ]);
    }
}
