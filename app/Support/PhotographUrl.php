<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Where the admin's upload boxes fetch a photograph from.
 *
 * On this server's own disk, its ordinary address. On a bucket, this shop's
 * address instead, because the box fetches the picture to draw and crop it
 * and a browser polices a fetch across domains — which is why a shop whose
 * photographs are perfect on the storefront can show the shopkeeper nothing
 * but a grey "Loading" bar.
 *
 * The storefront is untouched: it uses an <img> tag pointed straight at the
 * Space, which is what a CDN is for.
 */
final class PhotographUrl
{
    public static function forTheAdmin(string $disk, string $path): string
    {
        if (config("filesystems.disks.{$disk}.driver") !== 's3') {
            return Storage::disk($disk)->url($path);
        }

        /*
         * Built by hand rather than with route(): the path is a path, and
         * route() turns its slashes into %2F — which leaves every photograph
         * called by the name of the route rather than its own, because the
         * box reads the name off the end of the address.
         */
        return url('photograph-preview/'.implode('/', array_map('rawurlencode', explode('/', $path))));
    }
}
