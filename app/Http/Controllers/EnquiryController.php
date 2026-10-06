<?php

namespace App\Http\Controllers;

use App\Mail\EnquiryForShop;
use App\Models\Enquiry;
use App\Support\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Somebody writing in from the contact page.
 *
 * Kept and sent, both. The email is what gets answered — nobody signs in to an
 * admin panel to type a sentence back — and the row is what stops a question
 * being lost because an email was read on a phone at a bad moment.
 */
class EnquiryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:80'],
            'email'        => ['required', 'email', 'max:160'],
            'phone'        => ['nullable', 'string', 'max:20'],
            'order_number' => ['nullable', 'string', 'max:40'],
            'message'      => ['required', 'string', 'min:10', 'max:4000'],
            // A field no human sees and no human fills in.
            'website'      => ['prohibited'],
        ], [
            'message.min' => 'A sentence or two more, so we can actually answer it.',
            'website.prohibited' => 'Something went wrong. Please try again.',
        ]);

        $key = 'enquiry:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()
                ->with('enquiry_error', 'That is several messages in a row. Telephone us instead — it is quicker.')
                ->withInput();
        }

        RateLimiter::hit($key, 3600);

        $enquiry = Enquiry::create([
            'name'         => trim($data['name']),
            'email'        => mb_strtolower(trim($data['email'])),
            'phone'        => trim((string) ($data['phone'] ?? '')) ?: null,
            'order_number' => trim((string) ($data['order_number'] ?? '')) ?: null,
            'message'      => trim($data['message']),
            'ip'           => $request->ip(),
        ]);

        /*
         * Queued, like every other mail the shop sends: the cron runs the
         * queue once a minute, and a shopper should not wait on a mail server
         * to be told her message arrived. If the mail fails she is not left
         * guessing either, because the row is already saved.
         */
        foreach (Shop::orderRecipients() as $address) {
            Mail::to($address)->queue(new EnquiryForShop($enquiry));
        }

        return back()->with('enquiry', 'Thank you — we have it, and we answer within two working days.');
    }
}
