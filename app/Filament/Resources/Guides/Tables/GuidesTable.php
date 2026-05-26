<?php

namespace App\Filament\Resources\Guides\Tables;

use App\Filament\Resources\Guides\GuideResource;
use App\Models\Guide;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class GuidesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('mission'))
            ->columns([
                ImageColumn::make('cover_path')
                    ->label('Image')
                    ->state(fn (Guide $record): string => $record->coverUrl())
                    ->imageWidth(72)
                    ->imageHeight(48)
                    ->checkFileExistence(false),

                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('summary')
                    ->label('Resume')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('category')
                    ->label('Categorie')
                    ->badge()
                    ->formatStateUsing(fn (Guide $record): string => $record->categoryLabel())
                    ->sortable(),

                TextColumn::make('mission.title')
                    ->label('Mission')
                    ->searchable()
                    ->placeholder('Aucune')
                    ->toggleable(),

                IconColumn::make('is_published')
                    ->label('Publie')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('sections_count')
                    ->label('Sections')
                    ->state(fn (Guide $record): int => count($record->sections ?? [])),

                TextColumn::make('published_at')
                    ->label('Publication')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->defaultPaginationPageOption(12)
            ->paginationPageOptions([12, 25, 50])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categorie')
                    ->options(Guide::CATEGORIES),

                TernaryFilter::make('is_published')
                    ->label('Publie'),

                TrashedFilter::make(),
            ])
            ->recordUrl(fn (Guide $record): string => GuideResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->iconButton()
                    ->tooltip('Modifier'),
                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->iconButton()
                    ->tooltip('Supprimer'),
                RestoreAction::make()
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->iconButton()
                    ->tooltip('Restaurer'),
                ForceDeleteAction::make()
                    ->icon('heroicon-o-x-mark')
                    ->iconButton()
                    ->tooltip('Supprimer definitivement'),
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
