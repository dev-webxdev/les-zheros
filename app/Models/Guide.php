<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'mission_id',
    'title',
    'slug',
    'category',
    'summary',
    'chips',
    'checklist',
    'sections',
    'cover_path',
    'map_path',
    'is_published',
    'published_at',
])]
class Guide extends Model
{
    use SoftDeletes;

    public const CATEGORIES = [
        'donjon' => 'Donjon',
        'expedition' => 'Expédition',
        'songe' => 'Songe',
        'anomalie' => 'Anomalie',
        'regulation' => 'Régulation',
    ];

    protected function casts(): array
    {
        return [
            'chips' => 'array',
            'checklist' => 'array',
            'sections' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    public function coverUrl(): string
    {
        return $this->cover_path ?: asset('assets/img/card-mission/type.png');
    }

    public function mapUrl(): ?string
    {
        return $this->map_path;
    }

    /**
     * @return array<string, mixed>
     */
    public function frontPayload(): array
    {
        $sections = collect($this->sections ?? [])
            ->map(fn (array $section): array => $this->formatSection($section));

        $strategySections = $sections
            ->filter(fn (array $section): bool => ($section['kind'] ?? 'strategy') === 'strategy')
            ->values();

        $spellsSections = $sections
            ->filter(fn (array $section): bool => ($section['kind'] ?? 'strategy') === 'spells')
            ->values();

        $placement = $sections
            ->first(fn (array $section): bool => ($section['kind'] ?? '') === 'placement') ?? [
                'title' => 'Placement',
                'body' => '',
                'images' => [],
            ];

        return [
            'title' => $this->title,
            'type' => $this->categoryLabel(),
            'image' => $this->coverUrl(),
            'map' => $this->mapUrl(),
            'summary' => $this->summary,
            'chips' => $this->chips ?? [],
            'checklist' => $this->checklist ?? [],
            'placement' => $placement,
            'sections' => $strategySections->all(),
            'spells' => $spellsSections->all(),
        ];
    }

    private function formatSection(array $section): array
    {
        $images = collect($section['images'] ?? [])
            ->filter(fn (array $image): bool => ! empty($image['image']))
            ->map(fn (array $image): array => [
                'image' => $image['image'],
                'caption' => (string) ($image['caption'] ?? ''),
            ])
            ->values();

        if ($images->isEmpty() && ! empty($section['image'])) {
            $images = collect([[
                'image' => $section['image'],
                'caption' => (string) ($section['caption'] ?? ''),
            ]]);
        }

        return [
            ...$section,
            'kind' => $section['kind'] ?? 'strategy',
            'title' => (string) ($section['title'] ?? ''),
            'body' => $this->formatSectionBody((string) ($section['body'] ?? '')),
            'images' => $images->all(),
        ];
    }

    private function formatSectionBody(string $body): string
    {
        $body = trim($body);

        if ($body === '') {
            return '';
        }

        if (preg_match('/<[^>]+>/', $body) === 1) {
            return $body;
        }

        return collect(preg_split("/\R{2,}/", $body) ?: [])
            ->map(fn (string $paragraph): string => '<p>'.nl2br(e(trim($paragraph)), false).'</p>')
            ->join('');
    }
}
