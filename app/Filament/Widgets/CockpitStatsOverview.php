<?php

namespace App\Filament\Widgets;

use App\Enums\SiteStatus;
use App\Filament\Resources\SiteResource;
use App\Models\Site;
use App\Models\SiteSnapshot;
use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * KPI-Überblick im Cockpit-Stil: Wert + Trend-Indikator + Sparkline (aus echten
 * Telemetrie-Verlaufsdaten, wo vorhanden). "Alles grün" = Ruhezustand.
 */
class CockpitStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $base = Site::query()->where('is_archived', false);

        $total   = (clone $base)->count();
        $online  = (clone $base)->where('status', SiteStatus::Online->value)->count();
        $offline = (clone $base)->where('status', SiteStatus::Offline->value)->count();
        $updates = (int) (clone $base)->where('pending_updates', '>', 0)->sum('pending_updates');

        $sslSoon = (clone $base)
            ->whereNotNull('ssl_expires_at')
            ->whereDate('ssl_expires_at', '<=', now()->addDays(21))
            ->count();

        $domainSoon = (clone $base)
            ->whereNotNull('domain_expires_at')
            ->whereDate('domain_expires_at', '<=', now()->addDays(30))
            ->count();

        $openCritical = Task::query()->open()->where('severity', 'critical')->count();

        return [
            Stat::make('Sites online', "{$online} / {$total}")
                ->description($offline > 0 ? "{$offline} offline" : 'alle erreichbar')
                ->descriptionIcon($offline > 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-check-circle')
                ->color($offline > 0 ? 'danger' : 'success')
                ->chart($this->seriesActivity())
                ->url($offline > 0
                    ? SiteResource::getUrl('index', ['tableFilters' => ['status' => ['value' => SiteStatus::Offline->value]]])
                    : SiteResource::getUrl('index')),

            Stat::make('Ausstehende Updates', (string) $updates)
                ->description('Core, Plugins & Themes')
                ->descriptionIcon($updates > 0 ? 'heroicon-m-arrow-up-circle' : 'heroicon-m-check-circle')
                ->color($updates > 0 ? 'warning' : 'success')
                ->chart($this->seriesUpdates())
                ->url(SiteResource::getUrl('index', ['tableFilters' => ['pending_updates' => ['value' => true]]])),

            Stat::make('SSL läuft bald ab', (string) $sslSoon)
                ->description('≤ 21 Tage')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color($sslSoon > 0 ? 'warning' : 'gray')
                ->url(SiteResource::getUrl('index')),

            Stat::make('Domains laufen bald ab', (string) $domainSoon)
                ->description('≤ 30 Tage')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color($domainSoon > 0 ? 'warning' : 'gray')
                ->url(SiteResource::getUrl('index')),

            Stat::make('Kritische Aufgaben', (string) $openCritical)
                ->description($openCritical > 0 ? 'sofort handeln' : 'nichts Dringendes')
                ->descriptionIcon($openCritical > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($openCritical > 0 ? 'danger' : 'success'),
        ];
    }

    /** 7-Tage-Reihe der Telemetrie-Aktivität (Anzahl Snapshots/Tag). */
    private function seriesActivity(): array
    {
        return rescue(function (): array {
            $counts = SiteSnapshot::query()
                ->where('received_at', '>=', now()->subDays(6)->startOfDay())
                ->get(['received_at'])
                ->groupBy(fn ($s) => optional($s->received_at)?->format('Y-m-d'))
                ->map->count();

            return $this->sevenDays(fn (string $day) => (int) ($counts[$day] ?? 0));
        }, [0, 0, 0, 0, 0, 0, 1], false);
    }

    /** 7-Tage-Reihe ausstehender Updates (aus den Snapshots). */
    private function seriesUpdates(): array
    {
        return rescue(function (): array {
            $byDay = SiteSnapshot::query()
                ->where('received_at', '>=', now()->subDays(6)->startOfDay())
                ->get(['received_at', 'plugins_update', 'themes_update', 'wp_update'])
                ->groupBy(fn ($s) => optional($s->received_at)?->format('Y-m-d'))
                ->map(fn ($g) => $g->sum(fn ($s) => (int) $s->plugins_update + (int) $s->themes_update + (! empty($s->wp_update) ? 1 : 0)));

            return $this->sevenDays(fn (string $day) => (int) ($byDay[$day] ?? 0));
        }, [0, 0, 0, 0, 0, 0, 0], false);
    }

    /** @param  callable(string):int  $value */
    private function sevenDays(callable $value): array
    {
        $out = [];
        for ($i = 6; $i >= 0; $i--) {
            $out[] = $value(now()->subDays($i)->format('Y-m-d'));
        }

        return $out;
    }
}
