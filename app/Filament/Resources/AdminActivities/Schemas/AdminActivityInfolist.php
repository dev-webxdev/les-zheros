<?php

namespace App\Filament\Resources\AdminActivities\Schemas;

use App\Models\AdminActivityLog;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdminActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Activite')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Date')
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('actor')
                            ->label('Admin')
                            ->state(fn (AdminActivityLog $record): string => $record->actorName()),

                        TextEntry::make('area')
                            ->label('Module')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),

                        TextEntry::make('action')
                            ->label('Action')
                            ->badge(),

                        TextEntry::make('title')
                            ->label('Titre'),

                        TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Cible')
                    ->schema([
                        TextEntry::make('subject_label')
                            ->label('Element')
                            ->placeholder('-'),

                        TextEntry::make('subject_type')
                            ->label('Type')
                            ->placeholder('-'),

                        TextEntry::make('subject_id')
                            ->label('ID')
                            ->placeholder('-'),
                    ])
                    ->columns(3),

                Section::make('Contexte')
                    ->schema([
                        TextEntry::make('ip_address')
                            ->label('IP')
                            ->placeholder('-'),

                        TextEntry::make('user_agent')
                            ->label('Navigateur')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('properties')
                            ->label('Donnees')
                            ->state(fn (AdminActivityLog $record): string => filled($record->properties)
                                ? json_encode($record->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                : '-')
                            ->fontFamily('mono')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
