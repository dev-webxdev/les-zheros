<?php

namespace App\Filament\Resources\AdminRoles;

use App\Filament\Resources\AdminRoles\Pages\CreateAdminRole;
use App\Filament\Resources\AdminRoles\Pages\EditAdminRole;
use App\Filament\Resources\AdminRoles\Pages\ListAdminRoles;
use App\Filament\Resources\AdminRoles\Pages\ViewAdminRole;
use App\Filament\Resources\AdminRoles\Schemas\AdminRoleForm;
use App\Filament\Resources\AdminRoles\Schemas\AdminRoleInfolist;
use App\Filament\Resources\AdminRoles\Tables\AdminRolesTable;
use App\Models\AdminRole;
use App\Support\AdminAccess;
use App\Support\AdminRoleFilamentData;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AdminRoleResource extends Resource
{
    protected static ?string $model = AdminRole::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $modelLabel = 'role';

    protected static ?string $pluralModelLabel = 'roles';

    protected static ?string $navigationLabel = 'Roles';

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return AdminRoleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AdminRoleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminRolesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminRoles::route('/'),
            'create' => CreateAdminRole::route('/create'),
            'view' => ViewAdminRole::route('/{record}'),
            'edit' => EditAdminRole::route('/{record}/edit'),
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
        return auth()->user()?->canAccessAdminArea('roles') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canAccessAdminArea('roles') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('roles') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('roles') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof AdminRole
            && ! AdminRoleFilamentData::isProtected($record)
            && (auth()->user()?->canDeleteInAdminArea('roles') ?? false);
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->canDeleteInAdminArea('roles') ?? false;
    }

    public static function canRestore(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('roles') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->canAccessAdminArea('roles') ?? false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return $record instanceof AdminRole
            && ! AdminRoleFilamentData::isProtected($record)
            && (auth()->user()?->canDeleteInAdminArea('roles') ?? false);
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->canDeleteInAdminArea('roles') ?? false;
    }
}
