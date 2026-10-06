<?php

namespace Tests\Feature;

use Illuminate\Support\Env;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Where the shop's photographs are kept.
 *
 * Three answers, and a shop may need any of them: beside the application and
 * reached through a symlink, inside public/ where a host forbids symlinks, or
 * off the box altogether on DigitalOcean Spaces.
 *
 * Whichever it is, the path stored against each saree is the same, so moving
 * between them is copying the files and changing one line — nothing in the
 * database changes.
 */
class UploadsTest extends TestCase
{
    /** The disk as config/filesystems.php builds it for a given .env. */
    private function disk(array $env): array
    {
        $before = [];

        foreach ($env as $key => $value) {
            $before[$key] = Env::getRepository()->get($key);

            // null means "as if the line were not in the .env at all", which
            // is how a default is actually exercised.
            $value === null
                ? Env::getRepository()->clear($key)
                : Env::getRepository()->set($key, $value);
        }

        try {
            $config = require base_path('config/filesystems.php');

            return $config['disks']['public'];
        } finally {
            foreach ($before as $key => $value) {
                $value === null
                    ? Env::getRepository()->clear($key)
                    : Env::getRepository()->set($key, $value);
            }
        }
    }

    public function test_by_default_they_sit_beside_the_application(): void
    {
        $disk = $this->disk(['SHOP_UPLOADS_ON_SPACES' => 'false', 'SHOP_UPLOADS_IN_PUBLIC' => 'false']);

        $this->assertSame('local', $disk['driver']);
        $this->assertSame(storage_path('app/public'), $disk['root']);
        $this->assertStringEndsWith('/storage', $disk['url']);
    }

    /** For a host that forbids the symlink, which Hostinger does. */
    public function test_they_can_sit_inside_public_instead(): void
    {
        $disk = $this->disk(['SHOP_UPLOADS_ON_SPACES' => 'false', 'SHOP_UPLOADS_IN_PUBLIC' => 'true']);

        $this->assertSame('local', $disk['driver']);
        $this->assertSame(public_path('uploads'), $disk['root']);
        $this->assertStringEndsWith('/uploads', $disk['url']);
    }

    public function test_they_can_sit_on_digitalocean_spaces(): void
    {
        $disk = $this->disk([
            'SHOP_UPLOADS_ON_SPACES' => 'true',
            'SPACES_KEY'             => 'a-key',
            'SPACES_SECRET'          => 'a-secret',
            'SPACES_BUCKET'          => 'ojasvi',
            'SPACES_REGION'          => 'blr1',
            'SPACES_URL'             => 'https://cdn.ojasvidrapes.in',
        ]);

        $this->assertSame('s3', $disk['driver']);
        $this->assertSame('ojasvi', $disk['bucket']);
        $this->assertSame('public', $disk['visibility']);

        // Derived from the region, so a shop sets one thing rather than two.
        $this->assertSame('https://blr1.digitaloceanspaces.com', $disk['endpoint']);

        // And served from the CDN address where there is one.
        $this->assertSame('https://cdn.ojasvidrapes.in', $disk['url']);
    }

    /**
     * Bangalore unless told otherwise.
     *
     * The shop sells in India; its photographs should not travel to Amsterdam
     * and back for somebody in Hyderabad.
     */
    public function test_the_region_defaults_to_the_one_nearest_its_customers(): void
    {
        $disk = $this->disk(['SHOP_UPLOADS_ON_SPACES' => 'true', 'SPACES_REGION' => null]);

        $this->assertSame('blr1', $disk['region']);
        $this->assertSame('https://blr1.digitaloceanspaces.com', $disk['endpoint']);
    }

    /**
     * And the driver is actually installed.
     *
     * Laravel ships the s3 configuration whether or not the adapter behind it
     * is there; without league/flysystem-aws-s3-v3 this throws, and it throws
     * on the live site at the moment a shopkeeper uploads a photograph rather
     * than when the setting is changed.
     */
    public function test_the_s3_driver_is_there_to_be_used(): void
    {
        $disk = Storage::build([
            'driver'   => 's3',
            'key'      => 'a-key',
            'secret'   => 'a-secret',
            'region'   => 'blr1',
            'bucket'   => 'ojasvi',
            'endpoint' => 'https://blr1.digitaloceanspaces.com',
        ]);

        $this->assertInstanceOf(\Illuminate\Contracts\Filesystem\Filesystem::class, $disk);
    }
}
