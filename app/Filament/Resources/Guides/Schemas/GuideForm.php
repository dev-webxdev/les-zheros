<?php

namespace App\Filament\Resources\Guides\Schemas;

use App\Models\Guide;
use App\Models\Mission;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class GuideForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'default' => 1,
                    'lg' => 12,
                ])
                    ->schema([
                        Tabs::make('Guide')
                            ->tabs([
                                Tab::make('Infos')
                                    ->icon('heroicon-o-information-circle')
                                    ->schema([
                                        Section::make('Informations')
                                            ->schema([
                                                TextInput::make('title')
                                                    ->label('Titre du guide')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->columnSpan([
                                                        'default' => 1,
                                                        'lg' => 8,
                                                    ]),

                                                Select::make('category')
                                                    ->label('Categorie')
                                                    ->options(Guide::CATEGORIES)
                                                    ->required()
                                                    ->columnSpan([
                                                        'default' => 1,
                                                        'lg' => 4,
                                                    ]),

                                                Textarea::make('summary')
                                                    ->label('Resume court')
                                                    ->required()
                                                    ->maxLength(2000)
                                                    ->rows(4)
                                                    ->columnSpanFull(),

                                                TextInput::make('chips_text')
                                                    ->label('Tags du haut')
                                                    ->maxLength(500)
                                                    ->helperText('Separe les tags avec des virgules.')
                                                    ->dehydrated()
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns([
                                                'default' => 1,
                                                'lg' => 12,
                                            ]),
                                    ]),

                                Tab::make('Resume')
                                    ->icon('heroicon-o-list-bullet')
                                    ->schema([
                                        Section::make('Resume')
                                            ->schema([
                                                Repeater::make('checklist')
                                                    ->label('Points du resume')
                                                    ->schema([
                                                        TextInput::make('item')
                                                            ->label('Point')
                                                            ->maxLength(500)
                                                            ->required(),
                                                    ])
                                                    ->defaultItems(0)
                                                    ->reorderable()
                                                    ->addActionLabel('Ajouter un point')
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),

                                Tab::make('Placement')
                                    ->icon('heroicon-o-map')
                                    ->schema([
                                        Section::make('Placement')
                                            ->schema([
                                                self::sectionRepeater(
                                                    'placement_sections',
                                                    'Blocs de placement',
                                                    'Ajouter une section de placement',
                                                ),
                                            ]),
                                    ]),

                                Tab::make('Strategie')
                                    ->icon('heroicon-o-share')
                                    ->schema([
                                        Section::make('Strategie')
                                            ->schema([
                                                self::sectionRepeater(
                                                    'strategy_sections',
                                                    'Sections de strategie',
                                                    'Ajouter une section',
                                                ),
                                            ]),
                                    ]),

                                Tab::make('Sorts')
                                    ->icon('heroicon-o-sparkles')
                                    ->schema([
                                        Section::make('Sorts des monstres')
                                            ->schema([
                                                self::sectionRepeater(
                                                    'spells_sections',
                                                    'Sorts',
                                                    'Ajouter un sort',
                                                ),
                                            ]),
                                    ]),
                            ])
                            ->persistTabInQueryString('guide-tab')
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 9,
                            ]),

                        Section::make('Options')
                            ->schema([
                                Toggle::make('is_published')
                                    ->label('Publie')
                                    ->default(true)
                                    ->inline(false),

                                Select::make('mission_id')
                                    ->label('Mission liee')
                                    ->options(fn (): array => Mission::query()
                                        ->whereIn('category', ['donjon', 'expedition'])
                                        ->orderBy('title')
                                        ->pluck('title', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload(),

                                DateTimePicker::make('published_at')
                                    ->label('Publication')
                                    ->seconds(false)
                                    ->default(now()),

                                TextInput::make('cover_path')
                                    ->label('Image de couverture existante')
                                    ->url()
                                    ->maxLength(1000),

                                FileUpload::make('cover_upload')
                                    ->label('Nouvelle couverture')
                                    ->image()
                                    ->maxSize(4096)
                                    ->storeFiles(false)
                                    ->dehydrated(),

                                TextInput::make('map_path')
                                    ->label('Map existante')
                                    ->url()
                                    ->maxLength(1000),

                                FileUpload::make('map_upload')
                                    ->label('Nouvelle map')
                                    ->image()
                                    ->maxSize(4096)
                                    ->storeFiles(false)
                                    ->dehydrated(),
                            ])
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 3,
                            ]),
                    ]),
            ]);
    }

    private static function sectionRepeater(string $name, string $label, string $addActionLabel): Repeater
    {
        return Repeater::make($name)
            ->label($label)
            ->schema([
                TextInput::make('title')
                    ->label('Titre de section')
                    ->maxLength(255),

                RichEditor::make('body')
                    ->label('Contenu')
                    ->maxLength(8000)
                    ->columnSpanFull(),

                Repeater::make('images')
                    ->label('Images')
                    ->schema([
                        TextInput::make('image_path')
                            ->label('Image existante')
                            ->url()
                            ->maxLength(1000),

                        FileUpload::make('image_upload')
                            ->label('Nouvelle image')
                            ->image()
                            ->maxSize(4096)
                            ->storeFiles(false)
                            ->dehydrated(),

                        Textarea::make('caption')
                            ->label("Texte sous l'image")
                            ->rows(3)
                            ->maxLength(1200)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->addActionLabel('Ajouter une image')
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->defaultItems(0)
            ->reorderable()
            ->addActionLabel($addActionLabel)
            ->columnSpanFull();
    }
}
