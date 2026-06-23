<?php

namespace App\Filament\Widgets;

use App\Enums\SiteStatus;
use App\Models\Site;
use Filament\Widgets\ChartWidget;

/**
 * Verteilung der Site-Status als Doughnut. Gibt auf einen Blick das Verhältnis
 * online/Wartung/offline/unbekannt – passend zum Leitsatz "Alles grün = Ruhe".
 */
class SiteStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Status-Verteilung';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 2];

    protected static ?string $maxHeight = '240px';

    /** Feste Farbzuordnung passend zur Ampel-Logik. */
    private const COLORS = [
        'online'      => '#16a34a', // grün
        'maintenance' => '#f59e0b', // amber
        'offline'     => '#dc2626', // rot
        'unknown'     => '#6b7280', // grau
    ];

    protected function getData(): array
    {
        $base = Site::query()->where('is_archived', false);

        $labels = [];
        $values = [];
        $colors = [];

        foreach (SiteStatus::cases() as $case) {
            $count = (clone $base)->where('status', $case->value)->count();

            // Leere, unkritische Kategorien nicht anzeigen (ruhiges Bild).
            if ($count === 0 && $case !== SiteStatus::Online) {
                continue;
            }

            $labels[] = $case->label();
            $values[] = $count;
            $colors[] = self::COLORS[$case->value] ?? '#6b7280';
        }

        return [
            'datasets' => [[
                'data'            => $values,
                'backgroundColor' => $colors,
                'borderWidth'     => 0,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
        ];
    }
}
