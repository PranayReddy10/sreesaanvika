<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'confirmed_at'    => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }
}
