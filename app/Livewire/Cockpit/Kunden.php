<?php

namespace App\Livewire\Cockpit;

use App\Models\Customer;
use App\Models\Site;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.cockpit')]
class Kunden extends Component
{
    public ?int $customerId = null;
    public ?int $siteId = null;
    public string $tab = 'overview';
    public string $search = '';
    public string $filter = 'all';

    public function mount(): void
    {
        $first = Customer::query()->orderBy('name')->first();
        if ($first) {
            $this->customerId = $first->id;
            $this->siteId = Site::query()->where('customer_id', $first->id)->where('is_archived', false)->value('id');
        }
    }

    public function selectCustomer(int $id): void
    {
        $this->customerId = $id;
        $this->tab = 'overview';
        $this->siteId = Site::query()->where('customer_id', $id)->where('is_archived', false)->orderBy('label')->value('id');
    }

    public function selectSite(int $id): void { $this->siteId = $id; $this->tab = 'overview'; }
    public function setTab(string $t): void { $this->tab = $t; }
    public function setFilter(string $f): void { $this->filter = $f; }

    public function customersList()
    {
        $q = Customer::query()->with(['sites' => fn ($q) => $q->where('is_archived', false)])->orderBy('name');
        if ($this->search !== '') {
            $q->where('name', 'like', '%'.$this->search.'%');
        }
        $list = $q->get();
        if ($this->filter === 'problems') {
            $list = $list->filter(fn ($c) => $c->sites->contains(fn ($s) => in_array($s->status->value, ['offline', 'maintenance']) || ($s->ssl_expires_at && $s->ssl_expires_at->diffInDays(now(), false) <= 30)));
        }
        if ($this->filter === 'critical') {
            $list = $list->filter(fn ($c) => $c->sites->contains(fn ($s) => $s->status->value === 'offline'));
        }
        return $list->values();
    }

    public function currentCustomer(): ?Customer
    {
        return $this->customerId ? Customer::query()->with(['sites' => fn ($q) => $q->where('is_archived', false)->with('plugins', 'licenses', 'tasks')])->find($this->customerId) : null;
    }

    public function currentSite(): ?Site
    {
        return $this->siteId ? Site::query()->with(['plugins', 'licenses', 'tasks', 'latestSnapshot'])->find($this->siteId) : null;
    }

    public function customerSeverity(Customer $c): string
    {
        foreach ($c->sites as $s) {
            if ($s->status->value === 'offline') return 'crit';
        }
        foreach ($c->sites as $s) {
            if ($s->status->value === 'maintenance' || ($s->ssl_expires_at && $s->ssl_expires_at->diffInDays(now(), false) <= 30)) return 'warn';
        }
        return 'ok';
    }

    public function render()
    {
        $pal = ['#d7263d','#0ea5e9','#10b981','#a855f7','#f59e0b','#14b8a6','#ef4444'];
        return view('livewire.cockpit.kunden', [
            'customers' => $this->customersList(),
            'customer'  => $this->currentCustomer(),
            'site'      => $this->currentSite(),
            'pal'       => $pal,
        ]);
    }
}
