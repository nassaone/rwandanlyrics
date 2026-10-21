<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/account', ['name' => 'Updated Name', 'email' => 'Updated@Example.com'])
            ->assertSessionHas('status');

        $this->assertSame('Updated Name', $user->fresh()->name);
        $this->assertSame('updated@example.com', $user->fresh()->email);
    }

    public function test_profile_update_cannot_change_role_and_email_must_be_unique(): void
    {
        $other = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/account', ['name' => 'X', 'email' => $other->email])->assertSessionHasErrors('email');

        $this->actingAs($user)->patch('/account', ['name' => 'X', 'email' => $user->email, 'role' => 'super_admin']);
        $this->assertSame(Role::User, $user->fresh()->role);
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/account/password', [
            'current_password' => 'wrong',
            'password' => 'brand-new-pass-1',
            'password_confirmation' => 'brand-new-pass-1',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($user)->put('/account/password', [
            'current_password' => 'password',
            'password' => 'brand-new-pass-1',
            'password_confirmation' => 'brand-new-pass-1',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('brand-new-pass-1', $user->fresh()->password));
    }

    public function test_account_deletion_requires_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete('/account', ['delete_password' => 'wrong'])->assertSessionHasErrors('delete_password');
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $this->actingAs($user)->delete('/account', ['delete_password' => 'password'])->assertRedirect('/');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertGuest();
    }

    public function test_the_last_super_admin_cannot_delete_their_account(): void
    {
        $super = User::factory()->role(Role::SuperAdmin)->create();

        $this->actingAs($super)->delete('/account', ['delete_password' => 'password'])->assertSessionHasErrors('delete_password');
        $this->assertDatabaseHas('users', ['id' => $super->id]);

        $second = User::factory()->role(Role::SuperAdmin)->create();
        $this->actingAs($super)->delete('/account', ['delete_password' => 'password'])->assertRedirect('/');
        $this->assertDatabaseMissing('users', ['id' => $super->id]);
        $this->assertDatabaseHas('users', ['id' => $second->id]);
    }

    public function test_mass_assignment_cannot_set_role(): void
    {
        $user = User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'secret-pass-1', 'role' => 'super_admin']);

        $this->assertSame(Role::User, $user->fresh()->role);
    }
}
