<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A saree.
 *
 * Price lives in two places and that is deliberate: the product carries the
 * design's price, and a colourway may override it when one shade costs more to
 * weave. Everything that needs a number asks priceFor(), so the rule is stated
 * once instead of being re-derived at every call site — which is how a cart
 * and a product page end up disagreeing.
 */
class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price'          => 'decimal:2',
            'sale_price'     => 'decimal:2',
            'cost_price'     => 'decimal:2',
            'sale_starts_at' => 'datetime',
            'sale_ends_at'   => 'datetime',
            'published_at'   => 'datetime',
            'track_stock'    => 'boolean',
            'backorder'      => 'boolean',
            'is_featured'    => 'boolean',
        ];
    }

    /* ---------------------------------------------------------------- links */

    public function colourways()
    {
        return $this->hasMany(Colourway::class)->orderBy('position');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    /** The product's own photographs — the ones not tied to a shade. */
    public function baseImages()
    {
        return $this->hasMany(ProductImage::class)->whereNull('colourway_id')->orderBy('position');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class);
    }

    /** Complete the look. */
    public function matches()
    {
        return $this->belongsToMany(Product::class, 'product_matches', 'product_id', 'match_id')
            ->withPivot('position')
            ->orderBy('product_matches.position');
    }

    public function offers()
    {
        return $this->belongsToMany(Offer::class);
    }

    /** Short films of this saree, shown at the foot of its page. */
    public function videos()
    {
        return $this->hasMany(Video::class)->where('is_visible', true)->orderBy('position');
    }

    /** Every film, hidden ones included — what the admin edits through. */
    public function allVideos()
    {
        return $this->hasMany(Video::class)->orderBy('position');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews()
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    /* --------------------------------------------------------------- scopes */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /**
     * Newest first, counting a saree with no publish date as new.
     *
     * "Leave empty to publish as soon as the status says so" is what the
     * admin promises, and a plain `latest('published_at')` breaks it: the
     * database sorts an empty date last, so the saree added this morning went
     * to the back of every row on the front page and the shop saw only the
     * ones it had added before.
     */
    public function scopeNewestFirst(Builder $query): Builder
    {
        return $query->orderByRaw('COALESCE(published_at, created_at) DESC')->orderByDesc('id');
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->where('track_stock', false)
            ->orWhere('stock', '>', 0)
            ->orWhere('backorder', true));
    }

    /* ---------------------------------------------------------------- money */

    /**
     * Is the sale price live right now?
     *
     * A sale with no dates runs until it is taken off; one with dates obeys
     * them. Asked here rather than inline so a scheduled sale cannot appear on
     * the product page and then fail to apply in the bag.
     */
    public function onSale(?Colourway $colourway = null): bool
    {
        $sale = $colourway?->sale_price ?? $this->sale_price;
        $full = $colourway?->price ?? $this->price;

        if (! $sale || (float) $sale <= 0 || (float) $sale >= (float) $full) {
            return false;
        }

        if ($this->sale_starts_at && $this->sale_starts_at->isFuture()) {
            return false;
        }

        if ($this->sale_ends_at && $this->sale_ends_at->isPast()) {
            return false;
        }

        return true;
    }

    /** What one unit costs today. */
    public function priceFor(?Colourway $colourway = null): float
    {
        $full = (float) ($colourway?->price ?? $this->price);

        return $this->onSale($colourway)
            ? (float) ($colourway?->sale_price ?? $this->sale_price)
            : $full;
    }

    /** What it would cost without the sale, for the struck-through figure. */
    public function fullPriceFor(?Colourway $colourway = null): float
    {
        return (float) ($colourway?->price ?? $this->price);
    }

    public function discountPercent(?Colourway $colourway = null): int
    {
        $full = $this->fullPriceFor($colourway);
        $now  = $this->priceFor($colourway);

        if ($full <= 0 || $now >= $full) {
            return 0;
        }

        return (int) round((($full - $now) / $full) * 100);
    }

    /* ---------------------------------------------------------------- stock */

    public function stockFor(?Colourway $colourway = null): ?int
    {
        if (! $this->track_stock) {
            return null;                       // null means "as many as they like"
        }

        return $colourway?->stock ?? $this->stock;
    }

    public function canOrder(int $quantity = 1, ?Colourway $colourway = null): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        $stock = $this->stockFor($colourway);

        return $stock === null || $this->backorder || $stock >= $quantity;
    }

    public function isLowStock(?Colourway $colourway = null): bool
    {
        $stock = $this->stockFor($colourway);

        return $stock !== null && $stock > 0 && $stock <= $this->low_stock_at;
    }

    /* ---------------------------------------------------------------- media */

    /**
     * The photographs to show, which change with the shade chosen.
     *
     * A colourway with its own set shows that set; one without falls back to
     * the design's, so a shop can photograph only the shades it has had time
     * to shoot without leaving gaps on the page.
     */
    /**
     * What the shop puts forward: the pictures of the saree itself.
     *
     * This is "Pictures of this saree" in the admin, and it is what the front
     * page and the cards show. A shade with photographs of its own shows
     * those instead, so a shop that has photographed every colour gets the
     * right one on the card a shopper presses.
     */
    public function imagesFor(?Colourway $colourway = null)
    {
        if ($colourway) {
            $own = $this->images->where('colourway_id', $colourway->id)->values();

            if ($own->isNotEmpty()) {
                return $own;
            }
        }

        $base = $this->images->whereNull('colourway_id')->values();

        return $base->isNotEmpty() ? $base : $this->images->values();
    }

    /**
     * What the saree's own page shows: the shade being looked at, then worn.
     *
     * Deliberately not the pictures of the saree itself. Those are what the
     * front page and the cards are made of, and a shopper who has just
     * pressed one of them does not need to arrive at a page led by the same
     * picture.
     *
     * So: the photographs of this shade, in the order the shop put them in,
     * and the saree worn at the end of them. A shade nobody has photographed
     * separately — most shades of most sarees — shows the worn picture and
     * nothing else, which is the one picture that is true of every shade.
     * A shade can be told to do without it, and then shows only its own.
     *
     * With no shade asked for it is the first one, because that is the shade
     * the page stands on: its price, its stock, and now its photographs.
     *
     * A saree with neither falls back to its own pictures, because a page
     * with no photograph on it is worse than a repeated one.
     */
    public function galleryFor(?Colourway $colourway = null)
    {
        $colourway ??= $this->colourways->first();

        $gallery = $colourway
            ? $this->images->where('colourway_id', $colourway->id)->values()
            : collect();

        /*
         * And the saree worn at the end of them, unless this shade has been
         * told to do without: a pomegranate saree under a picture of the
         * indigo one being worn tells the shopper the wrong thing, and only
         * the shop knows which shades those are.
         */
        if (($colourway?->show_worn_picture ?? true) && ($worn = $this->modelPhotograph())) {
            $gallery = $gallery->push($worn)->values();
        }

        return $gallery->isNotEmpty() ? $gallery : $this->imagesFor($colourway);
    }

    /**
     * The saree worn.
     *
     * Kept on the saree rather than in its gallery because it is one picture
     * with a job: it opens the saree's page, and it stands in for every shade
     * nobody has photographed separately — which is most shades of most
     * sarees, since nobody photographs a model in every colour they weave.
     */
    public function modelPhotograph(): ?ProductImage
    {
        return $this->model_image
            ? $this->aPhotograph($this->model_image, $this->name.', worn')
            : null;
    }

    /**
     * A path, dressed as a photograph.
     *
     * Not a row in product_images and never saved: this one belongs to the
     * saree itself, and everything that draws a photograph — the address, the
     * alt text — already knows how to read one of these.
     */
    private function aPhotograph(string $path, string $alt): ProductImage
    {
        $photograph = new ProductImage(['path' => $path, 'alt' => $alt]);
        $photograph->setRelation('product', $this);

        return $photograph;
    }

    public function firstImage(?Colourway $colourway = null): ?ProductImage
    {
        return $this->imagesFor($colourway)->first();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
