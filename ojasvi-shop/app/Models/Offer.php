<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Buy 2 Get 1 Free, and quantity breaks.
 *
 * No code to type: the shop picks the eligible pieces and the bag works the
 * rest out. The cheapest unit goes free, because that is the promise a
 * "buy two get one free" banner makes, and a shopper who is given the dearest
 * one free will notice the day they are not.
 */
class Offer extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tiers'          => 'array',
            'repeats'        => 'boolean',
            'applies_to_all' => 'boolean',
            'is_active'      => 'boolean',
            'starts_at'      => 'datetime',
            'ends_at'        => 'datetime',
        ];
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function categories()
    {
        // Named explicitly: Laravel would guess "category_offer" from the two
        // model names in alphabetical order, and the table is offer_category.
        return $this->belongsToMany(Category::class, 'offer_category');
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('position');
    }

    /** How many pieces make one round of the offer. */
    public function groupSize(): int
    {
        return max(1, (int) $this->buy) + max(1, (int) $this->get);
    }

    /** Does this offer cover that piece? */
    public function covers(Product $product): bool
    {
        if ($this->applies_to_all) {
            return true;
        }

        if ($this->relationLoaded('products') && $this->products->contains('id', $product->id)) {
            return true;
        }

        if (! $this->relationLoaded('products') && $this->products()->whereKey($product->id)->exists()) {
            return true;
        }

        $categoryIds = $this->relationLoaded('categories')
            ? $this->categories->pluck('id')
            : $this->categories()->pluck('categories.id');

        if ($categoryIds->isEmpty()) {
            return false;
        }

        return $product->categories->pluck('id')->intersect($categoryIds)->isNotEmpty();
    }

    /**
     * The quantity breaks, cleaned up and sorted.
     *
     * Sorted by quantity so the richest break a bag qualifies for is simply
     * the last one that fits, rather than whichever the shop happened to type
     * first.
     */
    public function tierList(): array
    {
        $tiers = collect($this->tiers ?? [])
            ->map(fn ($t) => [
                'qty'     => (int) ($t['qty'] ?? 0),
                'percent' => (float) ($t['percent'] ?? 0),
            ])
            ->filter(fn ($t) => $t['qty'] > 1 && $t['percent'] > 0)
            ->sortBy('qty')
            ->values();

        return $tiers->all();
    }

    public function headlineText(): string
    {
        if ($this->headline) {
            return $this->headline;
        }

        if ($this->kind === 'tiers') {
            return __('The more you take, the less each one costs');
        }

        return 100 === (int) $this->percent
            ? __('Buy :buy, get :get free', ['buy' => $this->buy, 'get' => $this->get])
            : __('Buy :buy, get :get at :percent% off', [
                'buy' => $this->buy, 'get' => $this->get, 'percent' => (int) $this->percent,
            ]);
    }
}
