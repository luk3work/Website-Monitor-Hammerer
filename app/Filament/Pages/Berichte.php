<?php

namespace App\Filament\Pages;

use App\Models\Site;
use App\Models\Task;
use App\Models\SiteSnapshot;
use App\Models\Customer;
use App\Models\License;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class Berichte extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Berichte';
    protected static ?string $title = 'Berichte & Auswertungen';
    protected static ?string $navigationGroup = 'Verwaltung';
    protected static ?int $navigationSort = 10;
    protected static ?string $slug = 'berichte';
    protected static string $view = 'filament.pages.berichte';

    public string $period = '30';

    public function setPeriod(string $p): void { $this->period = $p; }

    public function kpis(): array
    {
        $since = now()->subDays((int) $this->period);

        $totalSites    = Site::query()->where('is_archived', false)->count();
        $totalCustomers = Customer::count();
        $tasksOpened   = Task::query()->where('created_at', '>=', $since)->count();
        $tasksClosed   = Task::query()->whereNotNull('resolved_at')->where('resolved_at', '>=', $since)->count();
        $snapshots     = SiteSnapshot::query()->where('received_at', '>=', $since)->count();

        $sslCrit  = Site::query()->where('is_archived', false)
            ->whereNotNull('ssl_expires_at')
            ->whereDate('ssl_expires_at', '<=', now()->addDays(30))->count();
        $domCrit  = Site::query()->where('is_archived', false)
            ->whereNotNull('domain_expires_at')
            ->whereDate('domain_expires_at', '<=', now()->addDays(60))->count();
        $updates  = Site::query()->where('is_archived', false)
            ->where('pending_updates', '>', 0)->sum('pending_updates');

        return compact('totalSites', 'totalCustomers', 'tasksOpened', 'tasksClosed', 'snapshots', 'sslCrit', 'domCrit', 'updates');
    }

    /** Last 12 months: snapshot count per month */
    public function snapshotChart(): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $cnt = SiteSnapshot::query()
                ->whereYear('received_at', $m->year)
                ->whereMonth('received_at', $m->month)
                ->count();
            $months[] = ['label' => $m->format('M'), 'value' => $cnt];
        }
        $max = max(1, collect($months)->max('value'));
        foreach ($months as &$m) {
            $m['pct'] = round(($m['value'] / $max) * 100);
        }
        return $months;
    }

    /** Tasks grouped by type, last N days */
    public function tasksByType(): array
    {
        $since = now()->subDays((int) $this->period);
        return Task::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('type, count(*) as cnt')
            ->groupBy('type')
            ->orderByDesc('cnt')
            ->get()
            ->map(fn ($r) => ['type' => $r->type, 'cnt' => $r->cnt])
            ->toArray();
    }

    /** Upcoming expiries (next 90 days) */
    public function upcomingExpiries(): array
    {
        $rows = [];

        // SSL
        Site::query()->where('is_archived', false)
            ->whereNotNull('ssl_expires_at')
            ->whereDate('ssl_expires_at', '<=', now()->addDays(90))
            ->with('customer')
            ->orderBy('ssl_expires_at')
            ->limit(10)
            ->get()
            ->each(function (Site $s) use (&$rows) {
                $days = now()->diffInDays($s->ssl_expires_at, false);
                $rows[] = [
                    'tag'   => 'SSL',
                    'name'  => $s->label,
                    'sub'   => $s->customer?->name ?? '—',
                    'days'  => (int) $days,
                    'tone'  => $days < 14 ? 'crit' : ($days < 45 ? 'warn' : 'ok'),
                    'href'  => \App\Filament\Resources\SiteResource::getUrl('edit', ['record' => $s]),
                ];
            });

        // Domain
        Site::query()->where('is_archived', false)
            ->whereNotNull('domain_expires_at')
            ->whereDate('domain_expires_at', '<=', now()->addDays(90))
            ->with('customer')
            ->orderBy('domain_expires_at')
            ->limit(6)
            ->get()
            ->each(function (Site $s) use (&$rows) {
                $days = now()->diffInDays($s->domain_expires_at, false);
                $rows[] = [
                    'tag'   => 'DOM',
                    'name'  => $s->label,
                    'sub'   => $s->customer?->name ?? '—',
                    'days'  => (int) $days,
                    'tone'  => $days < 30 ? 'crit' : ($days < 60 ? 'warn' : 'ok'),
                    'href'  => \App\Filament\Resources\SiteResource::getUrl('edit', ['record' => $s]),
                ];
            });

        // Licenses
        License::query()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', now()->addDays(90))
            ->with('site.customer')
            ->orderBy('expires_at')
            ->limit(6)
            ->get()
            ->each(function (License $l) use (&$rows) {
                $days = now()->diffInDays($l->expires_at, false);
                $rows[] = [
                    'tag'   => 'LIZ',
                    'name'  => $l->product_name ?? 'Lizenz',
                    'sub'   => $l->site?->label ?? '—',
                    'days'  => (int) $days,
                    'tone'  => $days < 14 ? 'crit' : ($days < 30 ? 'warn' : 'ok'),
                    'href'  => '#',
                ];
            });

        usort($rows, fn ($a, $b) => $a['days'] <=> $b['days']);
        return array_slice($rows, 0, 15);
    }

    /** Sites by status for pie */
    public function statusBreakdown(): array
    {
        $groups = Site::query()->where('is_archived', false)
            ->selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        return [
            ['label' => 'Online',    'color' => '#34d399', 'cnt' => $groups['online']      ?? 0],
            ['label' => 'Wartung',   'color' => '#fbbf24', 'cnt' => $groups['maintenance'] ?? 0],
            ['label' => 'Offline',   'color' => '#fb4d63', 'cnt' => $groups['offline']     ?? 0],
            ['label' => 'Unbekannt', 'color' => 'rgba(255,255,255,.25)', 'cnt' => $groups['unknown'] ?? 0],
        ];
    }
}
