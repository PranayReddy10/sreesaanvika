<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $guarded = [];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function colourway()
    {
        return $this->belongsTo(Colourway::class);
    }

    /**
     * One unit's price, read from the product every time.
     *
     * Never cached on the row. A cart that remembers a price will go on
     * charging it long after the shop has changed its mind.
     */
    public function unitPrice(): float
    {
        return $this->product?->priceFor($this->colourway) ?? 0.0;
    }

    public function fullUnitPrice(): float
    {
        return $this->product?->fullPriceFor($this->colourway) ?? 0.0;
    }

    public function lineTotal(): float
    {
        return round($this->unitPrice() * $this->quantity, 2);
    }

    public function name(): string
    {
        return $this->product?->name ?? __('Removed piece');
    }
}
