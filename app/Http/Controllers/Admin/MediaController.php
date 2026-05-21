<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\GalleryImage;
use App\Models\Guide;
use App\Models\Mission;
use App\Models\MissionValidation;
use App\Models\User;
use App\Support\AdminActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use SplFileInfo;

class MediaController extends Controller
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'];

    public function index(Request $request): View|RedirectResponse
    {
        $normalizedQuery = $request->query();

        if (array_key_exists('source', $normalizedQuery)) {
            unset($normalizedQuery['source']);
        }

        foreach (['search', 'status'] as $key) {
            if (! array_key_exists($key, $normalizedQuery)) {
                continue;
            }

            $normalizedQuery[$key] = trim((string) $normalizedQuery[$key]);

            if ($normalizedQuery[$key] === '' || in_array($normalizedQuery[$key], ['all', 'tous'], true)) {
                unset($normalizedQuery[$key]);
            }
        }

        if ($normalizedQuery !== $request->query()) {
            return redirect()->route('admin.mediatheque.index', $normalizedQuery);
        }

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => $request->query('status', 'all'),
        ];
        $usedReferences = $this->usedReferences();
        $images = collect($this->scanImages($usedReferences))
            ->when($filters['status'] !== 'all', function ($items) use ($filters) {
                return match ($filters['status']) {
                    'used' => $items->where('used', true),
                    'unused' => $items->where('used', false)->where('deletable', true),
                    default => $items,
                };
            })
            ->when($filters['search'] !== '', function ($items) use ($filters) {
                $search = Str::lower($filters['search']);

                return $items->filter(fn (array $image): bool => str_contains(Str::lower($image['name']), $search)
                    || str_contains(Str::lower($image['path']), $search)
                    || str_contains(Str::lower($image['directory']), $search));
            })
            ->values();
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 16;
        $paginatedImages = new LengthAwarePaginator(
            $images->forPage($page, $perPage)->values(),
            $images->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );

        return view('admin.admin-media', [
            'images' => $paginatedImages,
            'filters' => $filters,
            'stats' => [
                'total' => $images->count(),
                'size' => $this->humanSize($images->sum('size')),
                'unused' => $images->where('used', false)->where('deletable', true)->count(),
            ],
        ]);
    }

    public function picker(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $directory = $this->pickerDirectory((string) $request->query('directory', ''));
        $usedReferences = $this->usedReferences();
        $images = collect($this->scanImages($usedReferences))
            ->when($directory !== null, fn ($items) => $items->filter(
                fn (array $image): bool => Str::startsWith($image['path'], 'assets/uploads/'.$directory.'/')
            ))
            ->when($search !== '', function ($items) use ($search) {
                $search = Str::lower($search);

                return $items->filter(fn (array $image): bool => str_contains(Str::lower($image['name']), $search)
                    || str_contains(Str::lower($image['path']), $search)
                    || str_contains(Str::lower($image['directory']), $search));
            })
            ->take(30)
            ->values()
            ->map(fn (array $image): array => [
                'name' => $image['name'],
                'path' => $image['path'],
                'url' => $image['url'],
                'size' => $image['size_human'],
                'used' => $image['used'],
            ]);

        return response()->json([
            'images' => $images,
        ]);
    }

    private function pickerDirectory(string $directory): ?string
    {
        $directory = trim($directory, '/');

        return in_array($directory, ['missions', 'guides', 'gallery', 'avatars', 'validations'], true)
            ? $directory
            : null;
    }

    public function destroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string'],
        ]);
        $path = $this->normalizePublicPath($data['path']);
        $fullPath = public_path($path);
        $uploadsRoot = realpath(public_path('assets/uploads'));
        $target = realpath($fullPath);

        if (! $uploadsRoot || ! $target || ! Str::startsWith($target, $uploadsRoot) || ! File::isFile($target)) {
            return back()->with('admin_toast', [
                'title' => 'Suppression impossible',
                'text' => 'Seules les images uploadées peuvent être supprimées depuis la médiathèque.',
                'type' => 'error',
            ]);
        }

        if (! in_array(Str::lower(File::extension($target)), self::IMAGE_EXTENSIONS, true)) {
            abort(404);
        }

        if ($this->isUsed($path, $this->usedReferences())) {
            return back()->with('admin_toast', [
                'title' => 'Image utilisée',
                'text' => 'Cette image est encore référencée sur le site.',
                'type' => 'warning',
            ]);
        }

        File::delete($target);
        AdminActivity::log('media', 'deleted', 'Image supprimée', $path);

        return redirect()->route('admin.mediatheque.index')->with('admin_toast', [
            'title' => 'Image supprimée',
            'text' => 'Le fichier inutilisé a bien été retiré.',
            'type' => 'warning',
        ]);
    }

    /**
     * @param list<string> $usedReferences
     * @return list<array<string, mixed>>
     */
    private function scanImages(array $usedReferences): array
    {
        return collect([
            ['root' => public_path('assets/uploads'), 'public' => 'assets/uploads', 'label' => 'Uploads', 'key' => 'uploads', 'deletable' => true],
        ])
            ->flatMap(function (array $source) use ($usedReferences) {
                if (! File::isDirectory($source['root'])) {
                    return [];
                }

                return collect(File::allFiles($source['root']))
                    ->filter(fn (SplFileInfo $file): bool => in_array(Str::lower($file->getExtension()), self::IMAGE_EXTENSIONS, true))
                    ->map(function (SplFileInfo $file) use ($source, $usedReferences): array {
                        $path = $this->normalizePublicPath($source['public'].'/'.Str::after($file->getPathname(), $source['root']));
                        $used = $source['deletable'] ? $this->isUsed($path, $usedReferences) : true;

                        return [
                            'name' => $file->getFilename(),
                            'path' => $path,
                            'url' => asset($path),
                            'directory' => str_replace('\\', '/', dirname($path)),
                            'source' => $source['label'],
                            'source_key' => $source['key'],
                            'size' => $file->getSize(),
                            'size_human' => $this->humanSize($file->getSize()),
                            'modified_at' => date('d/m/Y H:i', $file->getMTime()),
                            'used' => $used,
                            'deletable' => $source['deletable'] && ! $used,
                        ];
                    });
            })
            ->sortBy([['source_key', 'asc'], ['directory', 'asc'], ['name', 'asc']])
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function usedReferences(): array
    {
        $references = collect();

        User::withTrashed()->pluck('avatar_path')->each(fn ($value) => $references->push($value));
        Mission::withTrashed()->pluck('image_path')->each(fn ($value) => $references->push($value));
        GalleryImage::withTrashed()->pluck('image_path')->each(fn ($value) => $references->push($value));
        MissionValidation::withTrashed()->pluck('proof_path')->each(fn ($value) => $references->push($value));
        Announcement::withTrashed()->pluck('content')->each(fn ($value) => $references->push($value));
        Guide::withTrashed()->get(['cover_path', 'map_path', 'sections'])->each(function (Guide $guide) use ($references): void {
            $references->push($guide->cover_path, $guide->map_path);
            $this->collectStrings($guide->sections ?? [], $references);
        });

        return $references
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => Str::lower(str_replace('\\', '/', $value)))
            ->unique()
            ->values()
            ->all();
    }

    private function collectStrings(mixed $value, $references): void
    {
        if (is_string($value)) {
            $references->push($value);
            return;
        }

        if (! is_array($value)) {
            return;
        }

        foreach ($value as $item) {
            $this->collectStrings($item, $references);
        }
    }

    /**
     * @param list<string> $usedReferences
     */
    private function isUsed(string $path, array $usedReferences): bool
    {
        $normalized = Str::lower($this->normalizePublicPath($path));
        $withSlash = '/'.$normalized;

        foreach ($usedReferences as $reference) {
            if (str_contains($reference, $normalized) || str_contains($reference, $withSlash)) {
                return true;
            }
        }

        return false;
    }

    private function normalizePublicPath(string $path): string
    {
        return trim(preg_replace('#/+#', '/', str_replace('\\', '/', $path)) ?? $path, '/');
    }

    private function humanSize(int|float $bytes): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $size = (float) $bytes;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return number_format($size, $unit === 0 ? 0 : 1, ',', ' ').' '.$units[$unit];
    }
}
