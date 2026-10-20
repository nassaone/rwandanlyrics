<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (getenv('CODESPACES') === 'true') {
            URL::forceScheme('https');
            URL::forceRootUrl('https://'.getenv('CODESPACE_NAME').'-8000.'.getenv('GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN'));
        }
    }
}
