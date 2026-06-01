<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'word',
    'hint',
    'difficulty',
    'reward_base',
    'reward_steps',
    'active_date',
    'is_active',
])]
class WordMysteryWord extends Model
{
    use SoftDeletes;

    public const DIFFICULTIES = [
        'easy' => 'Facile',
        'normal' => 'Normal',
        'hard' => 'Difficile',
    ];

    public const EXPECTED_LENGTHS = [
        'easy' => 4,
        'normal' => 6,
        'hard' => 8,
    ];

    protected function casts(): array
    {
        return [
            'active_date' => 'date',
            'is_active' => 'boolean',
            'reward_base' => 'integer',
            'reward_steps' => 'array',
        ];
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(WordMysteryAttempt::class, 'word_id');
    }

    public function difficultyLabel(): string
    {
        return self::DIFFICULTIES[$this->difficulty] ?? ucfirst($this->difficulty);
    }

    public static function expectedLength(string $difficulty): ?int
    {
        return self::EXPECTED_LENGTHS[$difficulty] ?? null;
    }
}
