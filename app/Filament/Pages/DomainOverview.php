<?php

namespace App\Filament\Pages;

use App\Models\Site;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DomainOverview extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Domains';
    protected static ?string $title = 'Domains & SSL';
    protected static ?string $navigationGroup = 'Betrieb';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'domains';
    protected static string $view = 'filament.pages.domain-overview';

    public string $filter = 'all';
    public string $search = '';

    public function setFilter(string $f): void { $this->filter = $f; }

    public function rows(): Collection
    {
        $today = now()->startOfDay();

        return Site::query()
            ->with('customer')
            ->where('is_archived', false)
            ->get()
            ->filter(function (Site $s) {
                if ($this->search !== '') {
                    $q = mb_strtolower($this->search);
                    if (!str_contains(mb_strtolower($s->label), $q) &&
                        !str_contains(mb_strtolower($s->url), $q)) {
                        return false;
                    }
                }
                return true;
            })
            ->map(function (Site $s) use ($today) {
                $sslDays  = $s->ssl_expires_at  ? (int) $today->diffInDays($s->ssl_expires_at,  false) : null;
                $domDays  = $s->domain_expires_at ? (int) $today->diffInDays($s->domain_expires_at, false) : null;

                $sslTone  = $sslDays === null ? 'neutral' : ($sslDays < 0 ? 'crit' : ($sslDays <= 14 ? 'crit' : ($sslDays <= 45 ? 'warn' : 'ok')));
                $domTone  = $domDays === null ? 'neutral' : ($domDays < 0 ? 'crit' : ($domDays <= 30 ? 'crit' : ($domDays <= 90 ? 'warn' : 'ok')));

                $worst = 'ok';
                if ($sslTone === 'crit' || $domTone === 'crit') $worst = 'crit';
                elseif ($sslTone === 'warn' || $domTone === 'warn') $worst = 'warn';
                elseif ($sslTone === 'neutral' && $domTone === 'neutral') $worst = 'neutral';

                return compact('s', 'sslDays', 'domDays', 'sslTone', 'domTone', 'worst');
            })
            ->filter(function ($row) {
                return match ($this->filter) {
                    'critical' => $row['worst'] === 'crit',
                    'warning'  => in_array($row['worst'], ['warn', 'crit']),
                    'ok'       => $row['worst'] === 'ok',
                    default    => true,
                };
            })
            ->sortBy(fn ($r) => match ($r['worst']) {
                'crit' => 0, 'warn' => 1, default => 2,
            });
    }

    public function summary(): array
    {
        $all  = $this->rows();
        return [
            'total'    => $all->count(),
            'critical' => $all->where('worst', 'crit')->count(),
            'warning'  => $all->where('worst', 'warn')->count(),
            'ok'       => $all->where('worst', 'ok')->count(),
        ];
    }
}
