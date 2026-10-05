<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * What delivery costs where, and whether cash on delivery is offered there.
 * Matched on a pincode prefix, so "500" covers Hyderabad without listing every
 * pincode in the city.
 */
class ShippingZone extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'pincodes'    => 'array',
            'rate'        => 'decimal:2',
            'free_from'   => 'decimal:2',
            'cod_allowed' => 'boolean',
            'is_active'   => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('position');
    }

    public function covers(string $pincode): bool
    {
        $prefixes = $this->pincodes ?: [];

        // A zone with no prefixes is the catch-all, which is what makes "rest
        // of India" expressible without listing the country.
        if (! $prefixes) {
            return true;
        }

        foreach ($prefixes as $prefix) {
            if (str_starts_with($pincode, (string) $prefix)) {
                return true;
            }
        }

        return false;
    }
}
