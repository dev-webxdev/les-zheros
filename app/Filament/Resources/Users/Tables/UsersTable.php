<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->state(fn (User $record): ?string => $record->avatarUrl())
                    ->circular()
                    ->height(38)
                    ->width(38),

                TextColumn::make('name')
                    ->label('Pseudo')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles')
                    ->label('Roles')
                    ->state(fn (User $record): string => $record->adminRolesLabel())
                    ->badge(),

                TextColumn::make('is_approved')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Valide' : 'A valider')
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Creation')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(12)
            ->paginationPageOptions([12, 25, 50])
            ->filters([
                SelectFilter::make('is_approved')
                    ->label('Statut')
                    ->options([
                        true => 'Valides',
                        false => 'A valider',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordUrl(fn (User $record): string => UserResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                Action::make('approve')
                    ->label('Valider')
                    ->icon(Heroicon::OutlinedCheck)
                    ->iconButton()
                    ->color('success')
                    ->visible(fn (User $record): bool => ! $record->is_approved && ! $record->trashed())
                    ->action(fn (User $record): bool => $record->forceFill(['is_approved' => true])->save()),

                EditAction::make()
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->iconButton()
                    ->tooltip('Modifier'),

                DeleteAction::make()
                    ->icon(Heroicon::OutlinedTrash)
                    ->iconButton()
                    ->tooltip('Supprimer'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label('Valider')
                        ->icon(Heroicon::OutlinedCheck)
                        ->color('success')
                        ->action(function (Collection $records): void {
                            $records->each(fn (User $user): bool => $user->forceFill(['is_approved' => true])->save());
                        }),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
