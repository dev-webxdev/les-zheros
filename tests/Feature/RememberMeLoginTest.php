<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RememberMeLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_remember_me_sets_a_30_day_recaller_cookie(): void
    {
        $user = User::factory()->create([
            'email' => 'remember@example.com',
            'is_approved' => true,
        ]);

        $response = $this->post(route('connexion.store'), [
            'email' => 'remember@example.com',
            'password' => 'password',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('profil'));

        $recallerName = Auth::guard()->getRecallerName();
        $recallerCookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === $recallerName);

        $this->assertNotNull($recallerCookie);
        $this->assertSame(43200, config('auth.guards.web.remember'));
        $this->assertGreaterThanOrEqual(now()->addDays(29)->timestamp, $recallerCookie->getExpiresTime());
        $this->assertNotEmpty($user->fresh()->remember_token);
    }
}
