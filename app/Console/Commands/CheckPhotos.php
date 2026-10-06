<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Why the photographs are not showing.
 *
 * There are two quite different faults behind the same broken square, and
 * from a shopper's side of the screen they are identical: the file is not
 * where the shop thinks it is, or the file is there and the web server will
 * not hand it over. Shared hosting makes the second one common, because the
 * link from public/storage to the uploads is a symbolic link and symlinks are
 * the first thing a host turns off.
 *
 * Nobody should have to tell those apart by reading a configuration file, so
 * this does it and says which it is.
 */
class CheckPhotos extends Command
{
    protected $signature = 'ojasvi:photos';

    protected $description = 'Say why the photographs are not showing';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $inPublic = config('filesystems.disks.public.root') === public_path('uploads');

        $this->line('');
        $this->line('  Uploads are kept in  <options=bold>'.config('filesystems.disks.public.root').'</>');
        $this->line('  and served from      <options=bold>'.config('filesystems.disks.public.url').'</>');
        $this->line('');

        $missing = $this->missing($disk);
        $expected = ProductImage::count() + Video::whereNotNull('path')->count();

        if ($expected === 0) {
            $this->warn('  There are no photographs or films in the shop yet.');

            return self::SUCCESS;
        }

        if ($missing !== []) {
            $this->error('  '.count($missing).' of '.$expected.' files are not on the disk at all:');

            foreach (array_slice($missing, 0, 5) as $path) {
                $this->line('    '.$path);
            }

            $this->line('');
            $this->line('  They were uploaded somewhere else — most often before');
            $this->line('  SHOP_UPLOADS_IN_PUBLIC was changed, which moves where uploads are');
            $this->line('  kept without moving the files already there. DEPLOYMENT.md, step 4.');
            $this->line('  For the example catalogue, <options=bold>php artisan db:seed --class=DemoSeeder</> puts');
            $this->line('  its photographs back.');

            return self::FAILURE;
        }

        $this->info('  All '.$expected.' files are on the disk.');
        $this->line('');

        if ($inPublic) {
            $this->line('  They are inside public/, so no link is needed. If they still do not');
            $this->line('  show, open one of these addresses in a browser and read what the');
            $this->line('  server says — a 403 is a permission, a 404 is an address:');
            $this->line('');
            $this->line('    '.$this->anAddress($disk));

            return self::SUCCESS;
        }

        return $this->reportTheLink();
    }

    /** The link from public/storage, which is what a shared host breaks. */
    private function reportTheLink(): int
    {
        $link = public_path('storage');

        if (! file_exists($link)) {
            $this->error('  public/storage does not exist, so nothing can be fetched.');
            $this->line('');
            $this->line('  Make it from the shell — artisan cannot, because this host turns off');
            $this->line('  both symlink() and exec():');
            $this->line('');
            $this->line('    cd '.public_path());
            $this->line('    ln -s ../storage/app/public storage');
            $this->line('');
            $this->line('  If the host forbids symbolic links altogether, put');
            $this->line('  SHOP_UPLOADS_IN_PUBLIC=true in .env and follow DEPLOYMENT.md, step 4.');

            return self::FAILURE;
        }

        $points = is_link($link) ? readlink($link) : null;
        $reaches = is_dir($link) && is_readable($link);

        $this->line('  public/storage '.($points ? 'points at '.$points : 'is a directory'));

        if (! $reaches) {
            $this->error('  …and nothing can be read through it. The link is there but broken.');
            $this->line('  Remove it and make it again, from public/:');
            $this->line('');
            $this->line('    rm storage && ln -s ../storage/app/public storage');

            return self::FAILURE;
        }

        $this->info('  …and the files can be read through it.');
        $this->line('');
        $this->line('  So the shop is right and anything still broken is the web server');
        $this->line('  refusing to follow the link. Open this in a browser:');
        $this->line('');
        $this->line('    '.$this->anAddress(Storage::disk('public')));
        $this->line('');
        $this->line('  A 403 means symbolic links are not being followed: add');
        $this->line('  <options=bold>Options +FollowSymLinks</> at the top of public_html/.htaccess, or set');
        $this->line('  SHOP_UPLOADS_IN_PUBLIC=true and follow DEPLOYMENT.md, step 4.');

        return self::SUCCESS;
    }

    /** @return array<int, string> */
    private function missing($disk): array
    {
        $paths = ProductImage::pluck('path')
            ->merge(Video::whereNotNull('path')->pluck('path'))
            ->unique()
            ->filter();

        return $paths->reject(fn (string $path) => $disk->exists($path))->values()->all();
    }

    private function anAddress($disk): string
    {
        $path = ProductImage::value('path') ?? Video::whereNotNull('path')->value('path');

        return $path ? $disk->url($path) : (string) config('filesystems.disks.public.url');
    }
}
