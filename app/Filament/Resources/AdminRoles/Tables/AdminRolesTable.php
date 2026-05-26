<?php

namespace App\Filament\Resources\AdminRoles\Tables;

use App\Filament\Resources\AdminRoles\AdminRoleResource;
use App\Models\AdminRole;
use App\Support\AdminAccess;
use App\Support\AdminRoleFilamentData;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class AdminRolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Role')
                    ->badge()
                    ->color('gray')
                    ->extraAttributes(fn (AdminRole $record): array => [
                        'class' => 'lz-admin-role-color lz-admin-role-color--'.($record->color ?: 'neutral'),
                    ])
                    ->searchable()
                    ->sortable(),

                TextColumn::make('key')
                    ->label('Cle')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->state(fn (AdminRole $record): string => self::permissionsLabel($record))
                    ->badge()
                    ->color(fn (AdminRole $record): string => count(AdminAccess::rolePermissions($record->key)) === count(AdminAccess::permissions()) ? 'danger' : 'gray'),

                TextColumn::make('permissions_preview')
                    ->label('Apercu permissions')
                    ->state(fn (AdminRole $record): string => self::permissionsPreview($record))
                    ->limit(80)
                    ->toggleable(),

                TextColumn::make('users_count')
                    ->label('Utilisateurs')
                    ->state(fn (AdminRole $record): int => AdminRoleFilamentData::userCount($record->key)),

                TextColumn::make('type')
                    ->label('Type')
                    ->state(fn (AdminRole $record): string => AdminRoleFilamentData::isDefaultRole($record->key) ? 'Defaut' : 'Personnalise')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Defaut' ? 'info' : 'success'),
            ])
            ->defaultSort('label')
            ->defaultPaginationPageOption(12)
            ->paginationPageOptions([12, 25, 50])
            ->filters([
                Filter::make('default')
                    ->label('Roles par defaut')
                    ->query(fn (Builder $query): Builder => $query->whereIn('key', array_keys(AdminAccess::defaultRoles()))),

                Filter::make('custom')
                    ->label('Roles personnalises')
                    ->query(fn (Builder $query): Builder => $query->whereNotIn('key', array_keys(AdminAccess::defaultRoles()))),

                TrashedFilter::make(),
            ])
            ->recordUrl(fn (AdminRole $record): string => AdminRoleResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Modifier'),
                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Supprimer')
                    ->visible(fn (AdminRole $record): bool => ! AdminRoleFilamentData::isProtected($record))
                    ->modalDescription(fn (AdminRole $record): string => AdminRoleFilamentData::isDefaultRole($record->key)
                        ? 'Ce role par defaut sera mis en corbeille et masque par AdminAccess.'
                        : 'Ce role sera mis en corbeille.'),
                RestoreAction::make()
                    ->iconButton()
                    ->tooltip('Restaurer'),
                ForceDeleteAction::make()
                    ->iconButton()
                    ->tooltip('Supprimer definitivement')
                    ->visible(fn (AdminRole $record): bool => ! AdminRoleFilamentData::isProtected($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (DeleteBulkAction $action, EloquentCollection $records): void {
                            if (self::haltIfProtectedRolesSelected($action, $records, 'supprimer')) {
                                return;
                            }

                            $records->each(fn (AdminRole $record): ?bool => $record->delete());
                        }),
                    ForceDeleteBulkAction::make()
                        ->action(function (ForceDeleteBulkAction $action, EloquentCollection $records): void {
                            if (self::haltIfProtectedRolesSelected($action, $records, 'supprimer definitivement')) {
                                return;
                            }

                            $records->each(fn (AdminRole $record): ?bool => $record->forceDelete());
                        }),
                    RestoreBulkAction::make()
                        ->action(function (RestoreBulkAction $action, EloquentCollection $records): void {
                            if (self::haltIfProtectedRolesSelected($action, $records, 'restaurer')) {
                                return;
                            }

                            $records->each(fn (AdminRole $record): ?bool => $record->restore());
                        }),
                ]),
            ]);
    }

    private static function haltIfProtectedRolesSelected(
        DeleteBulkAction | ForceDeleteBulkAction | RestoreBulkAction $action,
        EloquentCollection $records,
        string $operation,
    ): bool {
        $protectedRoles = AdminRoleFilamentData::protectedRoles($records);

        if ($protectedRoles->isEmpty()) {
            return false;
        }

        Notification::make()
            ->title('Action groupee refusee')
            ->body('Impossible de '.$operation.' une selection contenant un role protege : '.$protectedRoles->pluck('label')->join(', ').'.')
            ->danger()
            ->send();

        $action->failure();
        $action->halt();

        return true;
    }

    private static function permissionsLabel(AdminRole $record): string
    {
        $count = count(AdminAccess::rolePermissions($record->key));
        $total = count(AdminAccess::permissions());

        if ($count === $total) {
            return 'Acces complet';
        }

        return $count <= 1 ? $count.' permission' : $count.' permissions';
    }

    private static function permissionsPreview(AdminRole $record): string
    {
        return collect(AdminAccess::rolePermissions($record->key))
            ->take(5)
            ->map(fn (string $permission): string => AdminAccess::permissions()[$permission] ?? $permission)
            ->join(', ') ?: 'Aucune';
    }
}
