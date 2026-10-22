<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Preflight extends Command
{
    protected $signature = 'rwandanlyrics:preflight';

    protected $description = 'Check launch settings: environment, debug mode, URL and public contact emails.';

    public function handle(): int
    {
        $url = (string) config('app.url');

        $checks = [
            'APP_ENV is production' => config('app.env') === 'production',
            'APP_DEBUG is off' => ! config('app.debug'),
            'APP_URL uses https and is not localhost' => str_starts_with($url, 'https://') && ! str_contains($url, 'localhost'),
            'Copyright contact email is set (CONTACT_COPYRIGHT_EMAIL)' => $this->realEmail(config('rwandanlyrics.contact.copyright')),
            'Corrections contact email is set (CONTACT_CORRECTIONS_EMAIL)' => $this->realEmail(config('rwandanlyrics.contact.corrections')),
            'Privacy contact email is set (CONTACT_PRIVACY_EMAIL)' => $this->realEmail(config('rwandanlyrics.contact.privacy')),
        ];

        foreach ($checks as $label => $ok) {
            $this->line(($ok ? 'PASS  ' : 'FAIL  ').$label);
        }

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }

    private function realEmail(?string $email): bool
    {
        return filled($email)
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && ! str_ends_with(strtolower($email), '@yourdomain.com');
    }
}
