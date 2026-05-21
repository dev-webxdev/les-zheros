<?php

namespace Tests\Feature;

use App\Models\GalleryImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GalleryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        collect(File::glob(public_path('assets/uploads/gallery/gallery_*')) ?: [])
            ->each(fn (string $path) => File::delete($path));

        parent::tearDown();
    }

    public function test_gallery_create_form_prefills_today_and_card_truncates_description(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $longDescription = str_repeat('a', 260);

        $this->actingAs($admin)
            ->get(route('admin.galerie.create'))
            ->assertOk()
            ->assertSee('value="'.today()->toDateString().'"', false)
            ->assertSee('Aucune image')
            ->assertDontSee('required data-gallery-file', false);

        $this->actingAs($admin)
            ->post(route('admin.galerie.store'), [
                'title' => 'Sortie bavarde',
                'description' => $longDescription,
                'image' => UploadedFile::fake()->image('sortie.jpg', 800, 500),
                'taken_at' => today()->toDateString(),
                'published' => 'on',
            ])
            ->assertRedirect(route('admin.galerie.index'));

        $this->actingAs($admin)
            ->get(route('admin.galerie.index'))
            ->assertOk()
            ->assertSee(Str::limit($longDescription, 220));
    }

    public function test_admin_can_create_gallery_image_from_url_without_upload(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.galerie.store'), [
                'title' => 'Image distante',
                'description' => 'Ajoutée sans upload.',
                'image_url' => 'https://example.com/image.jpg',
                'taken_at' => today()->toDateString(),
                'published' => 'on',
            ])
            ->assertRedirect(route('admin.galerie.index'));

        $this->assertDatabaseHas('gallery_images', [
            'title' => 'Image distante',
            'image_path' => 'https://example.com/image.jpg',
        ]);
    }

    public function test_front_gallery_shows_read_more_only_after_220_characters(): void
    {
        GalleryImage::create([
            'title' => 'Petit souvenir',
            'description' => 'Description courte.',
            'image_path' => 'https://example.com/short.jpg',
            'is_published' => true,
            'taken_at' => today(),
        ]);
        $longDescription = str_repeat('Long texte ', 30);
        GalleryImage::create([
            'title' => 'Grand souvenir',
            'description' => $longDescription,
            'image_path' => 'https://example.com/long.jpg',
            'is_published' => true,
            'taken_at' => today(),
        ]);

        $this->get(route('galerie'))
            ->assertOk()
            ->assertSee('Description courte.')
            ->assertSee('Lire la suite')
            ->assertSee(\Illuminate\Support\Str::limit($longDescription, 220))
            ->assertSee('data-gallery-description="'.$longDescription.'"', false);
    }

    public function test_admin_can_create_update_and_trash_gallery_image(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.galerie.store'), [
                'title' => 'Sortie Frigost',
                'description' => 'Souvenir de la sortie guilde.',
                'image' => UploadedFile::fake()->image('sortie.jpg', 800, 500),
                'taken_at' => '2026-05-19',
                'published' => 'on',
            ])
            ->assertRedirect(route('admin.galerie.index'));

        $image = GalleryImage::firstOrFail();
        $this->assertSame('Sortie Frigost', $image->title);
        $this->assertTrue($image->is_published);

        $this->get(route('galerie'))
            ->assertOk()
            ->assertSee('Sortie Frigost')
            ->assertSee('Souvenir de la sortie guilde.');

        $this->actingAs($admin)
            ->patch(route('admin.galerie.update', $image), [
                'title' => 'Sortie Frigost privée',
                'description' => 'En attente de tri.',
                'taken_at' => '2026-05-20',
            ])
            ->assertRedirect(route('admin.galerie.index'));

        $image->refresh();
        $this->assertFalse($image->is_published);

        $this->get(route('galerie'))
            ->assertOk()
            ->assertDontSee('Sortie Frigost privée');

        $this->actingAs($admin)
            ->delete(route('admin.galerie.destroy', $image))
            ->assertRedirect(route('admin.galerie.index'));

        $this->assertSoftDeleted($image);
    }
}
