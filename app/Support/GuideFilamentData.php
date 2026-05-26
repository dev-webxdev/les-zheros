<?php

namespace App\Support;

use App\Models\Guide;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class GuideFilamentData
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function prepareForFill(array $data): array
    {
        $data['chips_text'] = collect($data['chips'] ?? [])->join(', ');
        $data['checklist'] = collect($data['checklist'] ?? [])
            ->map(fn (string $item): array => ['item' => $item])
            ->values()
            ->all();
        $sections = collect($data['sections'] ?? [])->map(fn (array $section): array => self::prepareSection($section));

        $data['placement_sections'] = $sections
            ->where('kind', 'placement')
            ->values()
            ->all();
        $data['strategy_sections'] = $sections
            ->where('kind', 'strategy')
            ->values()
            ->all();
        $data['spells_sections'] = $sections
            ->where('kind', 'spells')
            ->values()
            ->all();

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data, ?Guide $guide = null): array
    {
        $title = trim((string) ($data['title'] ?? ''));

        if ($title === '') {
            throw ValidationException::withMessages([
                'data.title' => 'Le titre du guide est obligatoire.',
            ]);
        }

        $slug = self::uniqueSlug($title, $guide);

        return [
            'mission_id' => $data['mission_id'] ?? null,
            'title' => $title,
            'slug' => $slug,
            'category' => $data['category'],
            'summary' => $data['summary'] ?? null,
            'chips' => self::splitList((string) ($data['chips_text'] ?? $title)),
            'checklist' => collect($data['checklist'] ?? [])
                ->pluck('item')
                ->map(fn (?string $item): string => trim((string) $item))
                ->filter()
                ->values()
                ->all(),
            'sections' => self::sections($data),
            'cover_path' => self::upload($data['cover_upload'] ?? null, 'guides', $data['cover_path'] ?? $guide?->cover_path),
            'map_path' => self::upload($data['map_upload'] ?? null, 'guides', $data['map_path'] ?? $guide?->map_path),
            'is_published' => (bool) ($data['is_published'] ?? false),
            'published_at' => $data['published_at'] ?? now(),
        ];
    }

    private static function uniqueSlug(string $title, ?Guide $guide = null): string
    {
        $slug = Str::slug($title);
        $baseSlug = $slug;
        $counter = 2;

        while (Guide::withTrashed()
            ->where('slug', $slug)
            ->when($guide, fn ($query) => $query->where('id', '!=', $guide->getKey()))
            ->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @return list<string>
     */
    private static function splitList(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $data
     * @return list<array<string, mixed>>
     */
    private static function sections(array $data): array
    {
        $sections = [];

        foreach ([
            'placement_sections' => 'placement',
            'strategy_sections' => 'strategy',
            'spells_sections' => 'spells',
        ] as $key => $kind) {
            foreach ($data[$key] ?? [] as $section) {
                $section['kind'] = $kind;
                $sections[] = $section;
            }
        }

        if ($sections === []) {
            $sections = $data['sections'] ?? [];
        }

        return self::normalizeSections($sections);
    }

    /**
     * @param list<array<string, mixed>> $sections
     * @return list<array<string, mixed>>
     */
    private static function normalizeSections(array $sections): array
    {
        return collect($sections)
            ->map(function (array $section): array {
                $images = collect($section['images'] ?? [])
                    ->map(fn (array $image): array => [
                        'image' => self::upload($image['image_upload'] ?? null, 'guides', $image['image_path'] ?? null),
                        'caption' => (string) ($image['caption'] ?? ''),
                    ])
                    ->filter(fn (array $image): bool => filled($image['image']))
                    ->values()
                    ->all();

                return [
                    'kind' => (string) ($section['kind'] ?? 'strategy'),
                    'title' => trim((string) ($section['title'] ?? '')),
                    'body' => (string) ($section['body'] ?? ''),
                    'images' => $images,
                ];
            })
            ->filter(fn (array $section): bool => $section['title'] !== '' || trim(strip_tags($section['body'])) !== '' || ! empty($section['images']))
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $section
     * @return array<string, mixed>
     */
    private static function prepareSection(array $section): array
    {
        return [
            'kind' => $section['kind'] ?? 'strategy',
            'title' => $section['title'] ?? '',
            'body' => $section['body'] ?? '',
            'images' => collect($section['images'] ?? [])
                ->map(fn (array $image): array => [
                    'image_path' => $image['image'] ?? '',
                    'caption' => $image['caption'] ?? '',
                ])
                ->values()
                ->all(),
        ];
    }

    private static function upload(mixed $upload, string $directory, ?string $fallback = null): ?string
    {
        $file = self::firstUpload($upload);

        if (! $file) {
            return filled($fallback) ? $fallback : null;
        }

        return PublicUploadManager::store($file, $directory, 'guide', includeOriginalName: true);
    }

    private static function firstUpload(mixed $upload): ?TemporaryUploadedFile
    {
        if ($upload instanceof TemporaryUploadedFile) {
            return $upload;
        }

        if (is_array($upload)) {
            return collect($upload)
                ->first(fn ($file): bool => $file instanceof TemporaryUploadedFile);
        }

        return null;
    }
}
