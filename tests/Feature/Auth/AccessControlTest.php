<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public static function matrix(): array
    {
        $levels = ['user' => 1, 'contributor' => 2, 'editor' => 3, 'admin' => 4, 'super_admin' => 5];
        $areas = ['/admin' => 3, '/contributor' => 2, '/account' => 1];
        $cases = [];

        foreach ($areas as $path => $minimum) {
            $cases["guest $path"] = [null, $path, 302];
            foreach ($levels as $role => $level) {
                $cases["$role $path"] = [$role, $path, $level >= $minimum ? 200 : 403];
            }
        }

        return $cases;
    }

    #[DataProvider('matrix')]
    public function test_route_access_by_role(?string $role, string $path, int $expected): void
    {
        $response = $role
            ? $this->actingAs(User::factory()->role(Role::from($role))->create())->get($path)
            : $this->get($path);

        $response->assertStatus($expected);

        if ($expected === 302) {
            $response->assertRedirect(route('login'));
        }
    }

    public function test_logged_in_users_are_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect();
    }
}
