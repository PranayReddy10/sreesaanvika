<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * What a shopper thought of a saree.
 *
 * Nothing written here appears on the shop until somebody at OJASVI has read
 * it — a saree shop with an open comment box is a saree shop selling other
 * people's handbags by Friday. The admin's Reviews screen is one click per
 * row for exactly that reason.
 */
class ReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $signedIn = $request->user();

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title'  => ['nullable', 'string', 'max:120'],
            'body'   => ['required', 'string', 'min:10', 'max:2000'],
            'name'   => [$signedIn ? 'nullable' : 'required', 'string', 'max:80'],
            'email'  => [$signedIn ? 'nullable' : 'required', 'email', 'max:160'],
            // A field no human sees and no human fills in.
            'website' => ['prohibited'],
        ], [
            'body.min' => 'A line or two more, so it is some use to the next person.',
            'website.prohibited' => 'Something went wrong. Please try again.',
        ]);

        $email = mb_strtolower(trim($signedIn?->email ?? $data['email']));

        if (RateLimiter::tooManyAttempts('review:'.$request->ip(), 5)) {
            return $this->backToReviews($product)->with('review_error', 'That is a lot of reviews at once. Try again in an hour.');
        }

        $order = $this->ordersFor($email, $product)->first();

        // One per person per saree. Somebody who has changed their mind should
        // tell the shop, not write the same piece twice. Recognised by the
        // account where there is one, and otherwise by the order the address
        // placed — which is the only thing a guest leaves behind.
        $already = Review::where('product_id', $product->id)
            ->where(function ($q) use ($signedIn, $order) {
                $q->whereRaw('1 = 0');

                if ($signedIn) {
                    $q->orWhere('user_id', $signedIn->id);
                }

                if ($order) {
                    $q->orWhere('order_id', $order->id);
                }
            })
            ->exists();

        if ($already) {
            return $this->backToReviews($product)
                ->with('review_error', 'You have already written about this one — thank you.');
        }

        RateLimiter::hit('review:'.$request->ip(), 3600);

        Review::create([
            'product_id'  => $product->id,
            'user_id'     => $signedIn?->id,
            'order_id'    => $order?->id,
            'name'        => trim($data['name'] ?? '') ?: ($signedIn?->name ?? 'A customer'),
            'rating'      => (int) $data['rating'],
            'title'       => trim((string) ($data['title'] ?? '')) ?: null,
            'body'        => trim($data['body']),
            // Bought it, so the badge is earned rather than claimed.
            'is_verified' => $order !== null,
            'is_approved' => false,
        ]);

        return $this->backToReviews($product)
            ->with('review', 'Thank you. We read every one before it goes up.');
    }

    /**
     * Back to where she was writing, rather than to the top of the page.
     *
     * She has just scrolled the length of a saree page to write this; being
     * thrown back to the photograph with a line of thanks somewhere above it
     * reads as though nothing happened.
     */
    private function backToReviews(Product $product): RedirectResponse
    {
        return redirect()->to(route('product', $product->slug).'#reviews');
    }

    /**
     * The orders this address has actually paid for, holding this saree.
     *
     * Matched on the address the order was placed with rather than on the
     * account, because most people buy as a guest and come back months later
     * to say what they thought.
     */
    private function ordersFor(string $email, Product $product)
    {
        return Order::query()
            ->where('email', $email)
            ->where('payment_status', 'paid')
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->latest('id');
    }
}
