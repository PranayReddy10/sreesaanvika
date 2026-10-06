<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per attempt. A shopper who fails twice and succeeds once leaves
 * three rows and one paid order, which is what makes a disputed payment
 * answerable months later.
 */
class Payment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount'  => 'decimal:2',
            'payload' => 'array',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
