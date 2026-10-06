<?php

namespace App\Filament\Pages;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Search;
use App\Support\Shop;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;

/**
 * What the shop can learn about itself.
 *
 * Google Analytics will tell the shop how many people came. It will not tell
 * it which saree is looked at forty times and bought twice, or that eleven
 * people searched for "banarasi" and were shown nothing. Those two facts are
 * worth more to a twelve-design shop than every chart in Analytics, and
 * neither can be had without the shop's own database.
 *
 * Money counts paid orders only, here as everywhere.
 */
class Insights extends Page
{
    protected string $view = 'filament.pages.insights';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static \UnitEnum|string|null $navigationGroup = 'Shop';

    protected static ?string $navigationLabel = 'Analysis';

    protected static ?string $title = 'What the figures say';

    protected static ?int $navigationSort = 3;

    #[Url]
    public string $period = '30';

    public function periods(): array
    {
        return ['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 3 months', '365' => 'Last year'];
    }

    public function since(): \Illuminate\Support\Carbon
    {
        return now()->subDays(max(1, (int) $this->period))->startOfDay();
    }

    /* --------------------------------------------------------- the money */

    public function headline(): array
    {
        $since = $this->since();

        $paid = Order::where('payment_status', 'paid')->where('created_at', '>=', $since);

        $taken = (float) (clone $paid)->sum('grand_total');
        $orders = (clone $paid)->count();

        /*
         * Bags still holding something, which means bags nobody checked out —
         * a bag is emptied when its order is placed. So this counts what was
         * left behind, not what was filled, and the ratio below is read the
         * same way: of the bags that reached a decision, how many became
         * orders. Rough, because a bag is not a person, but the direction of
         * it is the most useful number on this page.
         */
        $left = Cart::whereHas('items')->where('last_active_at', '>=', $since)->count();
        $all = Order::where('created_at', '>=', $since)->count();
        $decided = $all + $left;

        return [
            'taken'      => $taken,
            'orders'     => $orders,
            'average'    => $orders > 0 ? $taken / $orders : 0.0,
            'pieces'     => (int) DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.payment_status', 'paid')
                ->where('orders.created_at', '>=', $since)
                ->sum('order_items.quantity'),
            'left'       => $left,
            'conversion' => $decided > 0 ? round(($all / $decided) * 100) : null,
        ];
    }

    /* ---------------------------------------------------- what sold well */

    public function bestSellers(): array
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->where('orders.created_at', '>=', $this->since())
            ->selectRaw('order_items.name, order_items.product_id,
                         SUM(order_items.quantity) as pieces,
                         SUM(order_items.line_total) as money')
            ->groupBy('order_items.name', 'order_items.product_id')
            ->orderByDesc('money')
            ->limit(10)
            ->get()
            ->all();
    }

