<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'word',
    'hint',
    'difficulty',
    'reward_base',
    'active_date',
    'is_active',
])]
class WordMysteryWord extends Model
{
    public const DIFFICULTIES = [
        'easy' => 'Facile',
        'normal' => 'Normal',
        'hard' => 'Difficile',
    ];

    protected function casts(): array
    {
        return [
            'active_date' => 'date',
            'is_active' => 'boolean',
            'reward_base' => 'integer',
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
}
