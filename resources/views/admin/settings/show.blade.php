@php
    $intro = [
        'general' => 'Names, contact addresses and the default description used across the site.',
        'ads' => 'Code here runs on every public page. Only a super administrator can edit it. Paste only code from sources you trust, and keep the master switch off until your privacy policy and cookie notice cover it.',
        'storage' => 'Cloudflare R2 stores uploaded images. Fields marked "Set by server environment" come from the server\'s .env file and cannot be changed here. Only new uploads use R2; existing files stay where they are.',
        'mail' => 'Used for password reset emails. Leave the method on "Server default" to use the server\'s own configuration.',
        'social' => 'Social links appear in the footer. The sign-up switch and the notice banner take effect immediately.',
    ];
@endphp
<x-admin-layout title="Settings">
    <h1>Settings</h1>

    <nav class="-mx-4 mt-4 flex gap-5 overflow-x-auto border-b border-line px-4 text-sm" aria-label="Settings sections">
        @foreach (\App\Support\SettingsSchema::GROUPS as $key => $label)
            <a href="{{ route('admin.settings.show', $key) }}" @if ($key === $group) aria-current="page" @endif
               class="whitespace-nowrap border-b-2 py-3 {{ $key === $group ? 'border-accent-600 font-medium text-accent-700' : 'border-transparent hover:text-accent-600' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <p class="mt-4 max-w-2xl text-sm text-muted">{{ $intro[$group] }}</p>

    @if (session('error'))
        <x-alert type="error" class="mt-4 max-w-2xl">{{ session('error') }}</x-alert>
    @endif

    @if ($errors->any())
        <x-alert type="error" class="mt-4 max-w-2xl">Some settings were not saved. Fix the fields marked below and save again.</x-alert>
    @endif

    <form method="POST" action="{{ route('admin.settings.update', $group) }}" class="mt-6 max-w-2xl space-y-6">
        @csrf
        @method('PUT')

        @foreach ($fields as $key => $def)
            @php
                $locked = $settings->envControlled($key);
                $current = $settings->value($key);
                if ($key === 'registration_enabled' && $current === null) {
                    $current = config('rwandanlyrics.registration_enabled');
                }
            @endphp
            <div>
                @if ($locked)
                    <label for="{{ $key }}-locked" class="block text-sm font-medium">{{ $def['label'] }}</label>
                    <input id="{{ $key }}-locked" disabled value="{{ $def['secret'] ? $settings->mask($key) : $current }}"
                           class="mt-1 block min-h-11 w-full rounded-lg border border-line bg-surface px-3 py-2.5 text-base text-muted">
                    <p class="mt-1 text-sm text-muted">Set by server environment ({{ $def['env'] }}). Change it in the server's .env file.</p>
                @elseif ($def['type'] === 'bool')
                    <input type="hidden" name="{{ $key }}" value="0">
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="{{ $key }}" value="1" class="mt-1 size-5" @checked((bool) old($key, $current))>
                        <span class="font-medium">{{ $def['label'] }}</span>
                    </label>
                @elseif ($def['type'] === 'secret')
                    <x-field :name="$key" :label="$def['label']" type="password" autocomplete="new-password"
                             :placeholder="$settings->isSet($key) ? 'Saved: '.$settings->mask($key) : 'Not set'" />
                    @if ($settings->isSet($key))
                        <label class="mt-2 flex items-center gap-2 text-sm">
                            <input type="checkbox" name="clear_{{ $key }}" value="1" class="size-5"> Remove the saved value
                        </label>
                    @endif
                    <p class="mt-1 text-sm text-muted">Leave empty to keep the saved value. It is stored encrypted and never shown again.</p>
                @elseif ($def['type'] === 'code')
                    <x-textarea :name="$key" :label="$def['label']" :value="$current" rows="6" class="font-mono text-sm" spellcheck="false" autocomplete="off" />
                @elseif ($def['type'] === 'textarea')
                    <x-textarea :name="$key" :label="$def['label']" :value="$current" rows="4" />
                @elseif ($def['type'] === 'select')
                    <x-select :name="$key" :label="$def['label']" :options="$def['options']" :selected="$current" />
                @else
                    <x-field :name="$key" :label="$def['label']" :type="$def['type']" :value="$current" />
                @endif

                @if ($def['help'] && ! $locked)
                    <p class="mt-1 text-sm text-muted">{{ $def['help'] }}</p>
                @endif
            </div>
        @endforeach

        <x-button type="submit">Save settings</x-button>
    </form>
    @if ($group === 'storage')
        @php
            $activeDisk = config('rwandanlyrics.media_disk');
            $why = null;
            if ($activeDisk !== 'r2') {
                $why = ! $settings->bool('r2_enabled') ? 'R2 is switched off.'
                    : (! $settings->r2Configured() ? 'Some R2 fields are still empty.'
                    : (! class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class) ? 'The S3 package is not installed on the server.' : null));
            }
        @endphp
        <section class="mt-10 max-w-2xl border-t border-line pt-6" aria-labelledby="r2-status">
            <h2 id="r2-status">Connection</h2>
            <p class="mt-2">New uploads are stored on: <strong>{{ $activeDisk === 'r2' ? 'Cloudflare R2' : 'the server disk ('.$activeDisk.')' }}</strong>.
                @if ($why) <span class="text-muted">{{ $why }}</span> @endif
            </p>
            <p class="mt-2 text-sm text-muted">Save your settings first, then test. The test writes a small file to the bucket, reads it through the public URL, and deletes it.</p>
            <form method="POST" action="{{ route('admin.settings.test-r2') }}" class="mt-4">
                @csrf
                <x-button type="submit" variant="secondary">Test connection</x-button>
            </form>
        </section>
    @endif
</x-admin-layout>
