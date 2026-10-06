<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A page of words: Our story, Delivery, Returns, or anything the shop writes.
 *
 * Six of them are fixed, meaning the shop can rewrite them but not delete them
 * or change their address. The footer and the checkout link to those by name,
 * and Razorpay will not approve a shop whose returns page can be taken down.
 * Everything else a shop adds is entirely its own.
 */
class Page extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'is_fixed'   => 'boolean',
            'in_footer'  => 'boolean',
        ];
    }

    /**
     * The six the shop came with cannot be deleted.
     *
     * Enforced here rather than only by hiding the button, because a button
     * that is not shown is not a rule — selecting several rows and emptying
     * them in one go would still have taken the returns page down, and with
     * it the shop's standing with its payment gateway.
     */
    protected static function booted(): void
    {
        static::deleting(fn (Page $page) => $page->is_fixed ? false : null);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true)->orderBy('position')->orderBy('id');
    }

    public function scopeInFooter(Builder $query): Builder
    {
        return $query->visible()->where('in_footer', true);
    }

    /**
     * What a page says, for somewhere that is not the page itself.
     *
     * The saree page shows the returns policy under a heading of its own, and
     * should show what the returns page says rather than a second copy of it
     * that drifts.
     */
    public static function says(string $slug): string
    {
        return (string) static::query()->where('slug', $slug)->value('body');
    }
}
