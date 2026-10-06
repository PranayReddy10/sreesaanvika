<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

/**
 * Orders, in the order the work happens.
 *
 * Take the money, pack it, post it, and then it is the courier's problem. A
 * shop opening this screen in the morning wants the one pile it has to do
 * something about, not every order it has ever taken with a filter to set up
 * first — so the piles are tabs, and the numbers on the front page open them.
 */
class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    /*
     * The closure's argument has to be called $query.
     *
     * Filament hands these arguments by name, so a parameter called anything
     * else arrives as null and the page dies inside the table's own view, with
     * a message that names neither this file nor the tab it came from.
     */
    public function getTabs(): array
    {
        $count = fn (array|string $status): int => Order::query()
            ->whereIn('status', (array) $status)
            ->count();

        return [
            'needs-you' => Tab::make('Needs you')
                ->icon('heroicon-m-exclamation-circle')
                ->badge($count(['pending', 'confirmed', 'packed']))
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['pending', 'confirmed', 'packed'])),

            'to-pack' => Tab::make('To pack')
                ->badge($count('confirmed'))
                ->badgeColor($count('confirmed') > 0 ? 'danger' : 'gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'confirmed')),

            'to-post' => Tab::make('Packed, to post')
                ->badge($count('packed'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'packed')),

            'on-its-way' => Tab::make('On its way')
                ->badge($count('shipped'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'shipped')),

            'waiting-to-be-paid' => Tab::make('Waiting to be paid')
                ->badge($count('pending'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),

            'done' => Tab::make('Done')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['delivered', 'cancelled', 'refunded'])),

            'all' => Tab::make('All'),
        ];
    }

    /*
     * Opening on everything rather than on the first tab, because a shop that
     * has typed an order number into the search box expects to find it.
     */
    public function getDefaultActiveTab(): string|int|null
    {
        return 'all';
    }
}
