<?php

namespace App\Filament\Resources\Coupons\Tables;

use App\Models\Coupon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->copyable()
                    ->weight('medium')
                    ->fontFamily('mono'),

                TextColumn::make('worth')
                    ->label('Takes off')
                    ->state(fn (Coupon $record) => match ($record->type) {
                        'percent'       => (int) $record->value . '%'
                            . ($record->max_discount ? ' (max ₹' . number_format((float) $record->max_discount) . ')' : ''),
                        'fixed'         => '₹' . number_format((float) $record->value),
                        'free_shipping' => 'Delivery',
                        default         => '—',
                    }),

                TextColumn::make('min_spend')
                    ->label('Minimum bag')
                    ->money('INR')
                    ->placeholder('Any'),

                TextColumn::make('used')
                    ->label('Used')
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn ($state, Coupon $record) => $record->usage_limit
                        ? $state . ' of ' . $record->usage_limit
                        : (string) $state)
                    // Red once it is spent, so a code that has quietly stopped
                    // working says so from the list.
                    ->color(fn (Coupon $record) => $record->usage_limit && $record->used >= $record->usage_limit
                        ? 'danger'
                        : 'gray'),

                TextColumn::make('window')
                    ->label('When')
                    ->state(fn (Coupon $record) => ($record->starts_at?->format('j M Y') ?? 'now')
                        . ' → ' . ($record->ends_at?->format('j M Y') ?? 'no end'))
                    ->toggleable(),

                TextColumn::make('state')
                    ->label('Status')
                    ->badge()
                    ->state(function (Coupon $record) {
                        if (! $record->is_active) {
                            return 'Switched off';
                        }
                        if ($record->ends_at && $record->ends_at->isPast()) {
                            return 'Expired';
                        }
                        if ($record->starts_at && $record->starts_at->isFuture()) {
                            return 'Not started';
                        }
                        if ($record->usage_limit && $record->used >= $record->usage_limit) {
                            return 'Fully used';
                        }

                        return 'Working';
                    })
                    ->color(fn ($state) => $state === 'Working' ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Takes off')
                    ->options([
                        'percent' => 'A percentage',
                        'fixed' => 'A fixed amount',
                        'free_shipping' => 'The delivery charge',
                    ]),

                TernaryFilter::make('is_active')->label('Switched on'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No coupon codes')
            ->emptyStateDescription('A coupon is typed at checkout. For something that applies itself, use an offer.');
    }
}
