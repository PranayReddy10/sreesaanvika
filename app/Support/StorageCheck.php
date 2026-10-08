<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * One file, written where the photographs go, and read back the way a shopper
 * reads it.
 *
 * A photograph that will not upload says only "failed to upload" in the
 * browser, which is every possible fault wearing the same face: a mistyped
 * key, a Space in another region, a bucket name that is nearly right, a key
 * allowed to write but not to make a file public, or an install that never had
 * the S3 libraries put on it. This does the whole round trip and keeps what it
 * was told, so the shop can be given the reason instead of the symptom.
 */
final class StorageCheck
{
    /**
     * @param  array<string, bool>  $steps  Each stage, and whether it got through.
     */
    private function __construct(
        public readonly bool $ok,
        public readonly string $summary,
        public readonly ?string $detail = null,
        public readonly ?string $url = null,
        public readonly array $steps = [],
        public readonly ?string $advice = null,
    ) {}

    public static function run(): self
    {
        $disk = Storage::disk('public');
        $config = config('filesystems.disks.public');
        $onSpaces = ($config['driver'] ?? null) === 's3';

        // Nothing else can work without these, and a host where `composer
        // install` was never run is the commonest reason for their absence.
        if ($onSpaces && (! class_exists(\Aws\S3\S3Client::class)
            || ! class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class))) {
            return new self(
                ok: false,
                summary: 'The S3 libraries are not installed on this server.',
                advice: 'Run composer install --no-dev --optimize-autoloader in the application folder.',
            );
        }

        $path = 'ojasvi-check/'.Str::ulid().'.txt';
        $body = 'Written by the shop at '.now()->toDateTimeString();
        $steps = [];

        try {
            $disk->put($path, $body, 'public');
        } catch (\Throwable $e) {
            return new self(
                ok: false,
                summary: 'It could not write a file where the photographs go.',
                detail: $e->getMessage(),
                steps: ['Written' => false],
                advice: $onSpaces
                    ? 'Nine times in ten it is the access key, the secret, the Space name or the region — a Space in Bangalore is blr1 and nothing else.'
                    : 'Usually a folder the web server may not write to.',
            );
        }

        $steps['Written'] = true;

        try {
            $read = $disk->get($path);
        } catch (\Throwable $e) {
            return new self(
                ok: false,
                summary: 'It wrote a file but could not read it back.',
                detail: $e->getMessage(),
                steps: $steps + ['Read back' => false],
            );
        }

        if ($read !== $body) {
            return new self(
                ok: false,
                summary: 'It read the file back and it was not what was written.',
                steps: $steps + ['Read back' => false],
            );
        }

        $steps['Read back'] = true;
        $url = $disk->url($path);

        /*
         * And fetched with no credentials at all, which is how a shopper's
         * browser fetches it. This is the fault that passes every other check:
         * the file goes up, the shop's own credentials read it back happily,
         * and every photograph is still a broken square.
         */
        $seen = null;
        $summary = 'Photographs will upload.';
        $detail = null;
        $advice = null;

        $origin = rtrim((string) config('app.url'), '/');

        try {
            // Asked for as the admin's own browser asks, so the answer says
            // whether the Space will talk to it.
            $response = Http::withoutVerifying()->timeout(15)
                ->withHeaders($origin === '' ? [] : ['Origin' => $origin])
                ->get($url);

            if ($response->successful() && $response->body() === $body) {
                $seen = true;

                /*
                 * The storefront shows a photograph with an <img> tag, which
                 * no browser polices. The admin's upload box fetches it to
                 * draw the preview, which every browser does police — so a
                 * Space with no CORS rule looks perfect to a shopper and shows
                 * the shopkeeper a grey bar that never finishes loading.
                 */
                if ($onSpaces && $origin !== '' && ! $response->header('Access-Control-Allow-Origin')) {
                    $detail = 'The Space has no CORS rule for '.$origin.', so photographs show on the shop but not in the admin\'s upload boxes. In DigitalOcean, open the Space, then Settings, then CORS Configurations, and add that address with GET allowed.';
                }
            } elseif ($response->successful()) {
                // A CDN in front of the Space can answer with something it
                // cached earlier. Worth saying, not worth failing over.
                $detail = 'The address answered, but with something else. A CDN in front of the Space may be serving an older copy.';
            } elseif ($response->status() === 403) {
                $seen = false;
                $summary = 'Written, but not public — a shopper gets "access denied".';
                $advice = 'The key may write but not make a file public. In DigitalOcean, use a key with full access rather than one restricted to a Space.';
            } else {
                $seen = false;
                $summary = 'The file was written but its address answered '.$response->status().'.';
                $advice = 'Usually the CDN address: it must be the one in the Space\'s own settings, or left empty so the Space itself serves the files.';
            }
        } catch (\Throwable $e) {
            // Not fatal: a shared host that blocks outgoing HTTP cannot check
            // this, while a shopper's browser is not on that host at all.
            $detail = 'Could not fetch it from here ('.$e->getMessage().'). Open the address in a browser yourself.';
        }

        if ($seen !== null) {
            $steps['Seen from outside'] = $seen;
        }

        try {
            $disk->delete($path);
        } catch (\Throwable) {
            // Left behind, which is untidy and nothing worse.
        }

        return new self(
            ok: $seen !== false,
            summary: $summary,
            detail: $detail,
            url: $url,
            steps: $steps,
            advice: $advice,
        );
    }
}
