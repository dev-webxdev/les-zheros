<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cycle_value',
    'cycle_label',
    'drawn_at',
    'drawn_by',
    'drawn_by_name',
    'settings',
    'participants',
    'winners',
    'total_tickets',
    'total_points',
    'total_prize',
])]
class LotteryDraw extends Model
{
    protected function casts(): array
    {
        return [
            'drawn_at' => 'datetime',
            'settings' => 'array',
            'participants' => 'array',
            'winners' => 'array',
            'total_tickets' => 'integer',
            'total_points' => 'float',
            'total_prize' => 'integer',
        ];
    }

    public function drawer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'drawn_by');
    }
}
