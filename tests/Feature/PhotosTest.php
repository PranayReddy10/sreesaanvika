<?php

namespace Tests\Feature;

use App\Models\ProductImage;
use App\Models\Video;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The answer to "the photographs are not showing".
 *
 * Two quite different faults look identical from a shopper's side — the file
 * is not where the shop thinks it is, or it is there and the web server will
 * not hand it over — and a shop on shared hosting has no way to tell them
 * apart. This is the thing that tells them.
 */
class PhotosTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_names_the_photographs_that_are_not_on_the_disk(): void
    {
        $this->seed(DemoSeeder::class);

        // An empty disk, which is what a shop sees when the uploads were
        // written somewhere else — before SHOP_UPLOADS_IN_PUBLIC was changed,
        // usually, since changing it moves where uploads are kept without
        // moving what is already there.
        Storage::fake('public');

        $this->artisan('ojasvi:photos')
            ->expectsOutputToContain('are not on the disk at all')
            ->expectsOutputToContain('DemoSeeder')
            ->assertFailed();
    }

    public function test_it_says_so_when_every_file_is_where_it_should_be(): void
    {
        $this->seed(DemoSeeder::class);

        $this->artisan('ojasvi:photos')
            ->expectsOutputToContain('files are on the disk')
            ->assertSuccessful();
    }

    public function test_it_does_not_complain_about_a_shop_with_no_photographs_yet(): void
    {
        ProductImage::query()->delete();
        Video::query()->delete();

        $this->artisan('ojasvi:photos')
            ->expectsOutputToContain('no photographs or films in the shop yet')
            ->assertSuccessful();
    }
}
