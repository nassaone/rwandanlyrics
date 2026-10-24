<?php

namespace App\Providers;

use App\Http\Middleware\CountViews;
use App\Http\Middleware\NoindexThinContent;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Contracts\Http\Kernel;
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
        app(Kernel::class)->appendMiddlewareToGroup('web', SecurityHeaders::class);
        app(Kernel::class)->appendMiddlewareToGroup('web', CountViews::class);
        app(Kernel::class)->appendMiddlewareToGroup('web', NoindexThinContent::class);
        if (getenv('CODESPACES') === 'true') {
            URL::forceScheme('https');
            URL::forceRootUrl('https://'.getenv('CODESPACE_NAME').'-8000.'.getenv('GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN'));
        }
    }
}
