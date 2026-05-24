<?php

namespace App\Filament\Resources\MissionValidations\Schemas;

use App\Models\MissionValidation;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MissionValidationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Validation')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Joueur'),

                        TextEntry::make('mission.title')
                            ->label('Mission'),

                        TextEntry::make('characters')
                            ->label('Personnages'),

                        TextEntry::make('points')
                            ->label('Points')
                            ->state(fn (MissionValidation $record): string => number_format($record->estimatedPoints(), 2, ',', ' ')),

                        TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->formatStateUsing(fn (MissionValidation $record): string => $record->statusLabel())
                            ->color(fn (MissionValidation $record): string => match ($record->status) {
                                MissionValidation::VALIDATED => 'success',
                                MissionValidation::REFUSED => 'danger',
                                default => 'info',
                            }),

                        TextEntry::make('created_at')
                            ->label('Creation')
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('reviewer.name')
                            ->label('Relu par')
                            ->placeholder('Non relu'),

                        TextEntry::make('reviewed_at')
                            ->label('Relecture')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Non relu'),
                    ])
                    ->columns(2),

                Section::make('Preuve')
                    ->schema([
                        TextEntry::make('proof_path')
                            ->label('Fichier')
                            ->url(fn (?string $state): ?string => $state, true)
                            ->placeholder('Aucun fichier'),

                        TextEntry::make('proof_text')
                            ->label('Texte')
                            ->placeholder('Aucun texte')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
