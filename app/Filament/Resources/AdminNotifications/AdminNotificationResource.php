<?php

namespace App\Filament\Resources\AdminNotifications;

use App\Filament\Resources\AdminNotifications\Pages\ListAdminNotifications;
use App\Filament\Resources\AdminNotifications\Pages\ViewAdminNotification;
use App\Filament\Resources\AdminNotifications\Schemas\AdminNotificationInfolist;
use App\Filament\Resources\AdminNotifications\Tables\AdminNotificationsTable;
use App\Models\AdminNotification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AdminNotificationResource extends Resource
{
    protected static ?string $model = AdminNotification::class;

    protected static ?string $slug = 'notifications';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $modelLabel = 'notification';

    protected static ?string $pluralModelLabel = 'notifications';

    protected static ?string $navigationLabel = 'Notifications';

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 20;

    public static function infolist(Schema $schema): Schema
    {
        return AdminNotificationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminNotificationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminNotifications::route('/'),
            'view' => ViewAdminNotification::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessAdminArea('notifications') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('notifications') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('notifications') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->canAccessAdminArea('notifications') ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = AdminNotification::query()
            ->whereNull('read_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
