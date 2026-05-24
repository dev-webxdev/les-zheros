<?php

namespace App\Filament\Resources\Stuffs;

use App\Filament\Resources\Stuffs\Pages\CreateStuff;
use App\Filament\Resources\Stuffs\Pages\EditStuff;
use App\Filament\Resources\Stuffs\Pages\ListStuffs;
use App\Filament\Resources\Stuffs\Pages\ViewStuff;
use App\Filament\Resources\Stuffs\Schemas\StuffForm;
use App\Filament\Resources\Stuffs\Schemas\StuffInfolist;
use App\Filament\Resources\Stuffs\Tables\StuffsTable;
use App\Models\Stuff;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class StuffResource extends Resource
{
    protected static ?string $model = Stuff::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $modelLabel = 'stuff';

    protected static ?string $pluralModelLabel = 'stuffs';

    protected static ?string $navigationLabel = 'Catalogue stuffs';

    protected static string|UnitEnum|null $navigationGroup = 'Contenus';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return StuffForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StuffInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StuffsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStuffs::route('/'),
            'create' => CreateStuff::route('/create'),
            'view' => ViewStuff::route('/{record}'),
            'edit' => EditStuff::route('/{record}/edit'),
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
        return auth()->user()?->canAccessAdminArea('stuffs') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canAccessAdminArea('stuffs') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('stuffs') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('stuffs') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->canDeleteInAdminArea('stuffs') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->canDeleteInAdminArea('stuffs') ?? false;
    }

    public static function canRestore(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('stuffs') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->canAccessAdminArea('stuffs') ?? false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return auth()->user()?->canDeleteInAdminArea('stuffs') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->canDeleteInAdminArea('stuffs') ?? false;
    }
}
