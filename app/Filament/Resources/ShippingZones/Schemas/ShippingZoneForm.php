<?php

namespace App\Filament\Resources\ShippingZones\Schemas;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * What delivery costs where.
 *
 * Matched on the start of a pincode, so "500" covers all of Hyderabad without
 * anybody typing out every pincode in the city. Zones are tried in order and
 * the first match wins, so the catch-all belongs last.
 */
class ShippingZoneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('The area')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120)
                            ->placeholder('Telangana & Andhra Pradesh'),

                        TextInput::make('position')
                            ->label('Checked in this order')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('The first zone that matches is used, so leave the catch-all last.'),

                        TagsInput::make('pincodes')
                            ->label('Pincodes starting with')
                            ->placeholder('500')
                            ->helperText('Type a prefix and press enter — "50" covers every pincode beginning 50. Leave empty to match everywhere.')
                            ->columnSpanFull(),
                    ]),

                Section::make('What it costs')
                    ->columns(2)
                    ->schema([
                        TextInput::make('rate')
                            ->label('Delivery charge')
                            ->numeric()->prefix('₹')
                            ->default(0)
                            ->required(),

                        TextInput::make('free_from')
                            ->label('Free once the bag reaches')
                            ->numeric()->prefix('₹')
                            ->placeholder('Never free'),

                        TextInput::make('days_min')
                            ->label('Arrives in, at least')
                            ->numeric()->suffix('days')
                            ->minValue(1)->maxValue(60),

                        TextInput::make('days_max')
                            ->label('And at most')
                            ->numeric()->suffix('days')
                            ->minValue(1)->maxValue(90)
                            ->gte('days_min'),
                    ]),

                Section::make('How they can pay')
                    ->columns(2)
                    ->schema([
                        Toggle::make('cod_allowed')
                            ->label('Cash on delivery here')
                            ->default(true),

                        Toggle::make('is_active')
                            ->label('Delivering here')
                            ->default(true)
                            ->helperText('Off means checkout tells the shopper this pincode cannot be served.'),
                    ]),
            ]);
    }
}
