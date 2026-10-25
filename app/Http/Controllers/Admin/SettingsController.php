<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use App\Support\AuditLogger;
use App\Support\SettingsSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
}
