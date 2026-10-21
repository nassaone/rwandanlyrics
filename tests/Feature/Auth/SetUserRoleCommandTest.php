<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetUserRoleCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_promotes_an_existing_user(): void
    {
        $user = User::factory()->create(['email' => 'boss@example.com']);

        $this->artisan('rwandanlyrics:set-role', ['email' => 'Boss@Example.com'])->assertExitCode(0);
        $this->assertSame(Role::SuperAdmin, $user->fresh()->role);

        $this->artisan('rwandanlyrics:set-role', ['email' => 'boss@example.com', 'role' => 'editor'])->assertExitCode(0);
        $this->assertSame(Role::Editor, $user->fresh()->role);
    }

    public function test_it_rejects_unknown_users_and_roles(): void
    {
        $user = User::factory()->create(['email' => 'boss@example.com']);

        $this->artisan('rwandanlyrics:set-role', ['email' => 'nobody@example.com'])->assertExitCode(1);
        $this->artisan('rwandanlyrics:set-role', ['email' => 'boss@example.com', 'role' => 'emperor'])->assertExitCode(1);
        $this->assertSame(Role::User, $user->fresh()->role);
    }
}
