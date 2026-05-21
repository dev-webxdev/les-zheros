<?php

namespace App\Filament\Resources\Announcements\Schemas;

use App\Models\Announcement;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Annonce')
                    ->schema([
                        TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('tag')
                            ->label('Tag')
                            ->options(Announcement::TAGS)
                            ->default('info')
                            ->required(),

                        Select::make('status')
                            ->label('Statut')
                            ->options([
                                'draft' => 'Brouillon',
                                'published' => 'Publie',
                                'scheduled' => 'Programme',
                            ])
                            ->default('published')
                            ->required()
                            ->live(),

                        DateTimePicker::make('published_at')
                            ->label('Date de publication')
                            ->seconds(false)
                            ->default(now())
                            ->required(fn (Get $get): bool => $get('status') === 'scheduled')
                            ->visible(fn (Get $get): bool => in_array($get('status'), ['published', 'scheduled'], true)),

                        RichEditor::make('content')
                            ->label('Contenu')
                            ->required()
                            ->maxLength(20000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
