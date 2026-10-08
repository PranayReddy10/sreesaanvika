<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A photograph, handed to the admin from the shop's own address.
 *
 * The upload boxes in the admin do not show a picture with an <img> tag —
 * they fetch it, so they can draw the preview and let it be cropped. A
 * browser polices a fetch across domains and does not police an <img>, so a
 * shop keeping its photographs on a Space gets pictures that are perfect for
 * the shopper and a grey "Loading" bar for the shopkeeper, unless the Space
 * is given a CORS rule naming the shop.
 *
 * Asking every shop to get that right is a poor trade for two minutes of
 * bandwidth, so the admin asks this instead, and it fetches from the Space
 * server-side. Same address, no CORS, previews either way.
 *
 * Nothing is exposed that was not already: this disk is the public one, every
 * file on it is world-readable by design, and this is behind the admin login
 * regardless.
 */
class PhotographPreviewController extends Controller
{
    /** Content types we are prepared to name, by extension. */
    private const TYPES = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'webp' => 'image/webp', 'gif' => 'image/gif', 'avif' => 'image/avif',
        'svg' => 'image/svg+xml', 'mp4' => 'video/mp4', 'webm' => 'video/webm',
        'mov' => 'video/quicktime', 'pdf' => 'application/pdf',
    ];

    public function __invoke(Request $request, string $path): StreamedResponse
    {
        abort_unless($request->user()?->is_admin, 403);

        $path = ltrim($path, '/');

        // Nothing clever: a path from the shop's own records, which is what
        // the form puts in the link. Anything trying to climb out of the
        // uploads folder is not one of those.
        abort_if($path === '' || str_contains($path, '..'), 404);

        try {
            $stream = Storage::disk('public')->readStream($path);
        } catch (\Throwable) {
            abort(404);
        }

        abort_unless(is_resource($stream), 404);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => self::TYPES[$extension] ?? 'application/octet-stream',
            // The real file name, so the box shows "kanjivaram-indigo-1.jpg"
            // rather than the name of this route.
            'Content-Disposition' => 'inline; filename="'.addslashes(basename($path)).'"',
            // Private: this is one shopkeeper's screen, not a shared cache.
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
