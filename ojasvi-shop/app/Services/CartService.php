<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Colourway;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingZone;
use Illuminate\Support\Facades\Cookie;

/**
 * The bag.
 *
 * Everything that changes it goes through here, so the rules about stock,
 * quantity and who owns which bag are stated once. The totals are worked out
 * on demand from the products — the bag stores what was chosen, never what it
 * cost — which is what keeps a repriced saree honest in an old bag.
 */
class CartService
{
    public const COOKIE = 'ojasvi_bag';

    /**
     * The bag for this request.
     *
     * Held here because a cookie queued for the response cannot be read back
     * from the request that queued it — so without this, a guest asking for
     * their bag twice in one request would be handed two different bags, and
     * whatever they added to the first would vanish.
     */
    protected ?Cart $resolved = null;

    public function __construct(private OfferEngine $offers)
    {
    }

    /** Forget the request's bag — after signing in or out, when it changes hands. */
    public function forget(): void
    {
        $this->resolved = null;
    }

    /* ----------------------------------------------------------- the bag */

    public function current(bool $create = true): ?Cart
    {
        if ($this->resolved) {
            return $this->resolved->load('items.product.images', 'items.colourway');
        }

        $user = auth()->user();

        if ($user) {
            $cart = Cart::firstOrNew(['user_id' => $user->id]);

            // Somebody who filled a bag as a guest and has only now signed in
            // has no bag of their own yet. Without this they would be told
            // their bag is empty while still carrying it, because "do not
            // create one" would have answered before the guest bag was seen.
            if (! $cart->exists && ! $create && ! $this->guestBagWaiting()) {
                return null;
            }

            if (! $cart->exists) {
                $cart->save();
            }

            $this->claimGuestBag($cart);

            return $this->resolved = $cart->load('items.product.images', 'items.colourway');
        }

        $token = request()->cookie(self::COOKIE);
        $cart  = $token ? Cart::where('token', $token)->first() : null;

        if (! $cart) {
            if (! $create) {
                return null;
            }

            $cart = Cart::create(['token' => Cart::newToken()]);
            Cookie::queue(Cookie::make(self::COOKIE, $cart->token, 60 * 24 * 30));
        }

        return $this->resolved = $cart->load('items.product.images', 'items.colourway');
    }

    /** Is there a guest bag with something in it waiting to be claimed? */
    protected function guestBagWaiting(): bool
    {
        $token = request()->cookie(self::COOKIE);

        if (! $token) {
            return false;
        }

        return Cart::where('token', $token)
            ->whereNull('user_id')
            ->whereHas('items')
            ->exists();
    }

    /**
     * A guest who signs in keeps what they were carrying.
     *
     * Quantities add rather than replace: both bags were deliberate.
     */
    protected function claimGuestBag(Cart $mine): void
    {
        $token = request()->cookie(self::COOKIE);

        if (! $token) {
            return;
        }

        $guest = Cart::where('token', $token)->whereNull('user_id')->first();

        if ($guest && $guest->id !== $mine->id) {
            $mine->load('items');
            $guest->load('items');
            $mine->absorb($guest);
        }

        Cookie::queue(Cookie::forget(self::COOKIE));
    }

    /* -------------------------------------------------------- changing it */

    /**
     * @return array{ok: bool, message: ?string}
     */
    public function add(Product $product, int $quantity = 1, ?Colourway $colourway = null): array
    {
        $quantity = max(1, $quantity);

        if ($product->status !== 'published') {
            return ['ok' => false, 'message' => __('That piece is not available.')];
        }

        $cart = $this->current();
        $line = $cart->items()
            ->where('product_id', $product->id)
            ->where('colourway_id', $colourway?->id)
            ->first();

        $wanted = ($line?->quantity ?? 0) + $quantity;
        $stock  = $product->stockFor($colourway);

        // Clamp rather than refuse: a shopper who asks for more than there is
        // should get everything there is, and be told.
        $clamped = false;

        if ($stock !== null && ! $product->backorder && $wanted > $stock) {
            $wanted  = $stock;
            $clamped = true;
        }

        if ($wanted < 1) {
            return ['ok' => false, 'message' => __('That piece is out of stock.')];
        }

        $line
            ? $line->update(['quantity' => $wanted])
            : $cart->items()->create([
                'product_id'   => $product->id,
                'colourway_id' => $colourway?->id,
                'quantity'     => $wanted,
            ]);

        $cart->touchActivity();

        return [
            'ok'      => true,
            'message' => $clamped
                ? __('Only :count left, so that is what went in.', ['count' => $wanted])
                : null,
        ];
    }

