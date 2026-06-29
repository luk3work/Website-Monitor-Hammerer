<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Das alte Filament-Cockpit wurde durch das eigene Livewire-Cockpit (/cockpit)
 * ersetzt. Filament dient nur noch der Anmeldung. Diese Seite belegt weiterhin
 * die Panel-Startseite (/admin) und leitet sofort ins neue Cockpit um.
 */
class Dashboard extends BaseDashboard
{
    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        $this->redirect(route('cockpit.dashboard'));
    }
}
