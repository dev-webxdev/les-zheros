<?php

namespace App\Filament\Pages;

use App\Models\GuildSetting;
use App\Models\LotteryDraw;
use App\Support\AdminActivity;
use App\Support\LotteryData;
use App\Support\MissionCycle;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class Lottery extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'Activites';

    protected static ?string $navigationLabel = 'Loterie';

    protected static ?string $title = 'Loterie';

    protected static ?string $slug = 'loterie';

    protected static ?int $navigationSort = 25;

    protected string $view = 'filament.pages.lottery';

    public ?string $selectedWeek = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessAdminArea('lottery') ?? false;
    }

    public function mount(MissionCycle $missionCycle): void
    {
        $missionCycle->sync();
        $this->selectedWeek = $this->weeks()->first()['value'] ?? null;
    }

    /**
     * @return Collection<int, array{start: mixed, end: mixed, value: string, label: string, participants: array}>
     */
    public function weeks(): Collection
    {
        return app(LotteryData::class)->weeks(app(MissionCycle::class));
    }

    /**
     * @return array{start?: mixed, end?: mixed, value?: string, label?: string, participants?: array}
     */
    public function selectedCycle(): array
    {
        $weeks = $this->weeks();

        return $weeks->firstWhere('value', $this->selectedWeek)
            ?? $weeks->first()
            ?? [];
    }

    /**
     * @return array<int, array{name: string, initials: string, avatar: string|null, points: float, missions: int, helps: int, tickets: int}>
     */
    public function participants(): array
    {
        return app(LotteryData::class)->eligibleParticipants($this->selectedCycle()['participants'] ?? []);
    }

    /**
     * @return array{prizes: list<int>, multiplier: float, min_points: float}
     */
    public function lotterySettings(): array
    {
        return GuildSetting::lotterySettings();
    }

    public function totalTickets(): int
    {
        return collect($this->participants())->sum('tickets');
    }

    public function currentWeekDraw(): ?LotteryDraw
    {
        $cycleValue = $this->selectedCycle()['value'] ?? null;

        if (! $cycleValue) {
            return null;
        }

        return LotteryDraw::query()
            ->where('cycle_value', $cycleValue)
            ->latest('drawn_at')
            ->first();
    }

    public function latestDraw(): ?LotteryDraw
    {
        return $this->currentWeekDraw()
            ?? LotteryDraw::query()->latest('drawn_at')->first();
    }

    /**
     * @return Collection<int, LotteryDraw>
     */
    public function drawHistory(): Collection
    {
        return LotteryDraw::query()
            ->with('drawer')
            ->latest('drawn_at')
            ->latest('id')
            ->get();
    }

    public function refreshLottery(): void
    {
        Notification::make()
            ->title('Loterie actualisee')
            ->body('Les participants et tickets ont ete recalcules.')
            ->success()
            ->send();
    }

    public function drawLottery(): void
    {
        if ($this->currentWeekDraw()) {
            Notification::make()
                ->title('Loterie deja lancee')
                ->body('Un tirage existe deja pour ce cycle. Supprime-le de l historique si tu dois le refaire.')
                ->warning()
                ->send();

            return;
        }

        $participants = $this->participants();

        if (count($participants) < 3) {
            Notification::make()
                ->title('Tirage impossible')
                ->body('Il faut au moins trois gagnants possibles avec le bareme actuel.')
                ->warning()
                ->send();

            return;
        }

        $winners = $this->drawWinners($participants);

        if (count($winners) < 3) {
            Notification::make()
                ->title('Tirage impossible')
                ->body('Le tirage n a pas pu determiner trois gagnants.')
                ->warning()
                ->send();

            return;
        }

        $participantsSnapshot = $participants;
        $settingsSnapshot = $this->lotterySettings();
        $cycle = $this->selectedCycle();
        $draw = LotteryDraw::create([
            'cycle_value' => (string) ($cycle['value'] ?? ''),
            'cycle_label' => (string) ($cycle['label'] ?? 'Cycle en cours'),
            'drawn_at' => now(),
            'drawn_by' => auth()->id(),
            'drawn_by_name' => auth()->user()?->name ?? 'Admin',
            'settings' => $settingsSnapshot,
            'participants' => $participantsSnapshot,
            'winners' => $winners,
            'total_tickets' => collect($participantsSnapshot)->sum('tickets'),
            'total_points' => round(collect($participantsSnapshot)->sum('points'), 2),
            'total_prize' => collect($winners)->sum('prize'),
        ]);

        AdminActivity::log(
            'lottery',
            'drawn',
            'Loterie lancee',
            'Tirage effectue pour '.$draw->cycle_label.'.',
            $draw,
            [
                'winners' => collect($winners)->pluck('name')->all(),
                'total' => $draw->total_prize,
                'week' => $draw->cycle_label,
            ],
        );

        Notification::make()
            ->title('Loterie lancee')
            ->body('Les gagnants ont ete sauvegardes dans l historique.')
            ->success()
            ->send();

        $this->dispatch('lottery-drawn', draw: [
            'id' => $draw->id,
            'date' => $draw->drawn_at?->translatedFormat('d/m/Y H:i'),
            'week' => $draw->cycle_label,
            'participants' => $participantsSnapshot,
            'winners' => $winners,
            'total' => $draw->total_prize,
        ]);
    }

    public function deleteDraw(int $drawId): void
    {
        if (! (auth()->user()?->canAccessAdminPermission('lottery.manage') ?? false)) {
            Notification::make()
                ->title('Action refusee')
                ->body('Tu n as pas la permission de gerer la loterie.')
                ->danger()
                ->send();

            return;
        }

        $draw = LotteryDraw::query()->find($drawId);

        if (! $draw) {
            return;
        }

        AdminActivity::log(
            'lottery',
            'draw_deleted',
            'Tirage loterie supprime',
            'Tirage retire de l historique: '.$draw->cycle_label.'.',
            $draw,
        );

        $draw->delete();

        Notification::make()
            ->title('Tirage retire')
            ->body('Le tirage a ete retire de l historique.')
            ->success()
            ->send();
    }

    public function formatPoints(float|int $points): string
    {
        return rtrim(rtrim(str_replace('.', ',', number_format((float) $points, 2, '.', '')), '0'), ',');
    }

    public function formatKamas(float|int $kamas): string
    {
        return number_format((int) $kamas, 0, '', ' ').' kamas';
    }

    /**
     * @param array<int, array{name: string, initials: string, avatar: string|null, points: float, missions: int, helps: int, tickets: int}> $participants
     * @return array<int, array{name: string, prize: int, tickets: int, points: float, missions: int, helps: int}>
     */
    private function drawWinners(array $participants): array
    {
        $pool = array_values($participants);
        $winners = [];

        foreach ($this->lotterySettings()['prizes'] as $prize) {
            if ($prize <= 0 || $pool === []) {
                continue;
            }

            $winnerIndex = $this->drawWeightedWinnerIndex($pool);

            if ($winnerIndex === null) {
                continue;
            }

            $winner = $pool[$winnerIndex];
            $winners[] = [
                'name' => $winner['name'],
                'prize' => (int) $prize,
                'tickets' => $winner['tickets'],
                'points' => (float) $winner['points'],
                'missions' => $winner['missions'],
                'helps' => $winner['helps'],
            ];

            array_splice($pool, $winnerIndex, 1);
        }

        return $winners;
    }

    /**
     * @param array<int, array{tickets: int}> $pool
     */
    private function drawWeightedWinnerIndex(array $pool): ?int
    {
        $ticketTotal = collect($pool)->sum('tickets');

        if ($ticketTotal <= 0) {
            return null;
        }

        $cursor = random_int(1, (int) $ticketTotal);

        foreach ($pool as $index => $participant) {
            $cursor -= $participant['tickets'];

            if ($cursor <= 0) {
                return $index;
            }
        }

        return array_key_last($pool);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshLottery')
                ->label('Actualiser')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action('refreshLottery'),

            Action::make('drawLottery')
                ->label('Lancer la loterie')
                ->icon('heroicon-o-gift')
                ->requiresConfirmation()
                ->modalHeading('Lancer la loterie ?')
                ->modalDescription('Le tirage utilise les participants eligibles du cycle selectionne. Cette action est sensible.')
                ->modalSubmitActionLabel('Lancer le tirage')
                ->action('drawLottery'),
        ];
    }
}
