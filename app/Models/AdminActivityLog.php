<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'user_name',
    'area',
    'action',
    'title',
    'description',
    'subject_type',
    'subject_id',
    'subject_label',
    'ip_address',
    'user_agent',
    'properties',
])]
class AdminActivityLog extends Model
{
    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actorName(): string
    {
        return $this->user?->name ?? $this->user_name ?? 'Systeme';
    }
}
