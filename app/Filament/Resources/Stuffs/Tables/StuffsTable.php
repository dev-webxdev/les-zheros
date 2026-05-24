<?php

namespace App\Filament\Resources\Stuffs\Tables;

use App\Filament\Resources\Stuffs\StuffResource;
use App\Models\Stuff;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class StuffsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('class_label')
                    ->label('Classe')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Build')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('elements')
                    ->label('Elements')
                    ->formatStateUsing(fn ($state): string => collect($state ?? [])->join(' / '))
                    ->searchable(query: function ($query, string $search): void {
                        $query->orWhere('elements', 'like', "%{$search}%");
                    }),

                TextColumn::make('mode')
                    ->label('Mode')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('level')
                    ->label('Niveau')
                    ->state(fn (Stuff $record): string => $record->levelLabel()),

                IconColumn::make('is_featured')
                    ->label('Avant')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_published')
                    ->label('Publie')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Creation')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordUrl(fn (Stuff $record): string => StuffResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                Action::make('dofusbook')
                    ->label('Dofusbook')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->iconButton()
                    ->url(fn (Stuff $record): string => $record->dofusbook_url)
                    ->openUrlInNewTab()
                    ->tooltip('Ouvrir Dofusbook'),
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
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
