<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\Actions\OrderActions;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    /** The same actions as the list, so the two cannot drift apart. */
    protected function getHeaderActions(): array
    {
        return [
            OrderActions::ship(),
            OrderActions::delivered(),
            OrderActions::refund(),
            OrderActions::cancel(),
        ];
    }
}
