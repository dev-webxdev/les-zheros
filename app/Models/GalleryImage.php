<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'title',
    'description',
    'image_path',
    'is_published',
    'taken_at',
])]
class GalleryImage extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'taken_at' => 'datetime',
        ];
    }

    public function imageUrl(): string
    {
        return $this->image_path;
    }

    public function displayDate(): string
    {
        return ($this->taken_at ?: $this->created_at)?->translatedFormat('j F Y') ?? '';
    }

    public function dateValue(): ?string
    {
        return ($this->taken_at ?: $this->created_at)?->toDateString();
    }
}
