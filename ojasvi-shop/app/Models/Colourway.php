<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One shade of a design. Not a product of its own — the same saree, woven in
 * another colour, with its own photographs.
 */
class Colourway extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price'      => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_visible' => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }
}
