<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A saree added this morning, on the front page this morning.
 *
 * Two things stood between the two, and from the shop's side they looked like
 * one: "I add a saree, I tick Feature it, and the home page still shows the
 * old ones".
 *
 * The publish date box says "leave empty to publish as soon as the status
 * says so", and the shop leaves it empty — but a database sorts an empty date
 * last, so the newest saree went to the back of every row. And the featured
 * row was a sieve over the newest dozen rather than a question about which
 * sarees are featured, so ticking the box on anything older did nothing.
 */
class NewSareeOnTheFrontPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // A shop with a catalogue already, which is the only way this shows
        // up: with four sarees on the books, everything fits on the page.
        $this->seed(DemoSeeder::class);
    }

    public function test_a_saree_added_today_is_on_the_front_page(): void
    {
        $saree = $this->aNewSaree(['is_featured' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee($saree->name, false);
    }

    public function test_ticking_feature_it_puts_a_saree_on_the_front_page(): void
    {
        $saree = $this->aNewSaree(['is_featured' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee($saree->name, false);
    }

    /**
     * Including one that is not new.
     *
     * The shop features a saree it photographed in the spring; the front page
     * has to show it, however many have been added since.
     */
    public function test_featuring_an_older_saree_works_too(): void
    {
        // The shop has decided this one saree is the one to put forward.
        Product::query()->update(['is_featured' => false]);

        $saree = $this->aNewSaree([
            'name' => 'A Banarasi from the spring',
            'slug' => 'a-banarasi-from-the-spring',
            'is_featured' => true,
            'published_at' => now()->subYear(),
        ]);

        // Everything else is newer, so the newest-dozen pool cannot contain it.
        $this->assertFalse(
            Product::published()->newestFirst()->take(12)->get()->contains('id', $saree->id),
            'the fixture is wrong: this saree should be nowhere near the newest dozen',
        );

        $this->get('/')
            ->assertOk()
            ->assertSee($saree->name, false);
    }

    /**
     * And at the front of the listing, ahead of everything not featured.
     *
     * The shop's own order on /sarees is "the pieces it has chosen to put
     * forward, then the newest" — and the newest was going last, for the
     * same reason.
     */
    public function test_a_saree_added_today_is_at_the_front_of_the_listing(): void
    {
        $saree = $this->aNewSaree(['is_featured' => false]);

        $html = $this->get('/sarees')->assertOk()->getContent();

        $mine = strpos($html, $saree->name);

        $this->assertNotFalse($mine, 'it is not on the listing at all');

        $others = Product::published()
            ->where('id', '!=', $saree->id)
            ->where('is_featured', false)
            ->pluck('name');

        $this->assertNotEmpty($others);

        foreach ($others as $name) {
            $theirs = strpos($html, (string) $name);

            if ($theirs !== false) {
                $this->assertLessThan($theirs, $mine, "today's saree came after {$name}");
            }
        }
    }

    /** A saree the shop has not published yet still stays off the page. */
    public function test_a_draft_is_not_shown(): void
    {
        $saree = $this->aNewSaree(['is_featured' => true, 'status' => 'draft']);

        $this->get('/')->assertOk()->assertDontSee($saree->name, false);
    }

    /** One dated for next week waits until next week. */
    public function test_a_saree_dated_ahead_waits(): void
    {
        $saree = $this->aNewSaree([
            'name' => 'For the wedding season',
            'slug' => 'for-the-wedding-season',
            'is_featured' => true,
            'published_at' => now()->addWeek(),
        ]);

        $this->get('/')->assertOk()->assertDontSee($saree->name, false);
    }

    /**
     * A saree as the admin creates one: no publish date, because the box says
     * leaving it empty publishes it.
     */
    private function aNewSaree(array $attributes = []): Product
    {
        $saree = Product::create(array_merge([
            'name' => 'Kanjivaram added this morning',
            'slug' => 'kanjivaram-added-this-morning',
            'status' => 'published',
            'published_at' => null,
            'price' => 18500,
            'short_description' => 'Handwoven, added today.',
            'description' => 'Handwoven, added today.',
        ], $attributes));

        ProductImage::create([
            'product_id' => $saree->id,
            'path' => 'products/kanjivaram-indigo-1.jpg',
            'alt' => $saree->name,
            'position' => 0,
        ]);

        return $saree;
    }
}
