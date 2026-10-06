<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $guarded = [];

    public const STATUSES = [
        'pending'          => 'Not dispatched',
        'manifested'       => 'Manifested',
        'picked'           => 'Picked up',
        'in_transit'       => 'In transit',
        'out_for_delivery' => 'Out for delivery',
        'delivered'        => 'Delivered',
        'undelivered'      => 'Attempted, not delivered',
        'rto'              => 'Returning to origin',
    ];

    protected function casts(): array
    {
        return [
            'timeline'     => 'array',
            'expected_on'  => 'date',
            'shipped_at'   => 'datetime',
            'delivered_at' => 'datetime',
            'polled_at'    => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function label(): string
    {
        return $this->status_label ?: (self::STATUSES[$this->status] ?? $this->status);
    }
}
