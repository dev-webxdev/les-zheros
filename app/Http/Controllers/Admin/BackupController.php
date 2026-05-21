<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminActivity;
use App\Support\SiteBackupManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BackupController extends Controller
{
    public function store(SiteBackupManager $backups): RedirectResponse
    {
        try {
            $backup = $backups->create();
        } catch (Throwable $exception) {
            return $this->backToSettings('Sauvegarde impossible', $exception->getMessage(), 'error');
        }

        AdminActivity::log('settings', 'backup_created', 'Sauvegarde creee', 'Archive creee: '.basename($backup).'.');

        return $this->backToSettings('Sauvegarde creee', 'Le site a bien ete sauvegarde.', 'success');
    }

    public function download(string $backup, SiteBackupManager $backups): BinaryFileResponse
    {
        $path = $backups->pathFor($backup);

        abort_unless(is_file($path), 404);

        return response()->download($path);
    }

    public function restore(Request $request, string $backup, SiteBackupManager $backups): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['required', 'in:RESTAURER'],
        ]);

        try {
            $backups->restore($backup);
            DB::purge(config('database.default'));
            Artisan::call('migrate', ['--force' => true]);
        } catch (Throwable $exception) {
            return $this->backToSettings('Restauration impossible', $exception->getMessage(), 'error');
        }

        AdminActivity::log('settings', 'backup_restored', 'Sauvegarde restauree', 'Archive restauree: '.$backup.'.');

        return $this->backToSettings('Sauvegarde restauree', 'La base et les fichiers uploades ont ete restaures.', 'success');
    }

    public function destroy(string $backup, SiteBackupManager $backups): RedirectResponse
    {
        try {
            $backups->delete($backup);
        } catch (Throwable $exception) {
            return $this->backToSettings('Suppression impossible', $exception->getMessage(), 'error');
        }

        AdminActivity::log('settings', 'backup_deleted', 'Sauvegarde supprimee', 'Archive supprimee: '.$backup.'.');

        return $this->backToSettings('Sauvegarde supprimee', 'Le fichier de sauvegarde a ete retire.', 'success');
    }

    private function backToSettings(string $title, string $text, string $type): RedirectResponse
    {
        return redirect()->route('admin.parametres.index')->with('admin_toast', [
            'title' => $title,
            'text' => $text,
            'type' => $type,
        ]);
    }
}
