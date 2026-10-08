<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The command that answers "will a photograph upload?".
 *
 * The browser's answer is "failed to upload" and nothing else — the same four
 * words for a mistyped key, a Space in the wrong region, a bucket name that is
 * nearly right, and a key that may write but not make a file public. This
 * command exists to tell those apart, so these tests are about it being right
 * on each of them.
 */
class TestStorageCommandTest extends TestCase
{
    public function test_it_writes_reads_fetches_and_tidies_up(): void
    {
        Storage::fake('public');

        // Whatever is on the disk is what the open web is serving, which is
        // what a Space with public files does.
        Http::fake(fn (Request $request) => Http::response(
            // The last two segments of whatever address the disk built are the
            // file's own path: ojasvi-check/<name>.
            Storage::disk('public')->get(
                implode('/', array_slice(explode('/', (string) parse_url($request->url(), PHP_URL_PATH)), -2))
            )
        ));

        $this->artisan('ojasvi:test-storage')
            ->expectsOutputToContain('Written.')
            ->expectsOutputToContain('Read back.')
            ->expectsOutputToContain('Seen from outside.')
            ->expectsOutputToContain('Photographs will upload.')
            ->assertSuccessful();

        // Nothing of its own left behind in the shop's photographs.
        $this->assertEmpty(Storage::disk('public')->allFiles('ojasvi-check'));
    }

    /**
     * Written, and still a broken square.
     *
     * The one fault that passes every other check: the file goes up, the
     * credentials read it back happily, and a shopper with no credentials at
     * all is refused.
     */
    public function test_it_reports_a_file_that_is_written_but_not_public(): void
    {
        Storage::fake('public');

        Http::fake(fn () => Http::response('<Error><Code>AccessDenied</Code></Error>', 403));

        $this->artisan('ojasvi:test-storage')
            ->expectsOutputToContain('Written.')
            ->expectsOutputToContain('not public')
            ->assertFailed();

        $this->assertEmpty(Storage::disk('public')->allFiles('ojasvi-check'));
    }

    /** A bucket that cannot be reached at all, with the reason it gave. */
    public function test_it_prints_what_the_bucket_said_when_it_will_not_write(): void
    {
        config(['filesystems.disks.public' => [
            'driver' => 's3',
            'key' => 'DO00NOTAREALKEY',
            'secret' => 'not-a-real-secret',
            'region' => 'blr1',
            'bucket' => 'ojasvi',
            'endpoint' => 'http://127.0.0.1:1',
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
            'retries' => 0,
            'http' => ['connect_timeout' => 2, 'timeout' => 3],
        ]]);
        Storage::forgetDisk('public');

        $this->artisan('ojasvi:test-storage')
            ->expectsOutputToContain('on DigitalOcean Spaces')
            ->expectsOutputToContain('could not write a file')
            ->expectsOutputToContain('the access key')
            ->assertFailed();
    }
}
