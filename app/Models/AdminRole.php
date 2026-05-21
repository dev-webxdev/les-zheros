<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['key', 'label', 'color', 'permissions'])]
class AdminRole extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }
}
