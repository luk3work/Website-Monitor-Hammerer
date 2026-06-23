<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Widgets\ChartWidget;

/**
 * Offene Aufgaben gruppiert nach Typ – zeigt, wo die Arbeit gerade liegt
 * (Updates, Erreichbarkeit, Abläufe). Ergänzt die "Braucht Handlung"-Liste
 * um die aggregierte Sicht.
 */
class OpenTasksByTypeChart extends ChartWidget
{
    protected static ?string $heading = 'Offene Aufgaben nach Typ';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 3];

    protected static ?string $maxHeight = '240px';

    /** Technische Typen -> verständliche deutsche Labels. */
    private const LABELS = [
        'update'         => 'Updates',
        'offline'        => 'Offline',
        'ssl_expiry'     => 'SSL-Ablauf',
        'domain_expiry'  => 'Domain-Ablauf',
        'license_expiry' => 'Lizenz-Ablauf',
    ];

    protected function getData(): array
    {
        // Offene Aufgaben je Typ zählen (DB-agnostisch in PHP gruppiert).
        $counts = Task::query()
            ->open()
            ->selectRaw('type, COUNT(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type');

        $labels = [];
        $values = [];

        foreach (self::LABELS as $type => $label) {
            $labels[] = $label;
            $values[] = (int) ($counts[$type] ?? 0);
        }

        // Unbekannte (zukünftige) Typen ergänzen, damit nichts verloren geht.
        foreach ($counts as $type => $count) {
            if (! array_key_exists($type, self::LABELS)) {
                $labels[] = ucfirst((string) $type);
                $values[] = (int) $count;
            }
        }

        return [
            'datasets' => [[
                'label'           => 'Offene Aufgaben',
                'data'            => $values,
                'backgroundColor' => '#0ea5e9', // sky – passend zur Primärfarbe
                'borderRadius'    => 4,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks'       => ['precision' => 0],
                ],
            ],
        ];
    }
}
