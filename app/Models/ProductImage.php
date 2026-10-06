<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function colourway()
    {
        return $this->belongsTo(Colourway::class);
    }

    /** A full URL, whether the file is stored locally or on a bucket. */
    public function getUrlAttribute(): string
    {
        return Storage::disk(config('filesystems.default'))->url($this->path);
    }
}
