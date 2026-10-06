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

    /**
     * Buy it now goes to the checkout; Add to bag does not.
     *
     * Both press the same form, and which one was pressed is the whole
     * difference. It was broken twice over: the page worked out which button
     * had been used by asking the browser what was focused, and a touchscreen
     * does not focus a button it is tapped with — and the post left out the
     * button's own name and value, so the shop was never told either way.
     * Buy it now put the saree in the bag and left the shopper standing there.
     */
    public function test_buy_it_now_is_answered_with_the_checkout(): void
    {
        $product = Product::published()->first();

        $this->postJson('/bag/add', ['product_id' => $product->id, 'then' => 'checkout'])
            ->assertOk()
            ->assertJsonPath('checkout', route('checkout'));

        $this->postJson('/bag/add', ['product_id' => $product->id])
            ->assertOk()
            ->assertJsonPath('checkout', route('bag'));
    }

    /**
     * The small photographs on the front page are doors, not decoration.
     *
     * They looked exactly like thumbnails and did nothing when pressed, which
     * is worse than showing no photograph at all. Each opens the saree on that
     * photograph.
     *
     * And they are the saree's own photographs, in the order the shop put them
     * in under Photographs — the same ones the cards on /sarees show. For an
     * afternoon this showed the first shade's instead, so a shade with
     * pictures of its own quietly replaced what the shop had chosen as the
     * saree's first picture.
     */
    public function test_the_extra_photographs_on_the_front_page_open_the_saree(): void
    {
        $saree = Product::published()->has('images', '>=', 2)->orderBy('id')->firstOrFail();

        $gallery = $saree->imagesFor();

        $html = $this->get('/')->assertOk()->getContent();

        // Only sarees the front page actually features are on it, so this
        // proves nothing unless one of them is the one examined.
        if (! str_contains($html, route('product', $saree))) {
            $this->markTestSkipped('this saree is not on the front page');
        }

        $second = $gallery->get(1);

        $this->assertNotNull($second, 'needs a second photograph to link to');

        /*
         * By id rather than by address: the seeder gives a shade and the
         * saree itself the same photograph file, so comparing what is on the
         * page by its URL passes whichever list the page took it from. The id
         * is the only thing that tells them apart — which is why this is the
         * test that catches the list being swapped, and a comparison of the
         * pictures on two pages is not.
         */
        $this->assertStringContainsString(
            route('product', [$saree, 'photo' => $second->id]),
            $html,
            'the extra photograph must link to the saree, at that photograph',
        );

        // And the page takes the instruction.
        $this->get(route('product', [$saree, 'photo' => $second->id]))
            ->assertOk()
            ->assertSee('photo: '.$second->id, false);
    }

    /**
     * Every slide holds the front page open, not only the first.
     *
     * The slides used to be the first one in the flow of the page with the
     * rest laid over it absolutely, so showing the second sent the first to
     * display:none and the section collapsed to nothing at all: the arrows
     * jumped up under the header and the sarees below rode up through the
     * photograph. Measured at 0 pixels tall in a browser before the fix and
     * 738 after, on every slide.
     *
     * Checked in the markup because the fault is in the layout, where this
     * suite cannot see — but it has exactly one cause, and this is it.
     */
    public function test_no_slide_is_laid_over_the_others(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $slides = \App\Models\Section::where('key', 'hero')->first()?->slides->count() ?? 0;

        $this->assertGreaterThan(1, $slides, 'the front page needs more than one slide to prove this');

        // One cell, every slide in it.
        $this->assertSame(
            $slides,
            substr_count($html, 'grid-area: 1 / 1'),
            'every slide must sit in the same grid cell',
        );

        $this->assertStringNotContainsString(
            'x-transition.opacity.duration.600ms" class="absolute inset-0"',
            $html,
            'a slide laid over the others cannot hold the section open',
        );
    }

    /**
     * The phone menu and the search sheet are moved out of the header.
     *
     * Both are written inside it and both cover the screen, but the header is
     * blurred — and backdrop-filter makes an element the containing block for
     * everything fixed inside it. Left there, "fixed inset-0" means the inside
     * of the header bar: the menu opened as a 72-pixel sliver with none of its
     * links in it, which on a phone is a menu button that does nothing.
     */
    public function test_the_phone_menu_is_not_trapped_inside_the_header(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(
            2,
            substr_count($html, 'x-teleport="body"'),
            'the phone menu and the search sheet must both be moved out of the blurred header',
        );
    }

    /**
     * The page hands the pressed button to the script.
     *
     * Checked in the markup because the rest of it is in the browser, where
     * this suite cannot go: without $event.submitter there is nothing to tell
     * the two buttons apart, and Buy it now quietly becomes Add to bag again.
     */
    public function test_the_saree_page_says_which_button_was_pressed(): void
    {
        $product = Product::published()->first();

        $this->get(route('product', $product->slug))
            ->assertOk()
            ->assertSee('addToBag($el, $event.submitter)', false);
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
