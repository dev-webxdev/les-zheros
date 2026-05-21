<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'title',
    'class_slug',
    'class_label',
    'elements',
    'mode',
    'min_level',
    'max_level',
    'budget',
    'meta',
    'author',
    'description',
    'dofusbook_url',
    'is_featured',
    'is_published',
])]
class Stuff extends Model
{
    use SoftDeletes;

    public const CLASSES = [
        'cra' => 'Crâ',
        'ecaflip' => 'Ecaflip',
        'eliotrope' => 'Eliotrope',
        'eniripsa' => 'Eniripsa',
        'enutrof' => 'Enutrof',
        'feca' => 'Féca',
        'forgelance' => 'Forgelance',
        'huppermage' => 'Huppermage',
        'iop' => 'Iop',
        'osamodas' => 'Osamodas',
        'ouginak' => 'Ouginak',
        'pandawa' => 'Pandawa',
        'roublard' => 'Roublard',
        'sacrieur' => 'Sacrieur',
        'sadida' => 'Sadida',
        'sram' => 'Sram',
        'steamer' => 'Steamer',
        'xelor' => 'Xélor',
        'zobal' => 'Zobal',
    ];

    public const ELEMENTS = ['Feu', 'Eau', 'Air', 'Terre', 'Multi', 'Tank', 'Prospection', 'Do pou'];
    public const MODES = ['DPS', 'Tank', 'Soutien', 'Placement'];
    public const LEVELS = [20, 40, 60, 80, 100, 110, 120, 130, 160, 180, 199, 200];

    protected function casts(): array
    {
        return [
            'elements' => 'array',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public static function classSlug(string $label): string
    {
        return Str::slug($label);
    }

    public function levelLabel(): string
    {
        return $this->min_level === $this->max_level
            ? (string) $this->max_level
            : $this->min_level.'-'.$this->max_level;
    }

    public function elementsText(string $separator = ' '): string
    {
        return collect($this->elements ?? [])
            ->map(fn ($element) => Str::slug((string) $element))
            ->join($separator);
    }

    public function chips(): array
    {
        return collect($this->elements ?? [])
            ->when($this->meta, fn ($chips) => $chips->push('Meta '.$this->meta))
            ->push($this->levelLabel())
            ->filter()
            ->values()
            ->all();
    }

}
