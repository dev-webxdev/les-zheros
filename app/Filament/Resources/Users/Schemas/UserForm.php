<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use App\Support\AdminAccess;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identite')
                    ->schema([
                        TextInput::make('name')
                            ->label('Pseudo')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Select::make('country')
                            ->label('Pays')
                            ->options(self::countries())
                            ->searchable()
                            ->default('fr')
                            ->required(),

                        Toggle::make('is_approved')
                            ->label('Compte valide')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Roles')
                    ->schema([
                        ViewField::make('admin_roles')
                            ->label('Roles')
                            ->view('filament.forms.user-role-drag-drop')
                            ->default([AdminAccess::MEMBER])
                            ->required()
                            ->rules(['array', 'min:1']),
                    ]),

                Section::make('Avatar')
                    ->schema([
                        Placeholder::make('current_avatar')
                            ->label('Avatar actuel')
                            ->content(fn (?User $record): ?string => $record?->avatarUrl()
                                ? '<img src="'.e($record->avatarUrl()).'" alt="" style="width:96px;height:96px;object-fit:cover;border-radius:999px;border:1px solid #e5e7eb;">'
                                : null)
                            ->html()
                            ->visible(fn (?User $record): bool => filled($record?->avatarUrl())),

                        Toggle::make('remove_avatar')
                            ->label("Supprimer l'avatar actuel")
                            ->dehydrated()
                            ->visible(fn (?User $record): bool => filled($record?->avatarUrl())),

                        FileUpload::make('avatar_upload')
                            ->label('Nouvel avatar')
                            ->image()
                            ->maxSize(4096)
                            ->storeFiles(false)
                            ->dehydrated(),
                    ]),

                Section::make('Securite')
                    ->schema([
                        TextInput::make('password')
                            ->label(fn (string $operation): string => $operation === 'create' ? 'Mot de passe' : 'Nouveau mot de passe')
                            ->password()
                            ->revealable()
                            ->columnSpan(7)
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->rules(fn (string $operation): array => [
                                $operation === 'create' ? 'required' : 'nullable',
                                Password::min(6),
                            ])
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText(fn (string $operation): ?string => $operation === 'edit'
                                ? 'Laisse vide pour conserver le mot de passe actuel.'
                                : null),
                        SchemaActions::make([
                            Action::make('generatePassword')
                                ->label('Generer')
                                ->icon('heroicon-o-sparkles')
                                ->button()
                                ->action(fn (Set $set): mixed => $set('password', self::generatePassword())),
                        ])
                            ->extraAttributes(['class' => 'lz-password-generate-action'])
                            ->columnSpan(5),
                    ])
                    ->columns(12),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function countries(): array
    {
        $codes = [
            'AF', 'AX', 'AL', 'DZ', 'AS', 'AD', 'AO', 'AI', 'AQ', 'AG', 'AR', 'AM', 'AW', 'AU', 'AT', 'AZ',
            'BS', 'BH', 'BD', 'BB', 'BY', 'BE', 'BZ', 'BJ', 'BM', 'BT', 'BO', 'BQ', 'BA', 'BW', 'BV', 'BR',
            'IO', 'BN', 'BG', 'BF', 'BI', 'CV', 'KH', 'CM', 'CA', 'KY', 'CF', 'TD', 'CL', 'CN', 'CX', 'CC',
            'CO', 'KM', 'CG', 'CD', 'CK', 'CR', 'CI', 'HR', 'CU', 'CW', 'CY', 'CZ', 'DK', 'DJ', 'DM', 'DO',
            'EC', 'EG', 'SV', 'GQ', 'ER', 'EE', 'SZ', 'ET', 'FK', 'FO', 'FJ', 'FI', 'FR', 'GF', 'PF', 'TF',
            'GA', 'GM', 'GE', 'DE', 'GH', 'GI', 'GR', 'GL', 'GD', 'GP', 'GU', 'GT', 'GG', 'GN', 'GW', 'GY',
            'HT', 'HM', 'VA', 'HN', 'HK', 'HU', 'IS', 'IN', 'ID', 'IR', 'IQ', 'IE', 'IM', 'IL', 'IT', 'JM',
            'JP', 'JE', 'JO', 'KZ', 'KE', 'KI', 'KP', 'KR', 'KW', 'KG', 'LA', 'LV', 'LB', 'LS', 'LR', 'LY',
            'LI', 'LT', 'LU', 'MO', 'MG', 'MW', 'MY', 'MV', 'ML', 'MT', 'MH', 'MQ', 'MR', 'MU', 'YT', 'MX',
            'FM', 'MD', 'MC', 'MN', 'ME', 'MS', 'MA', 'MZ', 'MM', 'NA', 'NR', 'NP', 'NL', 'NC', 'NZ', 'NI',
            'NE', 'NG', 'NU', 'NF', 'MK', 'MP', 'NO', 'OM', 'PK', 'PW', 'PS', 'PA', 'PG', 'PY', 'PE', 'PH',
            'PN', 'PL', 'PT', 'PR', 'QA', 'RE', 'RO', 'RU', 'RW', 'BL', 'SH', 'KN', 'LC', 'MF', 'PM', 'VC',
            'WS', 'SM', 'ST', 'SA', 'SN', 'RS', 'SC', 'SL', 'SG', 'SX', 'SK', 'SI', 'SB', 'SO', 'ZA', 'GS',
            'SS', 'ES', 'LK', 'SD', 'SR', 'SJ', 'SE', 'CH', 'SY', 'TW', 'TJ', 'TZ', 'TH', 'TL', 'TG', 'TK',
            'TO', 'TT', 'TN', 'TR', 'TM', 'TC', 'TV', 'UG', 'UA', 'AE', 'GB', 'US', 'UM', 'UY', 'UZ', 'VU',
            'VE', 'VN', 'VG', 'VI', 'WF', 'EH', 'YE', 'ZM', 'ZW',
        ];

        return collect($codes)
            ->mapWithKeys(fn (string $code): array => [
                strtolower($code) => \Locale::getDisplayRegion('-'.$code, 'fr') ?: $code,
            ])
            ->sort()
            ->all();
    }

    private static function generatePassword(): string
    {
        $groups = [
            'ABCDEFGHJKLMNPQRSTUVWXYZ',
            'abcdefghijkmnopqrstuvwxyz',
            '23456789',
            '!@#$%?',
        ];
        $password = array_map(fn (string $group): string => $group[random_int(0, strlen($group) - 1)], $groups);
        $pool = implode('', $groups);

        while (count($password) < 14) {
            $password[] = $pool[random_int(0, strlen($pool) - 1)];
        }

        shuffle($password);

        return implode('', $password);
    }
}
