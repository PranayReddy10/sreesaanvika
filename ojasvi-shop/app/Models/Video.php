<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A short film of a saree.
 *
 * Where it shows is two separate questions: on_home puts it in the front
 * page's reel, product_id puts it at the foot of that saree's page, and most
 * films want both.
 */
class Video extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean', 'on_home' => 'boolean'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true)->orderBy('position')->orderBy('id');
    }

    /** The front page's reel — whether or not the film is of a particular saree. */
    public function scopeForHome(Builder $query): Builder
    {
        return $query->where('on_home', true);
    }

    /** Where the film actually is. Null means there is nothing to play. */
    public function src(): ?string
    {
        if ($this->path) {
            return Storage::disk('public')->url($this->path);
        }

        $url = trim((string) $this->url);

        return $url !== '' ? $url : null;
    }

    public function posterUrl(): ?string
    {
        if ($this->poster) {
            return Storage::disk('public')->url($this->poster);
        }

        // A saree's own photograph stands in, so the page shows the piece
        // rather than a black rectangle while the film loads.
        return $this->product?->firstImage()?->url;
    }

    /**
     * What the browser should be told it is about to play.
     *
     * Guessed from the extension rather than stored: a shop should not have to
     * know what a MIME type is to put a film on its own website.
     */
    public function mime(): string
    {
        $extension = Str::lower(pathinfo(
            (string) ($this->path ?: parse_url((string) $this->url, PHP_URL_PATH)),
            PATHINFO_EXTENSION,
        ));

        return match ($extension) {
            'webm' => 'video/webm',
            'ogv', 'ogg' => 'video/ogg',
            'mov' => 'video/quicktime',
            default => 'video/mp4',
        };
    }

    public function label(): string
    {
        return $this->title ?: ($this->product?->name ?: 'Untitled');
    }
}
