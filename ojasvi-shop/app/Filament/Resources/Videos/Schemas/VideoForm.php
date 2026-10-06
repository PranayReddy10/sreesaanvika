<?php

namespace App\Filament\Resources\Videos\Schemas;

use App\Filament\Shared\VideoFields;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VideoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('The film')
                    ->description('These play on the front page, muted, as each one scrolls into view. A film of a saree being worn shows what a photograph cannot.')
                    ->columns(2)
                    ->schema(VideoFields::make()),

                Section::make('Is it of one particular saree?')
                    ->schema([
                        Select::make('product_id')
                            ->label('This film shows')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Nothing in particular')
                            ->helperText('Pick one and the film gets a price and a link on the front page, and also appears at the foot of that saree’s own page. You can do the same from the saree itself, under Films.'),
                    ]),
            ]);
    }
}
