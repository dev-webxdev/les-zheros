<?php

namespace Tests\Feature;

use App\Filament\Pages\Backups;
use App\Models\AdminRole;
use App\Models\User;
use App\Support\SiteBackupManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class FilamentBackupPageTest extends TestCase
{
    use RefreshDatabase;

    private string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupPath = storage_path('framework/testing/backups-'.uniqid());
        File::ensureDirectoryExists($this->backupPath);

        $this->app->singleton(SiteBackupManager::class, fn (): SiteBackupManager => new class($this->backupPath) extends SiteBackupManager
        {
            public int $restoreCalls = 0;

            /**
             * @var array<int, string>
             */
            public array $restoredBackups = [];

            public function __construct(private readonly string $path) {}

            public function backupsPath(): string
            {
                return $this->path;
            }

            public function list(): Collection
            {
                File::ensureDirectoryExists($this->backupsPath());

                return collect(File::glob($this->backupsPath().'/*.tar') ?: [])
                    ->map(fn (string $path): array => [
                        'name' => basename($path),
                        'display_name' => 'Sauvegarde test '.basename($path),
                        'type_label' => 'Sauvegarde manuelle ou automatique',
                        'path' => $path,
                        'size' => File::size($path),
                        'created_at' => File::lastModified($path),
                    ])
                    ->sortByDesc('created_at')
                    ->values();
            }

            public function create(string $prefix = 'sauvegarde-site', int $keep = 10): string
            {
                File::ensureDirectoryExists($this->backupsPath());

                $path = $this->backupsPath().'/'.$prefix.'-test.tar';
                File::put($path, 'backup');

                return $path;
            }

            public function restore(string $backupName): void
            {
                $path = $this->pathFor($backupName);

                if (! File::exists($path)) {
                    throw new RuntimeException('Sauvegarde introuvable.');
                }

                $this->create('securite-avant-restauration', 10);
                $this->restoreCalls++;
                $this->restoredBackups[] = $backupName;
            }

            public function pathFor(string $backupName): string
            {
                $safeName = basename($backupName);

                if ($safeName !== $backupName || ! str_ends_with($safeName, '.tar')) {
                    throw new RuntimeException('Nom de sauvegarde invalide.');
                }

                return $this->backupsPath().'/'.$safeName;
            }
        });
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->backupPath);

        parent::tearDown();
    }

    public function test_user_with_backup_permission_can_access_filament_backup_page(): void
    {
        $this->actingAs($this->backupUser())
            ->get(Backups::getUrl())
            ->assertOk()
            ->assertSee('Sauvegardes')
            ->assertSee('Restauration complete avec double confirmation')
            ->assertSee('Cette action remplace la base de donnees, les uploads et les images publiques.');
    }

    public function test_user_without_backup_permission_is_refused(): void
    {
        AdminRole::create([
            'key' => 'points_manager',
            'label' => 'Gestion points',
            'color' => 'primary',
            'permissions' => ['settings.points'],
        ]);

        $user = User::factory()->create();
        $user->setAdminRoles(['points_manager']);
        $user->save();

        $this->actingAs($user)
            ->get(Backups::getUrl())
            ->assertForbidden();
    }

    public function test_filament_backup_page_lists_existing_archives(): void
    {
        File::put($this->backupPath.'/sauvegarde-site-test.tar', 'backup');

        $this->actingAs($this->backupUser());

        Livewire::test(Backups::class)
            ->assertSee('Sauvegarde test sauvegarde-site-test.tar')
            ->assertSee('sauvegarde-site-test.tar')
            ->assertSee('RESTAURER');
    }

    public function test_filament_backup_page_can_create_backup(): void
    {
        $this->actingAs($this->backupUser());

        Livewire::test(Backups::class)
            ->call('createBackup');

        $this->assertFileExists($this->backupPath.'/sauvegarde-site-test.tar');
        $this->assertDatabaseHas('admin_activity_logs', [
            'area' => 'settings',
            'action' => 'backup_created',
        ]);
    }

    public function test_filament_backup_delete_keeps_path_traversal_protection(): void
    {
        File::put($this->backupPath.'/sauvegarde-site-test.tar', 'backup');

        $this->actingAs($this->backupUser());

        Livewire::test(Backups::class)
            ->call('deleteBackup', '../sauvegarde-site-test.tar');

        $this->assertFileExists($this->backupPath.'/sauvegarde-site-test.tar');
        $this->assertDatabaseMissing('admin_activity_logs', [
            'area' => 'settings',
            'action' => 'backup_deleted',
        ]);
    }

    public function test_filament_backup_page_can_delete_backup(): void
    {
        File::put($this->backupPath.'/sauvegarde-site-test.tar', 'backup');

        $this->actingAs($this->backupUser());

        Livewire::test(Backups::class)
            ->call('deleteBackup', 'sauvegarde-site-test.tar');

        $this->assertFileDoesNotExist($this->backupPath.'/sauvegarde-site-test.tar');
        $this->assertDatabaseHas('admin_activity_logs', [
            'area' => 'settings',
            'action' => 'backup_deleted',
        ]);
    }

    public function test_filament_backup_restore_is_refused_without_exact_confirmation(): void
    {
        File::put($this->backupPath.'/sauvegarde-site-test.tar', 'backup');

        $this->actingAs($this->backupUser());

        $manager = app(SiteBackupManager::class);

        Livewire::test(Backups::class)
            ->call('restoreBackup', 'sauvegarde-site-test.tar', 'restaurer');

        $this->assertSame(0, $manager->restoreCalls);
        $this->assertDatabaseMissing('admin_activity_logs', [
            'area' => 'settings',
            'action' => 'backup_restored',
        ]);
    }

    public function test_filament_backup_restore_is_refused_with_invalid_filename(): void
    {
        File::put($this->backupPath.'/sauvegarde-site-test.tar', 'backup');

        $this->actingAs($this->backupUser());

        $manager = app(SiteBackupManager::class);

        Livewire::test(Backups::class)
            ->call('restoreBackup', '../sauvegarde-site-test.tar', 'RESTAURER');

        $this->assertSame(0, $manager->restoreCalls);
        $this->assertFileExists($this->backupPath.'/sauvegarde-site-test.tar');
        $this->assertDatabaseMissing('admin_activity_logs', [
            'area' => 'settings',
            'action' => 'backup_restored',
        ]);
    }

    public function test_filament_backup_restore_creates_safety_backup_before_restore(): void
    {
        File::put($this->backupPath.'/sauvegarde-site-test.tar', 'backup');

        $this->actingAs($this->backupUser());

        $manager = app(SiteBackupManager::class);

        Livewire::test(Backups::class)
            ->call('restoreBackup', 'sauvegarde-site-test.tar', 'RESTAURER');

        $this->assertSame(1, $manager->restoreCalls);
        $this->assertSame(['sauvegarde-site-test.tar'], $manager->restoredBackups);
        $this->assertFileExists($this->backupPath.'/securite-avant-restauration-test.tar');
        $this->assertDatabaseHas('admin_activity_logs', [
            'area' => 'settings',
            'action' => 'backup_restore_started',
        ]);
        $this->assertDatabaseHas('admin_activity_logs', [
            'area' => 'settings',
            'action' => 'backup_restored',
        ]);
    }

    public function test_filament_backup_page_has_no_bulk_restore_action(): void
    {
        $this->actingAs($this->backupUser());

        $this->assertFalse(method_exists(Backups::class, 'restoreSelectedBackups'));
        $this->assertFalse(method_exists(Backups::class, 'bulkRestore'));

        Livewire::test(Backups::class)
            ->assertDontSee('Restaurer la selection')
            ->assertDontSee('Restaurer la selection des sauvegardes');
    }

    private function backupUser(): User
    {
        AdminRole::firstOrCreate(
            ['key' => 'web_developer'],
            [
                'label' => 'Developpeur web',
                'color' => 'teal',
                'permissions' => ['settings.backups'],
            ],
        );

        $user = User::factory()->create();
        $user->setAdminRoles(['web_developer']);
        $user->save();

        return $user;
    }
}
