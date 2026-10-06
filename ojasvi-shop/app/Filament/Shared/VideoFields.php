<?php

namespace App\Filament\Shared;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;

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
                ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])
                ->maxSize(self::uploadCeiling())
                ->helperText(self::sizeAdvice())
                ->columnSpanFull(),

            TextInput::make('url')
                ->label('…or its address, if it lives elsewhere')
                ->url()
                ->maxLength(500)
                ->placeholder('https://…/draped.mp4')
                ->helperText('A direct link to an .mp4 or .webm — a bucket, or a CDN. Not a YouTube or Instagram page: those cannot be played inside the shop.')
                ->columnSpanFull()
                // Only one of the two, or there is no saying which plays.
                ->requiredWithout('path')
                ->disabled(fn (Get $get) => filled($get('path'))),

            FileUpload::make('poster')
                ->label('The frame to show first')
                ->image()
                ->disk('public')
                ->directory('videos/posters')
                ->imageEditor()
                ->maxSize(3072)
                ->helperText('Optional. Without one, the saree’s own first photograph is used.')
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
