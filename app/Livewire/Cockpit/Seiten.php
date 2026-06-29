<?php

namespace App\Livewire\Cockpit;

use App\Models\Site;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.cockpit')]
class Seiten extends Component
{
    use WithPagination;

    public string $search     = '';
    public string $filterStatus = '';
    public string $filterSsl    = '';
    public string $sortBy       = 'severity';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }
    public function updatedFilterSsl(): void { $this->resetPage(); }

    public function render()
    {
        $query = Site::query()
            ->with(['customer', 'packages'])
            ->where('is_archived', false);

        if ($this->search) {
            $query->where(fn ($q) =>
                $q->where('label', 'like', "%{$this->search}%")
                  ->orWhere('url', 'like', "%{$this->search}%")
                  ->orWhereHas('customer', fn ($q2) => $q2->where('name', 'like', "%{$this->search}%"))
            );
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterSsl === 'crit') {
            $query->whereNotNull('ssl_expires_at')->whereDate('ssl_expires_at', '<=', now()->addDays(14));
        } elseif ($this->filterSsl === 'warn') {
            $query->whereNotNull('ssl_expires_at')
                ->whereDate('ssl_expires_at', '>', now()->addDays(14))
                ->whereDate('ssl_expires_at', '<=', now()->addDays(30));
        }

        $sites = $query
            ->orderByRaw("CASE status WHEN 'offline' THEN 1 WHEN 'maintenance' THEN 2 WHEN 'unknown' THEN 3 WHEN 'online' THEN 4 ELSE 5 END")
            ->orderBy('label')
            ->paginate(25);

        $totalCount   = Site::where('is_archived', false)->count();
        $offlineCount = Site::where('is_archived', false)->where('status', 'offline')->count();
        $sslCritCount = Site::where('is_archived', false)->whereNotNull('ssl_expires_at')->whereDate('ssl_expires_at', '<=', now()->addDays(14))->count();

        return view('livewire.cockpit.seiten', compact('sites', 'totalCount', 'offlineCount', 'sslCritCount'));
    }
}
