<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'title',
    'description',
    'places',
    'close_at',
    'schedule',
    'confirmed_slot_id',
    'confirmed_at',
    'is_published',
])]
class Outing extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'places' => 'integer',
            'close_at' => 'datetime',
            'schedule' => 'array',
            'confirmed_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    public function votes(): HasMany
    {
        return $this->hasMany(OutingVote::class);
    }

    public function slotCount(): int
    {
        return collect($this->schedule ?? [])->sum(fn (array $day): int => count($day['times'] ?? []));
    }

    public function voteFor(?User $user): ?OutingVote
    {
        if (! $user) {
            return null;
        }

        return $this->votes->firstWhere('user_id', $user->id);
    }

    public function slotId(string $date, string $time): string
    {
        return Str::slug($date.' '.$time);
    }

    public function slotLabel(string $slotId): string
    {
        foreach ($this->schedule ?? [] as $day) {
            foreach ($day['times'] ?? [] as $time) {
                if ($this->slotId((string) $day['date'], (string) $time) === $slotId) {
                    return ((string) $day['date']).' '.$time;
                }
            }
        }

        return $slotId;
    }

    /**
     * @return array{date: string, time: string, label: string}|null
     */
    public function slotDetails(?string $slotId): ?array
    {
        if (! $slotId) {
            return null;
        }

        foreach ($this->schedule ?? [] as $day) {
            foreach ($day['times'] ?? [] as $time) {
                if ($this->slotId((string) $day['date'], (string) $time) === $slotId) {
                    return [
                        'date' => (string) $day['date'],
                        'time' => (string) $time,
                        'label' => ((string) $day['date']).' '.$time,
                    ];
                }
            }
        }

        return null;
    }

    public function confirmedSlotDetails(): ?array
    {
        return $this->slotDetails($this->confirmed_slot_id);
    }

    public function confirmedSlotDateTime(): ?string
    {
        $slot = $this->confirmedSlotDetails();

        return $slot ? $slot['date'].' '.$slot['time'] : null;
    }

    public function confirmedVotes()
    {
        if (! $this->confirmed_slot_id) {
            return collect();
        }

        $votes = $this->relationLoaded('votes') ? $this->votes : $this->votes()->with('user')->get();

        return $votes->where('slot_id', $this->confirmed_slot_id)->values();
    }

    public function bestSlotId(): ?string
    {
        $votes = $this->relationLoaded('votes') ? $this->votes : $this->votes()->get();

        if ($votes->isEmpty()) {
            return null;
        }

        $slot = collect($this->schedule ?? [])
            ->flatMap(fn (array $day): array => collect($day['times'] ?? [])
                ->map(fn (string $time): array => [
                    'id' => $this->slotId((string) $day['date'], $time),
                    'starts_at' => ((string) $day['date']).' '.$time,
                    'votes' => $votes->where('slot_id', $this->slotId((string) $day['date'], $time))->count(),
                ])
                ->all())
            ->filter(fn (array $slot): bool => $slot['votes'] > 0)
            ->sortBy([
                ['votes', 'desc'],
                ['starts_at', 'asc'],
            ])
            ->first();

        return $slot['id'] ?? null;
    }

    public function hasSlot(string $slotId): bool
    {
        foreach ($this->schedule ?? [] as $day) {
            foreach ($day['times'] ?? [] as $time) {
                if ($this->slotId((string) $day['date'], (string) $time) === $slotId) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isClosed(): bool
    {
        return $this->close_at !== null && $this->close_at->isPast();
    }
}
