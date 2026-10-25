<?php

namespace App\Support;

/**
 * Single source of truth for every editable setting: group, label, input type,
 * validation, whether it is a secret, and an optional .env name that overrides the database.
 */
final class SettingsSchema
{
    public const GROUPS = [
        'general' => 'General',
        'ads' => 'Ads and code',
        'storage' => 'Storage (R2)',
        'mail' => 'Mail',
        'social' => 'Social and site',
    ];

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        $f = fn (string $group, string $label, string $type = 'text', array $o = []) => array_merge([
            'group' => $group, 'label' => $label, 'type' => $type, 'default' => null,
            'rules' => ['nullable', 'string', 'max:255'], 'help' => null,
            'secret' => false, 'env' => null, 'options' => [],
        ], $o);
        $url = ['nullable', 'url:http,https', 'max:255'];
        $bool = ['boolean'];
        $code = ['nullable', 'string', 'max:20000'];

        return [
            // General
            'site_name' => $f('general', 'Site name', 'text', ['rules' => ['nullable', 'string', 'max:60'], 'help' => 'Shown in titles, the header and emails. Leave empty to use the default.']),
            'tagline' => $f('general', 'Tagline', 'text', ['rules' => ['nullable', 'string', 'max:120']]),
            'default_description' => $f('general', 'Default search description', 'textarea', ['rules' => ['nullable', 'string', 'max:300'], 'help' => 'Used on pages that have no description of their own.']),
            'contact_copyright_email' => $f('general', 'Copyright and takedown email', 'email', ['rules' => ['nullable', 'email', 'max:255']]),
            'contact_corrections_email' => $f('general', 'Corrections email', 'email', ['rules' => ['nullable', 'email', 'max:255']]),
            'contact_privacy_email' => $f('general', 'Privacy email', 'email', ['rules' => ['nullable', 'email', 'max:255']]),

            // Ads and code
            'ads_enabled' => $f('ads', 'Show ads and run custom code', 'bool', ['rules' => $bool, 'default' => false, 'help' => 'Master switch. When off, none of the code below is output. Update your privacy policy and add a cookie notice before turning this on.']),
            'ga4_id' => $f('ads', 'Google Analytics 4 ID', 'text', ['rules' => ['nullable', 'regex:/^G-[A-Z0-9]{4,}$/'], 'help' => 'Looks like G-XXXXXXXXXX.']),
            'plausible_domain' => $f('ads', 'Plausible domain', 'text', ['rules' => ['nullable', 'regex:/^[a-z0-9.-]+\.[a-z]{2,}$/i'], 'help' => 'For example rwandanlyrics.com.']),
            'head_code' => $f('ads', 'Code inside <head>', 'code', ['rules' => $code, 'help' => 'For the ad network script or site verification tags.']),
            'body_start_code' => $f('ads', 'Code after <body> opens', 'code', ['rules' => $code]),
            'body_end_code' => $f('ads', 'Code before </body>', 'code', ['rules' => $code]),
            'ad_list_top' => $f('ads', 'Ad slot: top of lists', 'code', ['rules' => $code]),
            'ad_home_middle' => $f('ads', 'Ad slot: homepage middle', 'code', ['rules' => $code]),
            'ad_song_top' => $f('ads', 'Ad slot: above lyrics', 'code', ['rules' => $code]),
            'ad_song_bottom' => $f('ads', 'Ad slot: below lyrics', 'code', ['rules' => $code]),
            'ads_txt' => $f('ads', 'ads.txt content', 'textarea', ['rules' => ['nullable', 'string', 'max:5000'], 'help' => 'Served at /ads.txt. Paste the lines your ad network gives you.']),

            // Storage (R2)
            'r2_enabled' => $f('storage', 'Store uploads in Cloudflare R2', 'bool', ['rules' => $bool, 'default' => false, 'help' => 'Only new uploads go to R2. Existing files stay where they are.']),
            'r2_account_id' => $f('storage', 'Account ID', 'text', ['env' => 'R2_ACCOUNT_ID', 'rules' => ['nullable', 'regex:/^[a-f0-9]{32}$/i']]),
            'r2_bucket' => $f('storage', 'Bucket name', 'text', ['env' => 'R2_BUCKET', 'rules' => ['nullable', 'regex:/^[a-z0-9][a-z0-9-]{1,61}[a-z0-9]$/']]),
            'r2_access_key_id' => $f('storage', 'Access key ID', 'secret', ['env' => 'R2_ACCESS_KEY_ID', 'secret' => true, 'rules' => ['nullable', 'string', 'max:200']]),
            'r2_secret_access_key' => $f('storage', 'Secret access key', 'secret', ['env' => 'R2_SECRET_ACCESS_KEY', 'secret' => true, 'rules' => ['nullable', 'string', 'max:200']]),
            'r2_public_url' => $f('storage', 'Public URL', 'url', ['env' => 'R2_PUBLIC_URL', 'rules' => $url, 'help' => 'The r2.dev or custom domain address of the bucket, for example https://media.example.com.']),

            // Mail
            'mail_mailer' => $f('mail', 'Mail method', 'select', ['options' => ['' => 'Server default', 'smtp' => 'SMTP', 'log' => 'Log only (no real email)'], 'rules' => ['nullable', 'in:smtp,log']]),
            'mail_host' => $f('mail', 'SMTP host'),
            'mail_port' => $f('mail', 'SMTP port', 'number', ['rules' => ['nullable', 'integer', 'between:1,65535'], 'default' => 587]),
            'mail_username' => $f('mail', 'SMTP username'),
            'mail_password' => $f('mail', 'SMTP password', 'secret', ['secret' => true, 'rules' => ['nullable', 'string', 'max:300']]),
            'mail_encryption' => $f('mail', 'Encryption', 'select', ['options' => ['tls' => 'TLS (port 587)', 'ssl' => 'SSL (port 465)', 'none' => 'None'], 'rules' => ['nullable', 'in:tls,ssl,none'], 'default' => 'tls']),
            'mail_from_address' => $f('mail', 'From address', 'email', ['rules' => ['nullable', 'email', 'max:255']]),
            'mail_from_name' => $f('mail', 'From name'),

            // Social and site
            'registration_enabled' => $f('social', 'Allow public sign-up', 'bool', ['rules' => $bool, 'help' => 'When off, the register page and link disappear.']),
            'maintenance_banner' => $f('social', 'Notice banner', 'text', ['rules' => ['nullable', 'string', 'max:200'], 'help' => 'A short message shown on top of every page, for example planned downtime. Empty hides it.']),
            'social_facebook' => $f('social', 'Facebook URL', 'url', ['rules' => $url]),
            'social_instagram' => $f('social', 'Instagram URL', 'url', ['rules' => $url]),
            'social_x' => $f('social', 'X (Twitter) URL', 'url', ['rules' => $url]),
            'social_youtube' => $f('social', 'YouTube URL', 'url', ['rules' => $url]),
            'social_tiktok' => $f('social', 'TikTok URL', 'url', ['rules' => $url]),
            'social_whatsapp' => $f('social', 'WhatsApp channel URL', 'url', ['rules' => $url]),
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public static function group(string $group): array
    {
        return array_filter(self::all(), fn ($d) => $d['group'] === $group);
    }
}
