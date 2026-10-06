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

    private function anAdmin(): User
    {
        return User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);
    }

    /**
     * One of the real films in tests/Fixtures, handed over as an upload.
     *
     * A real one, with the bytes an encoder actually wrote. A fake file would
     * prove nothing about a check whose whole job is to read the bytes.
     */
    private function aFilm(string $name): \Illuminate\Http\Testing\File
    {
        return new \Illuminate\Http\Testing\File(
            $name,
            fopen(__DIR__.'/../Fixtures/films/'.$name, 'rb'),
        );
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

        // Either kind counts towards the limit — they share the rail.
        $this->assertSame(
            2,
            substr_count($html, 'data-od-video') + substr_count($html, 'data-od-reel'),
        );
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

    /* ------------------------------------------ films that do not play */

    /**
     * A film in a format browsers will not play is refused at the door.
     *
     * This is the commonest way a shop ends up with a black rectangle on its
     * front page: a saree filmed on an iPhone is HEVC unless the phone has
     * been told otherwise, it uploads perfectly, and it plays for the one
     * person who filmed it and for nobody else.
     */
    public function test_a_film_the_browser_cannot_play_is_refused(): void
    {
        $form = \Livewire\Livewire::actingAs($this->anAdmin())
            ->test(\App\Filament\Resources\Videos\Pages\CreateVideo::class)
            ->fillForm([
                'title' => 'Filmed on a phone',
                'path' => $this->aFilm('hevc.mp4'),
                'position' => 0,
            ])
            ->call('create')
            ->assertHasFormErrors(['path']);

        // The reason is named, so this cannot pass because the upload was
        // refused for something else entirely — a size, a mime type — while
        // what it is actually about goes unchecked.
        $this->assertStringContainsString('HEVC', implode(' ', $form->errors()->get('data.path')));

        $this->assertDatabaseMissing('videos', ['title' => 'Filmed on a phone']);
    }

    public function test_an_ordinary_film_is_accepted(): void
    {
        \Livewire\Livewire::actingAs($this->anAdmin())
            ->test(\App\Filament\Resources\Videos\Pages\CreateVideo::class)
            ->fillForm([
                'title' => 'Filmed properly',
                'path' => $this->aFilm('h264.mp4'),
                'position' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('videos', ['title' => 'Filmed properly']);
    }

    public function test_a_webm_is_never_questioned(): void
    {
        \Livewire\Livewire::actingAs($this->anAdmin())
            ->test(\App\Filament\Resources\Videos\Pages\CreateVideo::class)
            ->fillForm([
                'title' => 'A webm',
                'path' => $this->aFilm('vp9.webm'),
                'position' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    /** What the parser makes of real files, rather than of the extension. */
    public function test_what_is_inside_a_film_is_read_not_guessed(): void
    {
        $films = __DIR__.'/../Fixtures/films/';

        $this->assertSame('avc1', \App\Support\Film::videoCodec($films.'h264.mp4'));
        $this->assertSame('hev1', \App\Support\Film::videoCodec($films.'hevc.mp4'));

        $this->assertNull(\App\Support\Film::unplayableCodec($films.'h264.mp4'));
        $this->assertSame('HEVC', \App\Support\Film::unplayableCodec($films.'hevc.mp4'));

        // A WebM is not this format at all, and holds nothing unplayable.
        $this->assertNull(\App\Support\Film::videoCodec($films.'vp9.webm'));
        $this->assertNull(\App\Support\Film::unplayableCodec($films.'vp9.webm'));

        // Nothing readable, and nothing to say about it. A file this cannot
        // make sense of is never refused on a guess.
        $this->assertNull(\App\Support\Film::unplayableCodec($films.'nothing-here.mp4'));
        $this->assertNull(\App\Support\Film::unplayableCodec(__DIR__.'/VideoTest.php'));
    }

    /**
     * A film that has stopped working says so where the shop will see it.
     *
     * Both of these look identical on the page — a black rectangle — and
     * identical in the admin's list too, unless it is asked.
     */
    public function test_the_admin_is_told_when_a_film_has_stopped_working(): void
    {
        \Illuminate\Support\Facades\Storage::disk('public')
            ->put('videos/real.mp4', file_get_contents(__DIR__.'/../Fixtures/films/h264.mp4'));
        \Illuminate\Support\Facades\Storage::disk('public')
            ->put('videos/iphone.mp4', file_get_contents(__DIR__.'/../Fixtures/films/hevc.mp4'));

        $this->assertNull((new Video(['path' => 'videos/real.mp4']))->problem());
        $this->assertSame('The file is missing', (new Video(['path' => 'videos/gone.mp4']))->problem());
        $this->assertStringContainsString(
            'HEVC',
            (string) (new Video(['path' => 'videos/iphone.mp4']))->problem(),
        );

        // Not ours to judge: Instagram plays its own, and a film on somebody
        // else's bucket cannot be read from here.
        $this->assertNull((new Video(['url' => 'https://www.instagram.com/reel/C8xYzExAbCd/']))->problem());
        $this->assertNull((new Video(['url' => 'https://cdn.example.in/a.mp4']))->problem());
    }

    /**
     * Nothing on the page is ever a bare video element.
     *
     * A <video> with no frame decoded paints flat black — the element itself,
     * which no amount of styling behind it changes. So there is always a still
     * or a panel underneath, and the film is faded in once there is something
     * to show.
     */
    public function test_a_film_still_loading_is_not_a_black_rectangle(): void
    {
        Video::query()->delete();

        Video::create([
            'title' => 'Not of any saree',
            'path' => 'videos/kanjivaram-indigo.webm',
            'on_home' => true,
            'is_visible' => true,
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('od-video-rest', $html, 'a film with nothing behind it');
        $this->assertStringContainsString('od-video-film', $html);
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

    /* ------------------------------------------------------ Instagram reels */

    public function test_an_instagram_link_is_understood_however_it_was_copied(): void
    {
        // All of these are the same post. The address copied out of the app
        // carries a tracking query, sometimes the account name, and says reel,
        // reels, p or tv depending on where it was copied from.
        $same = [
            'https://www.instagram.com/reel/C8xYz_1AbCd/',
            'https://www.instagram.com/reels/C8xYz_1AbCd',
            'https://instagram.com/p/C8xYz_1AbCd/?utm_source=ig_web_copy_link',
            'https://www.instagram.com/tv/C8xYz_1AbCd/',
            'https://www.instagram.com/ojasvidrapes/reel/C8xYz_1AbCd/?igsh=MXY%3D',
        ];

        foreach ($same as $url) {
            $this->assertSame(
                'C8xYz_1AbCd',
                (new Video(['url' => $url]))->instagramCode(),
                "Did not understand: {$url}",
            );
        }
    }

    public function test_something_that_is_not_a_reel_is_not_treated_as_one(): void
    {
        foreach ([
            'https://www.instagram.com/ojasvidrapes/',   // an account, not a post
            'https://cdn.example.in/film.mp4',
            'https://www.youtube.com/watch?v=abc',
            '',
        ] as $url) {
            $this->assertNull((new Video(['url' => $url]))->instagramCode(), $url);
        }
    }

    public function test_a_reel_shows_as_a_still_and_fetches_nothing_from_instagram(): void
    {
        Video::query()->delete();

        Video::create([
            'title' => 'From our Instagram',
            'url' => 'https://www.instagram.com/reel/C8xYz_1AbCd/',
            'on_home' => true,
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        /*
         * The promise this is here to keep: a page with four reels on it must
         * make no request to Instagram as it opens. Their embed brings scripts
         * and cookies, and it costs a shopper on a slow line more than the
         * whole rest of the page.
         */
        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringNotContainsString('instagram.com/embed.js', $html);
        $this->assertStringNotContainsString('cdninstagram', $html);

        // The address is on the page for the tap to use, and nothing more.
        $this->assertStringContainsString('data-od-reel', $html);
        $this->assertStringContainsString('instagram.com/reel/C8xYz_1AbCd/embed/', $html);
    }

    public function test_an_uploaded_film_is_still_played_by_the_shop_itself(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // The two kinds live side by side: ours autoplays, Instagram's waits
        // for a tap. Neither turns into the other.
        $this->assertStringContainsString('data-od-video', $html);
        $this->assertStringContainsString('data-od-reel', $html);
    }

    public function test_a_reel_can_belong_to_a_saree_too(): void
    {
        Video::query()->delete();

        $saree = $this->aSaree();

        Video::create([
            'product_id' => $saree->id,
            'title' => 'Worn at a wedding',
            'url' => 'https://www.instagram.com/reel/C8xYz_1AbCd/',
            'on_home' => false,
        ]);

        $this->get(route('product', $saree->slug))
            ->assertOk()
            ->assertSee('Worn at a wedding', false)
            ->assertSee('data-od-reel', false);
    }

    public function test_a_reel_takes_the_sarees_photograph_as_its_still(): void
    {
        $saree = Product::published()->has('images')->firstOrFail();

        $reel = Video::create([
            'product_id' => $saree->id,
            'url' => 'https://www.instagram.com/reel/C8xYz_1AbCd/',
        ]);

        // Instagram does not hand out the cover frame, so without this a reel
        // would be a blank rectangle until somebody tapped it.
        $this->assertSame($saree->firstImage()->url, $reel->posterUrl());
    }

    public function test_a_page_link_is_refused_with_a_reason(): void
    {
        $admin = User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\Videos\Pages\CreateVideo::class)
            ->fillForm([
                'title' => 'A YouTube page',
                // A page, not a film: it would show a shopper nothing at all.
                'url' => 'https://www.youtube.com/watch?v=abc',
                'position' => 0,
            ])
            ->call('create')
            ->assertHasFormErrors(['url']);
    }

    public function test_an_instagram_link_is_accepted(): void
    {
        $admin = User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\Videos\Pages\CreateVideo::class)
            ->fillForm([
                'title' => 'Pasted from the app',
                'url' => 'https://www.instagram.com/reel/C8xYz_1AbCd/?igsh=MXY%3D',
                'position' => 0,
                'is_visible' => true,
                'on_home' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('videos', ['title' => 'Pasted from the app']);
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
