<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\CartService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The shop, as a shopper meets it.
 *
 * Opening every page is most of this on purpose: a Blade template is only
 * checked when it renders, and a saree page that throws is a sale lost in
 * silence.
 */
class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoSeeder::class);
    }

    public function test_the_front_page_shows_the_shop(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('OJASVI', false)
            ->assertSee(Product::published()->first()->name, false);
    }

    public function test_the_listing_shows_every_published_saree(): void
    {
        $this->get('/sarees')
            ->assertOk()
            ->assertSee((string) Product::published()->count(), false);
    }

    public function test_the_listing_can_be_searched_and_narrowed(): void
    {
        $this->get('/sarees?q=kanjivaram')->assertOk()->assertSee('Kanjivaram', false);

        // A word nothing matches must empty the listing rather than ignore it.
        $this->get('/sarees?q=qwertyuiop')->assertOk()->assertSee('Nothing matches that');

        $this->get('/sarees?fabric=linen')->assertOk()->assertSee('Linen in soft blush');
        $this->get('/sarees?sort=price-low')->assertOk();
        $this->get('/sarees?on=offer')->assertOk();
        $this->get('/sarees?min=5000&max=8000')->assertOk();
    }

    public function test_a_saree_page_opens_with_its_shades(): void
    {
        $product = Product::published()->has('colourways')->first();

        $this->get("/saree/{$product->slug}")
            ->assertOk()
            ->assertSee($product->name, false)
            ->assertSee($product->colourways->first()->name, false);
    }

    public function test_a_draft_saree_is_not_public(): void
    {
        $product = Product::published()->first();
        $product->update(['status' => 'draft']);

        $this->get("/saree/{$product->slug}")->assertNotFound();
    }

    public function test_looking_at_a_saree_counts_a_view_without_touching_the_record(): void
    {
        $product = Product::published()->first();
        $views = $product->views;
        $changed = $product->updated_at;

        $this->get("/saree/{$product->slug}")->assertOk();

        $product->refresh();

        $this->assertSame($views + 1, $product->views);
        $this->assertEquals($changed, $product->updated_at, 'A view is not an edit.');
    }

    public function test_a_saree_can_be_added_to_the_bag(): void
    {
        // One with stock to spare, so this is testing the bag and not the
        // clamp — the clamp has its own test in CartServiceTest.
        $product = Product::published()->has('colourways')->where('stock', '>=', 6)->firstOrFail();

        $this->post('/bag/add', [
            'product_id' => $product->id,
            'colourway_id' => $product->colourways->first()->id,
            'quantity' => 2,
        ])->assertRedirect('/bag');

        $this->assertSame(2, app(CartService::class)->count());

        $this->get('/bag')->assertOk()->assertSee($product->name, false);
    }

    public function test_adding_answers_json_when_json_is_asked_for(): void
    {
        $product = Product::published()->first();

        $this->postJson('/bag/add', ['product_id' => $product->id])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('count', 1);
    }

    public function test_a_shade_from_another_saree_is_ignored(): void
    {
        $product = Product::published()->has('colourways')->first();
        $other = Product::published()->has('colourways')->whereKeyNot($product->id)->first();

        $this->post('/bag/add', [
            'product_id' => $product->id,
            // A shade that belongs to a different design: taken as none rather
            // than trusted, or a bag could be made to hold an impossible line.
            'colourway_id' => $other->colourways->first()->id,
        ])->assertRedirect('/bag');

        $this->assertNull(app(CartService::class)->current()->items->first()->colourway_id);
    }

    public function test_adding_is_answered_with_a_redirect_so_back_cannot_add_twice(): void
    {
        $product = Product::published()->first();

        // Post, Redirect, Get — the shape that makes the browser's back button
        // harmless. This is the bug that dogged the shop's first build.
        $this->post('/bag/add', ['product_id' => $product->id])
            ->assertStatus(302)
            ->assertRedirect('/bag');
    }

    public function test_checkout_sends_an_empty_bag_back(): void
    {
        $this->get('/checkout')->assertRedirect('/bag');
    }

    public function test_the_word_pages_open(): void
    {
        foreach (['returns', 'shipping', 'terms', 'privacy', 'story', 'contact'] as $slug) {
            $this->get("/page/{$slug}")->assertOk();
        }

        $this->get('/page/not-a-page')->assertNotFound();
    }

    public function test_an_order_can_be_tracked_with_the_number_and_the_contact(): void
    {
        $order = \App\Models\Order::first();

        $this->post('/track', ['number' => $order->number, 'contact' => $order->email])
            ->assertOk()
            ->assertSee($order->number, false);
    }

    public function test_an_order_number_alone_does_not_show_an_address(): void
    {
        $order = \App\Models\Order::first();

        $this->post('/track', ['number' => $order->number, 'contact' => 'someone@else.test'])
            ->assertOk()
            ->assertSee('We could not find that one');
    }
}
