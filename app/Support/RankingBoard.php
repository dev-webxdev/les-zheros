<?php

namespace App\Support;

use App\Models\MissionValidation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RankingBoard
{
    public function __construct(private readonly MissionCycle $missionCycle)
    {
    }

    /**
     * @return Collection<int, array{
     *     rank: int,
     *     name: string,
     *     initials: string,
     *     missions: int,
     *     helps: int,
     *     week: float,
     *     month: float,
     *     total: float
     * }>
     */
    public function rows(): Collection
    {
        $now = now();
        $weekStart = $this->missionCycle->current()['start'];
        $monthStart = $now->copy()->startOfMonth();

        return MissionValidation::query()
            ->with('user:id,name,avatar_path')
            ->where('status', MissionValidation::VALIDATED)
            ->latest()
            ->get()
            ->groupBy('user_id')
            ->map(function (Collection $validations): array {
                /** @var MissionValidation $first */
                $first = $validations->first();
                $name = $first?->user?->name ?? 'Joueur supprimé';

                return [
                    'name' => $name,
                    'initials' => $this->initials($name),
                    'avatar' => $first?->user?->avatarUrl(),
                    'missions' => $validations->count(),
                    'helps' => $validations->filter(fn (MissionValidation $validation): bool => filled($validation->teammates))->count(),
                    'validations' => $validations,
                ];
            })
            ->map(function (array $row) use ($weekStart, $monthStart): array {
                /** @var Collection<int, MissionValidation> $validations */
                $validations = $row['validations'];

                unset($row['validations']);

                return [
                    ...$row,
                    'week' => $this->pointsSince($validations, $weekStart),
                    'month' => $this->pointsSince($validations, $monthStart),
                    'total' => round($validations->sum(fn (MissionValidation $validation): float => $validation->points()), 2),
                ];
            })
            ->filter(fn (array $row): bool => $row['total'] > 0)
            ->sortByDesc('month')
            ->values()
            ->map(fn (array $row, int $index): array => [
                'rank' => $index + 1,
                ...$row,
            ]);
    }

    /**
     * @param Collection<int, MissionValidation> $validations
     */
    private function pointsSince(Collection $validations, mixed $start): float
    {
        return round($validations
            ->filter(fn (MissionValidation $validation): bool => $validation->created_at?->greaterThanOrEqualTo($start) ?? false)
            ->sum(fn (MissionValidation $validation): float => $validation->points()), 2);
    }

    private function initials(string $name): string
    {
        return Str::of($name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->join('') ?: 'J';
    }
}
