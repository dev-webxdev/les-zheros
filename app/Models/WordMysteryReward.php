<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'game_attempt_id',
    'amount',
    'status',
])]
class WordMysteryReward extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'pending' => 'En attente',
        'paid' => 'Payee',
        'cancelled' => 'Annulee',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(WordMysteryAttempt::class, 'game_attempt_id');
    }
}
