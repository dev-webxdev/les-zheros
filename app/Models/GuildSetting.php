<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'key',
    'value',
])]
class GuildSetting extends Model
{
    public const MISSION_CYCLE_END = 'mission_cycle_end';
    public const MISSION_POINTS_BASE = 'mission_points_base';
    public const MISSION_BONUS_PER_EXTRA_CHARACTER = 'mission_bonus_per_extra_character';
    public const GUILD_HELP_POINTS = 'guild_help_points';
    public const LOTTERY_PRIZE_1 = 'lottery_prize_1';
    public const LOTTERY_PRIZE_2 = 'lottery_prize_2';
    public const LOTTERY_PRIZE_3 = 'lottery_prize_3';
    public const LOTTERY_TICKET_MULTIPLIER = 'lottery_ticket_multiplier';
    public const LOTTERY_MIN_POINTS = 'lottery_min_points';
    public const WORD_MYSTERY_REWARDS = 'word_mystery_rewards';
    public const MAINTENANCE_ENABLED = 'maintenance_enabled';
    public const MAINTENANCE_MESSAGE = 'maintenance_message';

    public const DEFAULTS = [
        self::MISSION_CYCLE_END => '2026-05-19T08:00',
        self::MISSION_POINTS_BASE => 1.0,
        self::MISSION_BONUS_PER_EXTRA_CHARACTER => 0.25,
        self::GUILD_HELP_POINTS => 0.5,
        self::LOTTERY_PRIZE_1 => 250000,
        self::LOTTERY_PRIZE_2 => 150000,
        self::LOTTERY_PRIZE_3 => 100000,
        self::LOTTERY_TICKET_MULTIPLIER => 1,
        self::LOTTERY_MIN_POINTS => 1,
        self::WORD_MYSTERY_REWARDS => '{"easy":{"base":10000,"bonuses":{"1":20,"2":10,"3":0,"4":-10,"5":-20,"6":-30}},"normal":{"base":25000,"bonuses":{"1":20,"2":12,"3":0,"4":-12,"5":-20,"6":-28}},"hard":{"base":50000,"bonuses":{"1":20,"2":10,"3":0,"4":-10,"5":-20,"6":-30}}}',
        self::MAINTENANCE_ENABLED => 0,
        self::MAINTENANCE_MESSAGE => 'Le site est temporairement en maintenance. La guilde revient tres vite.',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function values(): array
    {
        $stored = self::query()
            ->whereIn('key', array_keys(self::DEFAULTS))
            ->pluck('value', 'key')
            ->all();

        return [
            self::MISSION_CYCLE_END => (string) ($stored[self::MISSION_CYCLE_END] ?? self::DEFAULTS[self::MISSION_CYCLE_END]),
            self::MISSION_POINTS_BASE => (float) ($stored[self::MISSION_POINTS_BASE] ?? self::DEFAULTS[self::MISSION_POINTS_BASE]),
            self::MISSION_BONUS_PER_EXTRA_CHARACTER => (float) ($stored[self::MISSION_BONUS_PER_EXTRA_CHARACTER] ?? self::DEFAULTS[self::MISSION_BONUS_PER_EXTRA_CHARACTER]),
            self::GUILD_HELP_POINTS => (float) ($stored[self::GUILD_HELP_POINTS] ?? self::DEFAULTS[self::GUILD_HELP_POINTS]),
            self::LOTTERY_PRIZE_1 => (int) ($stored[self::LOTTERY_PRIZE_1] ?? self::DEFAULTS[self::LOTTERY_PRIZE_1]),
            self::LOTTERY_PRIZE_2 => (int) ($stored[self::LOTTERY_PRIZE_2] ?? self::DEFAULTS[self::LOTTERY_PRIZE_2]),
            self::LOTTERY_PRIZE_3 => (int) ($stored[self::LOTTERY_PRIZE_3] ?? self::DEFAULTS[self::LOTTERY_PRIZE_3]),
            self::LOTTERY_TICKET_MULTIPLIER => (float) ($stored[self::LOTTERY_TICKET_MULTIPLIER] ?? self::DEFAULTS[self::LOTTERY_TICKET_MULTIPLIER]),
            self::LOTTERY_MIN_POINTS => (float) ($stored[self::LOTTERY_MIN_POINTS] ?? self::DEFAULTS[self::LOTTERY_MIN_POINTS]),
            self::WORD_MYSTERY_REWARDS => self::decodeRewardSteps((string) ($stored[self::WORD_MYSTERY_REWARDS] ?? self::DEFAULTS[self::WORD_MYSTERY_REWARDS])),
            self::MAINTENANCE_ENABLED => (bool) (int) ($stored[self::MAINTENANCE_ENABLED] ?? self::DEFAULTS[self::MAINTENANCE_ENABLED]),
            self::MAINTENANCE_MESSAGE => (string) ($stored[self::MAINTENANCE_MESSAGE] ?? self::DEFAULTS[self::MAINTENANCE_MESSAGE]),
        ];
    }

    public static function maintenanceEnabled(): bool
    {
        return (bool) self::values()[self::MAINTENANCE_ENABLED];
    }

    /**
     * @return array{prizes: list<int>, multiplier: float, min_points: float}
     */
    public static function lotterySettings(): array
    {
        $values = self::values();

        return [
            'prizes' => [
                (int) $values[self::LOTTERY_PRIZE_1],
                (int) $values[self::LOTTERY_PRIZE_2],
                (int) $values[self::LOTTERY_PRIZE_3],
            ],
            'multiplier' => 1.0,
            'min_points' => (float) $values[self::LOTTERY_MIN_POINTS],
        ];
    }

    /**
     * @return array{base: float, bonus_per_extra_character: float, help: float}
     */
    public static function missionPoints(): array
    {
        $values = self::values();

        return [
            'base' => (float) $values[self::MISSION_POINTS_BASE],
            'bonus_per_extra_character' => (float) $values[self::MISSION_BONUS_PER_EXTRA_CHARACTER],
            'help' => (float) $values[self::GUILD_HELP_POINTS],
        ];
    }

    /**
     * @return array<string, array{base: int, bonuses: array<int, int>}>
     */
    public static function wordMysteryRewards(): array
    {
        return self::values()[self::WORD_MYSTERY_REWARDS];
    }

    /**
     * @param array<string, mixed> $values
     */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }

