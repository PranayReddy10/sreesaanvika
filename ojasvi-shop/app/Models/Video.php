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

    /* ------------------------------------------------ what kind of film */

    /**
     * Instagram's code for this reel, or null if this is not one.
     *
     * Takes whatever the shop pasted. The address copied out of the app
     * carries a tracking query, sometimes the account name, and may say reel,
     * reels, p or tv depending on where it was copied from — all of which mean
     * the same post, and none of which a shop should have to think about.
     */
    public function instagramCode(): ?string
    {
        $url = trim((string) $this->url);

        if ($url === '' || ! preg_match(
            '#instagram\.com/(?:[\w.]+/)?(?:reels?|p|tv)/([A-Za-z0-9_-]{5,})#i',
            $url,
            $m,
        )) {
            return null;
        }

        return $m[1];
    }

    /** Is this played by Instagram rather than by us? */
    public function isEmbed(): bool
    {
        return $this->path === null && $this->instagramCode() !== null;
    }

    /**
     * The address of Instagram's own player.
     *
     * Nothing is fetched from it until somebody taps the film. Instagram's
     * embed brings its own scripts and its own cookies, and loading four of
     * them on the front page would undo every promise the shop makes about
     * not calling on anybody else as a page opens.
     */
    public function embedUrl(): ?string
    {
        $code = $this->instagramCode();

        return $code ? "https://www.instagram.com/reel/{$code}/embed/" : null;
    }

    public function watchUrl(): ?string
    {
        $code = $this->instagramCode();

        return $code ? "https://www.instagram.com/reel/{$code}/" : null;
    }

    /**
     * Where the film actually is.
     *
     * Only for films this shop plays itself. An Instagram reel has no such
     * address — Instagram plays it — so this is null and isEmbed() is true.
     */
    public function src(): ?string
    {
        if ($this->path) {
            return Storage::disk('public')->url($this->path);
        }

        if ($this->isEmbed()) {
            return null;
        }

        $url = trim((string) $this->url);

        return $url !== '' ? $url : null;
    }

    /** Is there anything at all to show for this row? */
    public function playable(): bool
    {
        return $this->src() !== null || $this->isEmbed();
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
