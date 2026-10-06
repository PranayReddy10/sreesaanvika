<?php

namespace App\Filament\Shared;

use App\Models\Video;
use App\Support\Film;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\HtmlString;

/**
 * The fields for a film, written once.
 *
 * Used by the front-page reel and by the tab on each saree, so the two cannot
 * drift apart — and so the warning about file size is in both places, which is
 * where it is actually needed.
 */
class VideoFields
{
    /** @return array<int, mixed> */
    public static function make(): array
    {
        return [
            TextInput::make('title')
                ->label('Call it')
                ->maxLength(140)
                ->placeholder('Draped three ways'),

            TextInput::make('caption')
                ->label('A line across the bottom')
                ->maxLength(140)
                ->placeholder('Pure silk, pure zari'),

            FileUpload::make('path')
                ->label('The film')
                ->disk('public')
                ->directory('videos')
                // application/mp4 as well: a film with no sound track — a stock
                // clip, something trimmed — is named that by the server's own
                // detector, and is an ordinary playable film.
                ->acceptedFileTypes(['video/mp4', 'application/mp4', 'video/webm', 'video/quicktime'])
                ->maxSize(self::uploadCeiling())
                ->helperText(self::sizeAdvice())
                /*
                 * Read before it is accepted. A film straight off an iPhone is
                 * usually HEVC, which plays on that phone and on no Android
                 * phone and no Windows browser — the upload works, the file is
                 * there, and every shopper gets a black rectangle. Said here,
                 * where it can still be fixed, rather than discovered later by
                 * a customer.
                 */
                ->rule(fn () => function (string $attribute, $value, $fail) {
                    $file = $value instanceof UploadedFile ? $value->getRealPath() : null;

                    if (! $file || ! is_file($file)) {
                        return;
                    }

                    $codec = Film::unplayableCodec($file);

                    if ($codec === null) {
                        return;
                    }

                    $fail("This film is {$codec}, which most browsers cannot play — it would show as "
                        . 'a black square to anyone not on an iPhone. Save or export it as MP4 (H.264) '
                        . 'and upload that. On an iPhone: Settings → Camera → Formats → Most Compatible '
                        . 'films in H.264 from then on.');
                })
                ->columnSpanFull(),

            TextInput::make('url')
                ->label('…or paste an Instagram reel, or an address elsewhere')
                ->maxLength(500)
                ->placeholder('https://www.instagram.com/reel/…')
                ->live(onBlur: true)
                ->helperText(new HtmlString(
                    'Paste the link straight out of Instagram — <em>Share → Copy link</em> — and it works. '
                    . 'A direct .mp4 or .webm on a bucket or a CDN works too.'
                ))
                ->columnSpanFull()
                // Only one of the two, or there is no saying which plays.
                ->requiredWithout('path')
                ->disabled(fn (Get $get) => filled($get('path')))
                ->rule(fn () => function (string $attribute, $value, $fail) {
                    if (blank($value)) {
                        return;
                    }

                    $video = new Video(['url' => $value]);

                    if ($video->instagramCode()) {
                        return;
                    }

                    // Anything else has to be a film the browser can play. A
                    // link to a YouTube page or an Instagram profile is a page,
                    // not a film, and would show a shopper nothing at all.
                    if (! filter_var($value, FILTER_VALIDATE_URL)) {
                        $fail('That does not look like a web address.');

                        return;
                    }

                    $extension = strtolower((string) pathinfo(
                        (string) parse_url($value, PHP_URL_PATH),
                        PATHINFO_EXTENSION,
                    ));

                    if (! in_array($extension, ['mp4', 'webm', 'ogv', 'ogg', 'mov'], true)) {
                        $fail('Either an Instagram reel link, or a direct link to an .mp4 or .webm file. '
                            . 'A YouTube or Facebook page cannot be played inside the shop.');
                    }
                }),

            Placeholder::make('instagram_note')
                ->hiddenLabel()
                ->visible(fn (Get $get) => (new Video(['url' => (string) $get('url')]))->instagramCode() !== null)
                ->content(new HtmlString(
                    '<strong>Instagram reel.</strong> It will show as a still with a play button, '
                    . 'and one tap hands over to Instagram. Instagram does not let a website start '
                    . 'its reels by itself — only a film you upload here plays on its own as the '
                    . 'shopper scrolls to it. Nothing of Instagram’s is loaded until she taps, '
                    . 'so the page stays quick.'
                ))
                ->columnSpanFull(),

            FileUpload::make('poster')
                ->label('The frame to show first')
                ->image()
                ->disk('public')
                ->directory('videos/posters')
                ->imageEditor()
                ->maxSize(3072)
                ->helperText(new HtmlString(
                    'Optional for a film you upload — without one, the saree’s own first photograph is used. '
                    . '<strong>Worth adding for an Instagram reel</strong>, since Instagram does not hand out '
                    . 'the cover frame and the still is all a shopper sees until she taps.'
                ))
                ->columnSpanFull(),

            TextInput::make('position')
                ->label('Order')
                ->numeric()
                ->default(0)
                ->required()
                ->helperText('Lower numbers come first.'),

            Toggle::make('is_visible')
                ->label('Show it')
                ->default(true),

            Toggle::make('on_home')
                ->label('Also show it on the front page')
                ->default(true)
                ->helperText('The reel along the bottom of the home page.')
                ->columnSpanFull(),
        ];
    }

    /**
     * What this server will actually accept, in kilobytes.
     *
     * Read from PHP rather than guessed: a shop told it may upload 50 MB by a
     * form on a server that stops at 8 MB gets a blank error and no idea why.
     */
    public static function uploadCeiling(): int
    {
        $limits = array_filter([
            self::bytes((string) ini_get('upload_max_filesize')),
            self::bytes((string) ini_get('post_max_size')),
        ]);

        $bytes = $limits === [] ? 8 * 1024 * 1024 : min($limits);

        // A little under, because the rest of the form goes in the same post.
        return (int) max(1024, floor(($bytes * 0.9) / 1024));
    }

    private static function sizeAdvice(): string
    {
        $mb = round(self::uploadCeiling() / 1024);

        return "Up to about {$mb} MB on this server, and the smaller the better — "
            . 'most of your customers are on a phone paying for their data. '
            . 'Fifteen to thirty seconds, portrait, is the right shape. '
            . 'For anything bigger, put it on a bucket and paste the address below.';
    }

    private static function bytes(string $size): ?int
    {
        $size = trim($size);

        if ($size === '' || $size === '-1') {
            return null;
        }

        $number = (float) $size;

        return (int) match (strtolower(substr($size, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
