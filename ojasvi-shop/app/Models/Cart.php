<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A bag that outlives the page.
 *
 * A guest's is found by the token in their cookie; signing in claims it. The
 * bag never stores a price — it stores what was chosen, and the price is
 * worked out from the product each time. That is what stops a bag quietly
 * selling yesterday's price after a repricing.
 */
class Cart extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['last_active_at' => 'datetime'];
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function newToken(): string
    {
        return (string) Str::uuid();
    }

    public function touchActivity(): void
    {
        $this->forceFill(['last_active_at' => now()])->saveQuietly();
    }

    /**
     * Fold another bag into this one.
     *
     * Used when a guest signs in holding a bag and already had one. Quantities
     * add up rather than overwrite: both were deliberate choices.
     */
    public function absorb(Cart $other): void
    {
        foreach ($other->items as $item) {
            $mine = $this->items()
                ->where('product_id', $item->product_id)
                ->where('colourway_id', $item->colourway_id)
                ->first();

            if ($mine) {
                $mine->increment('quantity', $item->quantity);
                continue;
            }

            $this->items()->create([
                'product_id'   => $item->product_id,
                'colourway_id' => $item->colourway_id,
                'quantity'     => $item->quantity,
            ]);
        }

        $other->items()->delete();

        // A bag that has just changed hands is demonstrably being used. Without
        // this it keeps whatever activity it had — which for a brand new
        // account is none at all, and it then reads as abandoned the moment it
        // is created.
        $this->touchActivity();
        $other->delete();
        $this->load('items');
    }
}