    /** Which shade actually sells, which is what to reorder. */
    public function bestShades(): array
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->where('orders.created_at', '>=', $this->since())
            ->whereNotNull('order_items.colourway_name')
            ->selectRaw('order_items.colourway_name as shade, SUM(order_items.quantity) as pieces')
            ->groupBy('order_items.colourway_name')
            ->orderByDesc('pieces')
            ->limit(8)
            ->get()
            ->all();
    }

    /**
     * Looked at often, bought rarely.
     *
     * Almost always one of three things: the photograph, the price, or a
     * description that does not say what the shopper needed to know. It is the
     * most fixable problem a shop has, and invisible without this list.
     */
    public function lookedAtNotBought(): array
    {
        $sold = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->where('orders.created_at', '>=', $this->since())
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as pieces')
            ->groupBy('order_items.product_id')
            ->pluck('pieces', 'product_id');

        return Product::query()
            ->where('status', 'published')
            ->where('views', '>=', 20)
            ->orderByDesc('views')
            ->get(['id', 'name', 'sku', 'views', 'price', 'sale_price'])
            ->map(fn (Product $p) => [
                'product' => $p,
                'views'   => (int) $p->views,
                'sold'    => (int) ($sold[$p->id] ?? 0),
                'rate'    => $p->views > 0 ? round((($sold[$p->id] ?? 0) / $p->views) * 100, 1) : 0.0,
            ])
            ->sortBy('rate')
            ->take(8)
            ->values()
            ->all();
    }

    /* ------------------------------------------------ what they asked for */

    public function searches(): array
    {
        return Search::query()
            ->where('created_at', '>=', $this->since())
            ->selectRaw('normalised, COUNT(*) as times, MAX(results) as best, MIN(results) as worst')
            ->groupBy('normalised')
            ->orderByDesc('times')
            ->limit(12)
            ->get()
            ->all();
    }

    /** Searches that found nothing — the shop being told what to stock. */
    public function emptySearches(): array
    {
        return Search::query()
            ->where('created_at', '>=', $this->since())
            ->where('results', 0)
            ->selectRaw('normalised, COUNT(*) as times, MAX(created_at) as last_asked')
            ->groupBy('normalised')
            ->orderByDesc('times')
            ->limit(12)
            ->get()
            ->all();
    }

    /* --------------------------------------------------- where it all goes */

    public function states(): array
    {
        return Order::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $this->since())
            ->get(['shipping_address', 'grand_total'])
            ->groupBy(fn (Order $o) => $o->shipping_address['state'] ?? 'Unknown')
            ->map(fn ($orders, $state) => [
                'state'  => $state,
                'orders' => $orders->count(),
                'money'  => (float) $orders->sum(fn ($o) => (float) $o->grand_total),
            ])
            ->sortByDesc('money')
            ->take(8)
            ->values()
            ->all();
    }

    public function payment(): array
    {
        return Order::query()
            ->where('created_at', '>=', $this->since())
            ->selectRaw('payment_method, payment_status, COUNT(*) as orders, SUM(grand_total) as money')
            ->groupBy('payment_method', 'payment_status')
            ->get()
            ->all();
    }

    /** Which offers and codes actually moved money. */
    public function discounts(): array
    {
        $since = $this->since();

        $offers = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->where('orders.created_at', '>=', $since)
            ->whereNotNull('order_items.offer_name')
            ->selectRaw('order_items.offer_name as name,
                         COUNT(DISTINCT orders.id) as orders,
                         SUM(order_items.free_units) as given')
            ->groupBy('order_items.offer_name')
            ->orderByDesc('orders')
            ->get();

        $coupons = Order::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $since)
            ->whereNotNull('coupon_code')
            ->selectRaw('coupon_code as name, COUNT(*) as orders, SUM(discount_total) as given_off')
            ->groupBy('coupon_code')
            ->orderByDesc('orders')
            ->get();

        return ['offers' => $offers->all(), 'coupons' => $coupons->all()];
    }

    /* ------------------------------------------------------- who is about */

    /**
     * Visitors and accounts, from the shop's own tables.
     *
     * Google counts everybody who arrived; this counts the ones the shop can
     * actually name. Who is on the site this minute comes from the session
     * table, which is only kept when sessions are stored in the database —
     * they are on this host, but a shop that has changed it should be told
     * that rather than shown a zero.
     *
     * @return array{live: ?int, liveNamed: ?int, signedIn: int, joined: int, accounts: int}
     */
    public function whoIsAbout(): array
    {
        $since = $this->since();

        $live = $named = null;

        if (config('session.driver') === 'database') {
            // Five minutes, which is what everybody else means by "now".
            $recent = DB::table(config('session.table', 'sessions'))
                ->where('last_activity', '>=', now()->subMinutes(5)->getTimestamp());

            $live = (clone $recent)->count();
            $named = (clone $recent)->whereNotNull('user_id')->distinct()->count('user_id');
        }

        return [
            'live'      => $live,
            'liveNamed' => $named,
            'signedIn'  => \App\Models\User::where('is_admin', false)
                ->where('last_login_at', '>=', $since)
                ->count(),
            'joined'    => \App\Models\User::where('is_admin', false)
                ->where('created_at', '>=', $since)
                ->count(),
            'accounts'  => \App\Models\User::where('is_admin', false)->count(),
        ];
    }

    /* ------------------------------------------------------ what Google knows */

    /**
     * Visitors, and what was typed into Google to find the shop.
     *
     * Two separate things that people run together. Analytics counts who
     * arrived; Search Console says what the shop was shown for and whether
     * anybody clicked — which is where a shop learns it comes up for
     * "banarasi saree hyderabad" and nobody clicks, and that no amount of
     * looking at its own database would ever tell it.
     *
     * Everything here can be null, and the page says so plainly rather than
     * showing zeroes: Google being slow, or a key not yet pasted, must not
     * read as nobody having visited.
     *
     * @return array{set: bool, days: int, visitors: ?array, search: ?array}
     */
    public function google(): array
    {
        $stats = new \App\Services\Google\GoogleStats;
        $days = max(1, (int) $this->period);

        if (! $stats->configured()) {
            return ['set' => false, 'days' => $days, 'visitors' => null, 'search' => null];
        }

        return [
            'set'      => true,
            'days'     => $days,
            'visitors' => $stats->visitors($days),
            'search'   => $stats->search($days),
        ];
    }

    public function money(float|int|null $amount): string
    {
        return Shop::money((float) $amount);
    }
}
