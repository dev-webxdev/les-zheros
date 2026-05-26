<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\Mission;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminMediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path('assets/uploads/test-media'));
        collect(File::glob(public_path('assets/uploads/missions/chaloeil*')) ?: [])->each(fn (string $path) => File::delete($path));
        collect(File::glob(public_path('assets/uploads/missions/merkator*')) ?: [])->each(fn (string $path) => File::delete($path));

        parent::tearDown();
    }

    public function test_admin_can_view_uploaded_images_with_sizes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->putFakeImage('assets/uploads/test-media/library.png');

        $this->actingAs($admin)
            ->get(route('admin.mediatheque.index', ['search' => 'library.png']))
            ->assertOk()
            ->assertSee('library.png')
            ->assertSee('Uploads')
            ->assertDontSee('assets/img/card-mission/type.png');
    }

    public function test_moderator_cannot_access_media_library_by_default(): void
    {
        $moderator = User::factory()->create();
        $moderator->setAdminRoles([AdminAccess::MODERATOR]);
        $moderator->save();

        $this->actingAs($moderator)
            ->get(route('admin.mediatheque.index'))
            ->assertForbidden();
    }

    public function test_custom_role_can_access_media_library(): void
    {
        AdminRole::create([
            'key' => 'media_manager',
            'label' => 'Gestion media',
            'color' => 'primary',
            'permissions' => ['media.manage'],
        ]);
        $user = User::factory()->create();
        $user->setAdminRoles(['media_manager']);
        $user->save();
        $this->putFakeImage('assets/uploads/test-media/custom.png');

        $this->actingAs($user)
            ->get(route('admin.mediatheque.index', ['search' => 'custom.png']))
            ->assertOk()
            ->assertSee('custom.png');
    }

    public function test_admin_can_delete_unused_uploaded_image(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $path = 'assets/uploads/test-media/unused.png';
        $this->putFakeImage($path);

        $this->actingAs($admin)
            ->delete(route('admin.mediatheque.destroy'), ['path' => $path])
            ->assertRedirect(route('admin.mediatheque.index'));

        $this->assertFileDoesNotExist(public_path($path));
    }

    public function test_used_uploaded_image_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $path = 'assets/uploads/test-media/avatar.png';
        $this->putFakeImage($path);
        User::factory()->create(['avatar_path' => asset($path)]);

        $this->actingAs($admin)
            ->delete(route('admin.mediatheque.destroy'), ['path' => $path])
            ->assertRedirect();

        $this->assertFileExists(public_path($path));
    }

    public function test_media_library_is_paginated_by_sixteen(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        foreach (range(1, 17) as $number) {
            $this->putFakeImage('assets/uploads/test-media/media-page-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT).'.png');
        }

        $this->actingAs($admin)
            ->get(route('admin.mediatheque.index', ['search' => 'media-page-']))
            ->assertOk()
            ->assertSee('media-page-01.png')
            ->assertSee('media-page-16.png')
            ->assertDontSee('media-page-17.png');

        $this->actingAs($admin)
            ->get(route('admin.mediatheque.index', ['search' => 'media-page-', 'page' => 2]))
            ->assertOk()
            ->assertSee('media-page-17.png');
    }

    public function test_media_picker_returns_uploaded_images_as_json(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->putFakeImage('assets/uploads/test-media/chaloeil-reference.png');

        $this->actingAs($admin)
            ->getJson(route('admin.mediatheque.images', ['search' => 'chaloeil']))
            ->assertOk()
            ->assertJsonPath('images.0.name', 'chaloeil-reference.png');
    }

    public function test_media_picker_can_be_limited_to_mission_uploads(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->putFakeImage('assets/uploads/missions/chaloeil-mission.png');
        $this->putFakeImage('assets/uploads/gallery/chaloeil-gallery.png');

        $this->actingAs($admin)
            ->getJson(route('admin.mediatheque.images', [
                'directory' => 'missions',
                'search' => 'chaloeil',
            ]))
            ->assertOk()
            ->assertSee('chaloeil-mission.png')
            ->assertDontSee('chaloeil-gallery.png');

        File::delete(public_path('assets/uploads/gallery/chaloeil-gallery.png'));
    }

    public function test_media_library_hides_mission_uploads_from_main_listing(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->putFakeImage('assets/uploads/missions/chaloeil-mission.png');
        $this->putFakeImage('assets/uploads/test-media/chaloeil-reference.png');

        $this->actingAs($admin)
            ->get(route('admin.mediatheque.index', ['search' => 'chaloeil']))
            ->assertOk()
            ->assertSee('chaloeil-reference.png')
            ->assertDontSee('chaloeil-mission.png');
    }

    public function test_mission_uploaded_image_is_named_from_mission_title(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.missions.store'), [
                'title' => 'Chaloeil',
                'category' => 'donjon',
                'guildatons' => 100,
                'activity_points' => 10,
                'image_mode' => 'upload',
                'image_files' => [
                    UploadedFile::fake()->image('capture.png'),
                ],
            ])
            ->assertRedirect(route('admin.missions.index'));

        $mission = Mission::where('title', 'Chaloeil')->firstOrFail();

        $this->assertStringContainsString('/assets/uploads/missions/chaloeil-donjon.', $mission->image_path);
        $this->assertFileExists(public_path(parse_url($mission->image_path, PHP_URL_PATH)));
    }

    public function test_mission_uploaded_image_gets_incremented_when_name_already_exists(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.missions.store'), [
                'title' => 'Merkator',
                'category' => 'donjon',
                'guildatons' => 100,
                'activity_points' => 10,
                'image_mode' => 'upload',
                'image_files' => [
                    UploadedFile::fake()->image('premiere.png'),
                ],
            ])
            ->assertRedirect(route('admin.missions.index'));

        $this->actingAs($admin)
            ->post(route('admin.missions.store'), [
                'title' => 'Merkator',
                'category' => 'donjon',
                'guildatons' => 100,
                'activity_points' => 10,
                'image_mode' => 'upload',
                'image_files' => [
                    UploadedFile::fake()->image('capture.png'),
                ],
            ])
            ->assertRedirect(route('admin.missions.index'));

        $mission = Mission::where('title', 'Merkator')->latest('id')->firstOrFail();

        $this->assertStringContainsString('/assets/uploads/missions/merkator-donjon-2.', $mission->image_path);
    }

    public function test_mission_can_reuse_existing_uploaded_image_from_media_library(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $path = 'assets/uploads/test-media/chaloeil-existing.png';
        $this->putFakeImage($path);

        $this->actingAs($admin)
            ->post(route('admin.missions.store'), [
                'title' => 'Chaloeil reutilise',
                'category' => 'donjon',
                'guildatons' => 100,
                'activity_points' => 10,
                'image_mode' => 'upload',
                'selected_image' => asset($path),
            ])
            ->assertRedirect(route('admin.missions.index'));

        $this->assertDatabaseHas('missions', [
            'title' => 'Chaloeil reutilise',
            'image_path' => asset($path),
        ]);
    }

    private function putFakeImage(string $path): void
    {
        File::ensureDirectoryExists(dirname(public_path($path)));
        File::put(public_path($path), base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));
    }
}
