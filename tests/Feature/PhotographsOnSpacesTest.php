<?php

namespace Tests\Feature;

use App\Support\PhotographUrl;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Tests\TestCase;

/**
 * What the admin does when the photographs are kept somewhere else.
 *
 * The day this shop's photographs moved to DigitalOcean and the files had not
 * been copied up yet, every Image box in the admin came up empty — Filament
 * asks the disk whether each stored file is there and drops the ones it
 * cannot confirm — and because the box is required, Save then refused,
 * including for the photograph that had just been uploaded. From the
 * shopkeeper's side: "after uploading, when I save, the images disappear".
 */
class PhotographsOnSpacesTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $toDelete = [];

    protected function tearDown(): void
    {
        foreach ($this->toDelete as $directory) {
            \Illuminate\Support\Facades\File::deleteDirectory($directory);
        }

        parent::tearDown();
    }

    /** A photograph the disk cannot find is still the shop's photograph. */
    public function test_the_form_does_not_ask_the_bucket_whether_each_file_is_there(): void
    {
        $this->assertFalse(FileUpload::make('path')->shouldFetchFileInformation());
    }

    /** And neither does a list of thumbnails, a request per row. */
    public function test_a_thumbnail_does_not_ask_either(): void
    {
        $this->assertFalse(ImageColumn::make('path')->shouldCheckFileExistence());
    }

    /**
     * The whole of it, through the form the shop actually uses.
     *
     * Nothing is on the disk, which is a shop that has switched to a Space
     * and not copied its files up. Saving must leave the saree exactly as it
     * was rather than throwing its photographs away.
     */
    public function test_saving_a_saree_whose_files_are_missing_keeps_its_photographs(): void
    {
        $this->seed(\Database\Seeders\DemoSeeder::class);

        // An empty disk: every path in the database points at nothing.
        Storage::fake('public');

        $admin = \App\Models\User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        $saree = \App\Models\Product::has('images')->firstOrFail();
        $before = $saree->images()->pluck('path', 'id')->all();

        $this->assertNotEmpty($before, 'needs a saree with photographs');

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\Products\Pages\EditProduct::class, ['record' => $saree->getKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($before, $saree->fresh()->images()->pluck('path', 'id')->all());
    }

    /* ----------------------------------------- showing them in the admin */

    /**
     * The grey "Loading" bar.
     *
     * An upload box does not show its picture with an <img> tag — it fetches
     * it, so it can draw and crop it — and a browser polices a fetch across
     * domains where it does not police an <img>. A shop on a Space therefore
     * had photographs that were perfect for the shopper and never finished
     * loading for the shopkeeper. The admin fetches from this shop's own
     * address instead, and this shop fetches from the Space.
     */
    public function test_on_a_bucket_the_admin_fetches_the_picture_from_this_shop(): void
    {
        config(['filesystems.disks.public' => ['driver' => 's3', 'bucket' => 'ojasvi']]);

        $this->assertSame(
            url('photograph-preview/products/kanjivaram-indigo-1.jpg'),
            PhotographUrl::forTheAdmin('public', 'products/kanjivaram-indigo-1.jpg'),
        );
    }

    /** On this server's own disk, nothing changes. */
    public function test_on_this_server_it_is_the_ordinary_address(): void
    {
        Storage::fake('public');

        $this->assertStringNotContainsString(
            'photograph-preview',
            PhotographUrl::forTheAdmin('public', 'products/one.jpg'),
        );
    }

    public function test_the_preview_hands_the_file_to_an_admin(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/one.jpg', 'the bytes of a saree');

        $admin = \App\Models\User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->get('/photograph-preview/products/one.jpg');

        $response->assertOk()->assertHeader('content-type', 'image/jpeg');

        // Named after the photograph, not after the route: the box reads the
        // name off the end of the address.
        $this->assertStringContainsString('one.jpg', (string) $response->headers->get('content-disposition'));
        $this->assertSame('the bytes of a saree', $response->streamedContent());
    }

    public function test_it_is_not_for_customers_or_strangers(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/one.jpg', 'the bytes of a saree');

        $customer = \App\Models\User::create([
            'name' => 'Lakshmi', 'email' => 'lakshmi@example.in',
            'password' => 'long-enough-for-this',
        ]);

        $this->actingAs($customer)->get('/photograph-preview/products/one.jpg')->assertForbidden();

        auth()->logout();

        $this->get('/photograph-preview/products/one.jpg')->assertRedirect('/sign-in');
    }

    public function test_it_will_not_climb_out_of_the_uploads_folder(): void
    {
        Storage::fake('public');

        $admin = \App\Models\User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        // Written out, and written in a way that reaches the route intact.
        $this->actingAs($admin)->get('/photograph-preview/../../.env')->assertNotFound();
        $this->actingAs($admin)->get('/photograph-preview/products/..%2F..%2F.env')->assertNotFound();
        $this->actingAs($admin)->get('/photograph-preview/products/nothing-here.jpg')->assertNotFound();
    }

    /* ------------------------------------- copying what is already here */

    public function test_the_copy_refuses_when_the_shop_is_not_on_a_space(): void
    {
        $this->artisan('ojasvi:photos-to-spaces')
            ->expectsOutputToContain('not keeping its photographs on a Space')
            ->assertFailed();
    }

    public function test_it_copies_everything_up_and_can_be_run_twice(): void
    {
        [$here, $space] = $this->aShopWithPhotographs([
            'products/one.jpg' => 'the first saree',
            'products/two.jpg' => 'the second saree',
            'videos/draped.mp4' => 'a film',
        ]);

        $this->artisan('ojasvi:photos-to-spaces')
            ->expectsOutputToContain('3 copied up, 0 already there')
            ->assertSuccessful();

        $this->assertSame('the first saree', file_get_contents($space.'/products/one.jpg'));
        $this->assertSame('a film', file_get_contents($space.'/videos/draped.mp4'));

        // Run again: nothing is sent twice, which is what makes it safe to
        // use after a copy that stopped half way.
        $this->artisan('ojasvi:photos-to-spaces')
            ->expectsOutputToContain('0 copied up, 3 already there')
            ->assertSuccessful();
    }

    /** A half-finished file is sent again, not skipped for having the name. */
    public function test_a_file_that_arrived_short_is_sent_again(): void
    {
        [$here, $space] = $this->aShopWithPhotographs(['products/one.jpg' => 'the whole saree']);

        \Illuminate\Support\Facades\File::ensureDirectoryExists($space.'/products');
        file_put_contents($space.'/products/one.jpg', 'half');

        $this->artisan('ojasvi:photos-to-spaces')->assertSuccessful();

        $this->assertSame('the whole saree', file_get_contents($space.'/products/one.jpg'));
    }

    public function test_pretending_changes_nothing(): void
    {
        [$here, $space] = $this->aShopWithPhotographs(['products/one.jpg' => 'the first saree']);

        $this->artisan('ojasvi:photos-to-spaces', ['--pretend' => true])
            ->expectsOutputToContain('would go up')
            ->assertSuccessful();

        $this->assertFileDoesNotExist($space.'/products/one.jpg');
    }

    /**
     * A shop with some photographs on its own disk and an empty Space.
     *
     * @param  array<string, string>  $files
     * @return array{0: string, 1: string}
     */
    private function aShopWithPhotographs(array $files): array
    {
        $base = storage_path('framework/testing/a-shop-'.\Illuminate\Support\Str::random(6));
        $here = $base.'/app/public';
        $space = $base.'/a-space';

        \Illuminate\Support\Facades\File::ensureDirectoryExists($here);
        \Illuminate\Support\Facades\File::ensureDirectoryExists($space);

        foreach ($files as $path => $contents) {
            \Illuminate\Support\Facades\File::ensureDirectoryExists($here.'/'.dirname($path));
            file_put_contents($here.'/'.$path, $contents);
        }

        // The command reads the folder the local disk would have used, which
        // is worked out from the storage path.
        $this->app->useStoragePath($base);
        $this->toDelete[] = $base;

        $this->pretendTheLocalFolderIsASpace($space);

        return [$here, $space];
    }

    /**
     * A folder on this machine, answering to the name of a Space.
     *
     * The command cares that the disk it is given behaves like one and that
     * the shop has said its photographs live there; a bucket of its own is
     * not needed to prove either.
     */
    private function pretendTheLocalFolderIsASpace(string $root): void
    {
        Storage::extend('s3', function ($app, array $config) use ($root) {
            $adapter = new LocalFilesystemAdapter($root);

            return new FilesystemAdapter(new Filesystem($adapter), $adapter, $config);
        });

        config(['filesystems.disks.public' => [
            'driver' => 's3',
            'bucket' => 'ojasvi',
            'visibility' => 'public',
            'throw' => true,
        ]]);

        Storage::forgetDisk('public');
    }
}
