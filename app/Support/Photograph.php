<?php

namespace App\Support;

/**
 * A photograph as it comes off a camera, made fit for a shop.
 *
 * A saree is photographed properly — a real camera, eleven megabytes, six
 * thousand pixels across — and all of that is for the person editing it. No
 * screen a shopper owns can show more than about two thousand pixels of it,
 * and sending the other four thousand costs her a minute of her data and the
 * shop a sale.
 *
 * So the long edge is capped and the file re-encoded at a quality where the
 * difference cannot be seen: the weave still holds up under a pinch-zoom,
 * which is the whole reason a saree is photographed that way, and the file is
 * a tenth of the size. Nothing is cropped and nothing is sharpened; what was
 * photographed is what is shown.
 */
final class Photograph
{
    /**
     * As wide or tall as the shop keeps a photograph.
     *
     * A phone at three times density showing a picture across half its screen
     * asks for about 1200; a desktop gallery about 2000. 2400 covers both and
     * leaves something in hand for a shopper who pinches in on the zari.
     */
    public const LONGEST_EDGE = 2400;

    /**
     * 86 out of 100.
     *
     * Below about 80 a flat expanse of silk starts to band, which is exactly
     * the fault a saree shop would notice; above about 90 the file doubles
     * and nobody can see where it went.
     */
    public const QUALITY = 86;

    /** What we are prepared to open. Anything else is left exactly as it is. */
    private const HANDLED = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

    /**
     * Tidy a photograph where it lies.
     *
     * Best effort by design: a file this cannot open, or is too large to open
     * safely, is left untouched rather than lost. Returns what it did, or null
     * if it did nothing.
     *
     * @return array{width: int, height: int, bytes: int, was: int}|null
     */
    public static function tidy(string $file, int $longestEdge = self::LONGEST_EDGE, int $quality = self::QUALITY): ?array
    {
        if (! is_file($file) || ! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $info = @getimagesize($file);

        if ($info === false || ! in_array($info[2], self::HANDLED, true)) {
            return null;
        }

        [$width, $height] = $info;
        $type = $info[2];
        $before = (int) filesize($file);

        if (! self::needsIt($file, $type, $width, $height, $before, $longestEdge)) {
            return null;
        }

        if (! self::thereIsRoomFor($width, $height)) {
            return null;
        }

        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file),
            IMAGETYPE_PNG  => @imagecreatefrompng($file),
            IMAGETYPE_WEBP => @imagecreatefromwebp($file),
        };

        if (! $image instanceof \GdImage) {
            return null;
        }

