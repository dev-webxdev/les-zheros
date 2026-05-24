<?php

namespace App\Filament\Resources\MissionValidations\Schemas;

use App\Models\Mission;
use App\Models\MissionValidation;
use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MissionValidationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Mission')
                    ->schema([
                        Select::make('mission_id')
                            ->label('Mission')
                            ->options(fn (): array => Mission::query()
                                ->orderByDesc('created_at')
                                ->pluck('title', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('user_id')
                            ->label('Joueur')
                            ->options(fn (): array => User::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Validation')
                    ->schema([
                        TextInput::make('characters')
                            ->label('Personnages')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(8)
                            ->default(1)
                            ->required(),

                        Select::make('status')
                            ->label('Statut')
                            ->options(MissionValidation::STATUSES)
                            ->default(MissionValidation::VALIDATED)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Coequipiers')
                    ->schema([
                        Repeater::make('teammates')
                            ->label('Coequipiers')
                            ->schema([
                                Select::make('user_id')
                                    ->label('Joueur')
                                    ->options(fn (): array => User::query()
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                TextInput::make('characters')
                                    ->label('Personnages')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(8)
                                    ->default(1)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Ajouter un coequipier'),
                    ])
                    ->visibleOn('create'),

                Section::make('Preuve')
                    ->schema([
                        Placeholder::make('proof_path_preview')
                            ->label('Fichier')
                            ->content(fn (?MissionValidation $record): ?string => filled($record?->proof_path)
                                ? '<a href="'.e($record->proof_path).'" target="_blank" rel="noopener">Ouvrir la preuve</a>'
                                : 'Aucun fichier')
                            ->html(),

                        Placeholder::make('proof_text_preview')
                            ->label('Texte')
                            ->content(fn (?MissionValidation $record): string => filled($record?->proof_text)
                                ? (string) $record->proof_text
                                : 'Aucun texte'),
                    ])
                    ->visible(fn (?MissionValidation $record): bool => $record?->exists ?? false),
            ]);
    }
}
