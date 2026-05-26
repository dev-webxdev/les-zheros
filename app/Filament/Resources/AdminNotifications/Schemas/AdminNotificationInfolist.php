<?php

namespace App\Filament\Resources\AdminNotifications\Schemas;

use App\Models\AdminNotification;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdminNotificationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Notification')
                    ->schema([
                        TextEntry::make('read_at')
                            ->label('Statut')
                            ->state(fn (AdminNotification $record): string => $record->isUnread() ? 'Non lue' : 'Lue')
                            ->badge()
                            ->color(fn (AdminNotification $record): string => $record->isUnread() ? 'warning' : 'gray'),

                        TextEntry::make('type')
                            ->label('Type')
                            ->badge(),

                        TextEntry::make('area')
                            ->label('Module')
                            ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state))
                            ->badge(),

                        TextEntry::make('created_at')
                            ->label('Date')
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('title')
                            ->label('Titre')
                            ->columnSpanFull(),

                        TextEntry::make('message')
                            ->label('Message')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('url')
                            ->label('Lien')
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab()
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
