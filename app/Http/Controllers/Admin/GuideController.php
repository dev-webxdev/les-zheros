<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Models\Mission;
use App\Support\PublicUploadManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function index(): View
    {
        $guides = Guide::query()
            ->with('mission')
            ->latest()
            ->paginate(12);

        $categoryCounts = Guide::query()
            ->get(['category'])
            ->groupBy('category')
            ->map->count();

        return view('admin.admin-guides', [
            'guides' => $guides,
            'guideCount' => $guides->total(),
            'categoryCounts' => $categoryCounts,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $mission = $request->filled('mission_id')
            ? Mission::query()
                ->find($request->integer('mission_id'))
            : null;

        if ($mission) {
            $existingGuide = Guide::query()
                ->where('mission_id', $mission->id)
                ->first();

            if ($existingGuide) {
                return redirect()->route('admin.guides.edit', $existingGuide);
            }
        }

        return view('admin.admin-guide-create', [
            'guide' => new Guide([
                'mission_id' => $mission?->id,
                'title' => $mission?->title,
                'category' => $mission?->category ?? 'donjon',
                'chips' => $mission ? [$mission->title] : [],
                'cover_path' => $mission?->imageUrl(),
                'is_published' => true,
                'published_at' => now(),
            ]),
            'missions' => $this->guideMissions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $draft = $request->filled('auto_draft_id')
            ? Guide::query()->whereKey($request->integer('auto_draft_id'))->first()
            : null;

        if ($draft) {
            $draft->update($this->payload($request, $draft));
        } else {
            Guide::create($this->payload($request));
        }

        return redirect()->route('admin.guides.index')->with('admin_toast', [
            'title' => 'Guide créé',
            'text' => 'Le guide a bien été enregistré.',
            'type' => 'success',
        ]);
    }

    public function edit(Guide $guide): View
    {
        return view('admin.admin-guide-create', [
            'guide' => $guide,
            'missions' => $this->guideMissions(),
        ]);
    }

    public function autosave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'auto_draft_id' => ['nullable', 'integer', 'exists:guides,id'],
            'mission_id' => ['nullable', 'exists:missions,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', Rule::in(array_keys(Guide::CATEGORIES))],
            'summary' => ['nullable', 'string', 'max:2000'],
            'chips' => ['nullable', 'string', 'max:500'],
            'checklist' => ['nullable', 'array'],
            'checklist.*' => ['nullable', 'string', 'max:500'],
            'sections' => ['nullable', 'array'],
            'sections.*.kind' => ['nullable', Rule::in(['placement', 'strategy', 'spells'])],
            'sections.*.title' => ['nullable', 'string', 'max:255'],
            'sections.*.body' => ['nullable', 'string', 'max:8000'],
            'sections.*.caption' => ['nullable', 'string', 'max:1000'],
            'sections.*.images' => ['nullable', 'array'],
            'sections.*.images.*.caption' => ['nullable', 'string', 'max:1200'],
            'cover_path' => ['nullable', 'string', 'max:1000'],
            'published_at' => ['nullable', 'date'],
        ]);

        $guide = ! empty($validated['auto_draft_id'])
            ? Guide::query()->whereKey($validated['auto_draft_id'])->first()
            : null;

        $title = trim((string) ($validated['title'] ?? ''));
        $title = $title !== '' ? $title : 'Brouillon guide '.now()->format('d/m/Y H:i');

        $payload = [
            'mission_id' => $validated['mission_id'] ?? null,
            'title' => $title,
            'slug' => $this->uniqueSlug($title, $guide),
            'category' => $validated['category'] ?? 'donjon',
            'summary' => $validated['summary'] ?? null,
            'chips' => $this->splitList($validated['chips'] ?? ''),
            'checklist' => collect($validated['checklist'] ?? [])->filter()->values()->all(),
            'sections' => $this->autosaveSections($request, $guide),
            'cover_path' => $validated['cover_path'] ?? $guide?->cover_path,
            'map_path' => $guide?->map_path,
            'is_published' => false,
            'published_at' => $validated['published_at'] ?? $guide?->published_at ?? now(),
        ];

        if ($guide) {
            $guide->update($payload);
        } else {
            $guide = Guide::create($payload);
        }

        return response()->json([
            'id' => $guide->id,
            'edit_url' => route('admin.guides.edit', $guide),
            'saved_at' => now()->format('H:i'),
        ]);
    }

    public function update(Request $request, Guide $guide): RedirectResponse
    {
        $guide->update($this->payload($request, $guide));

        return redirect()->route('admin.guides.index')->with('admin_toast', [
            'title' => 'Guide modifié',
            'text' => 'Les modifications du guide ont bien été enregistrées.',
            'type' => 'success',
        ]);
    }

    public function destroy(Guide $guide): RedirectResponse
    {
        $guide->delete();

        return redirect()->route('admin.guides.index')->with('admin_toast', [
            'title' => 'Guide en corbeille',
            'text' => 'Le guide a été déplacé dans la corbeille.',
            'type' => 'success',
        ]);
    }

    public function trash(): View
    {
        return view('admin.admin-guides-trash', [
            'guides' => Guide::onlyTrashed()
                ->latest('deleted_at')
                ->paginate(12),
        ]);
    }

    public function restore(int $guide): RedirectResponse
    {
        Guide::onlyTrashed()->findOrFail($guide)->restore();

        return redirect()->route('admin.guides.trash')->with('admin_toast', [
            'title' => 'Guide restauré',
            'text' => 'Le guide est de retour dans la liste.',
            'type' => 'success',
        ]);
    }

    public function forceDelete(int $guide): RedirectResponse
    {
        Guide::onlyTrashed()->findOrFail($guide)->forceDelete();

        return redirect()->route('admin.guides.trash')->with('admin_toast', [
            'title' => 'Guide supprimé',
            'text' => 'Le guide a été supprimé définitivement.',
            'type' => 'warning',
        ]);
    }

    public function emptyTrash(): RedirectResponse
    {
        Guide::onlyTrashed()->forceDelete();

        return redirect()->route('admin.guides.trash')->with('admin_toast', [
            'title' => 'Corbeille vidée',
            'text' => 'Tous les guides en corbeille ont été supprimés définitivement.',
            'type' => 'warning',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request, ?Guide $guide = null): array
    {
        $validated = $request->validate([
            'mission_id' => ['nullable', 'exists:missions,id'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(Guide::CATEGORIES))],
            'summary' => ['required', 'string', 'max:2000'],
            'chips' => ['nullable', 'string', 'max:500'],
            'checklist' => ['nullable', 'array'],
            'checklist.*' => ['nullable', 'string', 'max:500'],
            'sections' => ['nullable', 'array'],
            'sections.*.kind' => ['nullable', Rule::in(['placement', 'strategy', 'spells'])],
            'sections.*.title' => ['nullable', 'string', 'max:255'],
            'sections.*.body' => ['nullable', 'string', 'max:8000'],
            'sections.*.image' => ['nullable', 'image', 'max:4096'],
            'sections.*.caption' => ['nullable', 'string', 'max:1000'],
            'sections.*.images' => ['nullable', 'array'],
            'sections.*.images.*.image' => ['nullable', 'image', 'max:4096'],
            'sections.*.images.*.caption' => ['nullable', 'string', 'max:1200'],
            'cover' => ['nullable', 'image', 'max:4096'],
            'cover_path' => ['nullable', 'string', 'max:1000'],
            'map' => ['nullable', 'image', 'max:4096'],
            'published' => ['nullable'],
            'published_at' => ['nullable', 'date'],
        ]);

        $slug = $this->uniqueSlug($validated['title'], $guide);

        return [
            'mission_id' => $validated['mission_id'] ?? null,
            'title' => $validated['title'],
            'slug' => $slug,
            'category' => $validated['category'],
            'summary' => $validated['summary'] ?? null,
            'chips' => $this->splitList($validated['chips'] ?? $validated['title']),
            'checklist' => collect($validated['checklist'] ?? [])->filter()->values()->all(),
            'sections' => $this->sections($request, $guide),
            'cover_path' => $this->upload($request, 'cover', 'guides', $validated['cover_path'] ?? $guide?->cover_path),
            'map_path' => $this->upload($request, 'map', 'guides', $guide?->map_path),
            'is_published' => $request->boolean('published'),
            'published_at' => $validated['published_at'] ?? now(),
        ];
    }

    private function uniqueSlug(string $title, ?Guide $guide = null): string
    {
        $slug = Str::slug($title) ?: 'guide';
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
    private function splitList(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sections(Request $request, ?Guide $guide): array
    {
        return collect($request->input('sections', []))
            ->map(function (array $section, int $index) use ($request, $guide): array {
                $existing = $guide?->sections[$index] ?? [];
                $existingImages = collect($existing['images'] ?? [])
                    ->when(! empty($existing['image']), fn ($images) => $images->prepend([
                        'image' => $existing['image'],
                        'caption' => $existing['caption'] ?? '',
                    ]))
                    ->values();

                $images = collect($section['images'] ?? [])
                    ->map(function (array $image, int $imageIndex) use ($request, $index, $existingImages): array {
                        $existingImage = $existingImages[$imageIndex] ?? [];

                        return [
                            'image' => $this->upload($request, "sections.$index.images.$imageIndex.image", 'guides', $existingImage['image'] ?? null),
                            'caption' => (string) ($image['caption'] ?? $existingImage['caption'] ?? ''),
                        ];
                    })
                    ->filter(fn (array $image): bool => ! empty($image['image']))
                    ->values()
                    ->all();

                return [
                    'kind' => (string) ($section['kind'] ?? $existing['kind'] ?? 'strategy'),
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
     * @return list<array<string, mixed>>
     */
    private function autosaveSections(Request $request, ?Guide $guide): array
    {
        return collect($request->input('sections', []))
            ->map(function (array $section, int $index) use ($guide): array {
                $existing = $guide?->sections[$index] ?? [];
                $existingImages = collect($existing['images'] ?? [])->values();

                $images = collect($section['images'] ?? [])
                    ->map(fn (array $image, int $imageIndex): array => [
                        'image' => $existingImages[$imageIndex]['image'] ?? null,
                        'caption' => (string) ($image['caption'] ?? $existingImages[$imageIndex]['caption'] ?? ''),
                    ])
                    ->filter(fn (array $image): bool => ! empty($image['image']) || $image['caption'] !== '')
                    ->values()
                    ->all();

                return [
                    'kind' => (string) ($section['kind'] ?? $existing['kind'] ?? 'strategy'),
                    'title' => trim((string) ($section['title'] ?? '')),
                    'body' => (string) ($section['body'] ?? ''),
                    'images' => $images,
                ];
            })
            ->filter(fn (array $section): bool => $section['title'] !== '' || trim(strip_tags($section['body'])) !== '' || ! empty($section['images']))
            ->values()
            ->all();
    }

    private function upload(Request $request, string $key, string $directory, ?string $fallback = null): ?string
    {
        if (! $request->hasFile($key)) {
            return $fallback;
        }

        return PublicUploadManager::store($request->file($key), $directory, 'guide', includeOriginalName: true);
    }

    private function guideMissions()
    {
        return Mission::query()
            ->orderBy('title')
            ->get();
    }
}
