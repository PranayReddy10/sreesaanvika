<?php

namespace Tests\Feature;

use App\Support\Photograph;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * A photograph off a camera, made fit for a shop.
 *
 * Eleven megabytes and six thousand pixels across is right for the person
 * editing it and wrong for everybody else: no screen a shopper owns shows
 * more than about two thousand, and the rest is her data spent on nothing.
 *
 * The thing to be careful of is the opposite mistake — shrinking what was
 * already small, or re-encoding the same picture over and over until the
 * weave goes soft — so most of this is about what it leaves alone.
 */
class PhotographTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('framework/testing/photographs-'.\Illuminate\Support\Str::random(6));
        File::ensureDirectoryExists($this->dir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    public function test_a_camera_photograph_is_brought_down_to_a_size_a_screen_can_show(): void
    {
        $file = $this->aPhotograph(4000, 3000, quality: 95);

        $was = filesize($file);
        $result = Photograph::tidy($file);

        $this->assertNotNull($result, 'a four thousand pixel photograph should have been resized');
        $this->assertSame(Photograph::LONGEST_EDGE, $result['width']);

        // The shape is kept: a saree cropped by the shop stays as cropped.
        $this->assertSame(1800, $result['height']);
        $this->assertLessThan($was, $result['bytes']);

        clearstatcache();
        $this->assertSame([2400, 1800], array_slice(getimagesize($file), 0, 2));
    }

    public function test_a_photograph_already_the_right_size_is_left_alone(): void
    {
        $file = $this->aPhotograph(1200, 1600, quality: 86);

        $before = md5_file($file);

        $this->assertNull(Photograph::tidy($file));
        $this->assertSame($before, md5_file($file), 'the file was rewritten for no reason');
    }

    /**
     * The one that matters most.
     *
     * Every pass through a lossy format throws a little away. A tidy run that
     * re-encoded what it had already done would quietly ruin the whole
     * catalogue over a year of nightly runs.
     */
    public function test_tidying_twice_changes_nothing_the_second_time(): void
    {
        $file = $this->aPhotograph(4000, 3000, quality: 95);

        $this->assertNotNull(Photograph::tidy($file));

        $afterOnce = md5_file($file);

        $this->assertNull(Photograph::tidy($file), 'it went round again');
        $this->assertSame($afterOnce, md5_file($file));
    }

    /** A small picture saved at quality 100 is still worth re-saving. */
    public function test_a_bloated_photograph_is_re_saved_at_its_own_size(): void
    {
        $file = $this->aPhotograph(1600, 1200, quality: 100);

        $result = Photograph::tidy($file);

        $this->assertNotNull($result);
        $this->assertSame([1600, 1200], [$result['width'], $result['height']], 'it should not have been resized');
        $this->assertLessThan($result['was'], $result['bytes']);
    }

    public function test_a_photograph_taken_on_its_side_is_turned_upright(): void
    {
        // 6 means "rotate a quarter turn clockwise to show it", which is a
        // phone held upright.
        $file = $this->aPhotograph(400, 200, quality: 92, orientation: 6);

        $result = Photograph::tidy($file);

        $this->assertNotNull($result);
        $this->assertSame([200, 400], [$result['width'], $result['height']], 'it stayed on its side');
    }

    public function test_transparency_survives(): void
    {
        $file = $this->dir.'/logo.png';
        $image = imagecreatetruecolor(3000, 2000);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagepng($image, $file);
        imagedestroy($image);

        $this->assertNotNull(Photograph::tidy($file));

        $read = imagecreatefrompng($file);
        $corner = imagecolorsforindex($read, imagecolorat($read, 5, 5));

        $this->assertSame(127, $corner['alpha'], 'the transparent corner came back solid');
    }

    public function test_anything_that_is_not_a_photograph_is_untouched(): void
    {
        $film = $this->dir.'/draped.mp4';
        file_put_contents($film, str_repeat('not a picture at all', 500));
        $before = md5_file($film);

        $this->assertNull(Photograph::tidy($film));
        $this->assertSame($before, md5_file($film));

        $this->assertNull(Photograph::tidy($this->dir.'/there-is-no-such-file.jpg'));
    }

    /**
     * And it happens on the way in, not as an afterthought.
     *
     * Through the admin's own form, because that is the only path a shop
     * ever uses and a helper nobody calls shrinks nothing.
     */
    public function test_an_upload_is_shrunk_before_it_is_stored(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $admin = \App\Models\User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\ShopSettings::class)
            ->fillForm([
                'shop_name' => 'OJASVI',
                // Four thousand pixels across, as a camera gives it.
                'logo' => \Illuminate\Http\UploadedFile::fake()->image('saree.jpg', 4000, 3000),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $stored = \Illuminate\Support\Facades\Storage::disk('public')->allFiles('brand');

        $this->assertCount(1, $stored, 'nothing was stored');

        $path = \Illuminate\Support\Facades\Storage::disk('public')->path($stored[0]);
        [$width, $height] = getimagesize($path);

        $this->assertSame(Photograph::LONGEST_EDGE, $width, 'the whole camera file was stored');
        $this->assertSame(1800, $height);
    }

    /**
     * And for the camera files a shop has already uploaded.
     *
     * Every new upload is caught on the way in; a shop that has been running
     * a year is full of the old ones, sent whole to every customer.
     */
    public function test_the_command_goes_back_over_what_is_already_there(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $disk->put('products/camera.jpg', (string) file_get_contents($this->aPhotograph(4000, 3000, quality: 95)));
        $disk->put('products/already-fine.jpg', (string) file_get_contents($this->aPhotograph(1200, 900, quality: 86)));

        $untouched = md5($disk->get('products/already-fine.jpg'));

        $this->artisan('ojasvi:tidy-photos')
            ->expectsOutputToContain('1 shrunk')
            ->assertSuccessful();

        [$width] = getimagesize($disk->path('products/camera.jpg'));

        $this->assertSame(Photograph::LONGEST_EDGE, $width);
        $this->assertSame($untouched, md5($disk->get('products/already-fine.jpg')), 'it rewrote one that was fine');

        // Run it again and it has nothing to do, which is what keeps a
        // nightly run from slowly softening the whole catalogue.
        $this->artisan('ojasvi:tidy-photos')
            ->expectsOutputToContain('already the right size')
            ->assertSuccessful();
    }

    public function test_pretending_shrinks_nothing(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $disk->put('products/camera.jpg', (string) file_get_contents($this->aPhotograph(4000, 3000, quality: 95)));

        $before = md5($disk->get('products/camera.jpg'));

        $this->artisan('ojasvi:tidy-photos', ['--pretend' => true])
            ->expectsOutputToContain('would be shrunk')
            ->assertSuccessful();

        $this->assertSame($before, md5($disk->get('products/camera.jpg')));
    }

    /**
     * A photograph, with as much detail as a real one.
     *
     * A flat colour compresses to nothing and would make every size
     * comparison here meaningless.
     */
    private function aPhotograph(int $width, int $height, int $quality, ?int $orientation = null): string
    {
        $file = $this->dir.'/saree-'.$width.'x'.$height.'-'.$quality.'.jpg';

        // Drawn once as a small square of silk and then tiled, because
        // setting twelve million pixels from PHP takes half a minute and
        // teaches the test nothing.
        $tile = imagecreatetruecolor(200, 200);

        for ($y = 0; $y < 200; $y++) {
            for ($x = 0; $x < 200; $x++) {
                $thread = (($x + $y) % 7 < 2) ? 14 : 0;
                imagesetpixel($tile, $x, $y, imagecolorallocate(
                    $tile,
                    max(0, min(255, 62 + $thread + random_int(-6, 6))),
                    max(0, min(255, 70 + random_int(-6, 6))),
                    max(0, min(255, 138 + random_int(-6, 6))),
                ));
            }
        }

        $image = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y += 200) {
            for ($x = 0; $x < $width; $x += 200) {
                imagecopy($image, $tile, $x, $y, 0, 0, 200, 200);
            }
        }

        imagedestroy($tile);

        imagejpeg($image, $file, $quality);
        imagedestroy($image);

        if ($orientation !== null) {
            $this->sayItWasTakenFacing($file, $orientation);
        }

        return $file;
    }

    /** The note a camera leaves saying which way up it was held. */
    private function sayItWasTakenFacing(string $file, int $orientation): void
    {
        $tiff = "II\x2a\x00\x08\x00\x00\x00"
            ."\x01\x00"
            ."\x12\x01\x03\x00\x01\x00\x00\x00".pack('v', $orientation)."\x00\x00"
            ."\x00\x00\x00\x00";

        $exif = "Exif\x00\x00".$tiff;
        $app1 = "\xff\xe1".pack('n', strlen($exif) + 2).$exif;

        $jpeg = (string) file_get_contents($file);

        file_put_contents($file, substr($jpeg, 0, 2).$app1.substr($jpeg, 2));
    }
}
