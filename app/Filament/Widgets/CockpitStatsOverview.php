<?php

namespace App\Filament\Widgets;

use App\Enums\SiteStatus;
use App\Filament\Resources\SiteResource;
use App\Models\Site;
use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

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

        // Wiederkehrender Monatsumsatz (MRR) aus gebuchten Paketen: monatlich + jährlich/12.
        $mrr = (float) DB::table('site_packages')
            ->join('packages', 'packages.id', '=', 'site_packages.package_id')
            ->where('site_packages.state', 'booked')
            ->selectRaw('COALESCE(SUM(packages.price_monthly), 0) + COALESCE(SUM(packages.price_yearly), 0) / 12 AS mrr')
            ->value('mrr');

        return [
            Stat::make('Sites online', "{$online} / {$total}")
                ->description($offline > 0 ? "{$offline} offline" : 'alle erreichbar')
                ->color($offline > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-signal')
                ->url($offline > 0
                    ? SiteResource::getUrl('index', ['tableFilters' => ['status' => ['value' => SiteStatus::Offline->value]]])
                    : SiteResource::getUrl('index')),

            Stat::make('Ausstehende Updates', (string) $updates)
                ->description('Core, Plugins & Themes')
                ->color($updates > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-arrow-up-circle')
                ->url(SiteResource::getUrl('index', ['tableFilters' => ['pending_updates' => ['value' => true]]])),

            Stat::make('SSL läuft bald ab', (string) $sslSoon)
                ->description('≤ 21 Tage')
                ->color($sslSoon > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-lock-closed')
                ->url(SiteResource::getUrl('index')),

            Stat::make('Domains laufen bald ab', (string) $domainSoon)
                ->description('≤ 30 Tage')
                ->color($domainSoon > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-globe-alt')
                ->url(SiteResource::getUrl('index')),

            Stat::make('Kritische Aufgaben', (string) $openCritical)
                ->description($openCritical > 0 ? 'sofort handeln' : 'nichts Dringendes')
                ->color($openCritical > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),

            Stat::make('Wiederkehrender Umsatz', number_format($mrr, 0, ',', '.') . ' €/M')
                ->description('aus gebuchten Paketen (MRR)')
                ->color('gray')
                ->icon('heroicon-o-banknotes'),
        ];
    }
}
