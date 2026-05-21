<?php

namespace Tests\Feature;

use App\Models\Stuff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StuffManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_trash_stuff(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.stuffs.store'), [
                'title' => 'Feu air burst',
                'dofusbook_url' => 'https://www.dofusbook.net/fr/build/test',
                'class' => 'Iop',
                'element' => 'Feu/Air',
                'mode' => 'DPS',
                'min_level' => 199,
                'max_level' => 200,
                'meta' => '3.5',
                'budget' => 'Moyen',
                'author' => 'Yohan',
                'comment' => 'Un mode hybride.',
                'published' => 'on',
            ])
            ->assertRedirect(route('admin.stuffs.index'));

        $stuff = Stuff::firstOrFail();
        $this->assertSame(['Feu', 'Air'], $stuff->elements);
        $this->assertSame('iop', $stuff->class_slug);

        $this->get(route('stuffs.index'))
            ->assertOk()
            ->assertSee('Feu air burst')
            ->assertSee('Un mode hybride.')
            ->assertSee('Meta 3.5');

        $this->actingAs($admin)
            ->patch(route('admin.stuffs.update', $stuff), [
                'title' => 'Feu air do pou',
                'dofusbook_url' => 'https://www.dofusbook.net/fr/build/test',
                'class' => 'Iop',
                'element' => 'Feu/Air/Do pou',
                'mode' => 'DPS',
                'min_level' => 200,
                'max_level' => 200,
                'comment' => 'Version modifiée.',
                'published' => 'on',
            ])
            ->assertRedirect(route('admin.stuffs.index'));

        $stuff->refresh();
        $this->assertSame('Feu air do pou', $stuff->title);
        $this->assertSame(['Feu', 'Air', 'Do pou'], $stuff->elements);

        $this->actingAs($admin)
            ->delete(route('admin.stuffs.destroy', $stuff))
            ->assertRedirect(route('admin.stuffs.index'));

        $this->assertSoftDeleted($stuff);
    }

}
