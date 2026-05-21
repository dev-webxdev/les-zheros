<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'mission_id',
    'user_id',
    'characters',
    'teammates',
    'proof_text',
    'proof_path',
    'status',
    'reviewed_at',
    'reviewed_by',
])]
class MissionValidation extends Model
{
    use SoftDeletes;

    public const PENDING = 'pending';
    public const VALIDATED = 'validated';
    public const REFUSED = 'refused';

    public const STATUSES = [
        self::PENDING => 'En attente',
        self::VALIDATED => 'Validée',
        self::REFUSED => 'Refusée',
    ];

    protected function casts(): array
    {
        return [
            'characters' => 'integer',
            'teammates' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? self::STATUSES[self::PENDING];
    }

    public function statusTagClass(): string
    {
        return match ($this->status) {
            self::VALIDATED => 'admin-tag--success',
            self::REFUSED => 'admin-tag--danger',
            default => 'admin-tag--primary',
        };
    }

    public function frontPillClass(): string
    {
        return match ($this->status) {
            self::VALIDATED => 'pill--success',
            self::REFUSED => 'pill--error',
            default => 'pill--waiting',
        };
    }

    public function points(): float
    {
        if ($this->status !== self::VALIDATED) {
            return 0.0;
        }

        return $this->computedPoints(false);
    }

    public function estimatedPoints(): float
    {
        return $this->computedPoints(true);
    }

    public function isRepeatEstimate(): bool
    {
        return $this->hasPreviousCredit(true);
    }

    private function computedPoints(bool $includePending): float
    {
        $alreadyValidated = $this->hasPreviousCredit($includePending);
        $settings = GuildSetting::missionPoints();
        $baseValue = $settings['base'];
        $characterValue = $settings['bonus_per_extra_character'];
        $helpValue = $settings['help'];

        $characterBonus = $alreadyValidated
            ? max(1, $this->characters) * $characterValue
            : max(0, $this->characters - 1) * $characterValue;
        $helpBonus = filled($this->teammates) ? $helpValue : 0.0;
        $basePoints = $alreadyValidated ? 0.0 : $baseValue;

        return $basePoints + $characterBonus + $helpBonus;
    }

    private function hasPreviousCredit(bool $includePending): bool
    {
        $statuses = $includePending
            ? [self::VALIDATED, self::PENDING]
            : [self::VALIDATED];

        return self::query()
            ->where('user_id', $this->user_id)
            ->where('mission_id', $this->mission_id)
            ->whereIn('status', $statuses)
            ->where('id', '<', $this->id)
            ->exists();
    }

    /**
     * @return array<int, array{name: string, user_id?: int, characters: int, points: float}>
     */
    public function teammatePointRows(): array
    {
        if ($this->status !== self::VALIDATED) {
            return [];
        }

        return collect($this->teammates ?? [])
            ->map(function (array $teammate): array {
                $characters = max(1, (int) ($teammate['characters'] ?? 1));
                $settings = GuildSetting::missionPoints();
                $baseValue = $settings['base'];
                $characterValue = $settings['bonus_per_extra_character'];
                $helpValue = $settings['help'];
                $alreadyValidated = isset($teammate['user_id'])
                    && self::query()
                        ->where('user_id', $teammate['user_id'])
                        ->where('mission_id', $this->mission_id)
                        ->where('status', self::VALIDATED)
                        ->where('id', '<', $this->id)
                        ->exists();

                return [
                    'name' => (string) ($teammate['name'] ?? 'Coéquipier'),
                    'user_id' => isset($teammate['user_id']) ? (int) $teammate['user_id'] : null,
                    'characters' => $characters,
                    'points' => ($alreadyValidated ? 0.0 : $baseValue)
                        + ($alreadyValidated ? $characters * $characterValue : max(0, $characters - 1) * $characterValue)
                        + $helpValue,
                ];
            })
            ->values()
            ->all();
    }
}
