<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use PharData;
use RuntimeException;

class SiteBackupManager
{
    private const MANIFEST_NAME = 'manifest.json';
    private const BACKUP_VERSION = 1;

    public function backupsPath(): string
    {
        return storage_path('app/backups');
    }

    public function list(): Collection
    {
        File::ensureDirectoryExists($this->backupsPath());

        return collect(File::glob($this->backupsPath().'/*.tar') ?: [])
            ->map(fn (string $path): array => $this->backupInfo($path))
            ->sortByDesc('created_at')
            ->values();
    }

    public function create(string $prefix = 'sauvegarde-site', int $keep = 10): string
    {
        File::ensureDirectoryExists($this->backupsPath());

        $path = $this->backupsPath().'/'.$prefix.'-'.now()->format('Ymd-His').'.tar';

        if (File::exists($path)) {
            File::delete($path);
        }

        $archive = new PharData($path);
        $archive->addFromString(self::MANIFEST_NAME, json_encode([
            'app' => 'les-zheros',
            'type' => 'site-backup',
            'version' => self::BACKUP_VERSION,
            'created_at' => now()->toIso8601String(),
            'database' => 'database/database.sqlite',
            'uploads' => 'public/assets/uploads',
            'images' => 'public/assets/img',
        ], JSON_PRETTY_PRINT));

        $database = $this->databasePath();

        if (File::exists($database)) {
            $archive->addFile($database, 'database/database.sqlite');
        }

        $this->addDirectory($archive, public_path('assets/uploads'), 'public/assets/uploads');
        $this->addDirectory($archive, public_path('assets/img'), 'public/assets/img');

        $this->prune($keep);

        return $path;
    }

    public function restore(string $backupName): void
    {
        $backupPath = $this->pathFor($backupName);

        if (! File::exists($backupPath)) {
            throw new RuntimeException('Sauvegarde introuvable.');
        }

        if (! $this->isPreRestoreBackup($backupName)) {
            $this->create('securite-avant-restauration', 10);
        }

        $extractPath = storage_path('app/backup-restore/'.pathinfo($backupName, PATHINFO_FILENAME));
        File::deleteDirectory($extractPath);
        File::ensureDirectoryExists($extractPath);

        try {
            $archive = new PharData($backupPath);
            $archive->extractTo($extractPath, null, true);

            $this->assertValidBackup($extractPath);

            $extractedDatabase = $extractPath.'/database/database.sqlite';

            if (File::exists($extractedDatabase)) {
                File::ensureDirectoryExists(dirname($this->databasePath()));
                File::copy($extractedDatabase, $this->databasePath());
            }

            $extractedUploads = $extractPath.'/public/assets/uploads';
            $extractedImages = $extractPath.'/public/assets/img';
            $uploadsPath = public_path('assets/uploads');
            $imagesPath = public_path('assets/img');

            File::deleteDirectory($uploadsPath);
            File::deleteDirectory($imagesPath);

            if (File::isDirectory($extractedUploads)) {
                File::copyDirectory($extractedUploads, $uploadsPath);
            } else {
                File::ensureDirectoryExists($uploadsPath);
            }

            if (File::isDirectory($extractedImages)) {
                File::copyDirectory($extractedImages, $imagesPath);
            } else {
                File::ensureDirectoryExists($imagesPath);
            }
        } finally {
            File::deleteDirectory($extractPath);
        }
    }

    public function delete(string $backupName): void
    {
        $path = $this->pathFor($backupName);

        if (File::exists($path)) {
            File::delete($path);
        }
    }

    public function pathFor(string $backupName): string
    {
        $safeName = basename($backupName);

        if ($safeName !== $backupName || ! str_ends_with($safeName, '.tar')) {
            throw new RuntimeException('Nom de sauvegarde invalide.');
        }

        return $this->backupsPath().'/'.$safeName;
    }

    public function prune(int $keep = 10): void
    {
        $this->list()
            ->skip($keep)
            ->each(fn (array $backup): bool => File::delete($backup['path']));
    }

    private function databasePath(): string
    {
        return (string) config('database.connections.sqlite.database', database_path('database.sqlite'));
    }

    private function backupInfo(string $path): array
    {
        $name = basename($path);
        $createdAt = File::lastModified($path);

        return [
            'name' => $name,
            'display_name' => $this->displayName($name, $createdAt),
            'type_label' => $this->typeLabel($name),
            'path' => $path,
            'size' => File::size($path),
            'created_at' => $createdAt,
        ];
    }

    private function displayName(string $name, int $createdAt): string
    {
        $date = date('d/m/Y H:i', $createdAt);

        if ($this->isPreRestoreBackup($name)) {
            return 'Sécurité avant restauration du '.$date;
        }

        return 'Sauvegarde du site du '.$date;
    }

    private function typeLabel(string $name): string
    {
        if ($this->isPreRestoreBackup($name)) {
            return 'Créée automatiquement avant une restauration';
        }

        return 'Sauvegarde manuelle ou automatique';
    }

    private function isPreRestoreBackup(string $name): bool
    {
        return str_starts_with($name, 'pre-restore-')
            || str_starts_with($name, 'securite-avant-restauration-');
    }

    private function addDirectory(PharData $archive, string $source, string $archiveRoot): void
    {
        if (! File::isDirectory($source)) {
            $archive->addEmptyDir($archiveRoot);

            return;
        }

        $archive->addEmptyDir($archiveRoot);

        foreach (File::allFiles($source) as $file) {
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());
            $archive->addFile($file->getPathname(), $archiveRoot.'/'.$relativePath);
        }
    }

    private function assertValidBackup(string $extractPath): void
    {
        $manifestPath = $extractPath.'/'.self::MANIFEST_NAME;

        if (! File::exists($manifestPath)) {
            throw new RuntimeException('Cette archive ne contient pas de manifeste de sauvegarde.');
        }

        $manifest = json_decode((string) File::get($manifestPath), true);

        if (
            ! is_array($manifest)
            || ($manifest['app'] ?? null) !== 'les-zheros'
            || ($manifest['type'] ?? null) !== 'site-backup'
            || (int) ($manifest['version'] ?? 0) !== self::BACKUP_VERSION
        ) {
            throw new RuntimeException('Cette archive ne correspond pas a une sauvegarde compatible.');
        }
    }
}
