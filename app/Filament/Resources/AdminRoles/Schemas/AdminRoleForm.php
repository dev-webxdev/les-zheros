<?php

namespace App\Filament\Resources\AdminRoles\Schemas;

use App\Models\AdminRole;
use App\Support\AdminAccess;
use App\Support\AdminRoleFilamentData;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdminRoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identite')
                    ->schema([
                        TextInput::make('key')
                            ->label('Cle')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?AdminRole $record): bool => $record?->exists ?? false),

                        TextInput::make('label')
                            ->label('Nom du role')
                            ->required()
                            ->maxLength(120),

                        Select::make('color')
                            ->label('Couleur')
                            ->options(AdminRoleFilamentData::COLORS)
                            ->default('neutral')
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Permissions')
                    ->schema([
                        ViewField::make('permissions')
                            ->label('Permissions')
                            ->view('filament.forms.admin-role-permission-drag-drop')
                            ->default([])
                            ->rules(['array']),
                    ]),
            ]);
    }
}
