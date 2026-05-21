<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class UserAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_profile_avatar(): void
    {
        $user = User::factory()->create([
            'name' => 'Adon',
            'email' => 'adon@example.com',
            'country' => 'fr',
        ]);

        $this->actingAs($user)
            ->patch(route('profil.update'), [
                'name' => 'Adon',
                'email' => 'adon@example.com',
                'country' => 'fr',
                'avatar' => UploadedFile::fake()->image('avatar.jpg', 256, 256),
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        $this->assertStringContainsString('/assets/uploads/avatars/', $user->avatar_path);
        $this->get(route('profil'))
            ->assertOk()
            ->assertSee($user->avatar_path, false);

        $this->deleteUploadedAvatar($user->avatar_path);
    }

    public function test_user_avatar_endpoint_uploads_avatar_immediately(): void
    {
        $user = User::factory()->create([
            'name' => 'Adon',
            'email' => 'adon@example.com',
            'country' => 'fr',
        ]);

        $this->actingAs($user)
            ->postJson(route('profil.avatar.update'), [
                'avatar' => UploadedFile::fake()->image('avatar.jpg', 256, 256),
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Photo de profil enregistrée.');

        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        $this->assertStringContainsString('/assets/uploads/avatars/', $user->avatar_path);

        $this->deleteUploadedAvatar($user->avatar_path);
    }


    public function test_admin_user_list_displays_uploaded_avatar(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create([
            'name' => 'Yohan',
            'email' => 'yohan@example.com',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.utilisateurs.update', $user), [
                'name' => 'Yohan',
                'email' => 'yohan@example.com',
                'roles' => ['member'],
                'avatar' => UploadedFile::fake()->image('avatar.png', 256, 256),
            ])
            ->assertRedirect(route('admin.utilisateurs.index'));

        $user->refresh();

        $this->actingAs($admin)
            ->get(route('admin.utilisateurs.index'))
            ->assertOk()
            ->assertSee($user->avatar_path, false);

        $this->deleteUploadedAvatar($user->avatar_path);
    }

    private function deleteUploadedAvatar(?string $path): void
    {
        if (! $path) {
            return;
        }

        $relativePath = parse_url($path, PHP_URL_PATH);

        if ($relativePath) {
            File::delete(public_path(ltrim($relativePath, '/')));
        }
    }
}
