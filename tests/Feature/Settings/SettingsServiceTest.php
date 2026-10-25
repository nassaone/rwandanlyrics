<?php

namespace Tests\Feature\Settings;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private function svc(): SettingsService
    {
        return new SettingsService;
    }

    public function test_returns_schema_default_when_unset(): void
    {
        $this->assertFalse($this->svc()->bool('ads_enabled'));
        $this->assertSame(587, $this->svc()->value('mail_port'));
        $this->assertNull($this->svc()->value('site_name'));
    }

    public function test_round_trips_text_bool_and_number(): void
    {
        $s = $this->svc();
        $s->set(['site_name' => 'Rwandan lyrics', 'ads_enabled' => true, 'mail_port' => 465]);
        $s = $this->svc();
        $this->assertSame('Rwandan lyrics', $s->value('site_name'));
        $this->assertTrue($s->bool('ads_enabled'));
        $this->assertSame(465, $s->value('mail_port'));
    }

    public function test_empty_value_removes_the_row(): void
    {
        $s = $this->svc();
        $s->set(['tagline' => 'Hello']);
        $this->assertDatabaseHas('settings', ['key' => 'tagline']);
        $s->set(['tagline' => '']);
        $this->assertDatabaseMissing('settings', ['key' => 'tagline']);
    }

    public function test_secrets_are_encrypted_at_rest_and_masked(): void
    {
        $s = $this->svc();
        $s->set(['r2_secret_access_key' => 'supersecretvalue1234']);
        $stored = Setting::where('key', 'r2_secret_access_key')->value('value');
        $this->assertStringNotContainsString('supersecret', $stored);
        $this->assertSame('supersecretvalue1234', Crypt::decryptString($stored));

        $s = $this->svc();
        $this->assertSame('supersecretvalue1234', $s->value('r2_secret_access_key'));
        $this->assertSame('••••••••1234', $s->mask('r2_secret_access_key'));
        $this->assertTrue($s->isSet('r2_secret_access_key'));
    }

    public function test_unknown_keys_are_ignored(): void
    {
        $this->svc()->set(['not_a_setting' => 'x']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_environment_overrides_database_for_r2_keys(): void
    {
        $this->svc()->set(['r2_bucket' => 'from-db']);
        $_ENV['R2_BUCKET'] = $_SERVER['R2_BUCKET'] = 'from-env';
        try {
            $s = $this->svc();
            $this->assertTrue($s->envControlled('r2_bucket'));
            $this->assertSame('from-env', $s->value('r2_bucket'));
        } finally {
            unset($_ENV['R2_BUCKET'], $_SERVER['R2_BUCKET']);
        }
        $this->assertSame('from-db', $this->svc()->value('r2_bucket'));
    }

    public function test_missing_table_does_not_break_reads(): void
    {
        Schema::drop('settings');
        $this->assertNull($this->svc()->value('site_name'));
        $this->assertFalse($this->svc()->bool('ads_enabled'));
    }
}
