<?php

namespace App\Filament\Resources\MissionValidations;

use App\Filament\Resources\MissionValidations\Pages\CreateMissionValidation;
use App\Filament\Resources\MissionValidations\Pages\EditMissionValidation;
use App\Filament\Resources\MissionValidations\Pages\ListMissionValidations;
use App\Filament\Resources\MissionValidations\Pages\ViewMissionValidation;
use App\Filament\Resources\MissionValidations\Schemas\MissionValidationForm;
use App\Filament\Resources\MissionValidations\Schemas\MissionValidationInfolist;
use App\Filament\Resources\MissionValidations\Tables\MissionValidationsTable;
use App\Models\MissionValidation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MissionValidationResource extends Resource
{
    protected static ?string $model = MissionValidation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static ?string $modelLabel = 'validation';

    protected static ?string $pluralModelLabel = 'validations';

    protected static ?string $navigationLabel = 'Validations';

    protected static string|UnitEnum|null $navigationGroup = 'Communaute';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return MissionValidationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MissionValidationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MissionValidationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMissionValidations::route('/'),
            'create' => CreateMissionValidation::route('/create'),
            'view' => ViewMissionValidation::route('/{record}'),
            'edit' => EditMissionValidation::route('/{record}/edit'),
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
        return auth()->user()?->canAccessAdminArea('validations') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canAccessAdminArea('validations') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('validations') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('validations') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->canDeleteInAdminArea('validations') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->canDeleteInAdminArea('validations') ?? false;
    }

    public static function canRestore(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('validations') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->canAccessAdminArea('validations') ?? false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return auth()->user()?->canDeleteInAdminArea('validations') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->canDeleteInAdminArea('validations') ?? false;
    }
}
