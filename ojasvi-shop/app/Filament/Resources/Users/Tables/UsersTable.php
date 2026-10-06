<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The customer list — read as "who buys from us", so what they have spent and
 * when they last came are the two columns that earn their place.
 */
class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (User $record) => $record->email),

                TextColumn::make('phone')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'primary' : 'gray')
                    ->sortable(),

                TextColumn::make('spent')
                    ->label('Spent')
                    // Paid orders only: a basket abandoned at the payment page
                    // is not money, and totalling it would flatter the report.
                    ->state(fn (User $record) => '₹' . number_format(
                        (float) $record->orders()->where('payment_status', 'paid')->sum('grand_total')
                    ))
                    ->sortable(query: fn (Builder $query, string $direction) => $query
                        ->withSum(['orders as paid_total' => fn ($q) => $q->where('payment_status', 'paid')], 'grand_total')
                        ->orderBy('paid_total', $direction)),

                IconColumn::make('is_admin')
                    ->label('Staff')
                    ->boolean()
                    ->trueIcon('heroicon-o-key')
                    ->falseIcon('heroicon-o-minus')
                    ->falseColor('gray'),

                TextColumn::make('last_login_at')
                    ->label('Last seen')
                    ->since()
                    ->placeholder('Never signed in')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->date('j M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_admin')->label('Staff accounts'),

                Filter::make('has_ordered')
                    ->label('Has ordered')
                    ->query(fn (Builder $query) => $query->whereHas('orders')),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nobody has signed up yet');
    }
}
