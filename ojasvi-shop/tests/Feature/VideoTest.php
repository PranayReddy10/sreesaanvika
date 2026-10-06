<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Section;
use App\Models\User;
use App\Models\Video;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Short films of a saree.
 *
 * Where they show, where they do not, and what the page gives the browser —
 * because every autoplay rule in the world is enforced by attributes on one
 * tag, and a missing `muted` means a film that silently never starts.
 */
class VideoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoSeeder::class);
    }

    private function aSaree(): Product
    {
        return Product::published()->firstOrFail();
    }

    /* --------------------------------------------------- where they appear */

    public function test_the_front_page_shows_the_reel(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Seen worn')
            ->assertSee('Draped three ways', false)
            ->assertSee('.webm', false);
    }

    public function test_a_saree_shows_its_own_films_at_the_foot_of_its_page(): void
    {
        $film = Video::whereNotNull('product_id')->firstOrFail();

        $this->get(route('product', $film->product->slug))
            ->assertOk()
            ->assertSee('See it worn')
            ->assertSee($film->title, false);
    }

    public function test_a_film_can_be_on_the_front_page_without_belonging_to_a_saree(): void
    {
        Video::query()->delete();

        Video::create([
            'title' => 'At the loom',
            'url' => 'https://cdn.example.in/loom.mp4',
            'on_home' => true,
        ]);

        $this->get('/')->assertOk()->assertSee('At the loom', false);
    }

    public function test_a_film_can_belong_to_a_saree_and_stay_off_the_front_page(): void
    {
        Video::query()->delete();

        $saree = $this->aSaree();

        Video::create([
            'product_id' => $saree->id,
            'title' => 'Only on its own page',
            'url' => 'https://cdn.example.in/one.mp4',
            'on_home' => false,
        ]);

        $this->get(route('product', $saree->slug))->assertOk()->assertSee('Only on its own page', false);
        $this->get('/')->assertOk()->assertDontSee('Only on its own page', false);
    }

    public function test_a_hidden_film_is_shown_nowhere(): void
    {
        Video::query()->update(['is_visible' => false]);

        $this->get('/')->assertOk()->assertDontSee('Draped three ways', false);

        foreach (Product::published()->pluck('slug') as $slug) {
            $this->get(route('product', $slug))->assertOk()->assertDontSee('See it worn');
        }
    }

    public function test_a_film_with_neither_a_file_nor_an_address_is_not_rendered(): void
    {
        Video::query()->delete();

        // Nothing to play, so nothing should be put on the page — an empty
        // <video> is a grey rectangle where a saree should be.
        Video::create(['title' => 'Nothing here', 'on_home' => true]);

        $this->get('/')->assertOk()->assertDontSee('<video', false);
    }

    public function test_the_reel_row_can_be_taken_off_the_front_page(): void
    {
        Section::where('key', 'reels')->update(['is_visible' => false]);

        $this->get('/')->assertOk()->assertDontSee('Seen worn');
    }

    public function test_the_shop_chooses_how_many_films_the_reel_holds(): void
    {
        Section::where('key', 'reels')->update(['settings' => ['limit' => 2]]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'data-od-video'));
    }

    /* ------------------------------------------- what the browser is given */

    public function test_a_film_is_muted_looping_and_inline_or_it_will_never_autoplay(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // Every browser refuses to start a film with sound, and iOS refuses to
        // play one inline without playsinline. Miss either and the shop simply
        // shows still frames.
        $this->assertStringContainsString('muted', $html);
        $this->assertStringContainsString('loop', $html);
        $this->assertStringContainsString('playsinline', $html);
    }

    public function test_nothing_downloads_until_it_is_nearly_on_screen(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // One film ready to go, the rest fetched only when scrolled to: four
        // films loading at once is a data bill the shopper did not agree to.
        $this->assertSame(1, substr_count($html, 'preload="metadata"'));
        $this->assertGreaterThan(0, substr_count($html, 'preload="none"'));
    }

    public function test_a_film_falls_back_to_the_sarees_own_photograph(): void
    {
        $saree = Product::published()->has('images')->firstOrFail();

        $film = Video::create([
            'product_id' => $saree->id,
            'url' => 'https://cdn.example.in/x.mp4',
            'on_home' => true,
        ]);

        // Rather than a black rectangle while it loads.
        $this->assertSame($saree->firstImage()->url, $film->posterUrl());
    }

    public function test_the_type_is_worked_out_from_the_file(): void
    {
        $this->assertSame('video/webm', (new Video(['path' => 'videos/a.webm']))->mime());
        $this->assertSame('video/mp4', (new Video(['path' => 'videos/a.mp4']))->mime());
        $this->assertSame('video/mp4', (new Video(['url' => 'https://cdn.example.in/a.MP4?v=2']))->mime());
        $this->assertSame('video/quicktime', (new Video(['url' => 'https://cdn.example.in/a.mov']))->mime());
    }

    /* ------------------------------------------------------------ the admin */

    public function test_the_shop_can_add_a_film_from_the_saree_itself(): void
    {
        $admin = User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        $saree = $this->aSaree();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\Products\RelationManagers\VideosRelationManager::class, [
                'ownerRecord' => $saree,
                'pageClass' => \App\Filament\Resources\Products\Pages\EditProduct::class,
            ])
            ->callTableAction('create', data: [
                'title' => 'Draped at the shop',
                'url' => 'https://cdn.example.in/new.mp4',
                'position' => 0,
                'is_visible' => true,
                'on_home' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('videos', [
            'product_id' => $saree->id,
            'title' => 'Draped at the shop',
        ]);

        $this->get(route('product', $saree->slug))->assertOk()->assertSee('Draped at the shop', false);
    }

    public function test_a_film_needs_somewhere_to_play_from(): void
    {
        $admin = User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\Videos\Pages\CreateVideo::class)
            ->fillForm(['title' => 'No film attached', 'position' => 0])
            ->call('create')
            ->assertHasFormErrors(['url']);
    }

    public function test_the_films_screen_opens(): void
    {
        $admin = User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        $this->actingAs($admin);

        $this->get('/admin/videos')->assertOk();
        $this->get('/admin/videos/create')->assertOk();
        $this->get('/admin/videos/' . Video::value('id') . '/edit')->assertOk();
    }

    public function test_deleting_a_saree_takes_its_films_with_it(): void
    {
        $film = Video::whereNotNull('product_id')->firstOrFail();

        $film->product->forceDelete();

        $this->assertDatabaseMissing('videos', ['id' => $film->id]);
    }

    public function test_the_upload_limit_offered_never_exceeds_what_the_server_takes(): void
    {
        $ceiling = \App\Filament\Shared\VideoFields::uploadCeiling();

        $this->assertGreaterThan(0, $ceiling);
        // A shop told it may upload 50 MB by a form on a server that stops at
        // 8 MB gets a blank error and no idea why.
        $this->assertLessThanOrEqual(
            (int) (8 * 1024),
            $ceiling,
            'The form must not promise more than PHP will accept.',
        );
    }
}
