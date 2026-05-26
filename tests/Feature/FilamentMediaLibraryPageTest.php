<?php

namespace Tests\Feature;

use App\Filament\Pages\MediaLibrary;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentMediaLibraryPageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path('assets/uploads/test-filament-media'));
        File::delete(public_path('assets/uploads/missions/hidden-from-main-library.png'));

        parent::tearDown();
    }

    public function test_filament_media_library_displays_uploaded_images(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->putFakeImage('assets/uploads/test-filament-media/library.png');
        $this->putFakeImage('assets/uploads/missions/hidden-from-main-library.png');

        $this->actingAs($admin)
            ->get('/admin-filament/mediatheque')
            ->assertOk()
            ->assertSee('library.png')
            ->assertSee('assets/uploads/test-filament-media/library.png')
            ->assertDontSee('hidden-from-main-library.png')
            ->assertSee('Inutilisee');
    }

    public function test_filament_media_library_is_paginated_by_twelve(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        foreach (range(1, 13) as $number) {
            $this->putFakeImage('assets/uploads/test-filament-media/page-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT).'.png');
        }

        $this->actingAs($admin);

        Livewire::test(MediaLibrary::class)
            ->set('search', 'page-')
            ->assertSee('page-01.png')
            ->assertSee('page-12.png')
            ->assertDontSee('page-13.png');
    }

    public function test_filament_media_library_reuses_media_permission(): void
    {
        AdminRole::create([
            'key' => 'filament_media_manager',
            'label' => 'Gestion media Filament',
            'color' => 'primary',
            'permissions' => ['media.manage'],
        ]);

        $user = User::factory()->create();
        $user->setAdminRoles(['filament_media_manager']);
        $user->save();
        $this->putFakeImage('assets/uploads/test-filament-media/custom.png');

        $this->actingAs($user);

        Livewire::test(MediaLibrary::class)
            ->assertSee('custom.png')
            ->assertSee('Inutilisee');
    }

    public function test_filament_media_library_can_delete_unused_image(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $path = 'assets/uploads/test-filament-media/unused.png';
        $this->putFakeImage($path);

        $this->actingAs($admin);

        Livewire::test(MediaLibrary::class)
            ->call('deleteMedia', $path);

        $this->assertFileDoesNotExist(public_path($path));
        $this->assertDatabaseHas('admin_activity_logs', [
            'area' => 'media',
            'action' => 'deleted',
            'description' => $path,
        ]);
    }

    public function test_filament_media_library_cannot_delete_used_image(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $path = 'assets/uploads/test-filament-media/avatar.png';
        $this->putFakeImage($path);
        User::factory()->create(['avatar_path' => asset($path)]);

        $this->actingAs($admin);

        Livewire::test(MediaLibrary::class)
            ->call('deleteMedia', $path);

        $this->assertFileExists(public_path($path));
        $this->assertDatabaseMissing('admin_activity_logs', [
            'area' => 'media',
            'action' => 'deleted',
            'description' => $path,
        ]);
    }

    private function putFakeImage(string $path): void
    {
        File::ensureDirectoryExists(dirname(public_path($path)));
        File::put(public_path($path), base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));
    }
}
