<?php

namespace App\Filament\Resources\AdminRoles\Schemas;

use App\Models\AdminRole;
use App\Support\AdminAccess;
use App\Support\AdminRoleFilamentData;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdminRoleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Role')
                    ->schema([
                        TextEntry::make('label')
                            ->label('Nom'),

                        TextEntry::make('key')
                            ->label('Cle'),

                        TextEntry::make('color')
                            ->label('Couleur')
                            ->badge(),

                        TextEntry::make('users')
                            ->label('Utilisateurs')
                            ->state(fn (AdminRole $record): int => AdminRoleFilamentData::userCount($record->key)),

                        TextEntry::make('permissions')
                            ->label('Permissions')
                            ->state(fn (AdminRole $record): string => collect(AdminAccess::rolePermissions($record->key))
                                ->map(fn (string $permission): string => AdminAccess::permissions()[$permission] ?? $permission)
                                ->join(', ') ?: 'Aucune')
                            ->columnSpanFull(),
                    ])
                    ->columns(4),
            ]);
    }
}
