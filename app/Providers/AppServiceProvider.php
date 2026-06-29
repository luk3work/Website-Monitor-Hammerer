<?php

namespace App\Providers;

use App\Http\ViewComposers\CockpitSidebarComposer;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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

        View::composer('layouts.cockpit', CockpitSidebarComposer::class);

        // Split-Screen-Login (shadcn) global über FilamentView registrieren –
        // robuster als panel-gebundene Render-Hooks (greifen mit gecachten
        // Routes/Config in Produktion nicht zuverlässig). Wirkung bleibt über
        // CSS (.fi-simple-* / :has(.fi-simple-layout)) auf die Login-Seite begrenzt.
        FilamentView::registerRenderHook(PanelsRenderHook::HEAD_END, fn (): string => AdminPanelProvider::loginHead());
        FilamentView::registerRenderHook(PanelsRenderHook::BODY_START, fn (): string => AdminPanelProvider::loginBrand());
    }
}
