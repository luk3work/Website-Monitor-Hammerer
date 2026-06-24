<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\CockpitStatsOverview;
use App\Filament\Widgets\NeedsActionTable;
use App\Filament\Widgets\OpenTasksByTypeChart;
use App\Filament\Widgets\SiteStatusChart;
use App\Filament\Widgets\UpcomingExpiriesTable;
use Filament\Enums\ThemeMode;
use Filament\Support\Enums\MaxWidth;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // Profilseite: Name/E-Mail/Passwort selbst ändern (oben rechts im Benutzermenü).
            ->profile()
            // Dunkles Daten-Cockpit als Standard (Hell-/Dunkel-Umschalter bleibt erhalten).
            ->defaultThemeMode(ThemeMode::Dark)
            // Markenfarbe "Lavared" als Primärakzent (Navigation, Buttons, Links).
            // Kritisch/Gefahr nutzt bewusst das hellere Rose, damit Status-Rot vom
            // Marken-Rot klar unterscheidbar bleibt (Information nie nur über Farbe).
            ->colors([
                'primary' => Color::Red,
                'gray'    => Color::Slate,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger'  => Color::Rose,
            ])
            ->brandName('Ops Cockpit')
            // Ruhige, moderne Typografie.
            ->font('Inter')
            // Eigener Feinschliff (Apple/Notion-inspiriert): das Design-System wird
            // INLINE in den <head> geschrieben. So gibt es keine externe Datei, die
            // über einen falschen Schema-/Proxy-Pfad blockiert oder per CDN gecacht
            // werden könnte – das CSS steht garantiert im Markup und greift sofort.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                function (): string {
                    $css = @file_get_contents(public_path('css/ops-cockpit.css'));

                    return $css ? '<style id="ops-cockpit-css">' . $css . '</style>' : '';
                },
            )
            // Dark-Mode erzwingen ("dunkel & edel"): überschreibt eine evtl. hell
            // gespeicherte Nutzer-Präferenz, bevor Filament/Alpine sie liest.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<script>try{localStorage.setItem("theme","dark");}catch(e){}document.documentElement.classList.add("dark");</script>',
            )
            // Cockpit-Charakter: volle Breite, einklappbare Sidebar, gruppierte Navigation, SPA.
            ->maxContentWidth(MaxWidth::Full)
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups(['Betrieb', 'Verwaltung'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            // Dediziertes Dashboard als Startseite (sonst landet man ohne klare Home-Seite).
            ->pages([
                Dashboard::class,
            ])
            // Reihenfolge: KPIs -> Braucht Handlung -> Visualisierungen.
            ->widgets([
                CockpitStatsOverview::class,
                NeedsActionTable::class,
                SiteStatusChart::class,
                OpenTasksByTypeChart::class,
                UpcomingExpiriesTable::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
