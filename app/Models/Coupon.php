<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A code the shopper types. Offers are the ones that need no code; this is for
 * the campaigns that do.
 */
class Coupon extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value'        => 'decimal:2',
            'min_spend'    => 'decimal:2',
            'max_discount' => 'decimal:2',
            'is_active'    => 'boolean',
            'starts_at'    => 'datetime',
            'ends_at'      => 'datetime',
        ];
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function redemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }

    /**
     * Why this code cannot be used, or null when it can.
     *
     * Returns the reason rather than a bare false so the shopper is told
     * which rule they fell foul of — "spend ₹500 more" is actionable where
     * "invalid coupon" is not.
     */
    public function reasonItCannotApply(float $subtotal, ?int $userId = null): ?string
    {
        if ($this->usage_limit !== null && $this->used >= $this->usage_limit) {
            return __('This code has been fully claimed.');
        }

        if ($this->min_spend !== null && $subtotal < (float) $this->min_spend) {
            return __('This code needs a basket of at least :amount.', [
                'amount' => '₹'.number_format((float) $this->min_spend, 2),
            ]);
        }

        if ($userId && $this->usage_limit_per_user !== null) {
            $mine = $this->redemptions()->where('user_id', $userId)->count();

            if ($mine >= $this->usage_limit_per_user) {
                return __('You have already used this code.');
            }
        }

        return null;
    }

    public function discountOn(float $subtotal): float
    {
        $off = match ($this->type) {
            'percent'       => $subtotal * ((float) $this->value / 100),
            'fixed'         => (float) $this->value,
            'free_shipping' => 0.0,
            default         => 0.0,
        };

        if ($this->max_discount !== null) {
            $off = min($off, (float) $this->max_discount);
        }

        return round(min($off, $subtotal), 2);
    }
}
