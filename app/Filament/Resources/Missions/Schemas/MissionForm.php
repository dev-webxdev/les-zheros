<?php

namespace App\Filament\Resources\Missions\Schemas;

use App\Models\Mission;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations')
                    ->schema([
                        TextInput::make('title')
                            ->label('Titre de la mission')
                            ->maxLength(255)
                            ->live(debounce: 300)
                            ->required(fn (Get $get): bool => $get('category') !== 'anomalie')
                            ->visible(fn (Get $get): bool => $get('category') !== 'anomalie')
                            ->columnSpanFull(),

                        Select::make('category')
                            ->label('Categorie')
                            ->options(Mission::CATEGORIES)
                            ->required()
                            ->live(),

                        Select::make('anomaly_type')
                            ->label("Type d'anomalie")
                            ->options(Mission::ANOMALY_TYPES)
                            ->required(fn (Get $get): bool => $get('category') === 'anomalie')
                            ->visible(fn (Get $get): bool => $get('category') === 'anomalie'),

                        Select::make('anomaly_level')
                            ->label('Niveau anomalie')
                            ->options(array_combine(Mission::ANOMALY_LEVELS, Mission::ANOMALY_LEVELS))
                            ->required(fn (Get $get): bool => $get('category') === 'anomalie')
                            ->visible(fn (Get $get): bool => $get('category') === 'anomalie'),

                        Select::make('dream_type')
                            ->label('Type de songe')
                            ->options(Mission::DREAM_TYPES)
                            ->required(fn (Get $get): bool => $get('category') === 'songe')
                            ->visible(fn (Get $get): bool => $get('category') === 'songe'),

                        Select::make('dream_floor')
                            ->label('Palier')
                            ->options([1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5])
                            ->required(fn (Get $get): bool => $get('category') === 'songe')
                            ->visible(fn (Get $get): bool => $get('category') === 'songe'),
                    ])
                    ->columns(2),

                Section::make('Image')
                    ->schema([
                        Placeholder::make('current_image')
                            ->label('Image actuelle')
                            ->content(fn (?Mission $record): ?string => $record?->exists
                                ? '<img src="'.e($record->imageUrl()).'" alt="" style="max-height: 180px; border-radius: 6px;">'
                                : null)
                            ->html()
                            ->visible(fn (?Mission $record): bool => $record?->exists ?? false)
                            ->columnSpanFull(),

                        Radio::make('image_mode')
                            ->label("Source de l'image")
                            ->options([
                                'api' => 'Recherche DofusDB',
                                'upload' => 'Depuis mon PC',
                                'url' => 'Lien image',
                            ])
                            ->default('api')
                            ->inline()
                            ->live()
                            ->disabled(fn (Get $get): bool => ! self::canChooseImageSource($get))
                            ->extraAttributes(['class' => 'lz-image-source-mode'])
                            ->visible(fn (Get $get): bool => $get('category') !== 'anomalie'),

                        Select::make('selected_image')
                            ->label('Recherche du monstre')
                            ->searchable()
                            ->allowHtml()
                            ->placeholder(fn (Get $get): string => self::canChooseImageSource($get)
                                ? 'Tape au moins 2 lettres, puis clique un resultat'
                                : "Choisis d'abord un titre et une categorie")
                            ->getSearchResultsUsing(fn (string $search): array => self::searchDofusMonsters($search))
                            ->getOptionLabelUsing(fn (?string $value): ?string => self::selectedImageLabel($value))
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                [$monsterId] = self::parseDofusValue($state);

                                $set('monster_id', $monsterId);
                            })
                            ->dehydrated()
                            ->disabled(fn (Get $get): bool => ! self::canChooseImageSource($get))
                            ->visible(fn (Get $get): bool => $get('category') !== 'anomalie' && $get('image_mode') === 'api')
                            ->columnSpanFull(),

                        SchemaActions::make([
                            Action::make('chooseMedia')
                                ->label('Choisir dans la mediatheque')
                                ->icon('heroicon-o-photo')
                                ->button()
                                ->disabled(fn (Get $get): bool => ! self::canChooseImageSource($get))
                                ->modalHeading('Choisir une image')
                                ->modalSubmitActionLabel('Choisir')
                                ->fillForm(function (array $schemaState): array {
                                    $search = $schemaState['title'] ?? null;
                                    $firstImage = self::singleMissionMediaValue($search);

                                    return [
                                        'media_search' => $search,
                                        'media_image' => $firstImage ? [$firstImage] : [],
                                    ];
                                })
                                ->schema([
                                    TextInput::make('media_search')
                                        ->label('Rechercher')
                                        ->live(debounce: 300),

                                    CheckboxList::make('media_image')
                                        ->label('Image existante')
                                        ->options(fn (Get $get): array => self::missionMediaOptions($get('media_search')))
                                        ->allowHtml()
                                        ->live()
                                        ->afterStateUpdated(function (?array $state, Set $set): void {
                                            if (blank($state)) {
                                                return;
                                            }

                                            $values = array_values($state);
                                            $set('media_image', [end($values)]);
                                        })
                                        ->columnSpanFull(),
                                ])
                                ->action(function (array $data, Set $set): void {
                                    $path = is_array($data['media_image'] ?? null)
                                        ? ($data['media_image'][0] ?? null)
                                        : ($data['media_image'] ?? null);

                                    if (filled($path)) {
                                        $set('selected_image', $path);
                                        $set('image_mode', 'upload');
                                        $set('monster_id', null);
                                    }
                                }),
                        ])
                            ->visible(fn (Get $get): bool => $get('category') !== 'anomalie' && $get('image_mode') === 'upload')
                            ->columnSpanFull(),

                        FileUpload::make('image_upload')
                            ->label('Uploader depuis ton PC')
                            ->image()
                            ->maxSize(4096)
                            ->storeFiles(false)
                            ->dehydrated()
                            ->disabled(fn (Get $get): bool => ! self::canChooseImageSource($get))
                            ->visible(fn (Get $get): bool => $get('category') !== 'anomalie' && $get('image_mode') === 'upload')
                            ->columnSpanFull(),

                        TextInput::make('image_url')
                            ->label("Lien de l'image")
                            ->url()
                            ->maxLength(1000)
                            ->dehydrated()
                            ->disabled(fn (Get $get): bool => ! self::canChooseImageSource($get))
                            ->visible(fn (Get $get): bool => $get('category') !== 'anomalie' && $get('image_mode') === 'url')
                            ->columnSpanFull(),

                        Hidden::make('monster_id')
                            ->dehydrated(),
                    ])
                    ->columns(2),
            ]);
    }

    private static function canChooseImageSource(Get $get): bool
    {
        $category = $get('category');

        if (blank($category)) {
            return false;
        }

        if ($category === 'anomalie') {
            return true;
        }

        return filled($get('title'));
    }

    /**
     * @return array<string, string>
     */
    private static function searchDofusMonsters(string $search): array
    {
        $search = trim($search);

        if (mb_strlen($search) < 2) {
            return [];
        }

        try {
            $payload = Http::timeout(8)->get('https://api.dofusdb.fr/monsters', [
                'slug.fr[$search]' => $search,
                '$limit' => 20,
            ])->json();
        } catch (\Throwable) {
            return [];
        }

        $normalizedSearch = self::normalizeSearch($search);

        return collect($payload['data'] ?? [])
            ->sortBy(function (array $monster) use ($normalizedSearch): string {
                $name = self::normalizeSearch((string) data_get($monster, 'name.fr', data_get($monster, 'name.en', '')));
                $slug = self::normalizeSearch((string) data_get($monster, 'slug.fr', $name));
                $haystack = $slug ?: $name;

                $score = match (true) {
                    $haystack === $normalizedSearch => 0,
                    str_starts_with($haystack, $normalizedSearch) => 1,
                    str_contains($haystack, ' '.$normalizedSearch) => 2,
                    str_contains($haystack, $normalizedSearch) => 3,
                    default => 4,
                };

                return $score.'-'.$haystack;
            })
            ->mapWithKeys(function (array $monster): array {
                $id = (string) ($monster['id'] ?? '');
                $gfxId = (string) ($monster['gfxId'] ?? '');
                $name = (string) data_get($monster, 'name.fr', data_get($monster, 'name.en', 'Monstre'));
                $image = (string) ($monster['img'] ?? ($gfxId !== '' ? "https://api.dofusdb.fr/img/monsters/{$gfxId}.png" : ''));
                $level = data_get($monster, 'grades.0.level');

                if ($id === '' || $image === '') {
                    return [];
                }

                $meta = $level ? "Niv. {$level}" : 'Monstre';
                $label = self::imageOptionHtml($image, $name, $meta);
                $nameForValue = str_replace('|', '/', $level ? "{$name} - {$meta}" : $name);

                return ["dofusdb:{$id}|{$image}|{$nameForValue}" => $label];
            })
            ->all();
    }

    private static function selectedImageLabel(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (str_starts_with($value, 'dofusdb:')) {
            [$monsterId, $image, $label] = self::parseDofusValue($value);

            return $image
                ? self::imageOptionHtml($image, $label ?: "Monstre DofusDB #{$monsterId}", 'DofusDB')
                : ($label ?: "Monstre DofusDB #{$monsterId}");
        }

        $name = basename((string) parse_url($value, PHP_URL_PATH)) ?: $value;

        return self::imageOptionHtml($value, $name, 'Image existante');
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    public static function parseDofusValue(?string $value): array
    {
        if (! str_starts_with((string) $value, 'dofusdb:')) {
            return [null, $value, null];
        }

        [$monsterId, $image, $label] = explode('|', Str::after($value, 'dofusdb:'), 3) + [null, null, null];

        return [$monsterId ?: null, $image ?: null, $label ?: null];
    }

    /**
     * @return array<string, string>
     */
    private static function missionMediaOptions(?string $search = null): array
    {
        $root = public_path('assets/uploads/missions');

        if (! File::isDirectory($root)) {
            return [];
        }

        $search = self::normalizeSearch((string) $search);

        return collect(File::allFiles($root))
            ->filter(fn ($file): bool => in_array(Str::lower($file->getExtension()), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'], true))
            ->when($search !== '', fn ($files) => $files->filter(function ($file) use ($search, $root): bool {
                $relative = 'assets/uploads/missions/'.Str::after($file->getPathname(), $root.DIRECTORY_SEPARATOR);
                $haystack = self::normalizeSearch($file->getFilename().' '.$relative);

                return str_contains($haystack, $search);
            }))
            ->mapWithKeys(function ($file) use ($root): array {
                $relative = 'assets/uploads/missions/'.Str::after($file->getPathname(), $root.DIRECTORY_SEPARATOR);
                $path = str_replace('\\', '/', $relative);
                $size = number_format($file->getSize() / 1024, 1, ',', ' ');

                $url = asset($path);

                return [$url => self::mediaPickerOptionHtml($url, $file->getFilename(), "{$size} Ko")];
            })
            ->all();
    }

    private static function singleMissionMediaValue(?string $search = null): ?string
    {
        $options = self::missionMediaOptions($search);

        return count($options) === 1 ? array_key_first($options) : null;
    }

    private static function imageOptionHtml(string $image, string $title, string $meta): string
    {
        return '<div style="display:flex;align-items:center;gap:12px;min-height:52px;">'
            .'<img src="'.e($image).'" alt="" style="width:48px;height:48px;object-fit:contain;border-radius:6px;background:#f8fafc;border:1px solid #e5e7eb;">'
            .'<div style="display:flex;align-items:center;justify-content:space-between;gap:16px;width:100%;">'
            .'<strong style="font-size:14px;color:#111827;">'.e($title).'</strong>'
            .'<span style="font-size:12px;color:#64748b;white-space:nowrap;">'.e($meta).'</span>'
            .'</div>'
            .'</div>';
    }

    private static function mediaPickerOptionHtml(string $image, string $title, string $meta): string
    {
        return '<div style="display:grid;grid-template-columns:92px minmax(220px,1fr) 90px 80px;align-items:center;gap:22px;width:100%;min-height:82px;">'
            .'<img src="'.e($image).'" alt="" style="width:78px;height:58px;object-fit:contain;border-radius:6px;background:#f8fafc;border:1px solid #e5e7eb;padding:4px;">'
            .'<strong style="font-size:15px;color:#111827;">'.e($title).'</strong>'
            .'<span style="font-size:13px;color:#64748b;white-space:nowrap;">'.e($meta).'</span>'
            .'<span style="font-size:13px;color:#475569;white-space:nowrap;">Choisir</span>'
            .'</div>';
    }

    private static function normalizeSearch(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->trim()
            ->toString();
    }
}
