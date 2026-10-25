<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\SettingsSchema;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;

class SettingsService
{
    private const CACHE_KEY = 'settings.all';

    /** @var array<string, string|null>|null */
    private ?array $rows = null;

    /** Raw stored rows (secrets still encrypted). Never throws: before migration it returns []. */
    private function rows(): array
    {
        if ($this->rows !== null) {
            return $this->rows;
        }
        try {
            $cached = Cache::get(self::CACHE_KEY);
        } catch (\Throwable) {
            $cached = null;
        }
        if (is_array($cached)) {
            return $this->rows = $cached;
        }
        try {
            $rows = Setting::query()->pluck('value', 'key')->all();
        } catch (\Throwable) {
            return $this->rows = []; // table missing: do not cache
        }
        try {
            Cache::forever(self::CACHE_KEY, $rows);
        } catch (\Throwable) {
        }

        return $this->rows = $rows;
    }

    public function definition(string $key): ?array
    {
        return SettingsSchema::all()[$key] ?? null;
    }

    /** True when a server environment variable controls this key. */
    public function envControlled(string $key): bool
    {
        $env = $this->definition($key)['env'] ?? null;

        return $env && ($v = env($env)) !== null && $v !== '';
    }

    public function value(string $key): mixed
    {
        $def = $this->definition($key);
        if (! $def) {
            return null;
        }
        if ($this->envControlled($key)) {
            return env($def['env']);
        }
        $raw = $this->rows()[$key] ?? null;
        if ($raw === null || $raw === '') {
            return $def['default'];
        }
        if ($def['secret']) {
            try {
                return Crypt::decryptString($raw);
            } catch (DecryptException) {
                return null; // APP_KEY changed: treat as not set
            }
        }

        return match ($def['type']) {
            'bool' => $raw === '1',
            'number' => (int) $raw,
            default => $raw,
        };
    }

    public function bool(string $key): bool
    {
        return (bool) $this->value($key);
    }

    /** Whether a non-empty value exists (database or environment). */
    public function isSet(string $key): bool
    {
        $v = $this->value($key);

        return $v !== null && $v !== '' && $v !== false;
    }

    /** Last 4 characters only, for showing that a secret exists. */
    public function mask(string $key): ?string
    {
        $v = $this->value($key);
        if (! is_string($v) || $v === '') {
            return null;
        }

        return str_repeat('•', 8).substr($v, -4);
    }

    /** @param array<string, mixed> $values null or '' removes the stored value */
    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            $def = $this->definition($key);
            if (! $def) {
                continue;
            }
            if ($value === null || $value === '') {
                Setting::where('key', $key)->delete();

                continue;
            }
            if ($def['type'] === 'bool') {
                $value = $value ? '1' : '0';
            }
            $value = (string) $value;
            if ($def['secret']) {
                $value = Crypt::encryptString($value);
            }
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        $this->flush();
    }

    /** True when every value needed to talk to R2 is present. */
    public function r2Configured(): bool
    {
        foreach (['r2_account_id', 'r2_bucket', 'r2_access_key_id', 'r2_secret_access_key', 'r2_public_url'] as $k) {
            if (! $this->isSet($k)) {
                return false;
            }
        }

        return true;
    }

    /** Copy saved settings into Laravel config for this request. Safe to call when nothing is saved. */
    public function applyToConfig(): void
    {
        $c = app('config');

        if ($v = $this->value('site_name')) {
            $c->set('app.name', $v);
        }
        if ($v = $this->value('tagline')) {
            $c->set('rwandanlyrics.tagline', $v);
        }
        if ($v = $this->value('default_description')) {
            $c->set('rwandanlyrics.default_description', $v);
        }
        foreach (['copyright', 'corrections', 'privacy'] as $k) {
            if ($v = $this->value("contact_{$k}_email")) {
                $c->set("rwandanlyrics.contact.{$k}", $v);
            }
        }
        if (($v = $this->value('registration_enabled')) !== null) {
            $c->set('rwandanlyrics.registration_enabled', (bool) $v);
        }

        // Mail
        if ($mailer = $this->value('mail_mailer')) {
            if ($mailer === 'smtp' && $this->isSet('mail_host')) {
                $c->set('mail.default', 'smtp');
                $c->set('mail.mailers.smtp.host', $this->value('mail_host'));
                $c->set('mail.mailers.smtp.port', (int) $this->value('mail_port'));
                $c->set('mail.mailers.smtp.username', $this->value('mail_username'));
                $c->set('mail.mailers.smtp.password', $this->value('mail_password'));
                $c->set('mail.mailers.smtp.scheme', $this->value('mail_encryption') === 'ssl' ? 'smtps' : 'smtp');
            } elseif ($mailer === 'log') {
                $c->set('mail.default', 'log');
            }
        }
        if ($v = $this->value('mail_from_address')) {
            $c->set('mail.from.address', $v);
        }
        if ($v = $this->value('mail_from_name')) {
            $c->set('mail.from.name', $v);
        }

        // Cloudflare R2
        if ($this->r2Configured()) {
            $c->set('filesystems.disks.r2', [
                'driver' => 's3',
                'key' => $this->value('r2_access_key_id'),
                'secret' => $this->value('r2_secret_access_key'),
                'region' => 'auto',
                'bucket' => $this->value('r2_bucket'),
                'endpoint' => 'https://'.$this->value('r2_account_id').'.r2.cloudflarestorage.com',
                'url' => rtrim((string) $this->value('r2_public_url'), '/'),
                'use_path_style_endpoint' => true,
                'request_checksum_calculation' => 'when_required',
                'response_checksum_validation' => 'when_required',
                'throw' => true,
                'report' => false,
            ]);
            // Only switch uploads once the S3 adapter package is installed.
            if ($this->bool('r2_enabled') && class_exists(AwsS3V3Adapter::class)) {
                $c->set('rwandanlyrics.media_disk', 'r2');
            }
        }
    }

    public function flush(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (\Throwable) {
        }
        $this->rows = null;
    }
}
