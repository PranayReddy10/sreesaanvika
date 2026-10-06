<?php

namespace App\Services;

/**
 * Every figure the bag shows, worked out once and passed around whole.
 */
class CartTotals
{
    public int $items = 0;
    public float $subtotal = 0.0;
    public float $offerTotal = 0.0;
    public float $couponTotal = 0.0;
    public bool $couponFreeShip = false;
    public ?string $couponCode = null;
    public float $shippingTotal = 0.0;
    public float $grandTotal = 0.0;
    public ?OfferResult $offers = null;

    public function isEmpty(): bool
    {
        return $this->items === 0;
    }

    public function savings(): float
    {
        return round($this->offerTotal + $this->couponTotal, 2);
    }

    /** What is still to spend before delivery stops being charged. */
    public function awayFromFreeShipping(float $threshold): float
    {
        $goods = max(0, $this->subtotal - $this->offerTotal - $this->couponTotal);

        return round(max(0, $threshold - $goods), 2);
    }
}
