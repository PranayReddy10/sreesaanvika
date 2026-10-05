<?php

namespace App\Filament\Resources\Coupons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * A coupon is a code the shopper types. An offer applies itself — if nobody has
 * to be told a code, it belongs under Offers instead.
 */
class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('The code')
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->maxLength(40)
                            ->unique(ignoreRecord: true)
                            ->placeholder('OJASVI10')
                            // Shoppers type in whatever case they like; stored
                            // upper so the lookup never has to guess.
                            ->dehydrateStateUsing(fn (?string $state) => strtoupper(trim((string) $state)))
                            ->helperText('Not case sensitive for the shopper.'),

                        Radio::make('type')
                            ->label('Takes off')
                            ->required()
                            ->default('percent')
                            ->live()
                            ->options([
                                'percent'       => 'A percentage',
                                'fixed'         => 'A fixed amount',
                                'free_shipping' => 'The delivery charge',
                            ]),

                        TextInput::make('value')
                            ->label(fn (Get $get) => $get('type') === 'percent' ? 'Percentage off' : 'Amount off')
                            ->numeric()
                            ->minValue(1)
                            ->prefix(fn (Get $get) => $get('type') === 'fixed' ? '₹' : null)
                            ->suffix(fn (Get $get) => $get('type') === 'percent' ? '%' : null)
                            ->maxValue(fn (Get $get) => $get('type') === 'percent' ? 90 : 1000000)
                            ->required(fn (Get $get) => $get('type') !== 'free_shipping')
                            ->visible(fn (Get $get) => $get('type') !== 'free_shipping')
                            ->default(10),
                    ]),

                Section::make('Conditions')
                    ->columns(2)
                    ->schema([
                        TextInput::make('min_spend')
                            ->label('Bag must be at least')
                            ->numeric()->prefix('₹')
                            ->placeholder('No minimum'),

                        TextInput::make('max_discount')
                            ->label('But never take off more than')
                            ->numeric()->prefix('₹')
                            ->placeholder('No cap')
                            ->visible(fn (Get $get) => $get('type') === 'percent')
                            ->helperText('Worth setting: 25% off a bridal silk is a large number.'),

                        TextInput::make('usage_limit')
                            ->label('Total uses allowed')
                            ->numeric()->minValue(1)
                            ->placeholder('Unlimited'),

                        TextInput::make('usage_limit_per_user')
                            ->label('Uses per customer')
                            ->numeric()->minValue(1)
                            ->placeholder('Unlimited')
                            ->helperText('Set to 1 for a welcome code.'),
                    ]),

                Section::make('When it works')
                    ->columns(3)
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('From')->seconds(false)
                            ->placeholder('Straight away'),

                        DateTimePicker::make('ends_at')
                            ->label('Until')->seconds(false)
                            ->placeholder('No end')
                            ->after('starts_at'),

                        Toggle::make('is_active')
                            ->label('Working')
                            ->default(true),

                        TextInput::make('used')
                            ->label('Times used')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit')
                            ->helperText('Counted by the shop, not typed here.'),
                    ]),
            ]);
    }
}
