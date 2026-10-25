<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use App\Support\AuditLogger;
use App\Support\SettingsSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;

class SettingsController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.settings.show', 'general');
    }

    public function show(string $group, SettingsService $settings): View
    {
        abort_unless(isset(SettingsSchema::GROUPS[$group]), 404);

        return view('admin.settings.show', [
            'group' => $group,
            'fields' => SettingsSchema::group($group),
            'settings' => $settings,
        ]);
    }

    public function update(Request $request, string $group, SettingsService $settings): RedirectResponse
    {
        abort_unless(isset(SettingsSchema::GROUPS[$group]), 404);
        $fields = SettingsSchema::group($group);

        $rules = [];
        foreach ($fields as $key => $def) {
            if ($settings->envControlled($key)) {
                continue;
            }
            $rules[$key] = $def['rules'];
            if ($def['secret']) {
                $rules["clear_{$key}"] = ['nullable', 'boolean'];
            }
        }
        $data = $request->validate($rules);

        $values = [];
        $changed = [];
        foreach ($fields as $key => $def) {
            if ($settings->envControlled($key)) {
                continue;
            }
            if ($def['type'] === 'bool') {
                $new = $request->boolean($key);
                if ($new !== (bool) $settings->value($key)) {
                    $changed[] = $key;
                }
                $values[$key] = $new;
            } elseif ($def['secret']) {
                if ($request->boolean("clear_{$key}")) {
                    $values[$key] = null;
                    $changed[] = $key;
                } elseif (filled($data[$key] ?? null)) {
                    $values[$key] = $data[$key];
                    $changed[] = $key;
                }
                // blank and not cleared: keep the saved secret
            } else {
                $new = $data[$key] ?? null;
                if ((string) $new !== (string) $settings->value($key)) {
                    $changed[] = $key;
                }
                $values[$key] = $new;
            }
        }

        $settings->set($values);
        // Names of changed settings only. Values (especially secrets and code) are never logged.
        AuditLogger::record('settings.updated', $request->user(), ['group' => $group, 'keys' => $changed]);

        return redirect()->route('admin.settings.show', $group)->with('status', 'Settings saved.');
    }

    public function testR2(Request $request, SettingsService $settings): RedirectResponse
    {
        $back = redirect()->route('admin.settings.show', 'storage');

        if (! $settings->r2Configured()) {
            return $back->with('error', 'Save the account ID, bucket, both keys and the public URL first.');
        }
        if (! class_exists(AwsS3V3Adapter::class)) {
            return $back->with('error', 'The S3 package is not installed on the server. Run: composer require league/flysystem-aws-s3-v3');
        }
        if (! config('filesystems.disks.r2')) {
            $settings->applyToConfig();
        }

        $body = 'rwandanlyrics-healthcheck';
        $path = 'healthcheck/'.Str::random(24).'.txt';
        $disk = Storage::disk('r2');
        $public = false;

        try {
            $disk->put($path, $body);
            if ($disk->get($path) !== $body) {
                throw new \RuntimeException('The file was written but read back differently.');
            }
            try {
                $res = Http::timeout(8)->get($disk->url($path));
                $public = $res->successful() && trim($res->body()) === $body;
            } catch (\Throwable) {
                $public = false;
            }
        } catch (\Throwable $e) {
            $msg = class_basename($e).': '.$e->getMessage();
            foreach (['r2_secret_access_key', 'r2_access_key_id'] as $k) {
                if ($v = $settings->value($k)) {
                    $msg = str_replace($v, '***', $msg);
                }
            }
            AuditLogger::record('settings.r2_tested', $request->user(), ['ok' => false]);

            return $back->with('error', 'Connection failed. '.Str::limit($msg, 220));
        } finally {
            try {
                $disk->delete($path);
            } catch (\Throwable) {
            }
        }

        AuditLogger::record('settings.r2_tested', $request->user(), ['ok' => true, 'public' => $public]);

        if (! $public) {
            return $back->with('error', 'Uploading works, but the public URL did not return the test file. Check the public URL and that public access is switched on for the bucket.');
        }

        return $back->with('status', 'Connection works. A test file was written, read through the public URL, and deleted.');
    }
}
