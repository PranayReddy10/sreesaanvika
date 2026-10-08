<?php

namespace App\Console\Commands;

use App\Support\Photograph;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Shrink the photographs the shop has already uploaded.
 *
 * Every new upload is capped on its way in, but a shop that has been running
 * a while is full of whole camera files — eleven megabytes each, six thousand
 * pixels across, sent in full to every customer on a phone. This goes back
 * over them once.
 *
 * Nothing is cropped and nothing is recompressed twice: a photograph already
 * within the cap, or one that would come out larger than it went in, is left
 * exactly as it is.
 */
class TidyPhotos extends Command
{
    protected $signature = 'ojasvi:tidy-photos
                            {--pretend : Say what would be shrunk, and change nothing}';

    protected $description = 'Shrink photographs already uploaded, without visible loss';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $onSpaces = config('filesystems.disks.public.driver') === 's3';

        $files = collect($disk->allFiles())
            ->filter(fn (string $path) => in_array(
                strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                ['jpg', 'jpeg', 'png', 'webp'],
                true,
            ))
            ->reject(fn (string $path) => str_starts_with($path, 'livewire-tmp/')
                || str_starts_with($path, 'ojasvi-check/'))
            ->values();

        $this->line('');
        $this->line('  '.$files->count().' photograph'.($files->count() === 1 ? '' : 's')
            .' to look at, kept '.($onSpaces ? 'on DigitalOcean' : 'on this server'));
        $this->line('  Nothing wider or taller than '.Photograph::LONGEST_EDGE.' pixels is left behind');
        $this->line('');

        if ($files->isEmpty()) {
            return self::SUCCESS;
        }

        $shrunk = 0;
        $before = 0;
        $after = 0;
        $failed = [];

        $bar = $this->output->createProgressBar($files->count());
        $bar->start();

        foreach ($files as $path) {
            $bar->advance();

            try {
                // On a bucket there is no file to work on, only a key, so it
                // comes down to a scratch file and goes back up if it changed.
                $local = $onSpaces ? $this->fetch($disk, $path) : $disk->path($path);

                if ($local === null) {
                    continue;
                }

                $result = $this->option('pretend')
                    ? $this->wouldShrink($local)
                    : Photograph::tidy($local);

                if ($result !== null) {
                    $shrunk++;
                    $before += $result['was'];
                    $after += $result['bytes'];

                    if ($onSpaces && ! $this->option('pretend')) {
                        $stream = fopen($local, 'rb');
                        $disk->writeStream($path, $stream, ['visibility' => 'public']);

                        if (is_resource($stream)) {
                            fclose($stream);
                        }
                    }
                }

                if ($onSpaces) {
                    @unlink($local);
                }
            } catch (\Throwable $e) {
                $failed[$path] = $e->getMessage();
            }
        }

        $bar->finish();
        $this->line('');
        $this->line('');

        $saved = $before - $after;

        if ($shrunk === 0) {
            $this->info('  Every photograph was already the right size.');
        } elseif ($this->option('pretend')) {
            $this->info('  '.$shrunk.' would be shrunk, saving about '.$this->mb($saved).'.');
            $this->line('  Nothing was changed.');
        } else {
            $this->info('  '.$shrunk.' shrunk, '.$this->mb($before).' down to '.$this->mb($after)
                .' — '.$this->mb($saved).' that no customer has to download again.');
        }

        if ($failed !== []) {
            $this->line('');
            $this->error('  '.count($failed).' could not be read:');

            foreach (array_slice($failed, 0, 5, true) as $path => $why) {
                $this->line('    '.$path.' — '.$why);
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /** A copy on this machine to work on, for a photograph kept on a bucket. */
    private function fetch(\Illuminate\Contracts\Filesystem\Filesystem $disk, string $path): ?string
    {
        $stream = $disk->readStream($path);

        if (! is_resource($stream)) {
            return null;
        }

        $local = tempnam(sys_get_temp_dir(), 'ojasvi').'.'.pathinfo($path, PATHINFO_EXTENSION);
        $out = fopen($local, 'wb');
        stream_copy_to_stream($stream, $out);
        fclose($out);
        fclose($stream);

        return $local;
    }

    /**
     * What tidying would do, without doing it.
     *
     * @return array{width: int, height: int, bytes: int, was: int}|null
     */
    private function wouldShrink(string $file): ?array
    {
        $info = @getimagesize($file);

        if ($info === false) {
            return null;
        }

        [$width, $height] = $info;
        $was = (int) filesize($file);

        if (max($width, $height) <= Photograph::LONGEST_EDGE) {
            return null;
        }

        $scale = Photograph::LONGEST_EDGE / max($width, $height);

        return [
            'width' => (int) round($width * $scale),
            'height' => (int) round($height * $scale),
            // Roughly: the pixels fall by the square of the scale and so,
            // near enough, does the file. Only ever shown as "about".
            'bytes' => (int) round($was * $scale * $scale),
            'was' => $was,
        ];
    }

    private function mb(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 1).' MB'
            : round($bytes / 1024).' KB';
    }
}
