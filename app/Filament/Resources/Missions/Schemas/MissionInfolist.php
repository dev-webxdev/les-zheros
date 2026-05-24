<?php

namespace App\Filament\Resources\Missions\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Mission')
                    ->schema([
                        ImageEntry::make('image')
                            ->label('Image')
                            ->state(fn ($record): string => $record->imageUrl())
                            ->height(180)
                            ->columnSpanFull(),

                        TextEntry::make('title')
                            ->label('Titre'),

                        TextEntry::make('category')
                            ->label('Categorie')
                            ->badge()
                            ->formatStateUsing(fn ($record): string => $record->categoryLabel()),

                        TextEntry::make('anomaly_type')
                            ->label("Type d'anomalie")
                            ->formatStateUsing(fn ($record): ?string => $record->anomalyTypeLabel())
                            ->placeholder('Non concerne'),

                        TextEntry::make('anomaly_level')
                            ->label('Niveau anomalie')
                            ->placeholder('Non concerne'),

                        TextEntry::make('dream_type')
                            ->label('Type de songe')
                            ->formatStateUsing(fn ($record): ?string => $record->dreamTypeLabel())
                            ->placeholder('Non concerne'),

                        TextEntry::make('dream_floor')
                            ->label('Palier')
                            ->placeholder('Non concerne'),

                        TextEntry::make('image_mode')
                            ->label("Source de l'image")
                            ->placeholder('Aucune'),

                        TextEntry::make('image_path')
                            ->label('Chemin image')
                            ->placeholder('Image par defaut')
                            ->columnSpanFull(),

                        TextEntry::make('monster_id')
                            ->label('ID monstre')
                            ->placeholder('Aucun'),
                    ])
                    ->columns(2),
            ]);
    }
}
