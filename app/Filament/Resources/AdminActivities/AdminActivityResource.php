<?php

namespace App\Filament\Resources\AdminActivities;

use App\Filament\Resources\AdminActivities\Pages\ListAdminActivities;
use App\Filament\Resources\AdminActivities\Pages\ViewAdminActivity;
use App\Filament\Resources\AdminActivities\Schemas\AdminActivityInfolist;
use App\Filament\Resources\AdminActivities\Tables\AdminActivitiesTable;
use App\Models\AdminActivityLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AdminActivityResource extends Resource
{
    protected static ?string $model = AdminActivityLog::class;

    protected static ?string $slug = 'activite';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $modelLabel = 'activite';

    protected static ?string $pluralModelLabel = 'activite';

    protected static ?string $navigationLabel = 'Activite';

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 25;

    public static function infolist(Schema $schema): Schema
    {
        return AdminActivityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminActivitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminActivities::route('/'),
            'view' => ViewAdminActivity::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessAdminArea('activity') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('activity') ?? false;
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
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
