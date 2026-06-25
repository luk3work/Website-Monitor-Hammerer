<?php

namespace App\Livewire\Cockpit;

use App\Models\Customer;
use App\Models\Site;
use App\Models\Task;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.cockpit')]
class Berichte extends Component
{
    public function render()
    {
        $customers = Customer::query()
            ->with(['sites' => fn ($q) => $q->where('is_archived', false)->with(['packages', 'tasks'])])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $totalSites     = Site::where('is_archived', false)->count();
        $totalUpdates   = Site::where('is_archived', false)->sum('pending_updates');
        $totalOpenTasks = Task::whereIn('status', ['open','in_progress','blocked'])->count();
        $totalSslCrit   = Site::where('is_archived', false)->whereNotNull('ssl_expires_at')->whereDate('ssl_expires_at', '<=', now()->addDays(14))->count();

        return view('livewire.cockpit.berichte', compact('customers', 'totalSites', 'totalUpdates', 'totalOpenTasks', 'totalSslCrit'));
    }
}
