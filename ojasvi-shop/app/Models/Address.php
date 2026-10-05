<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** The shape copied onto an order, so the order never points back here. */
    public function toSnapshot(): array
    {
        return $this->only([
            'name', 'phone', 'line1', 'line2', 'landmark',
            'city', 'state', 'pincode', 'country',
        ]);
    }

    public function oneLine(): string
    {
        return collect([$this->line1, $this->line2, $this->landmark, $this->city, $this->state, $this->pincode])
            ->filter()
            ->implode(', ');
    }
}
