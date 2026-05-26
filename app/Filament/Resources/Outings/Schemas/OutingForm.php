<?php

namespace App\Filament\Resources\Outings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OutingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations')
                    ->schema([
                        TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Description courte')
                            ->maxLength(2000)
                            ->rows(4)
                            ->columnSpanFull(),

                        TextInput::make('places')
                            ->label('Places max')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(16)
                            ->default(8)
                            ->required(),

                        DateTimePicker::make('close_at')
                            ->label('Cloture des votes')
                            ->seconds(false)
                            ->helperText('Doit etre au plus tard 2 heures avant le premier creneau.'),

                        Toggle::make('is_published')
                            ->label('Publie')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Creneaux')
                    ->schema([
                        Repeater::make('schedule')
                            ->label('Jours')
                            ->schema([
                                TextInput::make('date')
                                    ->label('Date')
                                    ->type('date')
                                    ->required(),

                                TagsInput::make('times')
                                    ->label('Heures')
                                    ->placeholder('15:00')
                                    ->helperText('Ajoute les heures au format 15:00.')
                                    ->required()
                                    ->nestedRecursiveRules(['regex:/^\d{2}:\d{2}$/']),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->reorderable()
                            ->addActionLabel('Ajouter un jour')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
