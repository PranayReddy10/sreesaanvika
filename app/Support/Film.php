<?php

namespace App\Support;

/**
 * What is actually inside a film file.
 *
 * A shop films a saree on a phone and uploads what comes out. From an iPhone
 * that is usually HEVC — which plays beautifully on the phone it was filmed
 * on, and on no Android phone and no Windows browser at all. The upload
 * succeeds, the file is there, and every shopper gets a black rectangle where
 * the film should be, with nothing anywhere saying why.
 *
 * So the file is read before it is accepted. No ffmpeg: shared hosting has
 * none, and none is needed — an MP4 or a .mov says what it holds in its own
 * header, in a tree of boxes that is a few dozen lines to walk.
 */
class Film
{
    /**
     * Codecs a shopper's browser can be relied on to play.
     *
     * H.264 is the one every browser has had for fifteen years. VP8, VP9 and
     * AV1 arrive in a WebM, which is a different container entirely and is
     * never anything a browser cannot play, so it is not checked at all.
     */
    private const PLAYABLE = ['avc1', 'avc3', 'av01', 'vp08', 'vp09'];

    /** Codecs that are definitely a black rectangle somewhere. */
    private const REFUSED = [
        'hvc1' => 'HEVC',
        'hev1' => 'HEVC',
        'dvh1' => 'Dolby Vision',
        'dvhe' => 'Dolby Vision',
        'mp4v' => 'MPEG-4 Part 2',
    ];

    /**
     * The name of what the film is encoded with, as a person would say it,
     * or null where there is nothing to complain about.
     *
     * Null covers three different situations on purpose: a file that is fine,
     * a file this cannot read, and a container it does not know. Only a
     * positive identification of something unplayable is worth stopping an
     * upload for — guessing wrong and refusing a perfectly good film is the
     * worse mistake, because the shop then has no way at all to put it up.
     */
    public static function unplayableCodec(string $file): ?string
    {
        $codec = self::videoCodec($file);

        if ($codec === null || in_array($codec, self::PLAYABLE, true)) {
            return null;
        }

        return self::REFUSED[$codec] ?? null;
    }

    /**
     * The four letters an MP4 or .mov uses to name its video codec.
     *
     * Both are the same format underneath — ISO base media — a tree of boxes
     * each of which gives its own length, so the whole file does not have to
     * be read or understood to find the one that matters:
     *
     *     moov → trak → mdia → minf → stbl → stsd → [avc1|hvc1|…]
     */
    public static function videoCodec(string $file): ?string
    {
        $handle = @fopen($file, 'rb');

        if ($handle === false) {
            return null;
        }

        try {
            $size = filesize($file) ?: 0;

            foreach (self::boxes($handle, 0, $size) as [$type, $start, $end]) {
                if ($type !== 'moov') {
                    continue;
                }

                return self::findVideoTrack($handle, $start, $end);
            }
        } finally {
            fclose($handle);
        }

        return null;
    }

    /**
     * Down through the containers to the one box that names the codec.
     *
     *     moov → trak → mdia → minf → stbl → stsd → [avc1|hvc1|…]
     *
     * A film from a phone has two tracks, the sound usually written first, and
     * both are laid out this way — so the handler has to be read as well, or
     * what comes back is the name of the audio codec and the film's own is
     * never looked at. (Which is exactly what happened here until the test
     * films were given a sound track like a real one.)
     */
    private static function findVideoTrack($handle, int $start, int $end): ?string
    {
        foreach (self::boxes($handle, $start, $end) as [$type, $from, $to]) {
            if ($type !== 'trak') {
                continue;
            }

            $codec = self::codecInTrack($handle, $from, $to);

            if ($codec !== null) {
                return $codec;
            }
        }

        return null;
    }

    /** The codec of this one track, if this track is the picture. */
    private static function codecInTrack($handle, int $start, int $end): ?string
    {
        foreach (self::boxes($handle, $start, $end) as [$type, $from, $to]) {
            if ($type !== 'mdia') {
                continue;
            }

            $isPicture = false;
            $media = null;

            foreach (self::boxes($handle, $from, $to) as [$inner, $innerFrom, $innerTo]) {
                if ($inner === 'hdlr') {
                    // Version and flags, then four reserved bytes, then the
                    // four letters saying what kind of track this is.
                    fseek($handle, $innerFrom + 8);
                    $isPicture = fread($handle, 4) === 'vide';
                }

                if ($inner === 'minf') {
                    $media = [$innerFrom, $innerTo];
                }
            }

            if ($isPicture && $media !== null) {
                return self::sampleEntry($handle, $media[0], $media[1], 0);
            }
        }

        return null;
    }

    /**
     * The four letters naming the codec, somewhere below here.
     *
     * Depth is capped because the tree comes from a file somebody uploaded,
     * and a file that says a box contains itself should cost a few wasted
     * microseconds rather than the whole request.
     */
    private static function sampleEntry($handle, int $start, int $end, int $depth): ?string
    {
        if ($depth > 6) {
            return null;
        }

        foreach (self::boxes($handle, $start, $end) as [$type, $from, $to]) {
            if ($type === 'stsd') {
                // Version, flags, and the number of entries, then the first
                // entry: its own length, and then its four letters.
                fseek($handle, $from + 8);
                $head = fread($handle, 8);

                return strlen((string) $head) === 8 ? substr($head, 4, 4) : null;
            }

            if ($type === 'stbl') {
                $found = self::sampleEntry($handle, $from, $to, $depth + 1);

                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * The boxes lying directly inside a stretch of the file.
     *
     * Each is a length and four letters, and then its contents. A length of 1
     * means the real one is the next eight bytes — films over 4 GB — and 0
     * means it runs to the end of the file.
     *
     * @return \Generator<int, array{0: string, 1: int, 2: int}>
     */
    private static function boxes($handle, int $start, int $end): \Generator
    {
        $at = $start;

        while ($at + 8 <= $end) {
            fseek($handle, $at);
            $header = fread($handle, 8);

            if ($header === false || strlen($header) < 8) {
                return;
            }

            $size = unpack('N', substr($header, 0, 4))[1];
            $type = substr($header, 4, 4);
            $contents = $at + 8;

            if ($size === 1) {
                $large = fread($handle, 8);

                if ($large === false || strlen($large) < 8) {
                    return;
                }

                // Two 32-bit halves: PHP has no portable unsigned 64-bit read,
                // and a film longer than 4 GB is not one this shop will serve.
                $halves = unpack('Nhigh/Nlow', $large);
                $size = ($halves['high'] << 32) | $halves['low'];
                $contents = $at + 16;
            } elseif ($size === 0) {
                $size = $end - $at;
            }

            // A box that claims to be shorter than its own header, or longer
            // than the file, is a file worth giving up on rather than looping
            // over for ever.
            if ($size < 8 || $at + $size > $end) {
                return;
            }

            yield [$type, $contents, $at + $size];

            $at += $size;
        }
    }
}
