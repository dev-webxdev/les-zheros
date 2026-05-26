<?php

namespace App\Filament\Resources\Guides\Schemas;

use App\Models\Guide;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GuideInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Guide')
                    ->schema([
                        ImageEntry::make('cover_path')
                            ->label('Couverture')
                            ->state(fn (Guide $record): string => $record->coverUrl())
                            ->height(120),

                        TextEntry::make('title')
                            ->label('Titre'),

                        TextEntry::make('category')
                            ->label('Categorie')
                            ->formatStateUsing(fn (Guide $record): string => $record->categoryLabel())
                            ->badge(),

                        TextEntry::make('mission.title')
                            ->label('Mission')
                            ->placeholder('Aucune'),

                        TextEntry::make('summary')
                            ->label('Resume')
                            ->columnSpanFull(),

                        TextEntry::make('chips')
                            ->label('Tags')
                            ->formatStateUsing(fn ($state): string => collect($state ?? [])->join(', '))
                            ->placeholder('Aucun'),

                        TextEntry::make('checklist')
                            ->label('Checklist')
                            ->formatStateUsing(fn ($state): string => collect($state ?? [])->join("\n"))
                            ->placeholder('Aucune'),

                        TextEntry::make('sections_count')
                            ->label('Sections')
                            ->state(fn (Guide $record): int => count($record->sections ?? [])),

                        TextEntry::make('is_published')
                            ->label('Publication')
                            ->state(fn (Guide $record): string => $record->is_published ? 'Publie' : 'Brouillon')
                            ->badge()
                            ->color(fn (Guide $record): string => $record->is_published ? 'success' : 'gray'),

                        TextEntry::make('published_at')
                            ->label('Date')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Non definie'),
                    ])
                    ->columns(2),
            ]);
    }
}
