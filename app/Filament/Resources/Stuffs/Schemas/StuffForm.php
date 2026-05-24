<?php

namespace App\Filament\Resources\Stuffs\Schemas;

use App\Models\Stuff;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StuffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Build')
                    ->schema([
                        TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('dofusbook_url')
                            ->label('URL Dofusbook')
                            ->url()
                            ->required()
                            ->maxLength(1000),

                        Select::make('class_label')
                            ->label('Classe')
                            ->options(array_combine(array_values(Stuff::CLASSES), array_values(Stuff::CLASSES)))
                            ->required(),

                        Select::make('elements')
                            ->label('Elements')
                            ->options(array_combine(Stuff::ELEMENTS, Stuff::ELEMENTS))
                            ->multiple()
                            ->required(),

                        Select::make('mode')
                            ->label('Mode')
                            ->options(array_combine(Stuff::MODES, Stuff::MODES))
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Conditions')
                    ->schema([
                        TextInput::make('meta')
                            ->label('Meta optionnel')
                            ->maxLength(255),

                        Select::make('min_level')
                            ->label('Niveau min')
                            ->options(array_combine(Stuff::LEVELS, Stuff::LEVELS))
                            ->default(200)
                            ->required(),

                        Select::make('max_level')
                            ->label('Niveau max')
                            ->options(array_combine(Stuff::LEVELS, Stuff::LEVELS))
                            ->default(200),

                        Textarea::make('description')
                            ->label('Commentaire')
                            ->maxLength(2000)
                            ->rows(5)
                            ->columnSpanFull(),

                        Toggle::make('is_featured')
                            ->label('Mettre en avant ce build'),

                        Toggle::make('is_published')
                            ->label('Publie')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
