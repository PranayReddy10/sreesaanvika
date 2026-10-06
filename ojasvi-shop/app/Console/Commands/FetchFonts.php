<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Pull the shop's typefaces down and serve them ourselves.
 *
 * Running this once writes public/fonts, which is then committed and shipped.
 * The shop therefore makes no request to a font service on any page view: one
 * less thing to be slow on a phone in India, and the shopper's address stays
 * out of somebody else's log.
 */
class FetchFonts extends Command
{
    protected $signature = 'ojasvi:fonts {--subsets=latin,latin-ext}';

    protected $description = 'Download the shop’s web fonts into public/fonts so they are served from our own domain';

    /** family => the weights the shop actually uses. */
    private const FAMILIES = [
        'Playfair Display'   => [400, 600],
        'Cormorant Garamond' => [400, 600],
        'Jost'               => [300, 400, 500],
    ];

    // Google serves woff2 only to a browser that says it understands woff2.
    private const AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 '
        . '(KHTML, like Gecko) Chrome/120 Safari/537.36';

    public function handle(): int
    {
        $subsets = array_filter(array_map('trim', explode(',', (string) $this->option('subsets'))));
        $dir = public_path('fonts');

        if (! is_dir($dir) && ! mkdir($dir, 0o755, true) && ! is_dir($dir)) {
            $this->error("Could not create {$dir}.");

            return self::FAILURE;
        }

        $blocks = [];
        $written = [];

        foreach (self::FAMILIES as $family => $weights) {
            $url = 'https://fonts.googleapis.com/css2?family='
                . rawurlencode($family) . ':wght@' . implode(';', $weights)
                . '&display=swap';

            $css = Http::withHeaders(['User-Agent' => self::AGENT])->get($url);

            if (! $css->successful()) {
                $this->error("Could not fetch {$family}: HTTP {$css->status()}");

                return self::FAILURE;
            }

            preg_match_all(
                '#/\*\s*([\w-]+)\s*\*/\s*(@font-face\s*\{[^}]*\})#',
                $css->body(),
                $faces,
                PREG_SET_ORDER
            );

            foreach ($faces as [, $subset, $face]) {
                if (! in_array($subset, $subsets, true)) {
                    continue;
                }

                if (! preg_match('#url\((https://[^)]+\.woff2)\)#', $face, $m)
                    || ! preg_match('#font-weight:\s*(\d+)#', $face, $w)) {
                    continue;
                }

                $name = str($family)->lower()->slug()->toString() . "-{$subset}-{$w[1]}.woff2";

                if (! isset($written[$name])) {
                    $file = Http::withHeaders(['User-Agent' => self::AGENT])->get($m[1]);

                    if (! $file->successful()) {
                        $this->error("Could not fetch {$name}.");

                        return self::FAILURE;
                    }

                    file_put_contents("{$dir}/{$name}", $file->body());
                    $written[$name] = strlen($file->body());
                }

                $blocks[] = preg_replace('#url\(https://[^)]+\.woff2\)#', "url(/fonts/{$name})", $face);
            }
        }

        $header = "/*\n * Written by `php artisan ojasvi:fonts` — do not edit by hand.\n"
            . " * Served from our own domain so no page view reaches a font service.\n */\n\n";

        file_put_contents("{$dir}/fonts.css", $header . implode("\n\n", $blocks) . "\n");

        $this->info(count($written) . ' files, ' . round(array_sum($written) / 1024) . ' KB, written to public/fonts.');
        $this->line('Now run `npm run build` and commit public/fonts and public/build.');

        return self::SUCCESS;
    }
}
