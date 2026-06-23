<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SiteResource;
use App\Models\Site;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Ablauf-Vorschau: Sites mit SSL- oder Domain-Ablauf in den nächsten 90 Tagen,
 * der dringendste zuerst. Gibt dem Dashboard einen vorausschauenden Charakter.
 */
class UpcomingExpiriesTable extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 3];

    protected static ?string $heading = 'Ablauf-Vorschau (SSL & Domain, 90 Tage)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Site::query()
                    ->where('is_archived', false)
                    ->where(function ($q) {
                        $q->whereDate('ssl_expires_at', '<=', now()->addDays(90))
                            ->orWhereDate('domain_expires_at', '<=', now()->addDays(90));
                    })
                    ->orderByRaw("LEAST(COALESCE(ssl_expires_at, '9999-12-31'), COALESCE(domain_expires_at, '9999-12-31')) ASC")
            )
            ->emptyStateHeading('Keine baldigen Abläufe')
            ->emptyStateDescription('In den nächsten 90 Tagen läuft nichts ab.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('label')
                    ->label('Site')
                    ->description(fn (Site $r) => $r->url)
                    ->url(fn (Site $r) => SiteResource::getUrl('view', ['record' => $r]))
                    ->color('primary'),

                TextColumn::make('ssl_expires_at')
                    ->label('SSL')
                    ->date('d.m.Y')
                    ->placeholder('–')
                    ->color(fn ($state) => self::expiryColor($state, 21)),

                TextColumn::make('domain_expires_at')
                    ->label('Domain')
                    ->date('d.m.Y')
                    ->placeholder('–')
                    ->color(fn ($state) => self::expiryColor($state, 30)),
            ]);
    }

    /** Rot bei < 7 Tagen, Gelb innerhalb der Warnfrist, sonst neutral. */
    private static function expiryColor($date, int $warnDays): ?string
    {
        if (! $date) {
            return null;
        }
        $days = (int) round(now()->diffInDays($date, false));
        if ($days < 7) {
            return 'danger';
        }
        if ($days <= $warnDays) {
            return 'warning';
        }
        return null;
    }
}
