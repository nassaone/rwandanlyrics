<?php

namespace Tests\Feature\Settings;

use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;
use App\Support\SettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class SettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    private function as(Role $role): User
    {
        $user = User::factory()->create(['role' => $role->value]);
        $this->actingAs($user);

        return $user;
    }

    public function test_only_super_admin_can_open_settings(): void
    {
        $this->get('/admin/settings/general')->assertRedirect('/login');
        foreach ([Role::User, Role::Contributor, Role::Editor, Role::Admin] as $role) {
            $this->as($role);
            $this->get('/admin/settings/general')->assertForbidden();
            $this->put('/admin/settings/general', ['site_name' => 'Hacked'])->assertForbidden();
        }
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_every_group_page_renders_for_super_admin(): void
    {
        $this->as(Role::SuperAdmin);
        $this->get('/admin/settings')->assertRedirect('/admin/settings/general');
        foreach (array_keys(SettingsSchema::GROUPS) as $g) {
            $this->get("/admin/settings/{$g}")->assertOk()->assertSee('Save settings');
        }
        $this->get('/admin/settings/nope')->assertNotFound();
    }

    public function test_saves_general_settings(): void
    {
        $this->as(Role::SuperAdmin);
        $this->put('/admin/settings/general', ['site_name' => 'Ijwi Lyrics', 'tagline' => 'Words of Rwanda'])
            ->assertRedirect('/admin/settings/general');
        $this->assertSame('Ijwi Lyrics', app(SettingsService::class)->value('site_name'));
        $this->get('/admin/settings/general')->assertSee('Ijwi Lyrics')->assertSee('Settings saved.');
    }

    public function test_secret_is_encrypted_masked_kept_when_blank_and_removable(): void
    {
        $this->as(Role::SuperAdmin);
        $this->put('/admin/settings/storage', ['r2_secret_access_key' => 'topsecretvalue9876'])->assertRedirect();
        $stored = Setting::where('key', 'r2_secret_access_key')->value('value');
        $this->assertStringNotContainsString('topsecret', $stored);
        $this->assertSame('topsecretvalue9876', Crypt::decryptString($stored));

        $page = $this->get('/admin/settings/storage')->assertOk();
        $page->assertDontSee('topsecretvalue9876');
        $page->assertSee('9876');

        $this->put('/admin/settings/storage', ['r2_secret_access_key' => ''])->assertRedirect();
        $this->assertSame('topsecretvalue9876', app(SettingsService::class)->value('r2_secret_access_key'));

        $this->put('/admin/settings/storage', ['clear_r2_secret_access_key' => '1'])->assertRedirect();
        $this->assertDatabaseMissing('settings', ['key' => 'r2_secret_access_key']);
    }

    public function test_validation_rejects_bad_values(): void
    {
        $this->as(Role::SuperAdmin);
        $this->put('/admin/settings/ads', ['ga4_id' => 'not-an-id'])->assertSessionHasErrors('ga4_id');
        $this->put('/admin/settings/social', ['social_facebook' => 'javascript:alert(1)'])->assertSessionHasErrors('social_facebook');
        $this->put('/admin/settings/storage', ['r2_account_id' => 'short'])->assertSessionHasErrors('r2_account_id');
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_environment_controlled_field_is_locked_and_not_overwritten(): void
    {
        $this->as(Role::SuperAdmin);
        $_ENV['R2_BUCKET'] = $_SERVER['R2_BUCKET'] = 'env-bucket';
        try {
            $this->get('/admin/settings/storage')->assertSee('Set by server environment (R2_BUCKET)');
            $this->put('/admin/settings/storage', ['r2_bucket' => 'attacker-bucket'])->assertRedirect();
            $this->assertDatabaseMissing('settings', ['key' => 'r2_bucket']);
        } finally {
            unset($_ENV['R2_BUCKET'], $_SERVER['R2_BUCKET']);
        }
    }

    public function test_sign_up_switch_saves_as_boolean(): void
    {
        $this->as(Role::SuperAdmin);
        $this->put('/admin/settings/social', ['registration_enabled' => '0'])->assertRedirect();
        $this->assertFalse(app(SettingsService::class)->value('registration_enabled'));
        $this->put('/admin/settings/social', ['registration_enabled' => '1'])->assertRedirect();
        $this->assertTrue(app(SettingsService::class)->value('registration_enabled'));
    }

    public function test_settings_link_is_in_admin_nav_for_super_admin_only(): void
    {
        $this->as(Role::Admin);
        $this->get('/admin')->assertOk()->assertDontSee('/admin/settings');
        $this->as(Role::SuperAdmin);
        $this->get('/admin')->assertOk()->assertSee('/admin/settings');
    }
}
