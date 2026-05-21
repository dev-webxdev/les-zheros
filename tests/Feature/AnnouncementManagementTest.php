<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_trash_announcement(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.annonces.store'), [
                'title' => 'Sortie guilde vendredi',
                'status' => 'published',
                'tag' => 'event',
                'content' => '<p>Rendez-vous au zaap avant le départ.</p>',
            ])
            ->assertRedirect(route('admin.annonces.index'));

        $announcement = Announcement::firstOrFail();
        $this->assertSame('Sortie guilde vendredi', $announcement->title);
        $this->assertSame('published', $announcement->status);
        $this->assertNotNull($announcement->published_at);

        $this->get(route('accueil'))
            ->assertOk()
            ->assertSee('Sortie guilde vendredi')
            ->assertSee('Rendez-vous au zaap avant le départ.');

        $this->actingAs($admin)
            ->patch(route('admin.annonces.update', $announcement), [
                'title' => 'Sortie guilde samedi',
                'status' => 'draft',
                'tag' => 'info',
                'content' => '<p>Encore en brouillon.</p>',
            ])
            ->assertRedirect(route('admin.annonces.index'));

        $announcement->refresh();
        $this->assertSame('draft', $announcement->status);
        $this->assertNull($announcement->published_at);

        $this->get(route('accueil'))
            ->assertOk()
            ->assertDontSee('Sortie guilde samedi');

        $this->actingAs($admin)
            ->delete(route('admin.annonces.destroy', $announcement))
            ->assertRedirect(route('admin.annonces.index'));

        $this->assertSoftDeleted($announcement);
    }

    public function test_due_scheduled_announcement_is_visible_on_front(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.annonces.store'), [
                'title' => 'Petite sortie Guerre',
                'status' => 'scheduled',
                'tag' => 'event',
                'published_at' => now()->subMinute()->format('Y-m-d\TH:i'),
                'content' => '<p>Petit test maintenant visible.</p>',
            ])
            ->assertRedirect(route('admin.annonces.index'));

        $this->get(route('accueil'))
            ->assertOk()
            ->assertSee('Petite sortie Guerre')
            ->assertSee('Petit test');

        $this->actingAs($admin)
            ->get(route('admin.annonces.index'))
            ->assertOk()
            ->assertSee('Publié');

        $this->actingAs($admin)
            ->get(route('admin.annonces.edit', Announcement::firstOrFail()))
            ->assertOk()
            ->assertSee('value="published" selected', false);
    }

    public function test_plain_announcement_content_is_wrapped_in_paragraphs_for_modal(): void
    {
        Announcement::create([
            'title' => 'Logistique hebdo',
            'tag' => 'info',
            'content' => "Les screens doivent être déposés le soir même.\n\nMerci de prévenir si besoin.",
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get(route('accueil'))
            ->assertOk()
            ->assertSee('<p>Les screens doivent être déposés le soir même.</p><p>Merci de prévenir si besoin.</p>', false);
    }

    public function test_read_more_only_appears_when_announcement_has_more_content(): void
    {
        Announcement::create([
            'title' => 'Annonce courte',
            'tag' => 'priority',
            'content' => 'ezrez',
            'status' => 'published',
            'published_at' => now(),
        ]);
        Announcement::create([
            'title' => 'Annonce complète',
            'tag' => 'event',
            'content' => '<p>Petit résumé avec un contenu plus complet pour la modale, assez long pour dépasser le seuil automatique et afficher le bouton lire la suite sur la carte publique de l’accueil.</p>',
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ]);

        $response = $this->get(route('accueil'))->assertOk();

        $response->assertDontSee('data-news-source="news-'.Announcement::where('title', 'Annonce courte')->value('id').'"', false);
        $response->assertSee('data-news-source="news-'.Announcement::where('title', 'Annonce complète')->value('id').'"', false);
    }

    public function test_announcement_preview_decodes_editor_spaces_and_form_has_no_excerpt(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Announcement::create([
            'title' => 'Annonce espace',
            'tag' => 'priority',
            'content' => 'ezrez&nbsp;ezrez&nbsp;ezrez',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get(route('accueil'))
            ->assertOk()
            ->assertSee('ezrez ezrez ezrez')
            ->assertDontSee('&nbsp;', false);

        $this->actingAs($admin)
            ->get(route('admin.annonces.create'))
            ->assertOk()
            ->assertDontSee('announcement-excerpt');
    }
}
