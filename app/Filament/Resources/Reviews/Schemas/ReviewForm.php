<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Reviews are written by customers; this screen is for reading them, approving
 * them, and fixing a typo. The rating and the words stay the customer's.
 */
class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('What was written')
                    ->columns(2)
                    ->schema([
                        Select::make('product_id')
                            ->label('About')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('rating')
                            ->label('Stars')
                            ->required()
                            ->options([
                                5 => '★★★★★',
                                4 => '★★★★',
                                3 => '★★★',
                                2 => '★★',
                                1 => '★',
                            ])
                            ->native(false),

                        TextInput::make('name')
                            ->label('Signed')
                            ->required()
                            ->maxLength(120),

                        Select::make('user_id')
                            ->label('Account')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Not signed in'),

                        TextInput::make('title')
                            ->maxLength(140)
                            ->columnSpanFull(),

                        Textarea::make('body')
                            ->label('The review')
                            ->rows(5)
                            ->maxLength(4000)
                            ->columnSpanFull(),

                        FileUpload::make('images')
                            ->label('Photographs they sent')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->disk('public')
                            ->directory('reviews')
                            ->maxFiles(6)
                            ->maxSize(4096)
                            ->columnSpanFull(),
                    ]),

                Section::make('Showing it')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_approved')
                            ->label('Show on the shop')
                            ->default(false)
                            ->helperText('Nothing appears on a saree page until this is on.'),

                        Toggle::make('is_verified')
                            ->label('Marked as a real purchase')
                            ->default(false)
                            ->helperText('Set by the shop when the review is tied to a delivered order. Do not tick it by hand.'),
                    ]),
            ]);
    }
}
