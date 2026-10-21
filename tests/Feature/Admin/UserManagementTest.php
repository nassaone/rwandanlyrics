<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, ?string $email = null): User
    {
        $address = $email ?? $role.'@example.test';

        return User::where('email', $address)->first() ?? User::forceCreate([
            'name' => ucfirst($role), 'email' => $address,
            'password' => bcrypt('password-123'), 'role' => $role,
        ]);
    }

    public function test_editors_and_guests_cannot_reach_user_management(): void
    {
        $target = $this->user('user', 'target@example.test');

        $this->get(route('admin.users.index'))->assertRedirect();
        $this->actingAs($this->user('editor'))->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($this->user('editor'))->get(route('admin.users.edit', $target))->assertForbidden();
    }

    public function test_admin_can_list_and_filter_users(): void
    {
        $this->user('user', 'findme@example.test');

        $this->actingAs($this->user('admin'))->get(route('admin.users.index', ['q' => 'findme']))
            ->assertOk()->assertSee('findme@example.test');
    }

    public function test_admin_can_change_a_role_and_it_is_audited(): void
    {
        $target = $this->user('user', 'target@example.test');

        $this->actingAs($this->user('admin'))
            ->patch(route('admin.users.role', $target), ['role' => 'editor'])
            ->assertSessionHasNoErrors();

        $this->assertSame('editor', $target->fresh()->role->value);
        $this->assertSame(1, AuditLog::count());
    }

    public function test_invalid_roles_are_rejected(): void
    {
        $target = $this->user('user', 'target@example.test');

        $this->actingAs($this->user('admin'))
            ->patch(route('admin.users.role', $target), ['role' => 'emperor'])
            ->assertSessionHasErrors('role');
        $this->assertSame('user', $target->fresh()->role->value);
    }

    public function test_suspending_blocks_access_and_unsuspending_restores_it(): void
    {
        $target = $this->user('editor', 'target@example.test');
        $admin = $this->user('admin');

        $this->actingAs($admin)->post(route('admin.users.suspend', $target))->assertSessionHasNoErrors();
        $this->assertNotNull($target->fresh()->suspended_at);

        $this->actingAs($target->fresh())->get(route('admin.dashboard'))->assertRedirect();

        $this->actingAs($admin)->post(route('admin.users.unsuspend', $target))->assertSessionHasNoErrors();
        $this->assertNull($target->fresh()->suspended_at);
    }

    public function test_admin_cannot_suspend_or_delete_themselves(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->post(route('admin.users.suspend', $admin))->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertForbidden();
        $this->assertNotNull(User::find($admin->id));
    }

    public function test_admin_can_delete_a_lower_user(): void
    {
        $target = $this->user('user', 'target@example.test');

        $this->actingAs($this->user('admin'))->delete(route('admin.users.destroy', $target))->assertForbidden();
        $this->assertNotNull(User::find($target->id));

        $this->actingAs($this->user('super_admin'))->delete(route('admin.users.destroy', $target))->assertRedirect();
        $this->assertNull(User::find($target->id));
    }
}
