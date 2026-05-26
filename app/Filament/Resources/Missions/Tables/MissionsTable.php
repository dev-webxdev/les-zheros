<?php

namespace App\Filament\Resources\Missions\Tables;

use App\Filament\Resources\Missions\MissionResource;
use App\Models\Mission;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class MissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Image')
                    ->state(fn (Mission $record): string => $record->imageUrl())
                    ->height(48)
                    ->width(72),

                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (Mission $record): string => $record->categoryLabel())
                    ->sortable(),

                TextColumn::make('dream')
                    ->label('Songe')
                    ->state(fn (Mission $record): ?string => $record->category === 'songe'
                        ? trim(($record->dreamTypeLabel() ?? '').' - Palier '.$record->dream_floor)
                        : null)
                    ->placeholder(''),

                TextColumn::make('created_at')
                    ->label('Creation')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(12)
            ->paginationPageOptions([12, 25, 50])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categorie')
                    ->options(Mission::CATEGORIES),
                TrashedFilter::make(),
            ])
            ->recordUrl(fn (Mission $record): string => MissionResource::getUrl('view', ['record' => $record]))
            ->recordActions([
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
