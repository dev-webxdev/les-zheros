<?php

namespace Tests\Feature;

use App\Models\Outing;
use App\Models\OutingVote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutingManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-05-21 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_create_outing_and_member_can_vote(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create(['name' => 'Adon']);
        $schedule = [
            ['date' => '2026-05-23', 'times' => ['15:00', '20:00']],
        ];

        $this->actingAs($admin)
            ->post(route('admin.sorties.store'), [
                'title' => 'Songes du week-end',
                'description' => 'On cale le meilleur départ.',
                'places' => 8,
                'close_at' => '2026-05-22T20:00',
                'schedule' => json_encode($schedule),
            ])
            ->assertRedirect(route('admin.sorties.index'));

        $outing = Outing::firstOrFail();
        $this->assertTrue($outing->is_published);
        $slotId = $outing->slotId('2026-05-23', '15:00');

        $this->get(route('sorties.index'))
            ->assertOk()
            ->assertSee('Songes du week-end')
            ->assertSee($slotId);

        $this->actingAs($member)
            ->post(route('sorties.vote', $outing), ['slot_id' => $slotId])
            ->assertRedirect(route('sorties.index'));

        $this->assertDatabaseHas('outing_votes', [
            'outing_id' => $outing->id,
            'user_id' => $member->id,
            'slot_id' => $slotId,
        ]);

        $nextSlotId = $outing->slotId('2026-05-23', '20:00');

        $this->actingAs($member)
            ->post(route('sorties.vote', $outing), ['slot_id' => $nextSlotId])
            ->assertRedirect(route('sorties.index'));

        $this->assertSame(1, OutingVote::count());
        $this->assertDatabaseHas('outing_votes', [
            'outing_id' => $outing->id,
            'user_id' => $member->id,
            'slot_id' => $nextSlotId,
        ]);

        $this->actingAs($member)
            ->delete(route('sorties.vote.cancel', $outing))
            ->assertRedirect(route('sorties.index'));

        $this->assertSame(0, OutingVote::count());
    }

    public function test_admin_can_trash_restore_and_delete_outing(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $outing = Outing::create([
            'title' => 'Sortie Frigost',
            'places' => 8,
            'schedule' => [['date' => '2026-05-23', 'times' => ['15:00']]],
            'is_published' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.sorties.destroy', $outing))
            ->assertRedirect(route('admin.sorties.index'));

        $this->assertSoftDeleted($outing);

        $this->actingAs($admin)
            ->patch(route('admin.sorties.restore', $outing->id))
            ->assertRedirect(route('admin.sorties.trash'));

        $this->assertNotSoftDeleted($outing->fresh());

        $outing->delete();

        $this->actingAs($admin)
            ->delete(route('admin.sorties.force-delete', $outing->id))
            ->assertRedirect(route('admin.sorties.trash'));

        $this->assertDatabaseMissing('outings', ['id' => $outing->id]);
    }

    public function test_admin_cannot_close_votes_after_first_slot(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.sorties.create'))
            ->post(route('admin.sorties.store'), [
                'title' => 'Sortie trop tard',
                'places' => 8,
                'close_at' => '2026-05-24T20:00',
                'schedule' => json_encode([
                    ['date' => '2026-05-23', 'times' => ['15:00', '20:00']],
                ]),
            ])
            ->assertRedirect(route('admin.sorties.create'))
            ->assertSessionHasErrors('close_at');

        $this->assertSame(0, Outing::count());
    }

    public function test_admin_can_close_votes_two_hours_before_first_slot(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.sorties.store'), [
                'title' => 'Sortie valide',
                'places' => 8,
                'close_at' => '2026-05-23T13:00',
                'schedule' => json_encode([
                    ['date' => '2026-05-23', 'times' => ['15:00', '20:00']],
                ]),
            ])
            ->assertRedirect(route('admin.sorties.index'));

        $this->assertSame(1, Outing::count());
    }

    public function test_admin_can_confirm_best_outing_slot(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $adon = User::factory()->create(['name' => 'Adon']);
        $yohan = User::factory()->create(['name' => 'Yohan']);
        $outing = Outing::create([
            'title' => 'Donjon guerre',
            'places' => 8,
            'schedule' => [
                ['date' => '2026-05-23', 'times' => ['15:00', '20:00']],
            ],
            'is_published' => true,
        ]);

        OutingVote::create([
            'outing_id' => $outing->id,
            'user_id' => $adon->id,
            'slot_id' => $outing->slotId('2026-05-23', '20:00'),
        ]);
        OutingVote::create([
            'outing_id' => $outing->id,
            'user_id' => $yohan->id,
            'slot_id' => $outing->slotId('2026-05-23', '20:00'),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.sorties.confirm', $outing), [
                'slot_id' => $outing->slotId('2026-05-23', '20:00'),
            ])
            ->assertRedirect(route('admin.sorties.index'));

        $outing->refresh();

        $this->assertSame($outing->slotId('2026-05-23', '20:00'), $outing->confirmed_slot_id);
        $this->assertNotNull($outing->confirmed_at);

        $this->get(route('admin.sorties.index'))
            ->assertOk()
            ->assertSee('Prochaine sortie validée')
            ->assertSee('Adon')
            ->assertSee('Yohan');

        $this->get(route('sorties.index'))
            ->assertOk()
            ->assertSee('Sortie validée')
            ->assertSee('Adon')
            ->assertSee('Yohan');
    }

    public function test_admin_can_confirm_a_slot_with_fewer_votes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $adon = User::factory()->create(['name' => 'Adon']);
        $yohan = User::factory()->create(['name' => 'Yohan']);
        $lina = User::factory()->create(['name' => 'Lina']);
        $outing = Outing::create([
            'title' => 'Donjon guerre',
            'places' => 8,
            'schedule' => [
                ['date' => '2026-05-23', 'times' => ['15:00', '20:00']],
            ],
            'is_published' => true,
        ]);

        OutingVote::create([
            'outing_id' => $outing->id,
            'user_id' => $adon->id,
            'slot_id' => $outing->slotId('2026-05-23', '15:00'),
        ]);
        OutingVote::create([
            'outing_id' => $outing->id,
            'user_id' => $yohan->id,
            'slot_id' => $outing->slotId('2026-05-23', '20:00'),
        ]);
        OutingVote::create([
            'outing_id' => $outing->id,
            'user_id' => $lina->id,
            'slot_id' => $outing->slotId('2026-05-23', '20:00'),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.sorties.confirm', $outing), [
                'slot_id' => $outing->slotId('2026-05-23', '15:00'),
            ])
            ->assertRedirect(route('admin.sorties.index'));

        $this->assertSame($outing->slotId('2026-05-23', '15:00'), $outing->fresh()->confirmed_slot_id);
    }

    public function test_admin_confirm_menu_only_shows_slots_with_votes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $adon = User::factory()->create(['name' => 'Adon']);
        $outing = Outing::create([
            'title' => 'Donjon guerre',
            'places' => 8,
            'schedule' => [
                ['date' => '2026-05-23', 'times' => ['15:00', '20:00']],
            ],
            'is_published' => true,
        ]);

        OutingVote::create([
            'outing_id' => $outing->id,
            'user_id' => $adon->id,
            'slot_id' => $outing->slotId('2026-05-23', '20:00'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.sorties.index'))
            ->assertOk()
            ->assertSee('20:00')
            ->assertDontSee('15:00');
    }

    public function test_admin_cannot_confirm_slot_without_votes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $adon = User::factory()->create(['name' => 'Adon']);
        $outing = Outing::create([
            'title' => 'Donjon guerre',
            'places' => 8,
            'schedule' => [
                ['date' => '2026-05-23', 'times' => ['15:00', '20:00']],
            ],
            'is_published' => true,
        ]);

        OutingVote::create([
            'outing_id' => $outing->id,
            'user_id' => $adon->id,
            'slot_id' => $outing->slotId('2026-05-23', '20:00'),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.sorties.confirm', $outing), [
                'slot_id' => $outing->slotId('2026-05-23', '15:00'),
            ])
            ->assertRedirect(route('admin.sorties.index'));

        $this->assertNull($outing->fresh()->confirmed_slot_id);
    }

    public function test_admin_can_update_outing(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $outing = Outing::create([
            'title' => 'Sortie Frigost',
            'description' => 'Ancienne description.',
            'places' => 8,
            'schedule' => [['date' => '2026-05-23', 'times' => ['15:00']]],
            'is_published' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.sorties.update', $outing), [
                'title' => 'Sortie Frigost modifiée',
                'description' => 'Nouvelle description.',
                'places' => 6,
                'close_at' => '2026-05-22T20:00',
                'schedule' => json_encode([
                    ['date' => '2026-05-24', 'times' => ['16:00', '21:00']],
                ]),
            ])
            ->assertRedirect(route('admin.sorties.index'));

        $outing->refresh();

        $this->assertSame('Sortie Frigost modifiée', $outing->title);
        $this->assertSame('Nouvelle description.', $outing->description);
        $this->assertSame(6, $outing->places);
        $this->assertSame([
            ['date' => '2026-05-24', 'times' => ['16:00', '21:00']],
        ], $outing->schedule);
        $this->assertFalse($outing->is_published);
    }
}
