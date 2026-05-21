<?php

namespace App\Filament\Resources\Announcements\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AnnouncementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Annonce')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Titre'),

                        TextEntry::make('tag')
                            ->label('Tag')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                        TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->formatStateUsing(fn ($record): string => $record->statusLabel())
                            ->color(fn ($record): string => match ($record->statusForForm()) {
                                'published' => 'success',
                                'scheduled' => 'info',
                                default => 'gray',
                            }),

                        TextEntry::make('user.name')
                            ->label('Auteur')
                            ->placeholder('Aucun'),

                        TextEntry::make('published_at')
                            ->label('Publication')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Non publiee'),

                        TextEntry::make('created_at')
                            ->label('Creation')
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('content')
                            ->label('Contenu')
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
