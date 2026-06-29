<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Auth\Login;
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

/**
 * Das eigentliche Cockpit ist das eigene Livewire-UI unter /cockpit.
 * Filament dient hier nur noch der Authentifizierung (Login/Session).
 * Die Panel-Startseite (/admin) leitet via App\Filament\Pages\Dashboard
 * sofort ins neue Cockpit um.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->defaultThemeMode(ThemeMode::Dark)
            // Sky-Blue als Akzent, passend zum neuen Cockpit-Look.
            ->colors([
                'primary' => Color::Sky,
                'gray'    => Color::Zinc,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger'  => Color::Rose,
            ])
            ->brandName('Ops Cockpit')
            ->font('Inter')
            // Split-Screen-Login im shadcn-Stil – nur auf der Login-Seite.
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => $this->loginHead(), scopes: Login::class)
            ->renderHook(PanelsRenderHook::BODY_START, fn (): string => $this->loginBrand(), scopes: Login::class)
            ->pages([
                Dashboard::class,
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

    /**
     * CSS für den Split-Screen-Login (shadcn-Anmutung):
     * Augen-Bild als Vollflächen-Hintergrund – links voll sichtbar, rechts
     * ein geblurrtes, abgedunkeltes Panel, das die Login-Karte trägt.
     */
    private function loginHead(): string
    {
        return <<<'HTML'
<style>
.fi-simple-page .fi-theme-switcher{display:none!important;}
.fi-simple-page .fi-logo{display:none!important;}
.fi-simple-layout{
  min-height:100vh;
  background:#09090B url('/img/login-bg.webp') 28% center / cover no-repeat;
  flex-direction:row!important;align-items:stretch!important;justify-content:flex-end!important;
}
.fi-simple-layout::before{
  content:'';position:fixed;inset:0;pointer-events:none;z-index:1;
  background:radial-gradient(900px 520px at 2% 0%, rgba(0,0,0,.55), transparent 60%),
            linear-gradient(0deg, rgba(0,0,0,.40), transparent 32%);
}
.fi-simple-main-ctn{
  position:relative;z-index:2;width:50%!important;flex-grow:0!important;min-height:100vh;
  align-items:center!important;justify-content:center!important;padding:24px;
  background:rgba(9,9,11,.62);
  -webkit-backdrop-filter:blur(22px) saturate(115%);backdrop-filter:blur(22px) saturate(115%);
  border-left:1px solid rgba(255,255,255,.08);
}
.fi-simple-main{
  margin:0!important;width:100%!important;max-width:400px!important;
  background:rgba(18,18,21,.86)!important;border:1px solid rgba(255,255,255,.10)!important;
  --tw-ring-color:transparent!important;box-shadow:0 24px 64px rgba(0,0,0,.55)!important;
  border-radius:16px!important;
}
.oc-login-brand{position:fixed;top:26px;left:32px;z-index:10;display:flex;align-items:center;gap:11px;}
.oc-login-brand img{width:38px;height:38px;display:block;filter:drop-shadow(0 2px 10px rgba(0,0,0,.6));}
.oc-login-brand b{color:#fff;font:700 17px/1 Inter,system-ui,sans-serif;letter-spacing:-.02em;}
.oc-login-tag{position:fixed;left:32px;bottom:30px;z-index:10;max-width:40%;
  color:rgba(255,255,255,.92);font:600 17px/1.45 Inter,system-ui,sans-serif;letter-spacing:-.01em;
  text-shadow:0 2px 14px rgba(0,0,0,.7);}
.oc-login-tag small{display:block;margin-top:7px;font-weight:400;font-size:13.5px;color:rgba(255,255,255,.62);}
@media (max-width:900px){
  .fi-simple-layout{flex-direction:column!important;justify-content:center!important;}
  .fi-simple-main-ctn{width:100%!important;}
  .oc-login-tag{display:none;}
}
</style>
<script>document.documentElement.classList.add('dark')</script>
HTML;
    }

    /** Marken-Mark (SVG) oben links + Tagline unten links – über dem Augen-Bild. */
    private function loginBrand(): string
    {
        return <<<'HTML'
<div class="oc-login-brand"><img src="/img/brand-icon.svg" alt="Ops Cockpit"><b>Ops Cockpit</b></div>
<div class="oc-login-tag">Alle Websites im Blick.<small>Status, Domains, SSL und Aufgaben — ein Cockpit.</small></div>
HTML;
    }
}
