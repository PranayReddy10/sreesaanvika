<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The list.
 *
 * A saree shop's cheapest marketing: an email to people who asked to hear
 * about new pieces costs nothing and reaches everyone, unlike a post that an
 * algorithm may or may not show.
 *
 * Signing up is one field and one click. Leaving is one link, which has to
 * work without a password, or the shop will be marked as spam by people who
 * cannot find the way out.
 */
class NewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'name'  => ['nullable', 'string', 'max:120'],
            // A field no human sees and no human fills in.
            'website' => ['prohibited'],
        ], [
            'website.prohibited' => 'Something went wrong. Please try again.',
        ]);

        $key = 'newsletter:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->with('bag_error', 'That is a lot of sign-ups. Try again in a few minutes.');
        }

        RateLimiter::hit($key, 3600);

        $email = mb_strtolower(trim($data['email']));

        $name = trim((string) ($data['name'] ?? '')) ?: null;

        $existing = NewsletterSubscriber::firstWhere('email', $email);

        if ($existing) {
            // Somebody who left and came back. Quietly put them back on rather
            // than telling a stranger whether an address is on the list.
            $existing->update([
                'unsubscribed_at' => null,
                'name' => $name ?: $existing->name,
            ]);
        } else {
            NewsletterSubscriber::create([
                'email' => $email,
                'name'  => $name,
                // Taken as confirmed: this is a shop's own list, not a bought
                // one, and a double opt-in loses half of a small shop's
                // sign-ups to a confirmation email nobody opens.
                'confirmed_at' => now(),
            ]);
        }

        return back()->with('bag', 'Thank you — we will write when there is something worth seeing.');
    }

    /**
     * Leaving, from a link in an email.
     *
     * No password and no sign-in: somebody who wants off a list must be able
     * to get off it in one click, from whatever device the email is open on.
     */
    public function leave(Request $request): View
    {
        $email = mb_strtolower(trim((string) $request->query('email')));
        $token = (string) $request->query('token');

        $subscriber = $email !== '' ? NewsletterSubscriber::firstWhere('email', $email) : null;

        // Signed with the app key, so the link cannot be used to take somebody
        // else off the list by guessing their address.
        $expected = $subscriber ? self::token($subscriber->email) : '';
        $ok = $subscriber && $token !== '' && hash_equals($expected, $token);

        if ($ok) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }

        return view('shop.unsubscribed', ['ok' => $ok, 'email' => $email]);
    }

    public static function token(string $email): string
    {
        return substr(hash_hmac('sha256', mb_strtolower($email), config('app.key')), 0, 32);
    }

    public static function leaveUrl(string $email): string
    {
        return route('newsletter.leave', ['email' => $email, 'token' => self::token($email)]);
    }
}
