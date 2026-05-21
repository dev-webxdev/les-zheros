<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'outing_id',
    'user_id',
    'slot_id',
])]
class OutingVote extends Model
{
    public function outing(): BelongsTo
    {
        return $this->belongsTo(Outing::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
