<?php

namespace App\Filament\Widgets;

use App\Enums\SiteStatus;
use App\Models\Site;
use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Schnellüberblick oben im Cockpit. "Alles grün" soll langweilig aussehen:
 * Kacheln färben sich nur, wenn etwas Aufmerksamkeit braucht.
 */
class CockpitStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $base = Site::query()->where('is_archived', false);

        $total    = (clone $base)->count();
        $online   = (clone $base)->where('status', SiteStatus::Online->value)->count();
        $offline  = (clone $base)->where('status', SiteStatus::Offline->value)->count();
        $updates  = (clone $base)->where('pending_updates', '>', 0)->sum('pending_updates');

        $sslSoon = (clone $base)
            ->whereNotNull('ssl_expires_at')
            ->whereDate('ssl_expires_at', '<=', now()->addDays(21))
            ->count();

        $domainSoon = (clone $base)
            ->whereNotNull('domain_expires_at')
            ->whereDate('domain_expires_at', '<=', now()->addDays(30))
            ->count();

        $openCritical = Task::query()
            ->open()
            ->where('severity', 'critical')
            ->count();

        return [
            Stat::make('Sites online', "{$online} / {$total}")
                ->description($offline > 0 ? "{$offline} offline" : 'alle erreichbar')
                ->color($offline > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-signal'),

            Stat::make('Ausstehende Updates', (string) $updates)
                ->description('Core, Plugins & Themes')
                ->color($updates > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-arrow-up-circle'),

            Stat::make('SSL läuft bald ab', (string) $sslSoon)
                ->description('≤ 21 Tage')
                ->color($sslSoon > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-lock-closed'),

            Stat::make('Domains laufen bald ab', (string) $domainSoon)
                ->description('≤ 30 Tage')
                ->color($domainSoon > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-globe-alt'),

            Stat::make('Kritische Aufgaben', (string) $openCritical)
                ->description($openCritical > 0 ? 'sofort handeln' : 'nichts Dringendes')
                ->color($openCritical > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
