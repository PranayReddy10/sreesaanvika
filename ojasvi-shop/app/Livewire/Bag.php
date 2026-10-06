<?php

namespace App\Livewire;

use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\View\View;
use Livewire\Component;

/**
 * The bag page.
 *
 * Quantities change over the wire, not by navigating: the page is never
 * re-entered, so there is nothing for the back button to re-submit and nothing
 * for the browser's cache to restore wrongly. The first build of this shop
 * fought that bug for a week; this is the shape that does not have it.
 *
 * Every figure comes from CartService::totals(), so the bag, the checkout and
 * the order that is finally written cannot disagree.
 */
class Bag extends Component
{
    public string $coupon = '';

    public ?string $couponError = null;

    public ?string $notice = null;

    public function mount(CartService $bag): void
    {
        $this->coupon = (string) ($bag->current(false)?->coupon_code ?? '');
    }

    public function setQuantity(int $itemId, int $quantity): void
    {
        $item = $this->line($itemId);

        if (! $item) {
            return;
        }

        if ($quantity < 1) {
            $this->removeLine($itemId);

            return;
        }

        $result = app(CartService::class)->setQuantity($item, $quantity);

        // Say so when the shop could not give them what they asked for. The
        // silent clamp is what makes a bag feel broken.
        $this->notice = $result['message'] ?? null;

        $this->changed();
    }

    public function removeLine(int $itemId): void
    {
        $item = $this->line($itemId);

        if (! $item) {
            return;
        }

        $name = $item->product?->name;

        app(CartService::class)->remove($item);

        $this->notice = $name ? "{$name} taken out." : 'Taken out.';

        $this->changed();
    }

    public function applyCoupon(): void
    {
        $this->couponError = app(CartService::class)->applyCoupon($this->coupon);

        $this->changed();
    }

    public function clearCoupon(): void
    {
        app(CartService::class)->removeCoupon();

        $this->coupon = '';
        $this->couponError = null;

        $this->changed();
    }

    /**
     * Only a line in this shopper's own bag.
     *
     * The id comes from the browser, so it is never trusted to belong to them:
     * without this check, anyone could empty somebody else's bag by guessing.
     */
    private function line(int $itemId): ?CartItem
    {
        $cart = app(CartService::class)->current(false);

        if (! $cart) {
            return null;
        }

        return $cart->items->firstWhere('id', $itemId);
    }

    private function changed(): void
    {
        $bag = app(CartService::class);
        $bag->forget();

        $this->dispatch('bag-changed', count: $bag->count());
    }

    public function render(CartService $bag): View
    {
        $cart = $bag->current(false);

        return view('livewire.bag', [
            'cart'      => $cart,
            'totals'    => $bag->totals($cart),
            'threshold' => $bag->freeShippingFrom(),
        ]);
    }
}
