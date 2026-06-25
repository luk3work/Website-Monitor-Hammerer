<?php

namespace App\Livewire\Cockpit;

use App\Models\Site;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.cockpit')]
class Domains extends Component
{
    public string $search    = '';
    public string $filterSsl = '';
    public string $filterDom = '';

    public function render()
    {
        $query = Site::query()
            ->with(['customer'])
            ->where('is_archived', false);

        if ($this->search) {
            $query->where(fn ($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('domain', 'like', "%{$this->search}%")
                  ->orWhereHas('customer', fn ($q2) => $q2->where('name', 'like', "%{$this->search}%"))
            );
        }

        if ($this->filterSsl === 'crit') {
            $query->whereNotNull('ssl_expires_at')->whereDate('ssl_expires_at', '<=', now()->addDays(14));
        } elseif ($this->filterSsl === 'warn') {
            $query->whereNotNull('ssl_expires_at')
                ->whereDate('ssl_expires_at', '>', now()->addDays(14))
                ->whereDate('ssl_expires_at', '<=', now()->addDays(30));
        }

        if ($this->filterDom === 'crit') {
            $query->whereNotNull('domain_expires_at')->whereDate('domain_expires_at', '<=', now()->addDays(30));
        } elseif ($this->filterDom === 'warn') {
            $query->whereNotNull('domain_expires_at')
                ->whereDate('domain_expires_at', '>', now()->addDays(30))
                ->whereDate('domain_expires_at', '<=', now()->addDays(60));
        }

        $sites = $query->orderBy('name')->get();

        // Summary counts
        $sslCrit  = Site::where('is_archived', false)->whereNotNull('ssl_expires_at')->whereDate('ssl_expires_at', '<=', now()->addDays(14))->count();
        $sslWarn  = Site::where('is_archived', false)->whereNotNull('ssl_expires_at')->whereDate('ssl_expires_at', '>', now()->addDays(14))->whereDate('ssl_expires_at', '<=', now()->addDays(30))->count();
        $domCrit  = Site::where('is_archived', false)->whereNotNull('domain_expires_at')->whereDate('domain_expires_at', '<=', now()->addDays(30))->count();
        $domWarn  = Site::where('is_archived', false)->whereNotNull('domain_expires_at')->whereDate('domain_expires_at', '>', now()->addDays(30))->whereDate('domain_expires_at', '<=', now()->addDays(60))->count();

        return view('livewire.cockpit.domains', compact('sites', 'sslCrit', 'sslWarn', 'domCrit', 'domWarn'));
    }
}
