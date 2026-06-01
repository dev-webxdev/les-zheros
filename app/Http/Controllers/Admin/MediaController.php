<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\GalleryImage;
use App\Models\Guide;
use App\Models\Mission;
use App\Models\MissionValidation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;

class MediaController extends Controller
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'];

    public function picker(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $directory = $this->pickerDirectory((string) $request->query('directory', ''));
        $images = collect($this->scanImages($this->usedReferences()))
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

        return response()->json(['images' => $images]);
    }

    private function pickerDirectory(string $directory): ?string
    {
        $directory = trim($directory, '/');

        return in_array($directory, ['missions', 'guides', 'gallery', 'avatars', 'validations'], true)
            ? $directory
            : null;
    }

    /**
     * @param list<string> $usedReferences
     * @return list<array<string, mixed>>
     */
    private function scanImages(array $usedReferences): array
    {
        return collect([
            ['root' => public_path('assets/uploads'), 'public' => 'assets/uploads', 'label' => 'Uploads', 'key' => 'uploads_public'],
            ['root' => base_path('assets/uploads'), 'public' => 'assets/uploads', 'label' => 'Uploads', 'key' => 'uploads_root'],
        ])
            ->flatMap(function (array $source) use ($usedReferences) {
                if (! File::isDirectory($source['root'])) {
                    return [];
                }

                return collect(File::allFiles($source['root']))
                    ->filter(fn (SplFileInfo $file): bool => in_array(Str::lower($file->getExtension()), self::IMAGE_EXTENSIONS, true))
                    ->map(function (SplFileInfo $file) use ($source, $usedReferences): array {
                        $filePath = $this->normalizePublicPath($file->getPathname());
                        $rootPath = $this->normalizePublicPath($source['root']);
                        $path = $this->normalizePublicPath($source['public'].'/'.Str::after($filePath, $rootPath));

                        return [
                            'name' => $file->getFilename(),
                            'path' => $path,
                            'url' => asset($path),
                            'directory' => str_replace('\\', '/', dirname($path)),
                            'source' => $source['label'],
                            'source_key' => $source['key'],
                            'size' => $file->getSize(),
                            'size_human' => $this->humanSize($file->getSize()),
                            'used' => $this->isUsed($path, $usedReferences),
                        ];
                    });
            })
            ->sortBy([['source_key', 'asc'], ['directory', 'asc'], ['name', 'asc']])
            ->unique('path')
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
