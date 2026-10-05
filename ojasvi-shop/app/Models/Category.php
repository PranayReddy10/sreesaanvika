<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Optional. A shop selling one kind of thing has nothing to categorise, and
 * the storefront hides every trace of categories when none are visible — the
 * lesson of the WordPress build, built in rather than bolted on.
 */
class Category extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('position');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function offers()
    {
        return $this->belongsToMany(Offer::class, 'offer_category');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true)->orderBy('position');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
