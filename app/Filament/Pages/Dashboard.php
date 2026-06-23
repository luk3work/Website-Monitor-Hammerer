<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CockpitStatsOverview;
use App\Filament\Widgets\NeedsActionTable;
use App\Filament\Widgets\OpenTasksByTypeChart;
use App\Filament\Widgets\SiteStatusChart;
use App\Filament\Widgets\UpcomingExpiriesTable;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Dediziertes Cockpit-Dashboard als Startseite (Logo-Klick führt hierher).
 * Kuratierte Reihenfolge: Überblick (KPIs) → Handlungsbedarf → Trends/Abläufe.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    public function getTitle(): string
    {
        return 'Dashboard';
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    public function getWidgets(): array
    {
        return [
            CockpitStatsOverview::class,
            NeedsActionTable::class,
            SiteStatusChart::class,
            OpenTasksByTypeChart::class,
            UpcomingExpiriesTable::class,
        ];
    }
}
