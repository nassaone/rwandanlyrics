<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $extra = []): array
    {
        return array_merge([
            'name' => 'New Person',
            'email' => 'New.Person@Example.com',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
        ], $extra);
    }

    public function test_user_can_register_and_is_a_plain_user(): void
    {
        $this->post('/register', $this->payload())->assertRedirect('/');

        $user = User::where('email', 'new.person@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(Role::User, $user->role);
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame('secret-pass-1', $user->password);
    }

    public function test_registration_cannot_set_role_or_suspension(): void
    {
        $this->post('/register', $this->payload(['role' => 'super_admin', 'suspended_at' => now()->toDateTimeString()]));

        $user = User::where('email', 'new.person@example.com')->first();
        $this->assertSame(Role::User, $user->role);
        $this->assertNull($user->suspended_at);
    }

    public function test_registration_validates_input(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post('/register', $this->payload(['email' => 'taken@example.com']))->assertSessionHasErrors('email');
        $this->post('/register', $this->payload(['password' => 'short', 'password_confirmation' => 'short']))->assertSessionHasErrors('password');
        $this->post('/register', $this->payload(['password_confirmation' => 'different']))->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_registration_can_be_disabled(): void
    {
        config(['rwandanlyrics.registration_enabled' => false]);

        $this->get('/register')->assertNotFound();
        $this->post('/register', $this->payload())->assertNotFound();
        $this->assertDatabaseCount('users', 0);
        $this->get('/login')->assertOk()->assertDontSee('Register');
    }
}
