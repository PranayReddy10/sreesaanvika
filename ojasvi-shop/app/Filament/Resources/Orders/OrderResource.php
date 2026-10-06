<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Orders are made at checkout, never by hand — so this resource reads and
 * moves them along, and offers no way to invent one. An order typed into the
 * admin would have no payment behind it and no stock taken for it.
 */
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static \UnitEnum|string|null $navigationGroup = 'Selling';

    protected static ?string $recordTitleAttribute = 'number';

    protected static ?int $navigationSort = 1;

    /*
     * The shop's web addresses use the order number, but the admin's use the id. A
     * record being edited must stay findable even while the thing its public
     * address is built from is being changed.
     */
    protected static ?string $recordRouteKeyName = 'id';

    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    /** What is waiting to be dealt with, so the number means something. */
    public static function getNavigationBadge(): ?string
    {
        $waiting = Order::whereIn('status', ['pending', 'confirmed'])->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['items', 'shipment']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view'  => ViewOrder::route('/{record}'),
        ];
    }
}
