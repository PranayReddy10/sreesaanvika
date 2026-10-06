<?php

namespace App\Filament\Resources\Enquiries\Tables;

use App\Models\Enquiry;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * One decision per row: answered, or not yet.
 */
class EnquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                /*
                 * The question first and the person under it, the way the
                 * Reviews screen is laid out. Given a column of its own the
                 * message had to share the width evenly with four short ones
                 * and wrapped to a word a line, which is unreadable; width()
                 * does not help, because the table lays itself out from its
                 * content.
                 */
                TextColumn::make('message')
                    ->label('Asked')
                    ->weight('medium')
                    ->wrap()
                    ->searchable()
                    ->description(fn (Enquiry $record) => $record->name
                        .' · '.$record->email
                        .($record->phone ? ' · '.$record->phone : '')),

                TextColumn::make('order_number')
                    ->label('About')
                    ->placeholder('Nothing in particular')
                    ->searchable()
                    // Only a number somebody typed: it says so plainly when
                    // there is no such order, rather than quietly suggesting
                    // there is.
                    ->description(fn (Enquiry $record) => $record->order_number
                        ? ($record->order() ? 'An order of ours' : 'No order with that number')
                        : null),

                TextColumn::make('answered_at')
                    ->label('Answered')
                    ->badge()
                    ->state(fn (Enquiry $record) => $record->isAnswered() ? 'Answered' : 'Waiting')
                    ->color(fn ($state) => $state === 'Answered' ? 'success' : 'warning')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Came in')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('answered_at')
                    ->label('Answered')
                    ->placeholder('All messages')
                    ->trueLabel('Answered')
                    ->falseLabel('Still waiting')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('answered_at'),
                        false: fn ($query) => $query->whereNull('answered_at'),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->recordActions([
                // Opens the mail program with the address and the subject
                // filled in, because that is where a reply actually gets
                // written. The shop's own copy of the email does the same by
                // being addressed from them.
                Action::make('reply')
                    ->label('Reply')
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedEnvelope)
                    ->url(fn (Enquiry $record) => 'mailto:'.$record->email
                        .'?subject='.rawurlencode('Re: your message to OJASVI')
                        .'&body='.rawurlencode("\n\n—\nYou wrote:\n\n".$record->message))
                    ->openUrlInNewTab(),

                Action::make('answered')
                    ->label(fn (Enquiry $record) => $record->isAnswered() ? 'Not answered' : 'Mark answered')
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedCheckCircle)
                    ->color(fn (Enquiry $record) => $record->isAnswered() ? 'gray' : 'success')
                    ->action(function (Enquiry $record): void {
                        $record->update(['answered_at' => $record->isAnswered() ? null : now()]);

                        Notification::make()
                            ->title($record->isAnswered() ? 'Marked as answered' : 'Put back on the list')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make(),
            ])
            ->emptyStateHeading('Nothing yet')
            ->emptyStateDescription('Messages from the contact page arrive here, and in the shop’s inbox.');
    }
}
