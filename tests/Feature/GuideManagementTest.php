<?php

namespace Tests\Feature;

use App\Models\Guide;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuideManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_guide_and_front_displays_it(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.guides.store'), [
                'title' => 'Belvédère test',
                'category' => 'donjon',
                'summary' => 'Placement et focus pour le boss.',
                'chips' => 'Placement, Boss',
                'checklist' => ['Préparer le placement', 'Annoncer le focus'],
                'sections' => [
                    ['title' => 'Lecture de la map', 'body' => "Rester propre sur les lignes.\n\nGarder le groupe stable."],
                ],
                'published' => '1',
            ])
            ->assertRedirect(route('admin.guides.index'));

        $guide = Guide::firstOrFail();

        $this->assertSame('belvedere-test', $guide->slug);
        $this->assertSame(['Placement', 'Boss'], $guide->chips);
        $this->assertSame(['Préparer le placement', 'Annoncer le focus'], $guide->checklist);

        $this->get(route('guides.index'))
            ->assertOk()
            ->assertSee('Belvédère test');

        $this->get(route('guides.show', $guide))
            ->assertOk()
            ->assertSee('Placement et focus pour le boss.');

        $this->assertSame(
            '<p>Rester propre sur les lignes.</p><p>Garder le groupe stable.</p>',
            $guide->fresh()->frontPayload()['sections'][0]['body']
        );
    }

    public function test_admin_create_guide_from_mission_prefills_mission_data(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $mission = Mission::create([
            'title' => 'Minotot',
            'category' => 'expedition',
            'guildatons' => 30,
            'activity_points' => 500,
            'image_path' => 'https://example.test/minotot.png',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.guides.create', ['mission_id' => $mission->id]))
            ->assertOk()
            ->assertSee('value="Minotot"', false)
            ->assertSee('https://example.test/minotot.png');

        $this->actingAs($admin)
            ->post(route('admin.guides.store'), [
                'mission_id' => $mission->id,
                'title' => 'Minotot',
                'category' => 'expedition',
                'summary' => 'Guide lié à la mission.',
                'cover_path' => 'https://example.test/minotot.png',
                'published' => '1',
            ])
            ->assertRedirect(route('admin.guides.index'));

        $guide = Guide::where('mission_id', $mission->id)->firstOrFail();

        $this->assertSame('https://example.test/minotot.png', $guide->cover_path);
        $this->assertSame(['Minotot'], $guide->chips);
        $this->assertNotNull($guide->published_at);
    }

    public function test_create_guide_from_mission_redirects_to_existing_guide(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $mission = Mission::create([
            'title' => 'Protozorreur',
            'category' => 'donjon',
            'guildatons' => 100,
            'activity_points' => 1000,
            'image_path' => 'https://example.test/proto.png',
        ]);
        $guide = Guide::create([
            'mission_id' => $mission->id,
            'title' => 'Protozorreur',
            'slug' => 'protozorreur',
            'category' => 'donjon',
            'chips' => ['Protozorreur'],
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.guides.create', ['mission_id' => $mission->id]))
            ->assertRedirect(route('admin.guides.edit', $guide));

        $this->actingAs($admin)
            ->get(route('admin.missions.index'))
            ->assertOk()
            ->assertSee(route('admin.guides.edit', $guide), false);
    }

    public function test_admin_missions_does_not_show_guide_action_for_dreams_anomalies_and_regulations(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $dungeon = Mission::create([
            'title' => 'Guerre',
            'category' => 'donjon',
            'guildatons' => 60,
            'activity_points' => 700,
        ]);
        Mission::create([
            'title' => 'Balades fantastiques',
            'category' => 'songe',
            'dream_type' => 'reve_3',
            'dream_floor' => 2,
            'guildatons' => 45,
            'activity_points' => 600,
        ]);
        Mission::create([
            'title' => 'Anomalie 200',
            'category' => 'anomalie',
        ]);
        Mission::create([
            'title' => 'Zarbivores',
            'category' => 'regulation',
            'guildatons' => 80,
            'activity_points' => 800,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.missions.index'))
            ->assertOk()
            ->assertSee(route('admin.guides.create', ['mission_id' => $dungeon->id]), false)
            ->assertDontSee(route('admin.guides.create', ['mission_id' => Mission::where('category', 'songe')->value('id')]), false)
            ->assertDontSee(route('admin.guides.create', ['mission_id' => Mission::where('category', 'anomalie')->value('id')]), false)
            ->assertDontSee(route('admin.guides.create', ['mission_id' => Mission::where('category', 'regulation')->value('id')]), false);
    }

    public function test_front_guides_only_lists_dungeon_and_expedition_categories(): void
    {
        Guide::create([
            'title' => 'Guide donjon',
            'slug' => 'guide-donjon',
            'category' => 'donjon',
            'is_published' => true,
            'published_at' => now(),
        ]);
        Guide::create([
            'title' => 'Guide songe',
            'slug' => 'guide-songe',
            'category' => 'songe',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get(route('guides.index'))
            ->assertOk()
            ->assertSee('Guide donjon')
            ->assertDontSee('Guide songe')
            ->assertSee('<option value="donjon">Donjon</option>', false)
            ->assertSee('<option value="expedition">Expédition</option>', false)
            ->assertDontSee('<option value="songe">Songe</option>', false);
    }

    public function test_front_guides_empty_state_says_no_guides_when_catalog_is_empty(): void
    {
        $this->get(route('guides.index'))
            ->assertOk()
            ->assertSee('Aucun guide disponible pour le moment.');
    }

    public function test_guide_without_optional_content_has_empty_front_states(): void
    {
        $guide = Guide::create([
            'title' => 'Guide vide',
            'slug' => 'guide-vide',
            'category' => 'donjon',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->assertNull($guide->frontPayload()['map']);

        $this->get(route('guides.show', $guide))
            ->assertOk()
            ->assertSee('Aucune image de placement mise pour le moment.');
    }
}
