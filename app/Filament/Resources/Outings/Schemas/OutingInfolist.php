<?php

namespace App\Filament\Resources\Outings\Schemas;

use App\Models\Outing;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OutingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sortie')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Titre'),

                        TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('Aucune description')
                            ->columnSpanFull(),

                        TextEntry::make('status')
                            ->label('Statut')
                            ->state(fn (Outing $record): string => self::status($record))
                            ->badge()
                            ->color(fn (Outing $record): string => self::statusColor($record)),

                        TextEntry::make('places')
                            ->label('Places max'),

                        TextEntry::make('votes_count')
                            ->label('Inscrits')
                            ->state(fn (Outing $record): string => $record->votes()->count().' / '.$record->places),

                        TextEntry::make('slot_count')
                            ->label('Creneaux')
                            ->state(fn (Outing $record): int => $record->slotCount()),

                        IconEntry::make('is_published')
                            ->label('Publie')
                            ->boolean(),

                        TextEntry::make('close_at')
                            ->label('Cloture')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Non definie'),

                        TextEntry::make('confirmed_slot_id')
                            ->label('Creneau valide')
                            ->state(fn (Outing $record): string => $record->confirmed_slot_id ? $record->slotLabel($record->confirmed_slot_id) : 'Aucun')
                            ->columnSpanFull(),

                        TextEntry::make('participants')
                            ->label('Participants du creneau valide')
                            ->state(fn (Outing $record): string => $record->confirmedVotes()
                                ->map(fn ($vote): ?string => $vote->user?->name)
                                ->filter()
                                ->join(', ') ?: 'Aucun')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }

    private static function status(Outing $record): string
    {
        if (! $record->is_published) {
            return 'Brouillon';
        }

        if ($record->confirmed_slot_id) {
            return 'Validee';
        }

        return $record->isClosed() ? 'Cloturee' : 'Ouverte';
    }

    private static function statusColor(Outing $record): string
    {
        if (! $record->is_published) {
            return 'gray';
        }

        if ($record->confirmed_slot_id) {
            return 'success';
        }

        return $record->isClosed() ? 'danger' : 'info';
    }
}
