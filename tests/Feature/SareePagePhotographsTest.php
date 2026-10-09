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
 * Which photograph belongs on which page.
 *
 * "Pictures of this saree" are what the shop puts forward: the front page and
 * the cards are made of them. A shopper who has just pressed one of them does
 * not need to arrive at a page led by the same picture — so the saree's own
 * page shows the saree worn, and then the shade she is looking at.
 *
 * A shop cannot photograph a model wearing every saree in every colour, which
 * is why one worn picture stands for all of them.
 */
class SareePagePhotographsTest extends TestCase
{
    use RefreshDatabase;

    private Product $saree;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoSeeder::class);

        $this->saree = Product::with('images', 'colourways')->has('images')->firstOrFail();
    }

    /* ------------------------------------------------ the saree's page */

    public function test_the_page_opens_on_the_saree_worn(): void
    {
        $this->saree->update(['model_image' => 'products/worn.jpg']);

        $this->get('/saree/'.$this->saree->slug)
            ->assertOk()
            ->assertSee('products/worn.jpg', false);
    }

    /**
     * And does not show the pictures the shopper pressed to get here.
     *
     * This is the whole complaint: the front page shows a photograph, you
     * click it, and there it is again at the top of the saree's page.
     */
    /**
     * And does not show the pictures the shopper pressed to get here.
     *
     * This is the whole complaint: the front page shows a photograph, you
     * click it, and there it is again at the top of the saree's page.
     */
    public function test_the_page_does_not_repeat_the_pictures_of_the_saree(): void
    {
        [$saree] = $this->aSareeWithDistinctPictures();

        $this->get('/saree/'.$saree->slug)
            ->assertOk()
            ->assertSee('products/the-saree-worn.jpg', false)
            ->assertDontSee('products/for-the-front-page.jpg', false);
    }

    public function test_choosing_a_shade_shows_the_worn_picture_and_that_shade(): void
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

        $gallery = $this->saree->fresh(['images', 'colourways'])->galleryFor($shade);

        $this->assertSame('products/worn.jpg', $gallery->first()->path);
        $this->assertTrue($gallery->contains('path', 'products/this-very-shade.jpg'));

        // And nothing from the pictures of the saree itself.
        $this->assertSame(
            [],
            $gallery->whereNotNull('colourway_id')->where('colourway_id', '!=', $shade->id)->pluck('path')->all(),
        );
    }

    /** The shade nobody photographed, which is most of them. */
    public function test_a_shade_with_no_photographs_shows_the_worn_picture(): void
    {
        $this->saree->update(['model_image' => 'products/worn.jpg']);

        $bare = Colourway::create([
            'product_id' => $this->saree->id,
            'name' => 'Pomegranate',
            'hex' => '#7b1f2b',
            'position' => 9,
        ]);

        $gallery = $this->saree->fresh(['images', 'colourways'])->galleryFor($bare);

        $this->assertSame(['products/worn.jpg'], $gallery->pluck('path')->all());
    }

    /**
     * A saree with neither still has a page with photographs on it.
     *
     * Every saree in the shop was photographed before any of this existed,
     * and none of them has a worn picture yet.
     */
    public function test_a_saree_with_no_worn_picture_falls_back_to_its_own(): void
    {
        $this->assertNull($this->saree->model_image);

        $gallery = $this->saree->galleryFor();

        $this->assertNotEmpty($gallery);
        $this->assertSame($this->saree->imagesFor()->pluck('path')->all(), $gallery->pluck('path')->all());

        $this->get('/saree/'.$this->saree->slug)
            ->assertOk()
            ->assertSee($gallery->first()->path, false);
    }

    /* --------------------------------------------------- the front page */

    public function test_the_front_page_shows_the_pictures_of_the_saree(): void
    {
        $this->saree->update(['is_featured' => true, 'model_image' => 'products/worn.jpg']);

        $first = $this->saree->imagesFor()->first();

        $this->get('/')
            ->assertOk()
            ->assertSee($first->path, false);
    }

    /** The round trip, which is how the shop found this. */
    public function test_pressing_a_picture_on_the_front_page_opens_a_different_picture(): void
    {
        [$saree] = $this->aSareeWithDistinctPictures();

        $this->get('/')
            ->assertOk()
            ->assertSee('products/for-the-front-page.jpg', false)
            ->assertDontSee('products/the-saree-worn.jpg', false);

        $this->get('/saree/'.$saree->slug)
            ->assertOk()
            ->assertSee('products/the-saree-worn.jpg', false)
            ->assertDontSee('products/for-the-front-page.jpg', false);
    }

    /* ------------------------------------------------------- the admin */

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
        \Livewire\Livewire::actingAs($this->admin())
            ->test(\App\Filament\Resources\Products\Pages\EditProduct::class, ['record' => $this->saree->getKey()])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    /**
     * A saree whose every photograph is a different file.
     *
     * The demo catalogue gives a shade the same files as the saree itself,
     * which is fine for a shop and useless here: a test asking "is this
     * picture on the page" could not tell which of the two it had found.
     *
     * @return array{0: Product, 1: Colourway}
     */
    private function aSareeWithDistinctPictures(): array
    {
        $saree = Product::create([
            'name' => 'Kanjivaram for the test',
            'slug' => 'kanjivaram-for-the-test',
            'status' => 'published',
            'is_featured' => true,
            'price' => 18500,
            'short_description' => 'One of a kind.',
            'description' => 'One of a kind.',
            'model_image' => 'products/the-saree-worn.jpg',
        ]);

        // What the front page and the cards are made of.
        ProductImage::create([
            'product_id' => $saree->id,
            'path' => 'products/for-the-front-page.jpg',
            'alt' => 'The saree itself',
            'position' => 0,
        ]);

        $shade = Colourway::create([
            'product_id' => $saree->id,
            'name' => 'Indigo',
            'hex' => '#27356b',
            'position' => 0,
            'stock' => 4,
        ]);

        ProductImage::create([
            'product_id' => $saree->id,
            'colourway_id' => $shade->id,
            'path' => 'products/the-indigo-shade.jpg',
            'alt' => 'In indigo',
            'position' => 0,
        ]);

        return [$saree->fresh(['images', 'colourways']), $shade];
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);
    }
}
