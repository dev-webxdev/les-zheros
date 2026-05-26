<?php

namespace App\Filament\Resources\AdminNotifications\Tables;

use App\Filament\Pages\Lottery;
use App\Filament\Pages\Ranking;
use App\Filament\Pages\Settings;
use App\Filament\Resources\AdminActivities\AdminActivityResource;
use App\Filament\Resources\AdminNotifications\AdminNotificationResource;
use App\Filament\Resources\AdminRoles\AdminRoleResource;
use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Filament\Resources\GalleryImages\GalleryImageResource;
use App\Filament\Resources\Guides\GuideResource;
use App\Filament\Resources\MissionValidations\MissionValidationResource;
use App\Filament\Resources\Missions\MissionResource;
use App\Filament\Resources\Outings\OutingResource;
use App\Filament\Resources\Stuffs\StuffResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\AdminNotification;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AdminNotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('read_at')
                    ->label('Statut')
                    ->state(fn (AdminNotification $record): string => $record->isUnread() ? 'Non lue' : 'Lue')
                    ->badge()
                    ->color(fn (AdminNotification $record): string => $record->isUnread() ? 'warning' : 'gray')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Notification')
                    ->description(fn (AdminNotification $record): ?string => $record->message)
                    ->searchable(['title', 'message'])
                    ->wrap()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'success' => 'success',
                        'warning' => 'warning',
                        'danger', 'error' => 'danger',
                        default => 'info',
                    })
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('area')
                    ->label('Module')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state))
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(12)
            ->paginationPageOptions([12, 25, 50])
            ->filters([
                TernaryFilter::make('read_at')
                    ->label('Lecture')
                    ->placeholder('Toutes')
                    ->trueLabel('Lues')
                    ->falseLabel('Non lues')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('read_at'),
                        false: fn (Builder $query): Builder => $query->whereNull('read_at'),
                    ),

                SelectFilter::make('type')
                    ->label('Type')
                    ->options(fn (): array => AdminNotification::query()
                        ->select('type')
                        ->distinct()
                        ->orderBy('type')
                        ->pluck('type', 'type')
                        ->all())
                    ->searchable(),

                SelectFilter::make('area')
                    ->label('Module')
                    ->options(fn (): array => AdminNotification::query()
                        ->select('area')
                        ->distinct()
                        ->orderBy('area')
                        ->pluck('area', 'area')
                        ->mapWithKeys(fn (string $area): array => [$area => ucfirst($area)])
                        ->all())
                    ->searchable(),

                Filter::make('created_at')
                    ->label('Date')
                    ->form([
                        DatePicker::make('from')
                            ->label('Depuis'),
                        DatePicker::make('until')
                            ->label('Jusqu au'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->recordUrl(fn (AdminNotification $record): string => self::targetUrl($record))
            ->recordActions([
                Action::make('open')
                    ->label('Ouvrir la page')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->iconButton()
                    ->tooltip('Ouvrir la page principale')
                    ->url(fn (AdminNotification $record): string => self::targetUrl($record)),

                Action::make('markRead')
                    ->label('Marquer lue')
                    ->icon(Heroicon::OutlinedCheck)
                    ->iconButton()
                    ->color('success')
                    ->visible(fn (AdminNotification $record): bool => $record->isUnread())
                    ->action(fn (AdminNotification $record): bool => $record->forceFill(['read_at' => now()])->save()),

                Action::make('markUnread')
                    ->label('Marquer non lue')
                    ->icon(Heroicon::OutlinedBell)
                    ->iconButton()
                    ->color('warning')
                    ->visible(fn (AdminNotification $record): bool => ! $record->isUnread())
                    ->action(fn (AdminNotification $record): bool => $record->forceFill(['read_at' => null])->save()),

                DeleteAction::make()
                    ->icon(Heroicon::OutlinedTrash)
                    ->iconButton()
                    ->tooltip('Supprimer'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markRead')
                        ->label('Marquer lues')
                        ->icon(Heroicon::OutlinedCheck)
                        ->color('success')
                        ->action(fn (Collection $records): null => self::markRead($records)),
                    BulkAction::make('markUnread')
                        ->label('Marquer non lues')
                        ->icon(Heroicon::OutlinedBell)
                        ->color('warning')
                        ->action(fn (Collection $records): null => self::markUnread($records)),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function targetUrl(AdminNotification $notification): string
    {
        $path = filled($notification->url)
            ? (parse_url($notification->url, PHP_URL_PATH) ?: $notification->url)
            : null;

        $area = self::areaFromPath($path) ?? $notification->area;

        if (! self::canAccessArea($area)) {
            return AdminNotificationResource::getUrl('index');
        }

        return match ($area) {
            'activity', 'activite' => AdminActivityResource::getUrl('index'),
            'announcements', 'annonces' => AnnouncementResource::getUrl('index'),
            'gallery', 'galerie' => GalleryImageResource::getUrl('index'),
            'guides' => GuideResource::getUrl('index'),
            'lottery', 'loterie' => Lottery::getUrl(),
            'missions' => MissionResource::getUrl('index'),
            'notifications' => AdminNotificationResource::getUrl('index'),
            'outings', 'sorties' => OutingResource::getUrl('index'),
            'ranking', 'classement' => Ranking::getUrl(),
            'roles' => AdminRoleResource::getUrl('index'),
            'settings', 'parametres' => Settings::getUrl(),
            'stuffs' => StuffResource::getUrl('index'),
            'users', 'utilisateurs' => UserResource::getUrl('index'),
            'validations' => MissionValidationResource::getUrl('index'),
            default => AdminNotificationResource::getUrl('index'),
        };
    }

    private static function areaFromPath(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return match (true) {
            str_starts_with($path, '/admin-filament/activite'), str_starts_with($path, '/admin/activite') => 'activity',
            str_starts_with($path, '/admin-filament/announcements'), str_starts_with($path, '/admin/annonces') => 'announcements',
            str_starts_with($path, '/admin-filament/gallery-images'), str_starts_with($path, '/admin/galerie') => 'gallery',
            str_starts_with($path, '/admin-filament/guides'), str_starts_with($path, '/admin/guides') => 'guides',
            str_starts_with($path, '/admin-filament/loterie'), str_starts_with($path, '/admin/loterie') => 'lottery',
            str_starts_with($path, '/admin-filament/missions'), str_starts_with($path, '/admin/missions') => 'missions',
            str_starts_with($path, '/admin-filament/notifications'), str_starts_with($path, '/admin/notifications') => 'notifications',
            str_starts_with($path, '/admin-filament/outings'), str_starts_with($path, '/admin/sorties') => 'outings',
            str_starts_with($path, '/admin-filament/classement'), str_starts_with($path, '/admin/classement') => 'ranking',
            str_starts_with($path, '/admin-filament/admin-roles'), str_starts_with($path, '/admin/roles') => 'roles',
            str_starts_with($path, '/admin-filament/parametres'), str_starts_with($path, '/admin/parametres') => 'settings',
            str_starts_with($path, '/admin-filament/stuffs'), str_starts_with($path, '/admin/stuffs') => 'stuffs',
            str_starts_with($path, '/admin-filament/users'), str_starts_with($path, '/admin/utilisateurs') => 'users',
            str_starts_with($path, '/admin-filament/mission-validations'), str_starts_with($path, '/admin/validations') => 'validations',
            default => null,
        };
    }

    private static function canAccessArea(?string $area): bool
    {
        if (blank($area)) {
            return false;
        }

        $adminArea = match ($area) {
            'activity', 'activite' => 'activity',
            'announcements', 'annonces' => 'announcements',
            'gallery', 'galerie' => 'gallery',
            'lottery', 'loterie' => 'lottery',
            'outings', 'sorties' => 'outings',
            'ranking', 'classement' => 'ranking',
            'settings', 'parametres' => 'settings',
            'users', 'utilisateurs' => 'users',
            'validations' => 'validations',
            default => $area,
        };

        return auth()->user()?->canAccessAdminArea($adminArea) ?? false;
    }

    private static function markRead(Collection $records): null
    {
        $records->each(fn (AdminNotification $notification): bool => $notification->forceFill(['read_at' => now()])->save());

        return null;
    }

    private static function markUnread(Collection $records): null
    {
        $records->each(fn (AdminNotification $notification): bool => $notification->forceFill(['read_at' => null])->save());

        return null;
    }
}
