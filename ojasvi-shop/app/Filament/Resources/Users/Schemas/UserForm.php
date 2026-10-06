<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Who they are')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(140),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(190)
                            ->unique(ignoreRecord: true),

                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('9000000000'),

                        FileUpload::make('avatar')
                            ->label('Photograph')
                            ->image()
                            ->avatar()
                            ->disk('public')
                            ->directory('avatars')
                            ->maxSize(2048),
                    ]),

                Section::make('Signing in')
                    ->columns(2)
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->maxLength(72)
                            ->required(fn (string $operation) => $operation === 'create')
                            // Left blank on an edit, the existing password
                            // stands — saving a form must never lock someone out.
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->helperText(fn (string $operation) => $operation === 'edit'
                                ? 'Leave blank to keep the current password.'
                                : 'At least eight characters.'),

                        Toggle::make('is_admin')
                            ->label('Can use this admin')
                            ->helperText('Give this to staff only. An admin can change prices, orders and settings.'),
                    ]),
            ]);
    }
}
