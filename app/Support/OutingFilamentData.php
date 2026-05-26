<?php

namespace App\Support;

use App\Models\Outing;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class OutingFilamentData
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data, ?Outing $outing = null): array
    {
        $schedule = self::schedule($data['schedule'] ?? []);
        $closeAt = self::parseCloseAt($data['close_at'] ?? null);

        self::validateSchedule($schedule);
        self::validateCloseAt($closeAt, $schedule);

        $payload = [
            'title' => trim((string) ($data['title'] ?? '')),
            'description' => filled($data['description'] ?? null) ? (string) $data['description'] : null,
            'places' => (int) ($data['places'] ?? 8),
            'close_at' => $closeAt,
            'schedule' => $schedule,
            'is_published' => (bool) ($data['is_published'] ?? true),
        ];

        if ($outing?->confirmed_slot_id && ! self::hasSlot($schedule, $outing, $outing->confirmed_slot_id)) {
            $payload['confirmed_slot_id'] = null;
            $payload['confirmed_at'] = null;
        }

        return $payload;
    }

    /**
     * @param mixed $value
     * @return list<array{date: string, times: list<string>}>
     */
    private static function schedule(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true) ?: [];
        }

        return collect((array) $value)
            ->map(fn (array $day): array => [
                'date' => (string) ($day['date'] ?? ''),
                'times' => collect($day['times'] ?? [])
                    ->map(fn (mixed $time): string => trim((string) $time))
                    ->filter(fn (string $time): bool => preg_match('/^\d{2}:\d{2}$/', $time) === 1)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $day): bool => $day['date'] !== '' && count($day['times']) > 0)
            ->sortBy('date')
            ->values()
            ->all();
    }

    /**
     * @param list<array{date: string, times: list<string>}> $schedule
     */
    private static function validateSchedule(array $schedule): void
    {
        validator(['schedule' => $schedule], [
            'schedule' => ['required', 'array', 'min:1'],
            'schedule.*.date' => ['required', 'date'],
            'schedule.*.times' => ['required', 'array', 'min:1'],
            'schedule.*.times.*' => ['required', 'regex:/^\d{2}:\d{2}$/'],
        ])->validate();
    }

    /**
     * @param list<array{date: string, times: list<string>}> $schedule
     */
    private static function validateCloseAt(?string $closeAt, array $schedule): void
    {
        $firstSlot = collect($schedule)
            ->flatMap(fn (array $day): array => collect($day['times'] ?? [])
                ->map(fn (string $time): string => $day['date'].' '.$time)
                ->all())
            ->sort()
            ->first();
        $latestCloseAt = $firstSlot ? Carbon::parse($firstSlot)->subHours(2)->format('Y-m-d H:i:s') : null;

        validator([
            'close_at' => $closeAt,
            'latest_close_at' => $latestCloseAt,
        ], [
            'close_at' => ['nullable', 'date', 'before_or_equal:latest_close_at'],
        ], [
            'close_at.before_or_equal' => 'La cloture des votes doit etre au plus tard 2 heures avant le premier creneau de la sortie.',
        ])->validate();
    }

    private static function parseCloseAt(mixed $value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d H:i:s');
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y H:i', 'Y-m-d\TH:i', 'Y-m-d H:i', 'Y-m-d H:i:s'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'data.close_at' => 'La cloture des votes doit respecter le format jj/mm/aaaa hh:mm.',
            ]);
        }
    }

    /**
     * @param list<array{date: string, times: list<string>}> $schedule
     */
    private static function hasSlot(array $schedule, Outing $outing, string $slotId): bool
    {
        foreach ($schedule as $day) {
            foreach ($day['times'] ?? [] as $time) {
                if ($outing->slotId((string) $day['date'], (string) $time) === $slotId) {
                    return true;
                }
            }
        }

        return false;
    }
}
