<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What a shopper thought of a saree.
 *
 * The shop could already show reviews and moderate them; there was simply no
 * way for anybody to write one.
 */
class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoSeeder::class);
    }

    /** Always the same one: published() has no order of its own. */
    private function aSaree(): Product
    {
        return Product::published()->orderBy('id')->firstOrFail();
    }

    private function words(array $extra = []): array
    {
        return array_merge([
            'rating' => 5,
            'title'  => 'Wore it to my sister’s wedding',
            'body'   => 'The colour is exactly as it looks here, and it falls beautifully.',
            'name'   => 'Lakshmi',
            'email'  => 'lakshmi@example.in',
        ], $extra);
    }

    public function test_a_shopper_can_write_one(): void
    {
        $saree = $this->aSaree();

        $this->post(route('review.store', $saree->slug), $this->words())
            ->assertRedirect()
            ->assertSessionHas('review');

        $this->assertDatabaseHas('reviews', [
            'product_id' => $saree->id,
            'name'       => 'Lakshmi',
            'rating'     => 5,
        ]);
    }

    /** Nothing goes up until somebody at the shop has read it. */
    public function test_it_does_not_appear_on_the_shop_until_it_is_approved(): void
    {
        $saree = $this->aSaree();

        $this->post(route('review.store', $saree->slug), $this->words([
            'title' => 'A thing nobody has read yet',
        ]));

        $this->get(route('product', $saree->slug))
            ->assertOk()
            ->assertDontSee('A thing nobody has read yet');

        Review::where('title', 'A thing nobody has read yet')->update(['is_approved' => true]);

        $this->get(route('product', $saree->slug))
            ->assertOk()
            ->assertSee('A thing nobody has read yet');
    }

    /**
     * The verified badge is earned rather than claimed.
     *
     * Matched on the address the order was placed with, because most people
     * buy as a guest and come back weeks later to say what they thought.
     */
    public function test_someone_who_actually_bought_it_is_marked_as_such(): void
    {
        $order = Order::where('payment_status', 'paid')->whereHas('items')->firstOrFail();
        $bought = $order->items()->firstOrFail();
        $saree = Product::findOrFail($bought->product_id);

        $this->post(route('review.store', $saree->slug), $this->words([
            'email' => $order->email,
        ]));

        $this->assertDatabaseHas('reviews', [
            'product_id'  => $bought->product_id,
            'order_id'    => $order->id,
            'is_verified' => true,
        ]);
    }

    public function test_a_stranger_is_not_marked_as_having_bought_it(): void
    {
        $saree = $this->aSaree();

        $this->post(route('review.store', $saree->slug), $this->words(['email' => 'nobody@example.in']));

        $this->assertDatabaseHas('reviews', [
            'product_id'  => $saree->id,
            'is_verified' => false,
            'order_id'    => null,
        ]);
    }

    public function test_the_same_person_cannot_write_about_the_same_saree_twice(): void
    {
        $saree = $this->aSaree();

        // A customer of this test's own, because one the seeder made may
        // already have written about this saree — which is the very thing
        // being tested, and would pass for the wrong reason.
        $customer = User::create([
            'name' => 'Meera', 'email' => 'meera-'.uniqid().'@example.in',
            'password' => 'long-enough-for-this',
        ]);

        $this->actingAs($customer)
            ->post(route('review.store', $saree->slug), $this->words())
            ->assertSessionHas('review');

        $this->actingAs($customer)
            ->post(route('review.store', $saree->slug), $this->words(['title' => 'Again']))
            ->assertSessionHas('review_error');

        $this->assertSame(1, Review::where('product_id', $saree->id)->where('user_id', $customer->id)->count());
    }

    public function test_a_review_needs_a_rating_and_something_to_say(): void
    {
        $saree = $this->aSaree();

        $this->post(route('review.store', $saree->slug), $this->words(['rating' => null, 'body' => 'Nice']))
            ->assertSessionHasErrors(['rating', 'body']);

        $this->assertSame(0, Review::where('product_id', $saree->id)->where('name', 'Lakshmi')->count());
    }

    /** The field no human sees, which is the whole point of it. */
    public function test_something_filling_in_every_box_is_turned_away(): void
    {
        $saree = $this->aSaree();

        $this->post(route('review.store', $saree->slug), $this->words(['website' => 'http://buy-handbags.example']))
            ->assertSessionHasErrors('website');
    }

    public function test_the_form_is_on_the_saree_page(): void
    {
        $saree = $this->aSaree();

        // The heading rather than the button, whose wording depends on whether
        // anybody has written about this one yet.
        $this->get(route('product', $saree->slug))
            ->assertOk()
            ->assertSee('What did you think of it?')
            ->assertSee(route('review.store', $saree->slug), false);
    }

    public function test_a_saree_nobody_has_written_about_asks_to_be_the_first(): void
    {
        $quiet = Product::published()->whereDoesntHave('approvedReviews')->orderBy('id')->firstOrFail();

        $this->get(route('product', $quiet->slug))
            ->assertOk()
            ->assertSee('Be the first to say something');
    }
}
