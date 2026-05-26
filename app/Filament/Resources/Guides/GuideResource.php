<?php

namespace App\Filament\Resources\Guides;

use App\Filament\Resources\Guides\Pages\CreateGuide;
use App\Filament\Resources\Guides\Pages\EditGuide;
use App\Filament\Resources\Guides\Pages\ListGuides;
use App\Filament\Resources\Guides\Pages\ViewGuide;
use App\Filament\Resources\Guides\Schemas\GuideForm;
use App\Filament\Resources\Guides\Schemas\GuideInfolist;
use App\Filament\Resources\Guides\Tables\GuidesTable;
use App\Models\Guide;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class GuideResource extends Resource
{
    protected static ?string $model = Guide::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $modelLabel = 'guide';

    protected static ?string $pluralModelLabel = 'guides';

    protected static ?string $navigationLabel = 'Guides';

    protected static string|UnitEnum|null $navigationGroup = 'Contenus';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return GuideForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GuideInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GuidesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGuides::route('/'),
            'create' => CreateGuide::route('/create'),
            'view' => ViewGuide::route('/{record}'),
            'edit' => EditGuide::route('/{record}/edit'),
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
        return auth()->user()?->canAccessAdminArea('guides') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canAccessAdminArea('guides') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('guides') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('guides') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->canDeleteInAdminArea('guides') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->canDeleteInAdminArea('guides') ?? false;
    }

    public static function canRestore(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('guides') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->canAccessAdminArea('guides') ?? false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return auth()->user()?->canDeleteInAdminArea('guides') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->canDeleteInAdminArea('guides') ?? false;
    }
}
