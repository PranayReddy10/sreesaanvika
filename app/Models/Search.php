<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One line per search. No user, no address, no session — this records what the
 * shop was asked for, never who asked.
 */
class Search extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /**
     * Write down what was searched for.
     *
     * Never allowed to be the reason a listing fails to render — a full disk
     * or a locked table must cost the shop a statistic, not a page.
     */
    public static function record(string $term, int $results): void
    {
        $term = trim($term);

        if ($term === '' || mb_strlen($term) > 120) {
            return;
        }

        try {
            static::create([
                'term'       => $term,
                'normalised' => static::normalise($term),
                'results'    => $results,
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Quietly. A search box that breaks the page is worse than one
            // whose statistics have a gap in them.
        }
    }

    public static function normalise(string $term): string
    {
        $term = mb_strtolower(trim($term));
        $term = preg_replace('/\s+/u', ' ', $term) ?? $term;

        return mb_substr($term, 0, 120);
    }
}
