<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Utilisateur')
                    ->schema([
                        ImageEntry::make('avatar')
                            ->label('Avatar')
                            ->state(fn (User $record): ?string => $record->avatarUrl())
                            ->circular()
                            ->height(72),

                        TextEntry::make('name')
                            ->label('Pseudo'),

                        TextEntry::make('email')
                            ->label('Email'),

                        TextEntry::make('country')
                            ->label('Pays'),

                        TextEntry::make('roles')
                            ->label('Roles')
                            ->state(fn (User $record): string => $record->adminRolesLabel()),

                        IconEntry::make('is_approved')
                            ->label('Compte valide')
                            ->boolean(),

                        TextEntry::make('created_at')
                            ->label('Creation')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2),
            ]);
    }
}
