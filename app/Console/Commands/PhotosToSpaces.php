<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Copy the photographs already on this server up to the Space.
 *
 * Changing where photographs are kept does not move the ones already taken,
 * and nothing warns you: the shop keeps the same paths, the bucket does not
 * have the files, and every saree on the shop is a broken square. The usual
 * advice is "use any S3 tool", which assumes a server you may install things
 * on — not a shared host with no shell worth the name.
 *
 * So the shop can do it itself, with the libraries it already has. Safe to run
 * twice: anything already up there at the same size is left alone.
 */
class PhotosToSpaces extends Command
{
    protected $signature = 'ojasvi:photos-to-spaces
                            {--pretend : Say what would go up, and change nothing}';

    protected $description = 'Copy the photographs on this server up to DigitalOcean Spaces';

    public function handle(): int
    {
        $space = Storage::disk('public');

        if (config('filesystems.disks.public.driver') !== 's3') {
            $this->error('  The shop is not keeping its photographs on a Space.');
            $this->line('');
            $this->line('  Set that first in Settings, under Photograph storage, then run this');
            $this->line('  to send the files already here up to it.');

            return self::FAILURE;
        }

        /*
         * Where they are now. Not `Storage::disk('public')` — that is the
         * Space, which is the whole point — but the folder the local disk
         * would have used, worked out the same way config/filesystems.php
         * works it out.
         */
        $root = env('SHOP_UPLOADS_IN_PUBLIC', false)
            ? public_path('uploads')
            : storage_path('app/public');

        if (! is_dir($root)) {
            $this->error('  There is no folder at '.$root.', so there is nothing to copy.');

            return self::FAILURE;
        }

        $here = Storage::build(['driver' => 'local', 'root' => $root, 'throw' => true]);

        $files = collect($here->allFiles())
            ->reject(fn (string $path) => str_starts_with($path, 'livewire-tmp/')
                || str_contains($path, '/.')
                || str_starts_with($path, '.'))
            ->values();

        $this->line('');
        $this->line('  From  <options=bold>'.$root.'</>');
        $this->line('  To    <options=bold>'.config('filesystems.disks.public.bucket').'</> on DigitalOcean');
        $this->line('  '.$files->count().' file'.($files->count() === 1 ? '' : 's').' to consider');
        $this->line('');

        if ($files->isEmpty()) {
            $this->warn('  Nothing here to copy.');

            return self::SUCCESS;
        }

        $copied = 0;
        $skipped = 0;
        $failed = [];

        $bar = $this->output->createProgressBar($files->count());
        $bar->start();

        foreach ($files as $path) {
            $bar->advance();

            try {
                // Already up there, and the same size: leave it alone, so this
                // can be run again after a half-finished copy without sending
                // every photograph a second time.
                if ($space->exists($path) && $space->size($path) === $here->size($path)) {
                    $skipped++;

                    continue;
                }

                if ($this->option('pretend')) {
                    $copied++;

                    continue;
                }

                // Streamed rather than read into memory: a film is tens of
                // megabytes and shared hosting is not generous.
                $stream = $here->readStream($path);

                $space->writeStream($path, $stream, ['visibility' => 'public']);

                if (is_resource($stream)) {
                    fclose($stream);
                }

                $copied++;
            } catch (\Throwable $e) {
                $failed[$path] = $e->getMessage();
            }
        }

        $bar->finish();
        $this->line('');
        $this->line('');

        if ($this->option('pretend')) {
            $this->info('  '.$copied.' would go up, '.$skipped.' are already there.');
            $this->line('  Nothing was changed.');

            return self::SUCCESS;
        }

        $this->info('  '.$copied.' copied up, '.$skipped.' already there.');

        if ($failed !== []) {
            $this->line('');
            $this->error('  '.count($failed).' would not go:');

            foreach (array_slice($failed, 0, 5, true) as $path => $why) {
                $this->line('    '.$path);
                $this->line('      '.$why);
            }

            $this->line('');
            $this->line('  Run it again — anything that did go up is skipped the second time.');

            return self::FAILURE;
        }

        $this->line('');
        $this->line('  Check one with <options=bold>php artisan ojasvi:test-storage</>, then open the shop.');

        return self::SUCCESS;
    }
}