            self::query()->updateOrCreate(
                ['key' => $key],
                ['value' => is_array($value) ? json_encode($value) : (string) $value],
            );
        }
    }

    /**
     * @return array<string, array{base: int, bonuses: array<int, int>}>
     */
    private static function decodeRewardSteps(string $value): array
    {
        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            $decoded = json_decode((string) self::DEFAULTS[self::WORD_MYSTERY_REWARDS], true);
        }

        $fallback = json_decode((string) self::DEFAULTS[self::WORD_MYSTERY_REWARDS], true);

        return collect(['easy', 'normal', 'hard'])
            ->mapWithKeys(function (string $difficulty) use ($decoded, $fallback): array {
                $settings = $decoded[$difficulty] ?? [];

                if (isset($settings['base'], $settings['bonuses']) && is_array($settings['bonuses'])) {
                    return [
                        $difficulty => [
                            'base' => (int) $settings['base'],
                            'bonuses' => collect(range(1, 6))
                                ->mapWithKeys(fn (int $attempt): array => [$attempt => (int) ($settings['bonuses'][$attempt] ?? $settings['bonuses'][(string) $attempt] ?? 0)])
                                ->all(),
                        ],
                    ];
                }

                $legacySteps = collect(range(1, 6))
                    ->mapWithKeys(fn (int $attempt): array => [$attempt => (int) ($settings[$attempt] ?? $settings[(string) $attempt] ?? 0)])
                    ->all();
                $base = $legacySteps[3] ?: (int) ($fallback[$difficulty]['base'] ?? 0);

                return [
                    $difficulty => [
                        'base' => $base,
                        'bonuses' => collect(range(1, 6))
                            ->mapWithKeys(fn (int $attempt): array => [
                                $attempt => $base > 0 ? (int) round((($legacySteps[$attempt] - $base) / $base) * 100) : 0,
                            ])
                            ->all(),
                    ],
                ];
            })
            ->all();
    }
}
