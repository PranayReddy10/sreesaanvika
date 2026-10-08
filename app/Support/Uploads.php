<?php

namespace App\Support;

/**
 * What this server will actually take.
 *
 * Read from PHP rather than guessed: a shop told it may upload 50 MB by a
 * form on a server that stops at 8 MB gets a blank error and no idea why.
 * Hostinger's own default is generous enough for a photograph off a camera;
 * some hosts are not, and the form should say so before the upload rather
 * than after it.
 */
final class Uploads
{
    /** In kilobytes, which is the unit Laravel's rules and Filament want. */
    public static function ceiling(): int
    {
        $limits = array_filter([
            self::bytes((string) ini_get('upload_max_filesize')),
            self::bytes((string) ini_get('post_max_size')),
        ]);

        $bytes = $limits === [] ? 8 * 1024 * 1024 : min($limits);

        // A little under, because the rest of the form goes in the same post.
        return (int) max(1024, floor(($bytes * 0.9) / 1024));
    }

    public static function ceilingInMegabytes(): int
    {
        return (int) round(self::ceiling() / 1024);
    }

    private static function bytes(string $size): ?int
    {
        $size = trim($size);

        if ($size === '' || $size === '-1') {
            return null;
        }

        $unit = strtolower(substr($size, -1));
        $number = (int) $size;

        return match ($unit) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
