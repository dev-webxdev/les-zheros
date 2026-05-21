<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_search_filters_across_all_pages(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        User::factory()->count(15)->create();
        User::factory()->create([
            'name' => 'Joueur Cache',
            'email' => 'joueur-cache@example.test',
            'created_at' => now()->subDays(10),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.utilisateurs.index'))
            ->assertOk()
            ->assertDontSee('Joueur Cache');

        $this->actingAs($admin)
            ->get(route('admin.utilisateurs.index', ['search' => 'cache']))
            ->assertOk()
            ->assertSee('Joueur Cache');
    }
}
