<?php

namespace App\Filament\Resources\NewsletterSubscribers\Tables;

use App\Models\NewsletterSubscriber;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterSubscribersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->weight('medium'),

                TextColumn::make('name')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('state')
                    ->label('On the list')
                    ->badge()
                    ->state(fn (NewsletterSubscriber $record) => $record->unsubscribed_at ? 'Left' : 'Yes')
                    ->color(fn ($state) => $state === 'Yes' ? 'success' : 'gray'),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->date('j M Y')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('current')
                    ->label('Still on the list')
                    ->default()
                    ->query(fn (Builder $q) => $q->whereNull('unsubscribed_at')),
            ])
            ->headerActions([
                /*
                 * A file the shop can hand to whatever it sends email with.
                 * Everything here exists so the shop owns its own list: a
                 * marketing service that can be left is one that cannot hold
                 * the shop to ransom.
                 */
                Action::make('download')
                    ->label('Download as CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (): StreamedResponse => response()->streamDownload(function () {
                        $out = fopen('php://output', 'w');
                        fputcsv($out, ['email', 'name', 'joined']);

                        NewsletterSubscriber::whereNull('unsubscribed_at')
                            ->orderBy('created_at')
                            ->chunk(500, function ($rows) use ($out) {
                                foreach ($rows as $row) {
                                    fputcsv($out, [$row->email, $row->name, $row->created_at?->toDateString()]);
                                }
                            });

                        fclose($out);
                    }, 'ojasvi-list-' . now()->toDateString() . '.csv', ['Content-Type' => 'text/csv'])),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nobody has signed up yet')
            ->emptyStateDescription('The sign-up sits at the foot of every page on the shop.');
    }
}
