<?php

namespace App\Livewire\Cockpit;

use App\Models\Customer;
use App\Models\Task;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.cockpit')]
class Kunden extends Component
{
    public ?int   $customerId = null;
    public ?int   $siteId     = null;
    public string $tab        = 'overview';
    public string $search     = '';

    public function selectCustomer(int $id): void
    {
        $this->customerId = $id;
        $this->siteId     = null;
        $this->tab        = 'overview';
    }

    public function selectSite(int $id): void
    {
        $this->siteId = $id;
        $this->tab    = 'overview';
    }

    public function setTab(string $tab): void { $this->tab = $tab; }

    public function render()
    {
        $customers = Customer::query()
            ->with(['sites' => fn ($q) => $q->where('is_archived', false)->with('packages')])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($c) {
                $c->_severity = $this->customerSeverity($c);
                return $c;
            })
            ->sortBy(fn ($c) => match($c->_severity) { 'critical'=>0, 'warning'=>1, default=>2 });

        $customer    = $this->customerId ? Customer::with(['sites.packages', 'sites.tasks', 'contacts'])->find($this->customerId) : null;
        $currentSite = $this->siteId    ? $customer?->sites->firstWhere('id', $this->siteId) : null;

        $users = User::orderBy('name')->get();

        return view('livewire.cockpit.kunden', compact('customers', 'customer', 'currentSite', 'users'));
    }

    private function customerSeverity(Customer $c): string
    {
        foreach ($c->sites as $s) {
            if ($s->status?->value === 'offline') return 'critical';
            if (($s->sslDaysLeft() !== null && $s->sslDaysLeft() < 14) ||
                ($s->domainDaysLeft() !== null && $s->domainDaysLeft() < 30)) return 'critical';
        }
        foreach ($c->sites as $s) {
            if (($s->sslDaysLeft() !== null && $s->sslDaysLeft() < 30) ||
                ($s->domainDaysLeft() !== null && $s->domainDaysLeft() < 60) ||
                ($s->pending_updates ?? 0) >= 3) return 'warning';
        }
        return 'ok';
    }
}
