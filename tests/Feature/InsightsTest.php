<?php

namespace Tests\Feature;

use App\Filament\Pages\Insights;
use App\Models\NewsletterSubscriber;
use App\Models\Search;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * What the shop can learn about itself, and the list it owns.
 */
class InsightsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoSeeder::class);
    }

    private function asAdmin(): User
    {
        $admin = User::create([
            'name' => 'OJASVI', 'email' => 'admin@example.test',
            'password' => 'secret-for-tests', 'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        return $admin;
    }

    /* ------------------------------------------------------- what is asked */

    public function test_a_search_is_written_down_with_how_many_it_found(): void
    {
        $this->get('/sarees?q=kanjivaram')->assertOk();

        $search = Search::firstWhere('normalised', 'kanjivaram');

        $this->assertNotNull($search);
        $this->assertGreaterThan(0, $search->results);
    }

    public function test_a_search_that_found_nothing_is_the_one_worth_keeping(): void
    {
        $this->get('/sarees?q=' . urlencode('kalamkari dupatta'))->assertOk();

        $this->assertDatabaseHas('searches', ['normalised' => 'kalamkari dupatta', 'results' => 0]);
    }

    public function test_the_same_words_typed_differently_are_counted_together(): void
    {
        $this->get('/sarees?q=' . urlencode('Pure  Silk'))->assertOk();
        $this->get('/sarees?q=' . urlencode('pure silk'))->assertOk();

        $this->assertSame(2, Search::where('normalised', 'pure silk')->count());
    }

    public function test_paging_through_results_is_one_search_not_four(): void
    {
        $this->get('/sarees?q=silk')->assertOk();
        $this->get('/sarees?q=silk&page=2')->assertOk();
        $this->get('/sarees?q=silk&page=3')->assertOk();

        $this->assertSame(1, Search::where('normalised', 'silk')->count());
    }

    public function test_browsing_without_searching_records_nothing(): void
    {
        $this->get('/sarees')->assertOk();
        $this->get('/sarees?fabric=linen')->assertOk();

        $this->assertSame(0, Search::count());
    }

    public function test_a_broken_search_box_never_breaks_the_listing(): void
    {
        // Longer than the column will take. The page still has to render.
        $this->get('/sarees?q=' . str_repeat('a', 400))->assertOk();

        $this->assertSame(0, Search::count());
    }

    /* ----------------------------------------------------------- the page */

    public function test_the_analysis_page_opens_and_counts_paid_orders_only(): void
    {
        $this->asAdmin();

        $this->get('/admin/insights')->assertOk();

        $page = Livewire::test(Insights::class)->instance();
        $headline = $page->headline();

        $paid = \App\Models\Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $page->since())
            ->sum('grand_total');

        $this->assertEquals(round((float) $paid, 2), round($headline['taken'], 2));
    }

    public function test_the_period_buttons_change_what_is_counted(): void
    {
        $this->asAdmin();

        $component = Livewire::test(Insights::class);

        $week = $component->instance()->tap(fn ($p) => $p->period = '7')->headline()['taken'];
        $year = $component->instance()->tap(fn ($p) => $p->period = '365')->headline()['taken'];

        $this->assertGreaterThanOrEqual($week, $year, 'A year must include the week inside it.');
    }

    public function test_a_customer_cannot_read_the_shop_figures(): void
    {
        $this->actingAs(User::where('is_admin', false)->firstOrFail());

        $this->get('/admin/insights')->assertForbidden();
    }

    /* ------------------------------------------------------------ the list */

    public function test_somebody_can_join_the_list_and_leave_it_again(): void
    {
        $this->post('/newsletter', ['email' => 'reader@example.in'])
            ->assertRedirect()
            ->assertSessionHas('bag');

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'reader@example.in',
            'unsubscribed_at' => null,
        ]);

        $this->get(\App\Http\Controllers\NewsletterController::leaveUrl('reader@example.in'))
            ->assertOk()
            ->assertSee('Done');

        $this->assertNotNull(NewsletterSubscriber::firstWhere('email', 'reader@example.in')->unsubscribed_at);
    }

    public function test_one_address_cannot_be_used_to_remove_another(): void
    {
        $this->post('/newsletter', ['email' => 'reader@example.in']);

        // A guessed address with a guessed token.
        $this->get('/newsletter/leave?email=reader@example.in&token=' . str_repeat('a', 32))
            ->assertOk()
            ->assertSee('did not work');

        $this->assertNull(NewsletterSubscriber::firstWhere('email', 'reader@example.in')->unsubscribed_at);
    }

    public function test_joining_twice_does_not_make_two_rows(): void
    {
        $this->post('/newsletter', ['email' => 'reader@example.in']);
        $this->post('/newsletter', ['email' => 'READER@example.in']);

        $this->assertSame(1, NewsletterSubscriber::count());
    }

    public function test_somebody_who_left_can_come_back(): void
    {
        $this->post('/newsletter', ['email' => 'reader@example.in']);
        $this->get(\App\Http\Controllers\NewsletterController::leaveUrl('reader@example.in'));

        $this->post('/newsletter', ['email' => 'reader@example.in']);

        $this->assertNull(NewsletterSubscriber::firstWhere('email', 'reader@example.in')->unsubscribed_at);
    }

    public function test_the_honeypot_turns_away_what_is_not_a_person(): void
    {
        $this->post('/newsletter', [
            'email' => 'robot@example.in',
            'website' => 'http://cheap-watches.example',
        ])->assertSessionHasErrors('website');

        $this->assertSame(0, NewsletterSubscriber::count());
    }

    public function test_the_shop_can_take_its_own_list_away_with_it(): void
    {
        $this->asAdmin();

        $this->post('/newsletter', ['email' => 'reader@example.in']);

        $this->get('/admin/newsletter-subscribers')->assertOk()->assertSee('reader@example.in');
    }
}
