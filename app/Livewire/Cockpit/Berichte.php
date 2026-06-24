<?php

namespace App\Livewire\Cockpit;

use App\Models\Customer;
use App\Models\Site;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.cockpit')]
class Berichte extends Component
{
    public function render()
    {
        $customers = Customer::query()->with('sites')->get();
        $totalSites = Site::query()->where('is_archived', false)->count();
        $pendingUpdates = Site::query()->where('is_archived', false)->sum('pending_updates');
        $sslAlerts = Site::query()->where('is_archived', false)->whereNotNull('ssl_expires_at')->whereDate('ssl_expires_at', '<=', now()->addDays(30))->count();

        return view('livewire.cockpit.berichte', compact('customers', 'totalSites', 'pendingUpdates', 'sslAlerts'));
    }
}
