<?php

namespace Tests\Feature\Settings;

use App\Enums\Role;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class R2TestConnectionTest extends TestCase
{
    use RefreshDatabase;

    private function configure(): void
    {
        app(SettingsService::class)->set([
            'r2_account_id' => str_repeat('a', 32), 'r2_bucket' => 'media',
            'r2_access_key_id' => 'AKIATEST', 'r2_secret_access_key' => 'topsecret',
            'r2_public_url' => 'https://media.example.com',
        ]);
    }

    private function superAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::SuperAdmin->value]));
    }

    public function test_only_super_admin_can_test(): void
    {
        $this->post('/admin/settings/storage/test')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => Role::Admin->value]));
        $this->post('/admin/settings/storage/test')->assertForbidden();
    }

    public function test_reports_missing_configuration(): void
    {
        $this->superAdmin();
        $this->post('/admin/settings/storage/test')->assertRedirect('/admin/settings/storage')
            ->assertSessionHas('error');
    }

    public function test_success_writes_reads_checks_public_url_and_cleans_up(): void
    {
        $this->superAdmin();
        $this->configure();
        Storage::fake('r2', ['url' => 'https://media.example.com']);
        Http::fake(['https://media.example.com/*' => Http::response('rwandanlyrics-healthcheck')]);

        $this->post('/admin/settings/storage/test')->assertRedirect('/admin/settings/storage')
            ->assertSessionHas('status');
        $this->assertSame([], Storage::disk('r2')->allFiles());
    }

    public function test_unreachable_public_url_is_reported_separately(): void
    {
        $this->superAdmin();
        $this->configure();
        Storage::fake('r2', ['url' => 'https://media.example.com']);
        Http::fake(['https://media.example.com/*' => Http::response('nope', 403)]);

        $this->post('/admin/settings/storage/test')->assertSessionHas('error');
        $this->assertSame([], Storage::disk('r2')->allFiles());
    }

    public function test_storage_tab_shows_test_button_and_current_disk(): void
    {
        $this->superAdmin();
        $this->get('/admin/settings/storage')->assertOk()
            ->assertSee('Test connection')->assertSee('New uploads are stored on');
    }
}
