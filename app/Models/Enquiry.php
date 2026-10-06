<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Somebody who wrote in from the contact page.
 */
class Enquiry extends Model
{
    protected $table = 'enquiries';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['answered_at' => 'datetime'];
    }

    public function scopeWaiting(Builder $query): Builder
    {
        return $query->whereNull('answered_at');
    }

    public function isAnswered(): bool
    {
        return $this->answered_at !== null;
    }

    /** The order it is about, if the number given matches one. */
    public function order(): ?Order
    {
        return $this->order_number
            ? Order::firstWhere('number', trim($this->order_number))
            : null;
    }
}
