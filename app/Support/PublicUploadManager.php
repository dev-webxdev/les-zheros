<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class PublicUploadManager
{
    public static function store(
        UploadedFile $file,
        string $directory,
        string $prefix,
        bool $includeOriginalName = false,
        ?string $name = null,
        bool $cleanNameOnly = false
    ): string {
        $directory = trim($directory, '/');
        $target = public_path('assets/uploads/'.$directory);

        File::ensureDirectoryExists($target, 0755, true);

        $extension = $file->getClientOriginalExtension()
            ?: $file->extension()
            ?: 'png';

        $baseName = Str::slug($name ?: '');

        if ($cleanNameOnly) {
            $filename = self::uniqueFilename($target, $baseName ?: $prefix, $extension);
        } else {
            $nameParts = array_filter([
                $baseName ?: ($includeOriginalName ? Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) : $prefix),
                now()->format('Ymd_His'),
                bin2hex(random_bytes(4)),
            ]);

            $filename = implode('_', $nameParts).'.'.$extension;
        }

        try {
            $file->move($target, $filename);
        } catch (FileException) {
            File::copy($file->getRealPath(), $target.DIRECTORY_SEPARATOR.$filename);
        }

        return asset('assets/uploads/'.$directory.'/'.$filename);
    }

    private static function uniqueFilename(string $target, string $baseName, string $extension, int $startIndex = 1): string
    {
        $baseName = Str::slug($baseName) ?: 'image';
        $index = max(1, $startIndex);
        $filename = $index === 1 ? $baseName.'.'.$extension : $baseName.'-'.$index.'.'.$extension;

        while (File::exists($target.'/'.$filename)) {
            $index++;
            $filename = $baseName.'-'.$index.'.'.$extension;
        }

        return $filename;
    }
}