<?php

namespace App\Filament\Pages;

use App\Support\AdminActivity;
use App\Support\SiteBackupManager;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;
use UnitEnum;

class Backups extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Sauvegardes';

    protected static ?string $title = 'Sauvegardes';

    protected static ?string $slug = 'sauvegardes';

    protected static ?int $navigationSort = 32;

    protected string $view = 'filament.pages.backups';

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessAdminPermission('settings.backups') ?? false;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function backups(): Collection
    {
        return app(SiteBackupManager::class)->list();
    }

    public function createBackup(): void
    {
        abort_unless($this->canManageBackups(), 403);

        try {
            $path = app(SiteBackupManager::class)->create();
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Sauvegarde impossible')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        AdminActivity::log('settings', 'backup_created', 'Sauvegarde creee', 'Archive creee depuis Filament: '.basename($path).'.');

        Notification::make()
            ->title('Sauvegarde creee')
            ->body('Le site a bien ete sauvegarde.')
            ->success()
            ->send();
    }

    public function deleteBackup(string $backup): void
    {
        abort_unless($this->canManageBackups(), 403);

        try {
            app(SiteBackupManager::class)->delete($backup);
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Suppression impossible')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        AdminActivity::log('settings', 'backup_deleted', 'Sauvegarde supprimee', 'Archive supprimee depuis Filament: '.$backup.'.');

        Notification::make()
            ->title('Sauvegarde supprimee')
            ->body('Le fichier de sauvegarde a ete retire.')
            ->success()
            ->send();
    }

    public function restoreBackup(string $backup, string $confirmation): void
    {
        abort_unless($this->canManageBackups(), 403);

        if ($confirmation !== 'RESTAURER') {
            Notification::make()
                ->title('Confirmation incorrecte')
                ->body('Tape exactement RESTAURER pour confirmer la restauration.')
                ->danger()
                ->send();

            return;
        }

        $backups = app(SiteBackupManager::class);

        try {
            $this->assertRestorableBackup($backups, $backup);

            AdminActivity::log('settings', 'backup_restore_started', 'Restauration lancee', 'Archive selectionnee depuis Filament: '.$backup.'.');

            $backups->restore($backup);

            if (! app()->runningUnitTests()) {
                DB::purge(config('database.default'));
                Artisan::call('migrate', ['--force' => true]);
            }
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Restauration impossible')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        AdminActivity::log('settings', 'backup_restored', 'Sauvegarde restauree', 'Archive restauree depuis Filament: '.$backup.'.');

        Notification::make()
            ->title('Sauvegarde restauree')
            ->body('La base de donnees, les uploads et les images publiques ont ete restaures.')
            ->success()
            ->send();
    }

    public function downloadUrl(string $backup): string
    {
        return route('admin.parametres.backups.download', $backup);
    }

    public function oldSettingsUrl(): string
    {
        return route('admin.parametres.index');
    }

    private function canManageBackups(): bool
    {
        return auth()->user()?->canAccessAdminPermission('settings.backups') ?? false;
    }

    private function assertRestorableBackup(SiteBackupManager $backups, string $backup): void
    {
        $path = $backups->pathFor($backup);

        if (! is_file($path)) {
            throw new RuntimeException('Sauvegarde introuvable.');
        }

        if (pathinfo($path, PATHINFO_EXTENSION) !== 'tar') {
            throw new RuntimeException('Seules les archives .tar de sauvegarde sont autorisees.');
        }

        $realBackupsPath = realpath($backups->backupsPath());
        $realPath = realpath($path);

        if ($realBackupsPath === false || $realPath === false) {
            throw new RuntimeException('Archive de sauvegarde non autorisee.');
        }

        $normalizedBackupsPath = rtrim(str_replace('\\', '/', $realBackupsPath), '/').'/';
        $normalizedPath = str_replace('\\', '/', $realPath);

        if (! str_starts_with($normalizedPath, $normalizedBackupsPath)) {
            throw new RuntimeException('Archive de sauvegarde non autorisee.');
        }
    }
}
