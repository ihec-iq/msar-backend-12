<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // لو APP_URL عندك https، خلّي Laravel يجبر نفس الشي
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // إجبار الجذر على APP_URL (مهم إذا لديك أكثر من دومين/بروكسي)
        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }
    }
}
