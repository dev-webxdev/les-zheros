<?php

namespace App\Filament\Resources\GalleryImages\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GalleryImageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Image')
                    ->schema([
                        ImageEntry::make('image_path')
                            ->label('Image')
                            ->height(220)
                            ->columnSpanFull(),

                        TextEntry::make('title')
                            ->label('Titre'),

                        IconEntry::make('is_published')
                            ->label('Publiee')
                            ->boolean(),

                        TextEntry::make('taken_at')
                            ->label('Date')
                            ->date('d/m/Y')
                            ->placeholder('Non renseignee'),

                        TextEntry::make('created_at')
                            ->label('Creation')
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('Aucune description')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
