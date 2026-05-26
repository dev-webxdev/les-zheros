<?php

namespace App\Filament\Pages;

use App\Models\GuildSetting;
use App\Support\AdminActivity;
use App\Support\MissionCycle;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Parametres';

    protected static ?string $title = 'Parametres';

    protected static ?string $slug = 'parametres';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.settings';

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessAdminArea('settings') ?? false;
    }

    public function mount(MissionCycle $missionCycle): void
    {
        $missionCycle->sync();

        $this->form->fill($this->formData());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'default' => 1,
                    'xl' => 2,
                ])
                    ->schema(array_values(array_filter([
                        $this->canManageSetting('cycle') ? $this->cycleSection() : null,
                        $this->canManageSetting('points') ? $this->pointsSection() : null,
                        $this->canManageSetting('lottery') ? $this->lotterySection() : null,
                        $this->canManageSetting('maintenance') ? $this->maintenanceSection() : null,
                    ]))),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $updates = [];

        if ($this->canManageSetting('cycle')) {
            $updates[GuildSetting::MISSION_CYCLE_END] = $this->formatCycleEnd($data[GuildSetting::MISSION_CYCLE_END] ?? null);
        }

        if ($this->canManageSetting('points')) {
            $updates[GuildSetting::MISSION_POINTS_BASE] = $this->parseNumber($data[GuildSetting::MISSION_POINTS_BASE] ?? 0);
            $updates[GuildSetting::MISSION_BONUS_PER_EXTRA_CHARACTER] = $this->parseNumber($data[GuildSetting::MISSION_BONUS_PER_EXTRA_CHARACTER] ?? 0);
            $updates[GuildSetting::GUILD_HELP_POINTS] = $this->parseNumber($data[GuildSetting::GUILD_HELP_POINTS] ?? 0);
        }

        if ($this->canManageSetting('lottery')) {
            $updates[GuildSetting::LOTTERY_PRIZE_1] = (int) $this->parseNumber($data[GuildSetting::LOTTERY_PRIZE_1] ?? 0);
            $updates[GuildSetting::LOTTERY_PRIZE_2] = (int) $this->parseNumber($data[GuildSetting::LOTTERY_PRIZE_2] ?? 0);
            $updates[GuildSetting::LOTTERY_PRIZE_3] = (int) $this->parseNumber($data[GuildSetting::LOTTERY_PRIZE_3] ?? 0);
            $updates[GuildSetting::LOTTERY_MIN_POINTS] = $this->parseNumber($data[GuildSetting::LOTTERY_MIN_POINTS] ?? 0);
        }

        if ($this->canManageSetting('maintenance')) {
            $updates[GuildSetting::MAINTENANCE_ENABLED] = ! empty($data[GuildSetting::MAINTENANCE_ENABLED]) ? 1 : 0;
            $updates[GuildSetting::MAINTENANCE_MESSAGE] = filled($data[GuildSetting::MAINTENANCE_MESSAGE] ?? null)
                ? (string) $data[GuildSetting::MAINTENANCE_MESSAGE]
                : GuildSetting::DEFAULTS[GuildSetting::MAINTENANCE_MESSAGE];
        }

        GuildSetting::setMany($updates);
        $this->logUpdatedSections();

        Notification::make()
            ->title('Parametres enregistres')
            ->body('Les reglages autorises ont bien ete mis a jour.')
            ->success()
            ->send();
    }

    private function cycleSection(): Section
    {
        return Section::make('Date de fin des missions')
            ->description('Definis la fin du cycle actif. Une fois depassee, la date sera prolongee de 7 jours.')
            ->icon('heroicon-o-clock')
            ->schema([
                DateTimePicker::make(GuildSetting::MISSION_CYCLE_END)
                    ->label('Date de fin')
                    ->required()
                    ->seconds(false),
            ]);
    }

    private function pointsSection(): Section
    {
        return Section::make('Bareme de points')
            ->description('Regle les points qui servent ensuite a calculer les tickets de loterie.')
            ->icon('heroicon-o-adjustments-vertical')
            ->schema([
                TextInput::make(GuildSetting::MISSION_POINTS_BASE)
                    ->label('Mission terminee')
                    ->rules(['required', 'regex:/^\d+(?:[,.]\d+)?$/'])
                    ->validationMessages([
                        'regex' => 'Indique un nombre de points valide.',
                    ])
                    ->required(),

                TextInput::make(GuildSetting::MISSION_BONUS_PER_EXTRA_CHARACTER)
                    ->label('Bonus par perso')
                    ->rules(['required', 'regex:/^\d+(?:[,.]\d+)?$/'])
                    ->validationMessages([
                        'regex' => 'Indique un nombre de points valide.',
                    ])
                    ->required(),

                TextInput::make(GuildSetting::GUILD_HELP_POINTS)
                    ->label('Aide guilde')
                    ->rules(['required', 'regex:/^\d+(?:[,.]\d+)?$/'])
                    ->validationMessages([
                        'regex' => 'Indique un nombre de points valide.',
                    ])
                    ->required(),
            ])
            ->columns(3);
    }

    private function lotterySection(): Section
    {
        return Section::make('Loterie')
            ->description('Configure les gains et le seuil de points minimum pour participer au tirage.')
            ->icon('heroicon-o-gift')
            ->schema([
                TextInput::make(GuildSetting::LOTTERY_PRIZE_1)
                    ->label('Gain 1er')
                    ->rules(['required', 'regex:/^\d+(?:\s\d{3})*$/'])
                    ->validationMessages([
                        'regex' => 'Indique un montant comme 250 000.',
                    ])
                    ->required(),

                TextInput::make(GuildSetting::LOTTERY_PRIZE_2)
                    ->label('Gain 2e')
                    ->rules(['required', 'regex:/^\d+(?:\s\d{3})*$/'])
                    ->validationMessages([
                        'regex' => 'Indique un montant comme 150 000.',
                    ])
                    ->required(),

                TextInput::make(GuildSetting::LOTTERY_PRIZE_3)
                    ->label('Gain 3e')
                    ->rules(['required', 'regex:/^\d+(?:\s\d{3})*$/'])
                    ->validationMessages([
                        'regex' => 'Indique un montant comme 100 000.',
                    ])
                    ->required(),

                TextInput::make(GuildSetting::LOTTERY_MIN_POINTS)
                    ->label('Points minimum pour participer')
                    ->helperText('Exemple : 1 point donne acces a la loterie.')
                    ->rules(['required', 'regex:/^\d+(?:[,.]\d+)?$/'])
                    ->validationMessages([
                        'regex' => 'Indique un nombre de points valide.',
                    ])
                    ->required(),
            ])
            ->columns(3);
    }

    private function maintenanceSection(): Section
    {
        return Section::make('Maintenance')
            ->description('Active temporairement une page de maintenance sur le site public.')
            ->icon('heroicon-o-wrench')
            ->schema([
                Toggle::make(GuildSetting::MAINTENANCE_ENABLED)
                    ->label('Activer la maintenance')
                    ->inline(false),

                TextInput::make(GuildSetting::MAINTENANCE_MESSAGE)
                    ->label('Message')
                    ->maxLength(255),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $values = GuildSetting::values();

        foreach ([GuildSetting::LOTTERY_PRIZE_1, GuildSetting::LOTTERY_PRIZE_2, GuildSetting::LOTTERY_PRIZE_3] as $key) {
            $values[$key] = number_format((int) $values[$key], 0, '', ' ');
        }

        foreach ([GuildSetting::MISSION_POINTS_BASE, GuildSetting::MISSION_BONUS_PER_EXTRA_CHARACTER, GuildSetting::GUILD_HELP_POINTS, GuildSetting::LOTTERY_MIN_POINTS] as $key) {
            $values[$key] = rtrim(rtrim(str_replace('.', ',', number_format((float) $values[$key], 2, '.', '')), '0'), ',');
        }

        return $values;
    }

    private function canManageSetting(string $setting): bool
    {
        return auth()->user()?->canAccessAdminPermission('settings.'.$setting) ?? false;
    }

    private function formatCycleEnd(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->format('Y-m-d\TH:i');
        }

        return (string) $value;
    }

    private function parseNumber(mixed $value): float
    {
        return (float) str_replace(',', '.', str_replace(' ', '', (string) $value));
    }

    private function logUpdatedSections(): void
    {
        if ($this->canManageSetting('cycle')) {
            AdminActivity::log('settings', 'cycle_updated', 'Cycle missions modifie', 'Date de fin du cycle mise a jour.');
        }

        if ($this->canManageSetting('points')) {
            AdminActivity::log('settings', 'points_updated', 'Bareme de points modifie', 'Regles de points mises a jour.');
        }

        if ($this->canManageSetting('lottery')) {
            AdminActivity::log('settings', 'lottery_updated', 'Parametres loterie modifies', 'Gains et seuil de loterie mis a jour.');
        }

        if ($this->canManageSetting('maintenance')) {
            AdminActivity::log(
                'settings',
                'maintenance_updated',
                'Maintenance modifiee',
                ! empty($this->data[GuildSetting::MAINTENANCE_ENABLED]) ? 'Maintenance activee.' : 'Maintenance desactivee.',
            );
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Enregistrer')
                ->icon('heroicon-o-bookmark-square')
                ->submit('save'),
        ];
    }
}
