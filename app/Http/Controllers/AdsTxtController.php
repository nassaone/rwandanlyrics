<?php

namespace App\Http\Controllers;

use App\Services\SettingsService;

class AdsTxtController extends Controller
{
    public function __invoke(SettingsService $settings)
    {
        $content = trim((string) $settings->value('ads_txt'));
        abort_if($content === '', 404);

        return response($content."\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
