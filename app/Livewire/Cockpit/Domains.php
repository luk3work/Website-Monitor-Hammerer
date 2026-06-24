<?php

namespace App\Livewire\Cockpit;

use App\Models\Site;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.cockpit')]
class Domains extends Component
{
    public string $filter = 'all';
    public string $search = '';

    public function setFilter(string $f): void { $this->filter = $f; }

    public function render()
    {
        $rows = Site::query()->with('customer')->where('is_archived', false)->get()
            ->filter(fn ($s) => $this->search === '' || str_contains(mb_strtolower($s->label.$s->url), mb_strtolower($this->search)))
            ->map(function (Site $s) {
                $sslDays = $s->ssl_expires_at  ? (int)$s->ssl_expires_at->diffInDays(now(), false)  : null;
                $domDays = $s->domain_expires_at ? (int)$s->domain_expires_at->diffInDays(now(), false) : null;
                $sslTone = $sslDays === null ? 'neutral' : ($sslDays < 0 ? 'crit' : ($sslDays <= 14 ? 'crit' : ($sslDays <= 45 ? 'warn' : 'ok')));
                $domTone = $domDays === null ? 'neutral' : ($domDays < 0 ? 'crit' : ($domDays <= 30 ? 'crit' : ($domDays <= 90 ? 'warn' : 'ok')));
                $worst   = ($sslTone === 'crit' || $domTone === 'crit') ? 'crit' : (($sslTone === 'warn' || $domTone === 'warn') ? 'warn' : ($sslTone === 'neutral' && $domTone === 'neutral' ? 'neutral' : 'ok'));
                return compact('s', 'sslDays', 'domDays', 'sslTone', 'domTone', 'worst');
            })
            ->filter(fn ($r) => match($this->filter) {
                'critical' => $r['worst'] === 'crit',
                'warning'  => in_array($r['worst'], ['warn', 'crit']),
                'ok'       => $r['worst'] === 'ok',
                default    => true,
            })
            ->sortBy(fn ($r) => match($r['worst']) { 'crit' => 0, 'warn' => 1, default => 2 });

        $summary = [
            'total'    => $rows->count(),
            'critical' => $rows->where('worst', 'crit')->count(),
            'warning'  => $rows->where('worst', 'warn')->count(),
            'ok'       => $rows->where('worst', 'ok')->count(),
        ];

        return view('livewire.cockpit.domains', compact('rows', 'summary'));
    }
}
