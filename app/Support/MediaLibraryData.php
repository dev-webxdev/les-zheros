<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\GalleryImage;
use App\Models\Guide;
use App\Models\Mission;
use App\Models\MissionValidation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;

class MediaLibraryData
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'];

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function images(?string $search = null, string $status = 'all'): Collection
    {
        $search = Str::lower(trim((string) $search));
        $usedReferences = $this->usedReferences();

        return collect($this->scanImages($usedReferences))
            ->when($status !== 'all', function (Collection $items) use ($status): Collection {
                return match ($status) {
                    'used' => $items->where('used', true),
                    'unused' => $items->where('used', false)->where('deletable', true),
                    default => $items,
                };
            })
            ->when($search !== '', fn (Collection $items): Collection => $items->filter(
                fn (array $image): bool => str_contains(Str::lower($image['name']), $search)
                    || str_contains(Str::lower($image['path']), $search)
                    || str_contains(Str::lower($image['directory']), $search),
            ))
            ->values();
    }

    /**
     * @return array{ok: bool, title: string, body: string, type: string}
     */
    public function deleteUnusedImage(string $path): array
    {
        $path = $this->normalizePublicPath($path);
        $fullPath = public_path($path);
        $uploadsRoot = realpath(public_path('assets/uploads'));
        $target = realpath($fullPath);

        if (! $uploadsRoot || ! $target || ! Str::startsWith($target, $uploadsRoot) || ! File::isFile($target)) {
            return [
                'ok' => false,
                'title' => 'Suppression impossible',
                'body' => 'Seules les images uploadees peuvent etre supprimees depuis la mediatheque.',
                'type' => 'danger',
            ];
        }

        if (! in_array(Str::lower(File::extension($target)), self::IMAGE_EXTENSIONS, true)) {
            return [
                'ok' => false,
                'title' => 'Format non pris en charge',
                'body' => 'Ce fichier ne fait pas partie des images gerees par la mediatheque.',
                'type' => 'danger',
            ];
        }

        if ($this->isUsed($path, $this->usedReferences())) {
            return [
                'ok' => false,
                'title' => 'Image utilisee',
                'body' => 'Cette image est encore referencee sur le site.',
                'type' => 'warning',
            ];
        }

        File::delete($target);
        AdminActivity::log('media', 'deleted', 'Image supprimee', $path);

        return [
            'ok' => true,
            'title' => 'Image supprimee',
            'body' => 'Le fichier inutilise a bien ete retire.',
            'type' => 'success',
        ];
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
                    ->reject(fn (SplFileInfo $file): bool => Str::startsWith(
                        str_replace('\\', '/', $file->getPathname()),
                        str_replace('\\', '/', public_path('assets/uploads/missions')).'/',
                    ))
                    ->map(function (SplFileInfo $file) use ($source, $usedReferences): array {
                        $path = $this->normalizePublicPath($source['public'].'/'.Str::after($file->getPathname(), $source['root']));
                        $directory = str_replace('\\', '/', dirname($path));
                        $used = $source['deletable'] ? $this->isUsed($path, $usedReferences) : true;

                        return [
                            'name' => $file->getFilename(),
                            'path' => $path,
                            'url' => asset($path),
                            'directory' => $directory,
                            'directory_key' => $directory,
                            'source' => $source['label'],
                            'source_key' => $source['key'],
                            'type' => Str::upper($file->getExtension()),
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

    private function collectStrings(mixed $value, Collection $references): void
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
