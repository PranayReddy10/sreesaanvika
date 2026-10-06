<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The shop's own copy. Plain and complete — this is read on a phone at the
 * loom, and it has to be enough to start packing from.
 */
class NewOrderForShop extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        $method = $this->order->isCod() ? 'cash on delivery' : 'paid';

        return new Envelope(
            subject: "New order {$this->order->number} — ₹" . number_format((float) $this->order->grand_total) . " ({$method})",
            replyTo: array_filter([$this->order->email]),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.new-order-for-shop', with: ['order' => $this->order]);
    }
}
