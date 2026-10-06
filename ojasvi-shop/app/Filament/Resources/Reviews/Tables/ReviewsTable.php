<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Models\Review;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

/**
 * The job on this screen is one decision per row — show it or do not — so
 * approving is a single click from the list and never needs the form.
 */
class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('rating')
                    ->label('')
                    ->state(fn (Review $record) => str_repeat('★', (int) $record->rating)
                        . str_repeat('☆', 5 - (int) $record->rating))
                    ->color(fn (Review $record) => (int) $record->rating >= 4 ? 'warning' : 'gray')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Review')
                    ->searchable()
                    ->weight('medium')
                    ->wrap()
                    ->description(fn (Review $record) => str($record->body ?? '')->limit(110))
                    ->placeholder('(no title)'),

                TextColumn::make('product.name')
                    ->label('Saree')
                    ->searchable()
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('By')
                    ->searchable()
                    ->description(fn (Review $record) => $record->is_verified ? 'Verified purchase' : null),

                IconColumn::make('is_approved')
                    ->label('Showing')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Written')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_approved')
                    ->label('Showing on the shop')
                    ->placeholder('All reviews')
                    ->trueLabel('Showing')
                    ->falseLabel('Waiting for you'),

                SelectFilter::make('rating')
                    ->options([5 => '5 stars', 4 => '4 stars', 3 => '3 stars', 2 => '2 stars', 1 => '1 star']),

                SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Saree'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(fn (Review $record) => $record->is_approved ? 'Hide' : 'Show')
                    ->icon(fn (Review $record) => $record->is_approved ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (Review $record) => $record->is_approved ? 'gray' : 'success')
                    ->action(function (Review $record) {
                        $record->update(['is_approved' => ! $record->is_approved]);

                        Notification::make()
                            ->title($record->is_approved ? 'Now showing on the shop' : 'Hidden from the shop')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label('Show these')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['is_approved' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('hide')
                        ->label('Hide these')
                        ->icon('heroicon-o-eye-slash')
                        ->action(fn (Collection $records) => $records->each->update(['is_approved' => false]))
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No reviews yet');
    }
}
