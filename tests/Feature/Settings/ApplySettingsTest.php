<?php

namespace Tests\Feature\Settings;

use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use Tests\TestCase;

class ApplySettingsTest extends TestCase
{
    use RefreshDatabase;

    private function apply(array $values): void
    {
        $s = app(SettingsService::class);
        $s->set($values);
        $s->applyToConfig();
    }

    public function test_nothing_saved_changes_nothing(): void
    {
        $name = config('app.name');
        app(SettingsService::class)->applyToConfig();
        $this->assertSame($name, config('app.name'));
    }

    public function test_site_name_tagline_and_description_reach_the_page(): void
    {
        $this->apply(['site_name' => 'Ijwi Lyrics', 'tagline' => 'Words of Rwanda', 'default_description' => 'Custom default description here.']);
        $html = $this->get('/login')->assertOk()->getContent();
        $this->assertStringContainsString('Ijwi Lyrics', $html);
        $this->assertStringContainsString('Words of Rwanda', $html);
        $this->assertStringContainsString('Custom default description here.', $html);
    }

    public function test_contact_emails_apply(): void
    {
        $this->apply(['contact_copyright_email' => 'rights@example.rw']);
        $this->assertSame('rights@example.rw', config('rwandanlyrics.contact.copyright'));
    }

    public function test_registration_can_be_closed_from_settings(): void
    {
        $this->apply(['registration_enabled' => false]);
        $this->get('/register')->assertNotFound();
        $this->apply(['registration_enabled' => true]);
        $this->get('/register')->assertOk();
    }

    public function test_smtp_settings_apply_to_mail_config(): void
    {
        $this->apply([
            'mail_mailer' => 'smtp', 'mail_host' => 'smtp.example.com', 'mail_port' => 465,
            'mail_username' => 'u', 'mail_password' => 'p', 'mail_encryption' => 'ssl',
            'mail_from_address' => 'hello@example.rw', 'mail_from_name' => 'Ijwi',
        ]);
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame('hello@example.rw', config('mail.from.address'));
    }

    public function test_r2_disk_is_defined_only_when_complete(): void
    {
        $this->apply(['r2_enabled' => true, 'r2_bucket' => 'media']);
        $this->assertNull(config('filesystems.disks.r2'));

        $this->apply([
            'r2_account_id' => str_repeat('a', 32), 'r2_bucket' => 'media',
            'r2_access_key_id' => 'AKIATEST', 'r2_secret_access_key' => 'secret',
            'r2_public_url' => 'https://media.example.com/',
        ]);
        $disk = config('filesystems.disks.r2');
        $this->assertSame('s3', $disk['driver']);
        $this->assertSame('https://'.str_repeat('a', 32).'.r2.cloudflarestorage.com', $disk['endpoint']);
        $this->assertSame('https://media.example.com', $disk['url']);

        $expected = class_exists(AwsS3V3Adapter::class) ? 'r2' : config('rwandanlyrics.media_disk');
        $this->assertSame($expected, config('rwandanlyrics.media_disk'));
    }
}
