<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\Announcement;
use App\Models\GalleryImage;
use App\Models\Guide;
use App\Models\Mission;
use App\Models\MissionValidation;
use App\Models\Outing;
use App\Models\Stuff;
use App\Models\User;
use App\Models\WordMysteryAttempt;
use App\Models\WordMysteryReward;
use App\Models\WordMysteryWord;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTrashFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_delete_pages_include_shared_confirmation_modal(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        foreach ([
            route('admin.annonces.index'),
            route('admin.annonces.trash'),
            route('admin.galerie.index'),
            route('admin.galerie.trash'),
            route('admin.guides.index'),
            route('admin.guides.trash'),
            route('admin.missions.index'),
            route('admin.missions.trash'),
            route('admin.roles.index'),
            route('admin.roles.trash'),
            route('admin.sorties.index'),
            route('admin.sorties.trash'),
            route('admin.stuffs.index'),
            route('admin.stuffs.trash'),
            route('admin.utilisateurs.index'),
            route('admin.utilisateurs.trash'),
            route('admin.validations.index'),
            route('admin.validations.trash'),
        ] as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertSee('data-confirm-form-modal', false);
        }
    }

    public function test_admin_delete_buttons_render_expected_trash_routes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $targetUser = User::factory()->create();
        $validationPlayer = User::factory()->create();

        $mission = Mission::create([
            'title' => 'Mission bouton corbeille',
            'category' => 'donjon',
            'guildatons' => 10,
            'activity_points' => 20,
        ]);
        $validationMission = Mission::create([
            'title' => 'Mission bouton validation',
            'category' => 'donjon',
            'guildatons' => 10,
            'activity_points' => 20,
        ]);
        $validation = MissionValidation::create([
            'mission_id' => $validationMission->id,
            'user_id' => $validationPlayer->id,
            'characters' => 1,
            'status' => MissionValidation::PENDING,
        ]);
        $role = AdminRole::create([
            'key' => 'route_role',
            'label' => 'Route rôle',
            'color' => 'primary',
            'permissions' => ['missions.manage'],
        ]);
        $role = AdminRole::create([
            'key' => 'bouton_role',
            'label' => 'Bouton rôle',
            'color' => 'primary',
            'permissions' => ['missions.manage'],
        ]);

        $items = [
            [
                'model' => Announcement::create([
                    'user_id' => $admin->id,
                    'title' => 'Annonce bouton corbeille',
                    'tag' => 'info',
                    'content' => 'Message',
                    'status' => 'published',
                    'published_at' => now(),
                ]),
                'index' => 'admin.annonces.index',
                'destroy' => 'admin.annonces.destroy',
                'trash' => 'admin.annonces.trash',
                'restore' => 'admin.annonces.restore',
                'force' => 'admin.annonces.force-delete',
                'empty' => 'admin.annonces.empty-trash',
            ],
            [
                'model' => GalleryImage::create([
                    'title' => 'Image bouton corbeille',
                    'image_path' => 'https://example.com/image.jpg',
                    'is_published' => true,
                    'taken_at' => now(),
                ]),
                'index' => 'admin.galerie.index',
                'destroy' => 'admin.galerie.destroy',
                'trash' => 'admin.galerie.trash',
                'restore' => 'admin.galerie.restore',
                'force' => 'admin.galerie.force-delete',
                'empty' => 'admin.galerie.empty-trash',
            ],
            [
                'model' => Guide::create([
                    'title' => 'Guide bouton corbeille',
                    'slug' => 'guide-bouton-corbeille',
                    'category' => 'donjon',
                    'summary' => 'Resume',
                    'is_published' => true,
                ]),
                'index' => 'admin.guides.index',
                'destroy' => 'admin.guides.destroy',
                'trash' => 'admin.guides.trash',
                'restore' => 'admin.guides.restore',
                'force' => 'admin.guides.force-delete',
                'empty' => 'admin.guides.empty-trash',
            ],
            [
                'model' => $mission,
                'index' => 'admin.missions.index',
                'destroy' => 'admin.missions.destroy',
                'trash' => 'admin.missions.trash',
                'restore' => 'admin.missions.restore',
                'force' => 'admin.missions.force-delete',
                'empty' => 'admin.missions.empty-trash',
            ],
            [
                'model' => $role,
                'index' => 'admin.roles.index',
                'destroy' => 'admin.roles.destroy',
                'trash' => 'admin.roles.trash',
                'restore' => 'admin.roles.restore',
                'force' => 'admin.roles.force-delete',
                'empty' => 'admin.roles.empty-trash',
                'route_key' => $role->key,
            ],
            [
                'model' => Outing::create([
                    'title' => 'Sortie bouton corbeille',
                    'description' => 'Test',
                    'places' => 8,
                    'close_at' => now()->addDay(),
                    'schedule' => [['date' => now()->addDays(2)->toDateString(), 'times' => ['20:00']]],
                    'is_published' => true,
                ]),
                'index' => 'admin.sorties.index',
                'destroy' => 'admin.sorties.destroy',
                'trash' => 'admin.sorties.trash',
                'restore' => 'admin.sorties.restore',
                'force' => 'admin.sorties.force-delete',
                'empty' => 'admin.sorties.empty-trash',
            ],
            [
                'model' => Stuff::create([
                    'title' => 'Stuff bouton corbeille',
                    'class_slug' => 'iop',
                    'class_label' => 'Iop',
                    'elements' => ['Terre'],
                    'mode' => 'DPS',
                    'min_level' => 200,
                    'max_level' => 200,
                    'dofusbook_url' => 'https://example.com/stuff',
                    'is_published' => true,
                ]),
                'index' => 'admin.stuffs.index',
                'destroy' => 'admin.stuffs.destroy',
                'trash' => 'admin.stuffs.trash',
                'restore' => 'admin.stuffs.restore',
                'force' => 'admin.stuffs.force-delete',
                'empty' => 'admin.stuffs.empty-trash',
            ],
            [
                'model' => $targetUser,
                'index' => 'admin.utilisateurs.index',
                'destroy' => 'admin.utilisateurs.destroy',
                'trash' => 'admin.utilisateurs.trash',
                'restore' => 'admin.utilisateurs.restore',
                'force' => 'admin.utilisateurs.force-delete',
                'empty' => 'admin.utilisateurs.empty-trash',
            ],
            [
                'model' => $validation,
                'index' => 'admin.validations.index',
                'destroy' => 'admin.validations.destroy',
                'trash' => 'admin.validations.trash',
                'restore' => 'admin.validations.restore',
                'force' => 'admin.validations.force-delete',
                'empty' => 'admin.validations.empty-trash',
            ],
        ];

        foreach ($items as $item) {
            $model = $item['model'];
            $destroyParameter = $item['route_key'] ?? $model;

            $this->actingAs($admin)
                ->get(route($item['index']))
                ->assertOk()
                ->assertSee(route($item['destroy'], $destroyParameter), false);

            $model->delete();

            $this->actingAs($admin)
                ->get(route($item['trash']))
                ->assertOk()
                ->assertSee(route($item['empty']), false)
                ->assertSee(route($item['restore'], $model->id), false)
                ->assertSee(route($item['force'], $model->id), false);
        }
    }

    public function test_default_roles_except_admin_can_be_trashed_and_restored(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertDontSee('action="'.route('admin.roles.destroy', 'admin').'"', false)
            ->assertSee('action="'.route('admin.roles.destroy', 'moderator').'"', false)
            ->assertSee('action="'.route('admin.roles.destroy', 'member').'"', false);

        $this->actingAs($admin)
            ->delete(route('admin.roles.destroy', 'moderator'))
            ->assertRedirect(route('admin.roles.index'));

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertDontSee('Modérateur');

        $trashedRole = AdminRole::onlyTrashed()->where('key', 'moderator')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.roles.trash'))
            ->assertOk()
            ->assertSee('Modérateur')
            ->assertSee(route('admin.roles.restore', $trashedRole->id), false);

        $this->actingAs($admin)
            ->patch(route('admin.roles.restore', $trashedRole->id))
            ->assertRedirect(route('admin.roles.trash'));

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Modérateur');
    }

    public function test_users_return_to_member_when_their_role_is_trashed(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $player = User::factory()->create();
        $role = AdminRole::create([
            'key' => 'temporaires',
            'label' => 'Temporaires',
            'color' => 'primary',
            'permissions' => ['missions.manage'],
        ]);
        $player->setAdminRoles([$role->key]);
        $player->save();

        $this->actingAs($admin)
            ->delete(route('admin.roles.destroy', $role->key))
            ->assertRedirect(route('admin.roles.index'));

        $player->refresh();

        $this->assertSame(AdminAccess::MEMBER, $player->role);
        $this->assertSame([AdminAccess::MEMBER], $player->adminRoles());
    }

    public function test_admin_trash_restore_force_delete_and_empty_trash_routes_work(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $targetUser = User::factory()->create();
        $validationPlayer = User::factory()->create();

        $mission = Mission::create([
            'title' => 'Mission corbeille',
            'category' => 'donjon',
            'guildatons' => 10,
            'activity_points' => 20,
        ]);
        $validationMission = Mission::create([
            'title' => 'Mission validation corbeille',
            'category' => 'donjon',
            'guildatons' => 10,
            'activity_points' => 20,
        ]);
        $validation = MissionValidation::create([
            'mission_id' => $validationMission->id,
            'user_id' => $validationPlayer->id,
            'characters' => 1,
            'status' => MissionValidation::PENDING,
        ]);
        $role = AdminRole::create([
            'key' => 'route_role',
            'label' => 'Route rôle',
            'color' => 'primary',
            'permissions' => ['missions.manage'],
        ]);

        $items = [
            [
                'model' => Announcement::create([
                    'user_id' => $admin->id,
                    'title' => 'Annonce corbeille',
                    'tag' => 'info',
                    'content' => 'Message',
                    'status' => 'published',
                    'published_at' => now(),
                ]),
                'destroy' => 'admin.annonces.destroy',
                'trash' => 'admin.annonces.trash',
                'restore' => 'admin.annonces.restore',
                'force' => 'admin.annonces.force-delete',
                'empty' => 'admin.annonces.empty-trash',
            ],
            [
                'model' => GalleryImage::create([
                    'title' => 'Image corbeille',
                    'image_path' => 'https://example.com/image.jpg',
                    'is_published' => true,
                    'taken_at' => now(),
                ]),
                'destroy' => 'admin.galerie.destroy',
                'trash' => 'admin.galerie.trash',
                'restore' => 'admin.galerie.restore',
                'force' => 'admin.galerie.force-delete',
                'empty' => 'admin.galerie.empty-trash',
            ],
            [
                'model' => Guide::create([
                    'title' => 'Guide corbeille',
                    'slug' => 'guide-corbeille',
                    'category' => 'donjon',
                    'summary' => 'Resume',
                    'is_published' => true,
                ]),
                'destroy' => 'admin.guides.destroy',
                'trash' => 'admin.guides.trash',
                'restore' => 'admin.guides.restore',
                'force' => 'admin.guides.force-delete',
                'empty' => 'admin.guides.empty-trash',
            ],
            [
                'model' => $mission,
                'destroy' => 'admin.missions.destroy',
                'trash' => 'admin.missions.trash',
                'restore' => 'admin.missions.restore',
                'force' => 'admin.missions.force-delete',
                'empty' => 'admin.missions.empty-trash',
            ],
            [
                'model' => $role,
                'destroy' => 'admin.roles.destroy',
                'trash' => 'admin.roles.trash',
                'restore' => 'admin.roles.restore',
                'force' => 'admin.roles.force-delete',
                'empty' => 'admin.roles.empty-trash',
                'route_key' => $role->key,
            ],
            [
                'model' => Outing::create([
                    'title' => 'Sortie corbeille',
                    'description' => 'Test',
                    'places' => 8,
                    'close_at' => now()->addDay(),
                    'schedule' => [['date' => now()->addDays(2)->toDateString(), 'times' => ['20:00']]],
                    'is_published' => true,
                ]),
                'destroy' => 'admin.sorties.destroy',
                'trash' => 'admin.sorties.trash',
                'restore' => 'admin.sorties.restore',
                'force' => 'admin.sorties.force-delete',
                'empty' => 'admin.sorties.empty-trash',
            ],
            [
                'model' => Stuff::create([
                    'title' => 'Stuff corbeille',
                    'class_slug' => 'iop',
                    'class_label' => 'Iop',
                    'elements' => ['Terre'],
                    'mode' => 'DPS',
                    'min_level' => 200,
                    'max_level' => 200,
                    'dofusbook_url' => 'https://example.com/stuff',
                    'is_published' => true,
                ]),
                'destroy' => 'admin.stuffs.destroy',
                'trash' => 'admin.stuffs.trash',
                'restore' => 'admin.stuffs.restore',
                'force' => 'admin.stuffs.force-delete',
                'empty' => 'admin.stuffs.empty-trash',
            ],
            [
                'model' => $targetUser,
                'destroy' => 'admin.utilisateurs.destroy',
                'trash' => 'admin.utilisateurs.trash',
                'restore' => 'admin.utilisateurs.restore',
                'force' => 'admin.utilisateurs.force-delete',
                'empty' => 'admin.utilisateurs.empty-trash',
            ],
            [
                'model' => $validation,
                'destroy' => 'admin.validations.destroy',
                'trash' => 'admin.validations.trash',
                'restore' => 'admin.validations.restore',
                'force' => 'admin.validations.force-delete',
                'empty' => 'admin.validations.empty-trash',
            ],
        ];

        foreach ($items as $item) {
            $model = $item['model'];
            $destroyParameter = $item['route_key'] ?? $model;

            $this->actingAs($admin)->delete(route($item['destroy'], $destroyParameter))->assertRedirect();
            $this->assertSoftDeleted($model);

            $this->actingAs($admin)->get(route($item['trash']))->assertOk();
            $this->actingAs($admin)->patch(route($item['restore'], $model->id))->assertRedirect(route($item['trash']));
            $this->assertNotSoftDeleted($model->fresh());

            $this->actingAs($admin)->delete(route($item['destroy'], $destroyParameter))->assertRedirect();
            $this->actingAs($admin)->delete(route($item['force'], $model->id))->assertRedirect(route($item['trash']));
            $this->assertDatabaseMissing($model->getTable(), ['id' => $model->id]);

            $fresh = $model->replicate();
            $fresh->save();
            $fresh->delete();
            $this->actingAs($admin)->delete(route($item['empty']))->assertRedirect(route($item['trash']));
            $this->assertDatabaseMissing($fresh->getTable(), ['id' => $fresh->id]);
        }
    }

    public function test_word_mystery_word_with_reward_cannot_be_force_deleted_from_trash(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $player = User::factory()->create();
        $word = WordMysteryWord::create([
            'word' => 'Koutoulou',
            'hint' => 'Boss',
            'difficulty' => 'hard',
            'reward_base' => 50000,
            'active_date' => today(),
            'is_active' => true,
        ]);
        $attempt = WordMysteryAttempt::create([
            'user_id' => $player->id,
            'word_id' => $word->id,
            'difficulty' => 'hard',
            'attempts_count' => 1,
            'guesses' => [['word' => 'Koutoulou', 'result' => []]],
            'has_won' => true,
            'reward_earned' => 60000,
            'played_at' => now(),
        ]);
        $reward = WordMysteryReward::create([
            'user_id' => $player->id,
            'game_attempt_id' => $attempt->id,
            'amount' => 60000,
            'status' => 'pending',
        ]);

        $word->delete();

        $this->actingAs($admin)
            ->delete(route('admin.mot-mystere.words.force-delete', $word->id))
            ->assertRedirect(route('admin.mot-mystere.trash'));

        $this->assertSoftDeleted('word_mystery_words', ['id' => $word->id]);
        $this->assertDatabaseHas('word_mystery_attempts', ['id' => $attempt->id]);
        $this->assertDatabaseHas('word_mystery_rewards', ['id' => $reward->id, 'deleted_at' => null]);
    }

    public function test_word_mystery_empty_trash_keeps_words_with_rewards(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $player = User::factory()->create();
        $protectedWord = WordMysteryWord::create([
            'word' => 'Koutoulou',
            'hint' => 'Boss',
            'difficulty' => 'hard',
            'reward_base' => 50000,
            'active_date' => today(),
            'is_active' => true,
        ]);
        $plainWord = WordMysteryWord::create([
            'word' => 'Dofus',
            'hint' => 'Oeuf',
            'difficulty' => 'easy',
            'reward_base' => 10000,
            'active_date' => today(),
            'is_active' => true,
        ]);
        $attempt = WordMysteryAttempt::create([
            'user_id' => $player->id,
            'word_id' => $protectedWord->id,
            'difficulty' => 'hard',
            'attempts_count' => 1,
            'guesses' => [['word' => 'Koutoulou', 'result' => []]],
            'has_won' => true,
            'reward_earned' => 60000,
            'played_at' => now(),
        ]);
        $reward = WordMysteryReward::create([
            'user_id' => $player->id,
            'game_attempt_id' => $attempt->id,
            'amount' => 60000,
            'status' => 'pending',
        ]);

        $protectedWord->delete();
        $plainWord->delete();

        $this->actingAs($admin)
            ->delete(route('admin.mot-mystere.empty-trash'))
            ->assertRedirect(route('admin.mot-mystere.trash'));

        $this->assertSoftDeleted('word_mystery_words', ['id' => $protectedWord->id]);
        $this->assertDatabaseMissing('word_mystery_words', ['id' => $plainWord->id]);
        $this->assertDatabaseHas('word_mystery_attempts', ['id' => $attempt->id]);
        $this->assertDatabaseHas('word_mystery_rewards', ['id' => $reward->id, 'deleted_at' => null]);
    }
}
