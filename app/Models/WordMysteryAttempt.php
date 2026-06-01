<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'word_id',
    'difficulty',
    'attempts_count',
    'guesses',
    'has_won',
    'reward_earned',
    'played_at',
])]
class WordMysteryAttempt extends Model
{
    protected function casts(): array
    {
        return [
            'attempts_count' => 'integer',
            'guesses' => 'array',
            'has_won' => 'boolean',
            'reward_earned' => 'integer',
            'played_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function word(): BelongsTo
    {
        return $this->belongsTo(WordMysteryWord::class, 'word_id')->withTrashed();
    }

    public function reward(): HasOne
    {
        return $this->hasOne(WordMysteryReward::class, 'game_attempt_id');
    }

    public function hasLost(): bool
    {
        return ! $this->has_won && $this->attempts_count >= 6;
    }
}
