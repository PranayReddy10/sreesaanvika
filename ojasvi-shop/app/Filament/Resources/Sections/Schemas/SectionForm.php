<?php

namespace App\Filament\Resources\Sections\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * The front page, as rows the shop owns.
 *
 * Keeping the home page in the database rather than in a template is what lets
 * the shop reorder it, retitle it and switch a band off without a developer —
 * the lesson of the WordPress build, where every change meant an editor.
 */
class SectionForm
{
    public const KINDS = [
        'hero'       => 'Hero — the big pictures at the top',
        'featured'   => 'A chosen few — the sarees you want seen first',
        'collection' => 'Everything — the full grid of sarees',
        'lookbook'   => 'Lookbook — large photographs, little text',
        'band'       => 'A band of words — why shop here, how it is posted',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormSection::make('The row')
                    ->columns(2)
                    ->schema([
                        Select::make('key')
                            ->label('What kind of row')
                            ->options(self::KINDS)
                            ->required()
                            ->live()
                            ->native(false)
                            ->columnSpanFull(),

                        TextInput::make('eyebrow')
                            ->label('Small line above')
                            ->maxLength(80)
                            ->placeholder('Handwoven'),

                        TextInput::make('heading')
                            ->label('Heading')
                            ->maxLength(140)
                            ->placeholder('Sarees chosen one at a time'),

                        Textarea::make('subheading')
                            ->label('Line underneath')
                            ->rows(2)
                            ->maxLength(300)
                            ->columnSpanFull(),
                    ]),

                FormSection::make('How many to show')
                    ->visible(fn (Get $get) => in_array($get('key'), ['featured', 'collection', 'lookbook'], true))
                    ->schema([
                        TextInput::make('settings.limit')
                            ->label('Number of sarees')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(48)
                            ->default(8),
                    ]),

                FormSection::make('Where it sits')
                    ->columns(2)
                    ->schema([
                        TextInput::make('position')
                            ->label('Order on the page')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Lower numbers come first.'),

                        Toggle::make('is_visible')
                            ->label('Show this row')
                            ->default(true),
                    ]),
            ]);
    }
}
