<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuildSetting;
use App\Models\AdminNotification;
use App\Models\MissionValidation;
use App\Support\AdminNotifier;
use App\Support\MissionCycle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class LotteryController extends Controller
{
    public function index(MissionCycle $missionCycle): View
    {
        $cycles = $this->recentCycles($missionCycle)->map(function (array $cycle): array {
            return [
                'value' => $cycle['value'],
                'label' => $cycle['label'],
                'participants' => $this->participantsForWeek($cycle['start'], $cycle['end']),
                'pending_validations' => $this->pendingValidationsForWeek($cycle['start'], $cycle['end']),
            ];
        })->filter(fn (array $cycle, int $index): bool => $index === 0 || filled($cycle['participants']) || $cycle['pending_validations'] > 0)
            ->values();
        $this->notifyPendingValidationsBeforeDraw($cycles);

        return view('admin.admin-lottery', [
            'lotteryWeeks' => $cycles,
            'selectedLotteryWeek' => $cycles->first(),
            'lotteryParticipantsByWeek' => $cycles
                ->mapWithKeys(fn (array $cycle): array => [$cycle['value'] => $cycle['participants']])
                ->all(),
            'lotteryPendingValidationsByWeek' => $cycles
                ->mapWithKeys(fn (array $cycle): array => [$cycle['value'] => $cycle['pending_validations']])
                ->all(),
            'lotterySettings' => GuildSetting::lotterySettings(),
        ]);
    }

    /**
     * @return Collection<int, array{start: CarbonImmutable, end: CarbonImmutable, value: string, label: string}>
     */
    private function recentCycles(MissionCycle $missionCycle): Collection
    {
        $current = $missionCycle->current();

        return collect(range(0, 11))->map(function (int $weekOffset) use ($current): array {
            $start = $current['start']->subWeeks($weekOffset);
            $end = $current['end']->subWeeks($weekOffset);

            return [
                'start' => $start,
                'end' => $end,
                'value' => $start->format('Y-m-d_H-i'),
                'label' => 'Cycle du '.$start->format('d/m/Y H:i').' au '.$end->format('d/m/Y H:i'),
            ];
        });
    }

    /**
     * @return array<int, array{name: string, initials: string, avatar: string|null, points: float, missions: int, helps: int}>
     */
    private function participantsForWeek(mixed $start, mixed $end): array
    {
        return MissionValidation::query()
            ->with('user')
            ->where('status', MissionValidation::VALIDATED)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->get()
            ->groupBy('user_id')
            ->map(function (Collection $validations): array {
                /** @var MissionValidation $firstValidation */
                $firstValidation = $validations->first();

                return [
                    'name' => $firstValidation->user?->name ?? 'Utilisateur supprimé',
                    'initials' => $firstValidation->user?->initials() ?? mb_strtoupper(mb_substr($firstValidation->user?->name ?? 'US', 0, 2)),
                    'avatar' => $firstValidation->user?->avatarUrl(),
                    'points' => round($validations->sum(fn (MissionValidation $validation): float => $validation->points()), 2),
                    'missions' => $validations->count(),
                    'helps' => $validations->filter(fn (MissionValidation $validation): bool => filled($validation->teammates))->count(),
                ];
            })
            ->filter(fn (array $participant): bool => $participant['points'] > 0)
            ->sortByDesc('points')
            ->values()
            ->all();
    }

    private function pendingValidationsForWeek(mixed $start, mixed $end): int
    {
        return MissionValidation::query()
            ->where('status', MissionValidation::PENDING)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->count();
    }

    private function notifyPendingValidationsBeforeDraw(Collection $cycles): void
    {
        $pendingCount = (int) $cycles->sum('pending_validations');

        if ($pendingCount <= 0) {
            AdminNotification::query()
                ->where('area', 'lottery')
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return;
        }

        if (AdminNotification::query()
            ->where('area', 'lottery')
            ->whereNull('read_at')
            ->exists()) {
            return;
        }

        AdminNotifier::notify(
            'lottery',
            'Mission non validee avant loterie',
            $pendingCount.' declaration(s) de mission attendent encore une validation avant le tirage.',
            route('admin.validations.index', ['status' => MissionValidation::PENDING]),
            'warning',
        );
    }
}
