<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentDashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_simple_filament_dashboard(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'is_admin' => true,
            'role' => 'admin',
            'admin_roles' => json_encode(['admin']),
            'is_approved' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin-filament')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Notes admin')
            ->assertSee('Raccourcis')
            ->assertSee('Annonce')
            ->assertSee('Mission')
            ->assertSee('Utilisateur');
    }
}
