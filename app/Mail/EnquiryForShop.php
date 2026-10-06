<?php

namespace App\Mail;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A question from the contact page, in the shop's inbox.
 *
 * Reply-To is the person who wrote it, so answering is pressing Reply — which
 * is the whole point. Nobody is going to sign in to an admin panel to type a
 * sentence back.
 */
class EnquiryForShop extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Enquiry $enquiry)
    {
    }

    public function envelope(): Envelope
    {
        $about = $this->enquiry->order_number
            ? " about {$this->enquiry->order_number}"
            : '';

        return new Envelope(
            subject: "{$this->enquiry->name} wrote in{$about}",
            replyTo: [$this->enquiry->email],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.enquiry-for-shop', with: ['enquiry' => $this->enquiry]);
    }
}
