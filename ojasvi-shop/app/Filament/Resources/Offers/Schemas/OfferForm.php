<?php

namespace App\Filament\Resources\Offers\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * The offer screen.
 *
 * An offer here needs no code typed at checkout — the shop says which sarees it
 * covers and the bag works the rest out. Two shapes only, because these are the
 * two the shop actually runs: buy some and get some, or the more you take the
 * cheaper each one gets.
 */
class OfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('What the offer is')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Name it for yourself')
                            ->required()
                            ->maxLength(140)
                            ->helperText('Only you see this.')
                            ->columnSpanFull(),

                        TextInput::make('headline')
                            ->label('What the shopper is told')
                            ->maxLength(140)
                            ->placeholder('Buy 2, get 1 free')
                            ->helperText('Leave it blank and the shop writes the line itself.')
                            ->columnSpanFull(),

                        Radio::make('kind')
                            ->label('Shape')
                            ->required()
                            ->default('bogo')
                            ->live()
                            ->options([
                                'bogo'  => 'Buy some, get some',
                                'tiers' => 'The more you take, the less each costs',
                            ])
                            ->descriptions([
                                'bogo'  => 'The cheapest qualifying piece is the one given away — that is the promise the banner makes.',
                                'tiers' => 'A percentage off every qualifying piece once the bag reaches a set number.',
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Buy some, get some')
                    ->visible(fn (Get $get) => $get('kind') === 'bogo')
                    ->columns(3)
                    ->schema([
                        TextInput::make('buy')
                            ->label('Buy this many')
                            ->numeric()->minValue(1)->maxValue(20)
                            ->default(2)->required(),

                        TextInput::make('get')
                            ->label('Get this many')
                            ->numeric()->minValue(1)->maxValue(20)
                            ->default(1)->required(),

                        TextInput::make('percent')
                            ->label('Discount on those')
                            ->numeric()->minValue(1)->maxValue(100)
                            ->suffix('%')
                            ->default(100)->required()
                            ->helperText('100% is free.'),

                        Toggle::make('repeats')
                            ->label('Apply again and again')
                            ->default(true)
                            ->helperText('On: six pieces give two free. Off: only the first round counts.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Quantity breaks')
                    ->visible(fn (Get $get) => $get('kind') === 'tiers')
                    ->schema([
                        Repeater::make('tiers')
                            ->label('')
                            ->addActionLabel('Add a break')
                            ->defaultItems(1)
                            ->columns(2)
                            ->schema([
                                TextInput::make('qty')
                                    ->label('From this many pieces')
                                    ->numeric()->minValue(2)->maxValue(50)
                                    ->required(),

                                TextInput::make('percent')
                                    ->label('Off each one')
                                    ->numeric()->minValue(1)->maxValue(90)
                                    ->suffix('%')
                                    ->required(),
                            ])
                            ->helperText('The best break the bag reaches is the one used, whatever order you type them in.'),
                    ]),

                Section::make('Which sarees it covers')
                    ->schema([
                        Toggle::make('applies_to_all')
                            ->label('Every saree in the shop')
                            ->live()
                            ->default(false),

                        Select::make('products')
                            ->label('These sarees')
                            ->relationship('products', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => ! $get('applies_to_all')),

                        Select::make('categories')
                            ->label('Everything in these collections')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => ! $get('applies_to_all'))
                            ->helperText('Added to whatever you picked above.'),
                    ]),

                Section::make('When it runs')
                    ->columns(3)
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('From')
                            ->seconds(false)
                            ->placeholder('Straight away'),

                        DateTimePicker::make('ends_at')
                            ->label('Until')
                            ->seconds(false)
                            ->placeholder('No end')
                            ->after('starts_at'),

                        TextInput::make('position')
                            ->label('Order')
                            ->numeric()->default(0)->required()
                            ->helperText('When two offers could apply, the lower number wins.'),

                        Toggle::make('is_active')
                            ->label('Running')
                            ->default(true)
                            ->helperText('Turn this off to stop the offer without losing how it was set up.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
