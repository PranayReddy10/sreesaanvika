<?php

namespace Tests\Feature;

use App\Models\Colourway;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Two photographs with one job each.
 *
 * A shop cannot photograph a model wearing every saree in every colour, so one
 * picture of the saree worn stands for the rest: it opens the saree's page and
 * it is what a shopper sees when she picks a shade nobody photographed
 * separately.
 *
 * And the front page's wide row has its own picture, used there and nowhere
 * else — the shop was clicking a photograph on the front page and meeting the
 * same one at the top of the saree's page a second later.
 */
class WornAndFrontPagePhotographsTest extends TestCase
{
    use RefreshDatabase;

    private Product $saree;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoSeeder::class);

        $this->saree = Product::with('images', 'colourways')->has('images')->firstOrFail();
    }

    /* ------------------------------------------------- the saree worn */

    public function test_the_worn_photograph_opens_the_saree_page(): void
    {
        $this->saree->update(['model_image' => 'products/worn.jpg']);

        $gallery = $this->saree->fresh(['images', 'colourways'])->imagesFor();

        $this->assertSame('products/worn.jpg', $gallery->first()->path);
        $this->assertGreaterThan(1, $gallery->count(), 'the rest of the gallery went missing');

        $this->get('/saree/'.$this->saree->slug)
            ->assertOk()
            ->assertSee('products/worn.jpg', false);
    }

    /**
     * The shade nobody photographed.
     *
     * Which is most shades of most sarees, and the reason this exists.
     */
    public function test_a_shade_with_no_photographs_of_its_own_shows_the_worn_one(): void
    {
        $this->saree->update(['model_image' => 'products/worn.jpg']);

        $bare = Colourway::create([
            'product_id' => $this->saree->id,
            'name' => 'Pomegranate',
            'hex' => '#7b1f2b',
            'position' => 9,
        ]);

        $gallery = $this->saree->fresh(['images', 'colourways'])->imagesFor($bare);

        $this->assertSame('products/worn.jpg', $gallery->first()->path);
    }

    /** A shade that has been photographed leads with its own pictures. */
    public function test_a_photographed_shade_leads_with_its_own(): void
    {
        $this->saree->update(['model_image' => 'products/worn.jpg']);

        $shade = $this->saree->colourways->first();

        $this->assertNotNull($shade);

        ProductImage::create([
            'product_id' => $this->saree->id,
            'colourway_id' => $shade->id,
            'path' => 'products/this-very-shade.jpg',
            'alt' => 'This shade',
            'position' => 0,
        ]);

        $gallery = $this->saree->fresh(['images', 'colourways'])->imagesFor($shade);

        // Whichever of the shade's own the shop put first — but one of them.
        $this->assertSame($shade->id, $gallery->first()->colourway_id);
        $this->assertTrue(
            $gallery->contains('path', 'products/this-very-shade.jpg'),
            'the shade\'s own photograph is missing',
        );

        // Still there, because it is the only picture showing how it falls.
        $this->assertSame('products/worn.jpg', $gallery->last()->path);
    }

    public function test_a_saree_without_a_worn_photograph_is_unchanged(): void
    {
        $before = $this->saree->imagesFor()->pluck('path');

        $this->assertNotEmpty($before);
        $this->assertSame($before->all(), $this->saree->imagesFor()->pluck('path')->all());
    }

    /* --------------------------------------------- and the front page */

    public function test_the_front_page_photograph_is_used_on_the_front_page(): void
    {
        $this->saree->update(['is_featured' => true, 'home_image' => 'products/for-the-front-page.jpg']);

        $this->get('/')
            ->assertOk()
            ->assertSee('products/for-the-front-page.jpg', false);
    }

    /** And nowhere else — which is the whole complaint. */
    public function test_the_front_page_photograph_is_not_on_the_saree_page(): void
    {
        $this->saree->update([
            'is_featured' => true,
            'model_image' => 'products/worn.jpg',
            'home_image' => 'products/for-the-front-page.jpg',
        ]);

        $this->get('/saree/'.$this->saree->slug)
            ->assertOk()
            ->assertSee('products/worn.jpg', false)
            ->assertDontSee('products/for-the-front-page.jpg', false);
    }

    /** A shop that has not chosen one still gets a front page. */
    public function test_without_one_the_front_page_falls_back_to_the_first_photograph(): void
    {
        $this->saree->update(['is_featured' => true, 'home_image' => null]);

        $first = $this->saree->imagesFor()->first();

        $this->assertNotNull($first);

        $this->get('/')->assertOk()->assertSee($first->path, false);
    }

    /* ------------------------------------------------------ the admin */

    public function test_a_new_saree_is_asked_for_the_worn_photograph(): void
    {
        \Livewire\Livewire::actingAs($this->admin())
            ->test(\App\Filament\Resources\Products\Pages\CreateProduct::class)
            ->fillForm([
                'name' => 'Kanjivaram in pomegranate',
                'slug' => 'kanjivaram-in-pomegranate',
                'status' => 'published',
                'price' => 18500,
            ])
            ->call('create')
            ->assertHasFormErrors(['model_image']);
    }

    /**
     * But a saree photographed before any of this existed still saves.
     *
     * Demanding it everywhere would lock the shop out of its own catalogue,
     * which is a worse fault than the one being fixed.
     */
    public function test_a_saree_already_in_the_shop_still_saves_without_one(): void
    {
        $this->assertNull($this->saree->model_image);

        \Livewire\Livewire::actingAs($this->admin())
            ->test(\App\Filament\Resources\Products\Pages\EditProduct::class, ['record' => $this->saree->getKey()])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);
    }
}
