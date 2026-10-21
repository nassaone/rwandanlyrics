<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    private function user(Role $role): User
    {
        return User::factory()->role($role)->create();
    }

    public function test_content_permissions_by_role(): void
    {
        $models = [Song::factory()->create(), Artist::factory()->create(), Album::factory()->create()];

        // role => [create, update, publish, delete]
        $matrix = [
            'user' => [false, false, false, false],
            'contributor' => [true, false, false, false],
            'editor' => [true, true, true, false],
            'admin' => [true, true, true, true],
            'super_admin' => [true, true, true, true],
        ];

        foreach ($matrix as $role => [$create, $update, $publish, $delete]) {
            $user = $this->user(Role::from($role));
            foreach ($models as $model) {
                $this->assertSame($create, $user->can('create', $model::class), "$role create ".class_basename($model));
                $this->assertSame($update, $user->can('update', $model), "$role update ".class_basename($model));
                $this->assertSame($publish, $user->can('publish', $model), "$role publish ".class_basename($model));
                $this->assertSame($delete, $user->can('delete', $model), "$role delete ".class_basename($model));
            }
        }
    }

    public function test_guests_cannot_do_anything_to_content(): void
    {
        $song = Song::factory()->create();

        $this->assertFalse(Gate::allows('publish', $song));
        $this->assertFalse(Gate::allows('create', Song::class));
    }

    public function test_admin_can_only_grant_roles_below_their_own_to_users_below_them(): void
    {
        $admin = $this->user(Role::Admin);
        $plain = $this->user(Role::User);

        $this->assertTrue($admin->can('assignRole', [$plain, Role::Contributor]));
        $this->assertTrue($admin->can('assignRole', [$plain, Role::Editor]));
        $this->assertFalse($admin->can('assignRole', [$plain, Role::Admin]), 'admin must not mint admins');
        $this->assertFalse($admin->can('assignRole', [$plain, Role::SuperAdmin]), 'admin must not mint super admins');
        $this->assertFalse($admin->can('assignRole', [$this->user(Role::Admin), Role::User]), 'admin must not demote peers');
        $this->assertFalse($admin->can('assignRole', [$this->user(Role::SuperAdmin), Role::User]));
    }

    public function test_nobody_can_change_their_own_role(): void
    {
        foreach ([Role::User, Role::Editor, Role::Admin, Role::SuperAdmin] as $role) {
            $user = $this->user($role);
            $this->assertFalse($user->can('assignRole', [$user, Role::SuperAdmin]), "{$role->value} self-promotion");
        }
    }

    public function test_editors_and_below_cannot_manage_users(): void
    {
        $target = $this->user(Role::User);

        foreach ([Role::User, Role::Contributor, Role::Editor] as $role) {
            $actor = $this->user($role);
            $this->assertFalse($actor->can('assignRole', [$target, Role::Contributor]));
            $this->assertFalse($actor->can('suspend', $target));
            $this->assertFalse($actor->can('viewAny', User::class));
        }
    }

    public function test_super_admin_can_grant_any_role(): void
    {
        $super = $this->user(Role::SuperAdmin);

        $this->assertTrue($super->can('assignRole', [$this->user(Role::User), Role::Admin]));
        $this->assertTrue($super->can('assignRole', [$this->user(Role::Admin), Role::SuperAdmin]));
    }

    public function test_suspend_and_delete_rules(): void
    {
        $admin = $this->user(Role::Admin);
        $super = $this->user(Role::SuperAdmin);

        $this->assertTrue($admin->can('suspend', $this->user(Role::Editor)));
        $this->assertFalse($admin->can('suspend', $this->user(Role::Admin)));
        $this->assertFalse($admin->can('suspend', $super));
        $this->assertFalse($admin->can('suspend', $admin));

        $this->assertTrue($super->can('delete', $this->user(Role::Admin)));
        $this->assertFalse($admin->can('delete', $this->user(Role::User)));
        $this->assertFalse($super->can('delete', $super));
    }
}
