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
        // Hinter dem Mittwald-Proxy terminiert TLS extern; die App sieht http.
        // Ohne dies erzeugen asset()/route() http-URLs, die der Browser auf einer
        // https-Seite als "Mixed Content" blockiert (Stylesheets/Assets laden nicht).
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
