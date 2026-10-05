<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A band of the homepage. Kept in the database so the shop can reorder its own
 * front page without a developer.
 */
class Section extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['settings' => 'array', 'is_visible' => 'boolean'];
    }

    public function slides()
    {
        return $this->hasMany(Slide::class)->where('is_visible', true)->orderBy('position');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true)->orderBy('position');
    }

    public function setting(string $key, mixed $fallback = null): mixed
    {
        return data_get($this->settings, $key, $fallback);
    }
}
