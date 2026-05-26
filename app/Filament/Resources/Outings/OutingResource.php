<?php

namespace App\Filament\Resources\Outings;

use App\Filament\Resources\Outings\Pages\CreateOuting;
use App\Filament\Resources\Outings\Pages\EditOuting;
use App\Filament\Resources\Outings\Pages\ListOutings;
use App\Filament\Resources\Outings\Pages\ViewOuting;
use App\Filament\Resources\Outings\Schemas\OutingForm;
use App\Filament\Resources\Outings\Schemas\OutingInfolist;
use App\Filament\Resources\Outings\Tables\OutingsTable;
use App\Models\Outing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class OutingResource extends Resource
{
    protected static ?string $model = Outing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $modelLabel = 'sortie';

    protected static ?string $pluralModelLabel = 'sorties';

    protected static ?string $navigationLabel = 'Sorties';

    protected static string|UnitEnum|null $navigationGroup = 'Activites';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return OutingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OutingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OutingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOutings::route('/'),
            'create' => CreateOuting::route('/create'),
            'view' => ViewOuting::route('/{record}'),
            'edit' => EditOuting::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessAdminArea('outings') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canAccessAdminArea('outings') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('outings') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('outings') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->canDeleteInAdminArea('outings') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->canDeleteInAdminArea('outings') ?? false;
    }

    public static function canRestore(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('outings') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->canAccessAdminArea('outings') ?? false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return auth()->user()?->canDeleteInAdminArea('outings') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->canDeleteInAdminArea('outings') ?? false;
    }
}
