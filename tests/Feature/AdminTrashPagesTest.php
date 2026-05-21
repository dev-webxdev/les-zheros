<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTrashPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_trash_pages_render_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $routes = [
            'admin.annonces.trash',
            'admin.galerie.trash',
            'admin.missions.trash',
            'admin.roles.trash',
            'admin.sorties.trash',
            'admin.stuffs.trash',
            'admin.utilisateurs.trash',
            'admin.validations.trash',
        ];

        foreach ($routes as $route) {
            $this->actingAs($admin)
                ->get(route($route))
                ->assertOk()
                ->assertSee('Corbeille');
        }
    }

    public function test_guides_trash_keeps_its_card_layout(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.guides.trash'))
            ->assertOk()
            ->assertSee('admin-guide-list', false);
    }
}
