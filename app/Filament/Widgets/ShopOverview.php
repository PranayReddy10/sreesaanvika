<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * The four numbers worth looking at before anything else.
 *
 * Money counts paid orders only. A bag abandoned at the payment page is not a
 * sale, and a dashboard that counts it will be believed until the day the bank
 * statement disagrees.
 */
class ShopOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $from = now()->startOfMonth();
        $previousFrom = now()->subMonthNoOverflow()->startOfMonth();
        $previousTo = now()->subMonthNoOverflow()->endOfMonth();

        $paid = fn ($q) => $q->where('payment_status', 'paid');

        $thisMonth = (float) Order::query()->where($paid)
            ->where('created_at', '>=', $from)->sum('grand_total');

        $lastMonth = (float) Order::query()->where($paid)
            ->whereBetween('created_at', [$previousFrom, $previousTo])->sum('grand_total');

        $orders = Order::query()->where('created_at', '>=', $from)->count();

        $average = (float) Order::query()->where($paid)
            ->where('created_at', '>=', $from)->avg('grand_total');

        $low = Product::query()
            ->where('status', 'published')
            ->where('track_stock', true)
            ->whereColumn('stock', '<=', 'low_stock_at')
            ->count();

        $waiting = Order::whereIn('status', ['pending', 'confirmed'])->count();

        return [
            Stat::make('Taken this month', '₹' . number_format($thisMonth))
                ->description($this->shift($thisMonth, $lastMonth))
                ->descriptionIcon($thisMonth >= $lastMonth ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($thisMonth >= $lastMonth ? 'success' : 'danger')
                ->chart($this->dailyTotals()),

            Stat::make('Orders this month', (string) $orders)
                ->description($waiting > 0 ? $waiting . ' still to deal with' : 'Nothing waiting')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color($waiting > 0 ? 'warning' : 'gray'),

            Stat::make('Average order', '₹' . number_format($average))
                ->description('Paid orders this month')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Running low', (string) $low)
                ->description($low > 0 ? 'Sarees at or below their warning level' : 'Nothing is running out')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($low > 0 ? 'danger' : 'success'),
        ];
    }

    private function shift(float $now, float $before): string
    {
        if ($before <= 0) {
            return $now > 0 ? 'Nothing to compare with last month' : 'No sales yet';
        }

        $percent = round((($now - $before) / $before) * 100);

        return abs($percent) . '% ' . ($percent >= 0 ? 'up on' : 'down on') . ' last month';
    }

    /** The last fourteen days of takings, for the sparkline. */
    private function dailyTotals(): array
    {
        $since = now()->subDays(13)->startOfDay();

        $rows = Order::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, SUM(grand_total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $out = [];

        for ($day = $since->copy(); $day <= now(); $day->addDay()) {
            $out[] = (float) ($rows[$day->toDateString()] ?? 0);
        }

        return $out;
    }
}
