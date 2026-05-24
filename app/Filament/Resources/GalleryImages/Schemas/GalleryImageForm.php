<?php

namespace App\Filament\Resources\GalleryImages\Schemas;

use App\Models\GalleryImage;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GalleryImageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Image')
                    ->schema([
                        TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Description')
                            ->maxLength(1000)
                            ->rows(5)
                            ->columnSpanFull(),

                        Placeholder::make('current_image')
                            ->label('Image actuelle')
                            ->content(fn (?GalleryImage $record): ?string => $record?->image_path
                                ? '<img src="'.e($record->image_path).'" alt="" style="max-height: 180px; border-radius: 6px;">'
                                : null)
                            ->html()
                            ->visible(fn (?GalleryImage $record): bool => filled($record?->image_path))
                            ->columnSpanFull(),

                        FileUpload::make('image_upload')
                            ->label('Image depuis ton PC')
                            ->image()
                            ->maxSize(6144)
                            ->storeFiles(false)
                            ->dehydrated()
                            ->columnSpanFull(),

                        TextInput::make('image_url')
                            ->label('Ou URL image')
                            ->url()
                            ->maxLength(1000)
                            ->dehydrated()
                            ->columnSpanFull(),

                        DatePicker::make('taken_at')
                            ->label('Date')
                            ->default(today()),

                        Toggle::make('is_published')
                            ->label('Publiée')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
