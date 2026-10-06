<?php

namespace App\Filament\Resources\Enquiries;

use App\Filament\Resources\Enquiries\Pages\ListEnquiries;
use App\Filament\Resources\Enquiries\Tables\EnquiriesTable;
use App\Models\Enquiry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * What people have written in from the contact page.
 *
 * Read here, answered from an inbox: every one of these also arrives as an
 * email addressed from the person who wrote it, so replying is pressing Reply.
 * This screen exists so that nothing is lost when an email is read on a phone
 * at a bad moment, and so the shop can see what is still unanswered.
 */
class EnquiryResource extends Resource
{
    protected static ?string $model = Enquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static \UnitEnum|string|null $navigationGroup = 'Shop';

    protected static ?string $navigationLabel = 'Messages';

    protected static ?string $modelLabel = 'message';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $waiting = Enquiry::waiting()->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return EnquiriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEnquiries::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