        try {
            $image = self::theRightWayUp($image, $file, $type);

            $width = imagesx($image);
            $height = imagesy($image);

            $scale = $longestEdge / max($width, $height);

            if ($scale < 1) {
                $resized = imagescale($image, (int) round($width * $scale), (int) round($height * $scale), IMG_BICUBIC);

                if ($resized instanceof \GdImage) {
                    imagedestroy($image);
                    $image = $resized;
                    $width = imagesx($image);
                    $height = imagesy($image);
                }
            }

            $written = self::write($image, $file, $type, $quality);

            if (! $written) {
                return null;
            }

            clearstatcache(true, $file);

            return ['width' => $width, 'height' => $height, 'bytes' => (int) filesize($file), 'was' => $before];
        } finally {
            if ($image instanceof \GdImage) {
                imagedestroy($image);
            }
        }
    }

    /**
     * Is there anything here worth doing?
     *
     * Asked before the file is opened, because the answer is usually no and
     * the alternative is worse than wasted work: every pass through a lossy
     * format throws a little away, so a photograph already the right size
     * that is re-encoded nightly would be quietly ruined over a year. It is
     * touched only when it is too big for any screen, lying on its side, or
     * carrying far more bytes than its pixels can account for.
     */
    private static function needsIt(string $file, int $type, int $width, int $height, int $bytes, int $longestEdge): bool
    {
        if (max($width, $height) > $longestEdge) {
            return true;
        }

        if (self::orientation($file, $type) !== 1) {
            return true;
        }

        // A lossy photograph saved at quality 100 runs to about a byte a
        // pixel; the same picture at a quality nobody can tell apart is a
        // fifth of that. Half a byte is comfortably between the two, and
        // PNG is left out of it — lossless, so its size says nothing about
        // how it was saved.
        return in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)
            && $bytes / max(1, $width * $height) > 0.5;
    }

    /**
     * Written to a file beside it, then moved over the top.
     *
     * A half-written photograph is worse than a large one, and encoding
     * straight over the file being read from is how that happens.
     */
    private static function write(\GdImage $image, string $file, int $type, int $quality): bool
    {
        $temporary = $file.'.tidying';

        $ok = match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $temporary, $quality),
            IMAGETYPE_WEBP => imagewebp($image, $temporary, $quality),
            // PNG is lossless, so quality is a compression level instead, and
            // transparency has to survive the round trip.
            IMAGETYPE_PNG  => imagealphablending($image, false)
                && imagesavealpha($image, true)
                && imagepng($image, $temporary, 8),
        };

        if (! $ok || ! is_file($temporary)) {
            @unlink($temporary);

            return false;
        }

        // Never make a file bigger. A picture already small and sharp is one
        // somebody prepared on purpose.
        if (filesize($temporary) >= filesize($file)) {
            @unlink($temporary);

            return false;
        }

        return rename($temporary, $file);
    }

    /**
     * Turned to match the way the camera was held.
     *
     * A phone writes the picture out as the sensor saw it and adds a note
     * saying which way up it was. Re-encoding drops the note, so a portrait
     * saree would come out on its side — the rotation has to be baked in
     * before the picture is written again.
     */
    private static function theRightWayUp(\GdImage $image, string $file, int $type): \GdImage
    {
        $orientation = self::orientation($file, $type);

        $turned = match ($orientation) {
            3, 4 => imagerotate($image, 180, 0),
            5, 6 => imagerotate($image, -90, 0),
            7, 8 => imagerotate($image, 90, 0),
            default => null,
        };

        if ($turned instanceof \GdImage) {
            imagedestroy($image);
            $image = $turned;
        }

        // The mirrored orientations, which come from a front camera.
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        return $image;
    }

    /** Which way up the camera says it was held. 1 is upright. */
    private static function orientation(string $file, int $type): int
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return 1;
        }

        return (int) (@exif_read_data($file)['Orientation'] ?? 1);
    }

    /**
     * Is there enough memory to open this at all?
     *
     * GD holds every pixel as four bytes and needs a second copy while it
     * resamples, so a 24-megapixel photograph wants a couple of hundred
     * megabytes. Whether that counts against PHP's own limit depends on how
     * PHP was built: where it does, going over is a fatal error and the shop
     * sees a blank screen, so the question has to be settled before the
     * attempt — and where it does not, refusing on those grounds would skip
     * every photograph worth shrinking. Hence the measurement rather than an
     * assumption.
     */
    private static function thereIsRoomFor(int $width, int $height): bool
    {
        // Larger than any camera a shop owns, and the point past which being
        // wrong costs the whole request rather than one upload.
        if ($width * $height > 80_000_000) {
            return false;
        }

        $limit = self::memoryLimit();

        if ($limit <= 0 || ! self::gdIsCountedAgainstTheLimit()) {
            return true;
        }

        $needed = $width * $height * 4 * 2.2;

        return $needed < ($limit - memory_get_usage(true)) * 0.8;
    }

    /**
     * Does a GD image show up in PHP's own accounting?
     *
     * Four megabytes, allocated and freed, and the arithmetic answers it.
     */
    private static function gdIsCountedAgainstTheLimit(): bool
    {
        static $counted = null;

        if ($counted !== null) {
            return $counted;
        }

        $before = memory_get_usage();
        $probe = @imagecreatetruecolor(1000, 1000);

        if (! $probe instanceof \GdImage) {
            return $counted = true;
        }

        $counted = (memory_get_usage() - $before) > 2 * 1024 * 1024;

        imagedestroy($probe);

        return $counted;
    }

    private static function memoryLimit(): int
    {
        $limit = self::bytes((string) ini_get('memory_limit'));

        // Shared hosts usually allow this to be raised for one request, and a
        // photograph is exactly the sort of request it exists for.
        if ($limit > 0 && $limit < 512 * 1024 * 1024 && @ini_set('memory_limit', '512M') !== false) {
            $limit = self::bytes((string) ini_get('memory_limit'));
        }

        return $limit;
    }

    private static function bytes(string $size): int
    {
        $size = trim($size);

        if ($size === '' || $size === '-1') {
            return -1;
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