    public function setQuantity(CartItem $item, int $quantity): array
    {
        if ($quantity < 1) {
            $item->delete();

            return ['ok' => true, 'removed' => true, 'quantity' => 0, 'message' => null];
        }

        $stock   = $item->product?->stockFor($item->colourway);
        $clamped = false;

        if ($stock !== null && ! $item->product->backorder && $quantity > $stock) {
            $quantity = max(0, $stock);
            $clamped  = true;
        }

        if ($quantity < 1) {
            $item->delete();

            return ['ok' => true, 'removed' => true, 'quantity' => 0, 'message' => __('That piece is out of stock.')];
        }

        $item->update(['quantity' => $quantity]);
        $item->cart->touchActivity();

        return [
            'ok'       => true,
            'removed'  => false,
            'quantity' => $quantity,
            'message'  => $clamped ? __('Only :count left.', ['count' => $quantity]) : null,
        ];
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
    }

    /** How many pieces are in the bag — what the number on the bag icon means. */
    public function count(): int
    {
        $cart = $this->current(false);

        return $cart ? (int) $cart->items->sum('quantity') : 0;
    }

    /* ---------------------------------------------------------- coupons */

    /**
     * Try a code against the bag.
     *
     * Returns the reason it cannot be used, or null if it was applied. The
     * code is checked again when the order is placed — a coupon that runs out
     * between the bag and the payment must not be honoured because it was
     * valid ten minutes ago.
     */
    public function applyCoupon(string $code): ?string
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return 'Type a code first.';
        }

        $cart = $this->current();

        if (! $cart || $cart->items->isEmpty()) {
            return 'Your bag is empty.';
        }

        $coupon = Coupon::live()->where('code', $code)->first();

        if (! $coupon) {
            return 'That code is not one of ours, or it has expired.';
        }

        $totals = $this->totals($cart);
        $after = max(0, $totals->subtotal - $totals->offerTotal);

        if ($reason = $coupon->reasonItCannotApply($after, $cart->user_id)) {
            return $reason;
        }

        $cart->update(['coupon_code' => $coupon->code]);
        $cart->refresh();

        return null;
    }

    public function removeCoupon(): void
    {
        $this->current(false)?->update(['coupon_code' => null]);
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->update(['coupon_code' => null]);
    }

    /* ------------------------------------------------------------ totals */

    /**
     * What the bag costs, start to finish.
     *
     * Worked out in one place and returned whole, so the bag page, the
     * checkout and the order that gets written can never disagree about a
     * figure.
     */
    public function totals(?Cart $cart = null, ?string $pincode = null): CartTotals
    {
        $cart ??= $this->current(false);
        $t = new CartTotals();

        if (! $cart || $cart->items->isEmpty()) {
            return $t;
        }

        $lines = $cart->items->map(fn (CartItem $item) => [
            'key'       => (string) $item->id,
            'product'   => $item->product,
            'colourway' => $item->colourway,
            'quantity'  => (int) $item->quantity,
        ])->filter(fn ($line) => $line['product'] !== null)->values();

        $offer = $this->offers->apply($lines);

        $t->items      = $cart->items->sum('quantity');
        $t->subtotal   = $offer->subtotal;
        $t->offerTotal = $offer->discount;
        $t->offers     = $offer;

        if ($cart->coupon_code) {
            $coupon = Coupon::live()->where('code', $cart->coupon_code)->first();
            $after  = max(0, $t->subtotal - $t->offerTotal);

            if ($coupon && ! $coupon->reasonItCannotApply($after, $cart->user_id)) {
                $t->couponCode     = $coupon->code;
                $t->couponTotal    = $coupon->discountOn($after);
                $t->couponFreeShip = $coupon->type === 'free_shipping';
            }
        }

        $t->shippingTotal = $this->shippingFor($t, $pincode);
        $t->grandTotal    = round(max(0, $t->subtotal - $t->offerTotal - $t->couponTotal + $t->shippingTotal), 2);

        return $t;
    }

    public function freeShippingFrom(): float
    {
        return (float) Setting::get('free_shipping_from', config('shop.free_shipping_from', 2999));
    }

    public function flatShipping(): float
    {
        return (float) Setting::get('flat_rate', config('shop.flat_shipping', 99));
    }

    protected function shippingFor(CartTotals $t, ?string $pincode): float
    {
        $goods = max(0, $t->subtotal - $t->offerTotal - $t->couponTotal);

        if ($t->couponFreeShip) {
            return 0.0;
        }

        $zone = $pincode
            ? ShippingZone::active()->get()->first(fn (ShippingZone $z) => $z->covers($pincode))
            : null;

        if ($zone) {
            /*
             * A matching area has the last word, including when it says there
             * is no free delivery here at all. Falling back to the shop-wide
             * figure for a zone whose own is blank would quietly give free
             * delivery to exactly the places that cost most to reach — and the
             * admin already shows that blank as "Never free".
             */
            if ($zone->free_from === null) {
                return (float) $zone->rate;
            }

            return $goods >= (float) $zone->free_from ? 0.0 : (float) $zone->rate;
        }

        // Zero means free for everybody, which is what the setting promises.
        $threshold = $this->freeShippingFrom();

        return $threshold >= 0 && $goods >= $threshold ? 0.0 : $this->flatShipping();
    }
}
