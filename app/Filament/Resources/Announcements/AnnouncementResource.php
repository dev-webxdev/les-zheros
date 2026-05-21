<?php

namespace App\Filament\Resources\Announcements;

use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\Resources\Announcements\Pages\ViewAnnouncement;
use App\Filament\Resources\Announcements\Schemas\AnnouncementForm;
use App\Filament\Resources\Announcements\Schemas\AnnouncementInfolist;
use App\Filament\Resources\Announcements\Tables\AnnouncementsTable;
use App\Models\Announcement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'annonce';

    protected static ?string $pluralModelLabel = 'annonces';

    protected static ?string $navigationLabel = 'Annonces';

    protected static string|UnitEnum|null $navigationGroup = 'Contenus';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return AnnouncementForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AnnouncementInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnnouncementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'view' => ViewAnnouncement::route('/{record}'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
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
        return auth()->user()?->canAccessAdminArea('announcements') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canAccessAdminArea('announcements') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('announcements') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('announcements') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->canDeleteInAdminArea('announcements') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->canDeleteInAdminArea('announcements') ?? false;
    }

    public static function canRestore(Model $record): bool
    {
        return auth()->user()?->canAccessAdminArea('announcements') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->canAccessAdminArea('announcements') ?? false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return auth()->user()?->canDeleteInAdminArea('announcements') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->canDeleteInAdminArea('announcements') ?? false;
    }
}
