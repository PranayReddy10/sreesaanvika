<?php

namespace App\Services\Payments;

use App\Models\Setting;

/**
 * Which gateway the shop is using, and the one an old payment went through.
 *
 * These are two different questions and answering them with the same value is
 * a way to lose money. A shop that moves from Razorpay to Cashfree on Tuesday
 * still has to refund Monday's orders through Razorpay, because that is who is
 * holding the money — so a refund asks for the gateway named on the payment
 * row, and only a new payment asks for the one the shop has chosen.
 */
class Gateways
{
    /** Every gateway the shop could choose, in the order the admin lists them. */
    public const ALL = [
        'razorpay' => 'Razorpay',
        'cashfree' => 'Cashfree',
    ];

    public const FALLBACK = 'razorpay';

    /**
     * The one chosen in the admin.
     *
     * Razorpay when nothing has been chosen: it is what this shop opened with,
     * and a setting nobody has touched must not move a shop's payments.
     */
    public static function chosen(): string
    {
        $chosen = (string) Setting::get('pay_gateway');

        return array_key_exists($chosen, self::ALL) ? $chosen : self::FALLBACK;
    }

    public function active(): Gateway
    {
        return $this->named(self::chosen());
    }

    /** By name — for a refund, where the question is who took the money. */
    public function named(?string $name): Gateway
    {
        return match ($name) {
            'cashfree' => app(Cashfree::class),
            default    => app(Razorpay::class),
        };
    }

    /** Can anything be paid online at all? */
    public function ready(): bool
    {
        return $this->active()->configured();
    }
}
