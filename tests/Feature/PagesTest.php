<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The pages that are words rather than sarees.
 *
 * They used to be five boxes at the bottom of the Settings form, and a shop
 * that wanted a sixth page — a size guide, something about the weavers — had
 * nowhere at all to put it.
 */
class PagesTest extends TestCase
{
    use RefreshDatabase;

    private function anAdmin(): User
    {
        return User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);
    }

    public function test_the_pages_the_shop_came_with_are_there_and_written(): void
    {
        foreach (['story', 'contact', 'shipping', 'returns', 'terms', 'privacy'] as $slug) {
            $this->assertTrue(Page::where('slug', $slug)->where('is_fixed', true)->exists(), $slug);

            $this->get("/page/{$slug}")->assertOk();
        }
    }

    public function test_the_shop_can_write_a_page_of_its_own(): void
    {
        \Livewire\Livewire::actingAs($this->anAdmin())
            ->test(\App\Filament\Resources\Pages\Pages\CreatePage::class)
            ->fillForm([
                'title' => 'How to drape a Nivi',
                'slug' => 'how-to-drape-a-nivi',
                'blurb' => 'Six yards, nine pleats, ten minutes.',
                'body' => "**Start at the waist**\n\nTuck the plain end in at the navel.",
                'is_visible' => true,
                'in_footer' => true,
                'position' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->get('/page/how-to-drape-a-nivi')
            ->assertOk()
            ->assertSee('How to drape a Nivi')
            ->assertSee('Tuck the plain end in at the navel.')
            // The two stars are a heading, not two stars.
            ->assertSee('Start at the waist')
            ->assertDontSee('**Start at the waist**');

        // Linked where the shop asked for it, and offered to Google.
        $this->get('/')->assertOk()->assertSee('How to drape a Nivi');
        $this->get('/sitemap.xml')->assertOk()->assertSee('/page/how-to-drape-a-nivi');
    }

    public function test_a_page_that_is_not_published_is_not_there_at_all(): void
    {
        Page::create([
            'title' => 'Half written', 'slug' => 'half-written',
            'body' => 'Not ready.', 'is_visible' => false,
        ]);

        $this->get('/page/half-written')->assertNotFound();
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('half-written');
    }

    /**
     * The legal four and the two the shop links to by name stay put.
     *
     * A returns page that can be deleted is a merchant account that can be
     * closed, and the footer and the checkout link to these by name.
     */
    public function test_a_page_the_shop_came_with_cannot_be_deleted(): void
    {
        $returns = Page::where('slug', 'returns')->firstOrFail();

        $this->assertFalse($returns->delete());
        $this->assertTrue(Page::where('slug', 'returns')->exists());

        // Including when several are deleted at once, which is how a button
        // that is merely hidden gets got round. (The admin has no bulk delete
        // on this screen at all, because Filament's own one empties the rows
        // with a single query, and a query delete goes round the model and
        // every rule in it.)
        Page::all()->each->delete();
        $this->assertSame(6, Page::count());
    }

    public function test_a_page_the_shop_wrote_can_be_deleted(): void
    {
        $page = Page::create(['title' => 'Temporary', 'slug' => 'temporary', 'body' => 'x']);

        $this->assertTrue((bool) $page->delete());
        $this->assertFalse(Page::where('slug', 'temporary')->exists());
    }

    public function test_the_saree_page_shows_what_the_returns_page_says(): void
    {
        $this->seed(DemoSeeder::class);

        Page::where('slug', 'returns')->update(['body' => 'Nothing comes back after a fortnight.']);

        $product = \App\Models\Product::published()->firstOrFail();

        $this->get(route('product', $product->slug))
            ->assertOk()
            ->assertSee('Nothing comes back after a fortnight.');
    }
}
