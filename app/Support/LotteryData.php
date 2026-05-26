<?php

namespace App\Support;

use App\Models\GuildSetting;
use App\Models\MissionValidation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class LotteryData
{
    /**
     * @return Collection<int, array{start: CarbonImmutable, end: CarbonImmutable, value: string, label: string, participants: array<int, array{name: string, initials: string, avatar: string|null, points: float, missions: int, helps: int}>}>
     */
    public function weeks(MissionCycle $missionCycle): Collection
    {
        $current = $missionCycle->current();

        return collect(range(0, 11))
            ->map(function (int $weekOffset) use ($current): array {
                $start = $current['start']->subWeeks($weekOffset);
                $end = $current['end']->subWeeks($weekOffset);

                return [
                    'start' => $start,
                    'end' => $end,
                    'value' => $start->format('Y-m-d_H-i'),
                    'label' => 'Cycle du '.$start->format('d/m/Y H:i').' au '.$end->format('d/m/Y H:i'),
                    'participants' => $this->participantsForWeek($start, $end),
                ];
            })
            ->filter(fn (array $cycle, int $index): bool => $index === 0 || filled($cycle['participants']))
            ->values();
    }

    /**
     * @param array<int, array{name: string, initials: string, avatar: string|null, points: float, missions: int, helps: int}> $participants
     * @return array<int, array{name: string, initials: string, avatar: string|null, points: float, missions: int, helps: int, tickets: int}>
     */
    public function eligibleParticipants(array $participants): array
    {
        $settings = GuildSetting::lotterySettings();
        $minPoints = (float) $settings['min_points'];
        $multiplier = (float) $settings['multiplier'];

        return collect($participants)
            ->filter(fn (array $participant): bool => (float) $participant['points'] >= $minPoints)
            ->map(fn (array $participant): array => [
                ...$participant,
                'tickets' => max((int) round((float) $participant['points'] * $multiplier), 1),
            ])
            ->values()
            ->all();
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
                    'name' => $firstValidation->user?->name ?? 'Utilisateur supprime',
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
}
