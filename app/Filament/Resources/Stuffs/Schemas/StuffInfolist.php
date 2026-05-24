<?php

namespace App\Filament\Resources\Stuffs\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StuffInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Stuff')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Titre'),

                        TextEntry::make('dofusbook_url')
                            ->label('URL Dofusbook')
                            ->url(fn ($record): string => $record->dofusbook_url)
                            ->openUrlInNewTab(),

                        TextEntry::make('class_label')
                            ->label('Classe')
                            ->badge(),

                        TextEntry::make('elements')
                            ->label('Elements')
                            ->formatStateUsing(fn ($state): string => collect($state ?? [])->join(' / ')),

                        TextEntry::make('mode')
                            ->label('Mode')
                            ->badge(),

                        TextEntry::make('level')
                            ->label('Niveau')
                            ->state(fn ($record): string => $record->levelLabel()),

                        TextEntry::make('meta')
                            ->label('Meta')
                            ->placeholder('Aucune'),

                        IconEntry::make('is_featured')
                            ->label('Mis en avant')
                            ->boolean(),

                        IconEntry::make('is_published')
                            ->label('Publie')
                            ->boolean(),

                        TextEntry::make('description')
                            ->label('Commentaire')
                            ->placeholder('Aucun commentaire')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
