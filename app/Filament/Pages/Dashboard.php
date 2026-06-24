<?php

namespace App\Filament\Pages;

use App\Enums\Severity;
use App\Enums\SiteStatus;
use App\Filament\Resources\SiteResource;
use App\Models\Setting;
use App\Models\Site;
use App\Models\SiteSnapshot;
use App\Models\Task;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Carbon;

/**
 * Maßgeschneidertes Cockpit-Dashboard: Überblick zuerst (Health + KI + KPIs),
 * dann Handlungsbedarf, dann Trends/Abläufe. Alle Werte stammen aus echten
 * Daten (Sites, Aufgaben, Telemetrie). Reine Darstellung, keine Logikänderung.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    /** Eigene Ansicht statt der Standard-Widget-Liste. */
    protected static string $view = 'filament.pages.cockpit-dashboard';

    public function getTitle(): string
    {
        return 'Dashboard';
    }

    public function getSubheading(): ?string
    {
        return 'Portfolio-Überblick — Status, Handlungsbedarf und Abläufe auf einen Blick.';
    }

    /** Alle Kennzahlen für die Ansicht (defensiv gekapselt). */
    protected function getViewData(): array
    {
        return rescue(fn (): array => $this->buildData(), $this->emptyData(), false);
    }

    private function buildData(): array
    {
        $base = fn () => Site::query()->where('is_archived', false);

        $total       = $base()->count();
        $online      = $base()->where('status', SiteStatus::Online->value)->count();
        $maintenance = $base()->where('status', SiteStatus::Maintenance->value)->count();
        $offline     = $base()->where('status', SiteStatus::Offline->value)->count();
        $unknown     = $base()->where('status', SiteStatus::Unknown->value)->count();

        $updates = (int) $base()->where('pending_updates', '>', 0)->sum('pending_updates');
        $sitesWithUpdates = $base()->where('pending_updates', '>', 0)->count();

        $sslSoon    = $base()->whereNotNull('ssl_expires_at')->whereDate('ssl_expires_at', '<=', now()->addDays(30))->count();
        $domainSoon = $base()->whereNotNull('domain_expires_at')->whereDate('domain_expires_at', '<=', now()->addDays(30))->count();

        $openTasks    = Task::query()->open()->count();
        $inProgress   = Task::query()->where('status', 'in_progress')->count();
        $openCritical = Task::query()->open()->where('severity', Severity::Critical->value)->count();

        // ---- Health-Bewertung -------------------------------------------------
        $healthPct = $total > 0 ? (int) round($online / $total * 100) : 100;
        $issues    = $offline + $openCritical;
        $warnings  = $maintenance + $sslSoon + $domainSoon;

        if ($total === 0) {
            $tone = 'ok';
            $healthLabel = 'Bereit';
            $healthText  = 'Noch keine Sites verbunden. Verbinde eine Website oder aktiviere in den Einstellungen die Demo-Daten.';
        } elseif ($issues > 0) {
            $tone = 'crit';
            $healthLabel = 'Handlung nötig';
            $healthText  = trim(($offline > 0 ? "{$offline} Site(s) offline. " : '')
                . ($openCritical > 0 ? "{$openCritical} kritische Aufgabe(n) offen. " : '')
                . 'Bitte zuerst die markierten Punkte unten prüfen.');
        } elseif ($warnings > 0) {
            $tone = 'warn';
            $healthLabel = 'Weitgehend ruhig';
            $healthText  = "{$total} Sites überwacht. Keine kritischen Vorfälle — einige Warnungen (Updates/Abläufe) warten auf dich.";
        } else {
            $tone = 'ok';
            $healthLabel = 'Alles ruhig';
            $healthText  = "{$total} Sites überwacht, alle erreichbar. Keine offenen Warnungen — Ruhezustand.";
        }

        $ringC = 263.89; // 2·π·42
        $healthDash  = round($ringC * $healthPct / 100, 2);
        $healthColor = $tone === 'crit' ? 'var(--oc-rose)' : ($tone === 'warn' ? 'var(--oc-amber)' : 'var(--oc-emerald)');

        // ---- KPIs -------------------------------------------------------------
        $kpis = [
            [
                'label' => 'Sites online', 'icon' => '◉',
                'value' => (string) $online, 'suffix' => $total > 0 ? "/ {$total}" : null,
                'sub' => $offline > 0 ? "{$offline} offline" : 'alle erreichbar',
                'subTone' => $offline > 0 ? 'rose' : 'emerald',
                'spark' => $this->sparkPoints($this->seriesActivity(), '#34d399'),
                'edge' => $offline > 0 ? 'alert' : null,
                'href' => SiteResource::getUrl('index'),
            ],
            [
                'label' => 'Offene Updates', 'icon' => '⟳',
                'value' => (string) $updates, 'suffix' => null,
                'sub' => $sitesWithUpdates > 0 ? "{$sitesWithUpdates} Sites betroffen" : 'alles aktuell',
                'subTone' => $updates > 0 ? 'amber' : 'emerald',
                'spark' => $this->sparkPoints($this->seriesUpdates(), '#fbbf24'),
                'edge' => $updates > 0 ? 'warnedge' : null,
                'href' => SiteResource::getUrl('index'),
            ],
            [
                'label' => 'Abläufe ≤ 30 T', 'icon' => '⚠',
                'value' => (string) ($sslSoon + $domainSoon), 'suffix' => null,
                'sub' => "{$sslSoon} SSL · {$domainSoon} Domain",
                'subTone' => ($sslSoon + $domainSoon) > 0 ? 'amber' : 'neutral',
                'spark' => null,
                'edge' => ($sslSoon + $domainSoon) > 0 ? 'warnedge' : null,
                'href' => SiteResource::getUrl('index'),
            ],
            [
                'label' => 'Offene Aufgaben', 'icon' => '✓',
                'value' => (string) $openTasks, 'suffix' => null,
                'sub' => $inProgress > 0 ? "{$inProgress} in Arbeit" : 'nichts in Arbeit',
                'subTone' => 'sky',
                'spark' => null,
                'edge' => null,
                'href' => null,
            ],
            [
                'label' => 'Kritische Aufgaben', 'icon' => '!',
                'value' => (string) $openCritical, 'suffix' => null,
                'sub' => $openCritical > 0 ? 'sofort handeln' : 'nichts Dringendes',
                'subTone' => $openCritical > 0 ? 'rose' : 'emerald',
                'spark' => null,
                'edge' => $openCritical > 0 ? 'alert' : null,
                'href' => null,
            ],
        ];

        // ---- Handlungs-Queue --------------------------------------------------
        $queue = Task::query()->open()->with('site')
            ->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
            ->orderBy('due_date')
            ->limit(6)->get()
            ->map(function (Task $t) {
                $sev = $t->severity instanceof Severity ? $t->severity : Severity::Info;
                $map = [
                    Severity::Critical->value => ['crit', '!', 'Kritisch'],
                    Severity::Warning->value  => ['warn', '↻', 'Warnung'],
                    Severity::Info->value     => ['info', 'i', 'Info'],
                ];
                [$cls, $glyph, $pill] = $map[$sev->value] ?? $map['info'];

                return [
                    'cls' => $cls, 'glyph' => $glyph, 'pill' => $pill,
                    'title' => $t->title,
                    'site' => $t->site?->label ?? '—',
                    'href' => $t->site ? SiteResource::getUrl('view', ['record' => $t->site]) : null,
                    'age' => $t->created_at ? $this->germanAge($t->created_at) : null,
                    'overdue' => $t->due_date && $t->due_date->isPast(),
                ];
            })->all();

        // ---- Status-Donut -----------------------------------------------------
        $donut = $this->donutSegments([
            ['online', $online, 'var(--oc-emerald)', 'Online'],
            ['maintenance', $maintenance, 'var(--oc-amber)', 'Wartung'],
            ['offline', $offline, 'var(--oc-rose)', 'Offline'],
            ['unknown', $unknown, 'rgba(255,255,255,.22)', 'Unbekannt'],
        ], $total);

        // ---- Ablauf-Timeline --------------------------------------------------
        $expiries = $this->upcomingExpiries($base());

        // ---- Regelbasierte Assistenz / KI ------------------------------------
        $aiActive = ! empty(Setting::get('ai_provider'));
        $insights = $this->buildInsights($base(), $expiries, $maintenance);

        return [
            'total' => $total, 'online' => $online, 'maintenance' => $maintenance,
            'offline' => $offline, 'unknown' => $unknown,
            'healthPct' => $healthPct, 'healthDash' => $healthDash, 'ringC' => $ringC,
            'healthColor' => $healthColor, 'healthLabel' => $healthLabel,
            'healthText' => $healthText, 'tone' => $tone,
            'kpis' => $kpis, 'queue' => $queue, 'openTasks' => $openTasks,
            'donut' => $donut, 'expiries' => $expiries,
            'insights' => $insights, 'aiActive' => $aiActive,
            'updatedAt' => now()->format('d.m.Y, H:i'),
            'tasksUrl' => SiteResource::getUrl('index'),
        ];
    }

    /** Flache, nach Dringlichkeit sortierte Ablaufliste (SSL & Domain, ≤90 Tage). */
    private function upcomingExpiries($query): array
    {
        $rows = [];
        $sites = (clone $query)->where(function ($q) {
            $q->whereDate('ssl_expires_at', '<=', now()->addDays(90))
                ->orWhereDate('domain_expires_at', '<=', now()->addDays(90));
        })->get();

        foreach ($sites as $site) {
            foreach ([['ssl', 'SSL', 'SSL-Zertifikat', $site->ssl_expires_at], ['domain', 'WEB', 'Domain-Verlängerung', $site->domain_expires_at]] as [$type, $tag, $sub, $date]) {
                if (! $date) {
                    continue;
                }
                $days = (int) round(now()->diffInDays($date, false));
                if ($days > 90) {
                    continue;
                }
                $tone = $days < 7 ? 'crit' : ($days <= 30 ? 'soon' : 'ok');
                $rows[] = [
                    'name' => $site->label, 'tag' => $tag, 'sub' => $sub,
                    'days' => $days, 'tone' => $tone,
                    'pct' => max(3, min(100, (int) round($days / 90 * 100))),
                    'color' => $tone === 'crit' ? 'var(--oc-rose)' : ($tone === 'soon' ? 'var(--oc-amber)' : 'var(--oc-emerald)'),
                    'href' => SiteResource::getUrl('view', ['record' => $site]),
                ];
            }
        }

        usort($rows, fn ($a, $b) => $a['days'] <=> $b['days']);

        return array_slice($rows, 0, 5);
    }

    /** Bis zu drei umsetzbare Hinweise aus echten Daten (Mensch entscheidet). */
    private function buildInsights($query, array $expiries, int $maintenance): array
    {
        $out = [];

        if ($maintenance > 0) {
            $site = (clone $query)->where('status', SiteStatus::Maintenance->value)->orderBy('last_seen_at')->first();
            if ($site) {
                $since = $site->last_seen_at ? ' (seit ' . $this->germanAge($site->last_seen_at) . ')' : '';
                $out[] = ['dot' => 'amber', 'html' => '<b>' . e($site->label) . '</b> ist im Wartungsmodus' . e($since) . ' — Status prüfen und ggf. Kunden informieren.'];
            }
        }

        if (! empty($expiries)) {
            $first = $expiries[0];
            $dot = $first['tone'] === 'crit' ? 'rose' : 'sky';
            $out[] = ['dot' => $dot, 'html' => '<b>' . e($first['name']) . '</b>: ' . e($first['sub']) . ' läuft in ' . (int) $first['days'] . ' Tagen ab — Verlängerung einplanen.'];
        }

        $topUpdate = (clone $query)->where('pending_updates', '>', 0)->orderByDesc('pending_updates')->first();
        if ($topUpdate && count($out) < 3) {
            $out[] = ['dot' => 'amber', 'html' => '<b>' . e($topUpdate->label) . '</b>: ' . (int) $topUpdate->pending_updates . ' Updates offen — Wartungsfenster einplanen.'];
        }

        return array_slice($out, 0, 3);
    }

    /** Donut-Segmente als stroke-dasharray/-offset (r=50, C≈314.16). */
    private function donutSegments(array $defs, int $total): array
    {
        $c = 314.16;
        $cum = 0;
        $segments = [];
        foreach ($defs as [$key, $val, $color, $label]) {
            $dash = $total > 0 ? $val / $total * $c : 0;
            $segments[] = [
                'value' => $val, 'label' => $label, 'color' => $color,
                'dash' => round($dash, 2), 'gap' => round($c - $dash, 2),
                'offset' => round(-$cum, 2), 'show' => $val > 0,
            ];
            $cum += $dash;
        }

        return ['total' => $total, 'segments' => $segments];
    }

    /** 96×38-Sparkline-Punkte aus einer Zahlenreihe. */
    private function sparkPoints(array $series, string $color): ?array
    {
        $series = array_values(array_map('intval', $series));
        if (count($series) < 2 || (max($series) === min($series) && max($series) === 0)) {
            return null;
        }
        $min = min($series);
        $max = max($series);
        $span = max(1, $max - $min);
        $n = count($series) - 1;
        $pts = [];
        foreach ($series as $i => $v) {
            $x = round($i / $n * 96, 1);
            $y = round(34 - ($v - $min) / $span * 28, 1);
            $pts[] = "{$x},{$y}";
        }

        return ['points' => implode(' ', $pts), 'color' => $color];
    }

    private function seriesActivity(): array
    {
        return rescue(function (): array {
            $counts = SiteSnapshot::query()
                ->where('received_at', '>=', now()->subDays(6)->startOfDay())
                ->get(['received_at'])
                ->groupBy(fn ($s) => optional($s->received_at)?->format('Y-m-d'))
                ->map->count();

            return $this->sevenDays(fn (string $d) => (int) ($counts[$d] ?? 0));
        }, [0, 0, 0, 0, 0, 0, 0], false);
    }

    private function seriesUpdates(): array
    {
        return rescue(function (): array {
            $byDay = SiteSnapshot::query()
                ->where('received_at', '>=', now()->subDays(6)->startOfDay())
                ->get(['received_at', 'plugins_update', 'themes_update', 'wp_update'])
                ->groupBy(fn ($s) => optional($s->received_at)?->format('Y-m-d'))
                ->map(fn ($g) => $g->sum(fn ($s) => (int) $s->plugins_update + (int) $s->themes_update + (! empty($s->wp_update) ? 1 : 0)));

            return $this->sevenDays(fn (string $d) => (int) ($byDay[$d] ?? 0));
        }, [0, 0, 0, 0, 0, 0, 0], false);
    }

    /** @param callable(string):int $value */
    private function sevenDays(callable $value): array
    {
        $out = [];
        for ($i = 6; $i >= 0; $i--) {
            $out[] = $value(now()->subDays($i)->format('Y-m-d'));
        }

        return $out;
    }

    /** Kurze deutsche Relativzeit ("vor 3 Std", "vor 2 Tg"). */
    private function germanAge(Carbon $dt): string
    {
        $min = (int) round($dt->diffInMinutes(now()));
        if ($min < 60) {
            return 'vor ' . max(1, $min) . ' Min';
        }
        $h = (int) round($dt->diffInHours(now()));
        if ($h < 48) {
            return 'vor ' . $h . ' Std';
        }

        return 'vor ' . (int) round($dt->diffInDays(now())) . ' Tg';
    }

    private function emptyData(): array
    {
        return [
            'total' => 0, 'online' => 0, 'maintenance' => 0, 'offline' => 0, 'unknown' => 0,
            'healthPct' => 100, 'healthDash' => 263.89, 'ringC' => 263.89,
            'healthColor' => 'var(--oc-emerald)', 'healthLabel' => 'Bereit',
            'healthText' => 'Daten werden geladen.', 'tone' => 'ok',
            'kpis' => [], 'queue' => [], 'openTasks' => 0,
            'donut' => ['total' => 0, 'segments' => []], 'expiries' => [],
            'insights' => [], 'aiActive' => false,
            'updatedAt' => now()->format('d.m.Y, H:i'), 'tasksUrl' => '#',
        ];
    }
}
