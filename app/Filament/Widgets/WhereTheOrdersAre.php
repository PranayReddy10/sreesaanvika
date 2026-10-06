<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Every order that is not finished, and which stage it is at.
 *
 * The row above says what the shop took. This says what it owes: four numbers
 * that between them are the morning's work, in the order the work happens —
 * take the money, pack it, post it, and then it is somebody else's problem.
 *
 * Each one opens the orders at that stage, because a count nobody can act on
 * from where they are reading it is decoration.
 */
class WhereTheOrdersAre extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        /*
         * One query rather than four. Shared hosting, and this is on the first
         * screen of every visit to the admin.
         */
        $counts = Order::query()
            ->whereNotIn('status', ['delivered', 'cancelled', 'refunded'])
            ->selectRaw('status, COUNT(*) as orders')
            ->groupBy('status')
            ->pluck('orders', 'status');

        $at = fn (string $status): int => (int) ($counts[$status] ?? 0);

        $unpaid = Order::where('status', 'pending')
            ->where('payment_status', '!=', 'paid')
            ->count();

        $posted = $at('shipped');

        return [
            $this->stage('Waiting to be paid', $at('pending'), 'heroicon-m-clock', 'waiting-to-be-paid')
                ->description($unpaid > 0
                    ? $unpaid.' of them not paid for yet'
                    : 'Nothing waiting on a payment')
                ->color($at('pending') > 0 ? 'warning' : 'gray'),

            $this->stage('To pack', $at('confirmed'), 'heroicon-m-archive-box', 'to-pack')
                ->description($at('confirmed') > 0 ? 'Paid, and nobody has packed them' : 'Nothing to pack')
                ->color($at('confirmed') > 0 ? 'danger' : 'success'),

            $this->stage('Packed, to post', $at('packed'), 'heroicon-m-cube', 'to-post')
                ->description($at('packed') > 0 ? 'Boxed and still here' : 'Nothing boxed and waiting')
                ->color($at('packed') > 0 ? 'warning' : 'gray'),

            $this->stage('On its way', $posted, 'heroicon-m-truck', 'on-its-way')
                ->description($posted > 0 ? 'With the courier' : 'Nothing in transit')
                ->color($posted > 0 ? 'primary' : 'gray'),
        ];
    }

    /**
     * A number that opens the pile behind it.
     *
     * A tab on the orders screen rather than a filter in the address: the tabs
     * are a plain query parameter that will still mean the same thing after an
     * upgrade, and they are the more useful thing to arrive at anyway.
     *
     * The parameter is `tab`, which is what Filament binds the active tab to —
     * not `activeTab`, the name of the property behind it. Passing the
     * property name is accepted in silence and simply shows every order.
     */
    private function stage(string $label, int $count, string $icon, string $tab): Stat
    {
        return Stat::make($label, (string) $count)
            ->descriptionIcon($icon)
            ->url(OrderResource::getUrl('index', ['tab' => $tab]));
    }
}
