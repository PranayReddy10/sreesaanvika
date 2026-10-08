<?php

namespace App\Console\Commands;

use App\Support\StorageCheck;
use Illuminate\Console\Command;

/**
 * Write one file where the photographs go, and say what happened.
 *
 * The browser's account of a failed upload is "failed to upload" and nothing
 * else. This prints the refusal the bucket actually gave, which is the one
 * thing the browser never shows.
 */
class TestStorage extends Command
{
    protected $signature = 'ojasvi:test-storage';

    protected $description = 'Check that photographs can be written, read and seen';

    public function handle(): int
    {
        $config = config('filesystems.disks.public');
        $onSpaces = ($config['driver'] ?? null) === 's3';

        $this->line('');
        $this->line('  Photographs are kept  <options=bold>'
            .($onSpaces ? 'on DigitalOcean Spaces' : 'on this server').'</>');

        if ($onSpaces) {
            $this->line('  Space                 <options=bold>'.($config['bucket'] ?? null ?: '(none)').'</>');
            $this->line('  Region                <options=bold>'.($config['region'] ?? null ?: '(none)').'</>');
            $this->line('  Endpoint              <options=bold>'.($config['endpoint'] ?? null ?: '(none)').'</>');
            $this->line('  Served from           <options=bold>'.($config['url'] ?? null ?: 'the Space itself').'</>');
        } else {
            $this->line('  Folder                <options=bold>'.($config['root'] ?? '?').'</>');
        }

        $this->line('');

        $check = StorageCheck::run();

        foreach ($check->steps as $step => $done) {
            $done ? $this->info('  '.$step.'.') : $this->error('  '.$step.' — no.');
        }

        if ($check->url) {
            $this->line('  '.$check->url);
        }

        $this->line('');

        $check->ok ? $this->info('  '.$check->summary) : $this->error('  '.$check->summary);

        foreach ([$check->detail, $check->advice] as $more) {
            if ($more) {
                $this->line('');
                $this->line('  '.$this->wrapped($more));
            }
        }

        return $check->ok ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Wrapped to the terminal, counting letters rather than bytes.
     *
     * wordwrap() counts bytes, so a rupee sign or an accent is cut in half
     * and the line comes out short and broken.
     */
    private function wrapped(string $text, int $width = 68): string
    {
        $lines = [];
        $line = '';

        foreach (explode(' ', $text) as $word) {
            if ($line !== '' && mb_strlen($line.' '.$word) > $width) {
                $lines[] = $line;
                $line = $word;

                continue;
            }

            $line = $line === '' ? $word : $line.' '.$word;
        }

        $lines[] = $line;

        return implode("\n  ", $lines);
    }
}
