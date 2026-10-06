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

    /** What the shop shows: visible slides, in order. */
    public function slides()
    {
        return $this->hasMany(Slide::class)->where('is_visible', true)->orderBy('position');
    }

    /**
     * Every slide, hidden ones included. The admin edits through this one — a
     * slide switched off must still be findable, and creating through the
     * filtered relation above would quietly force it back on.
     */
    public function allSlides()
    {
        return $this->hasMany(Slide::class)->orderBy('position');
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
