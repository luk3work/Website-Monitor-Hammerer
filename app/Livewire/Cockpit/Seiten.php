<?php

namespace App\Livewire\Cockpit;

use App\Models\Site;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.cockpit')]
class Seiten extends Component
{
    public string $filter = 'all';
    public string $search = '';

    public function setFilter(string $f): void { $this->filter = $f; }

    public function render()
    {
        $sites = Site::query()
            ->with('customer')
            ->where('is_archived', false)
            ->when($this->search, fn ($q) => $q->where('label', 'like', '%'.$this->search.'%')->orWhere('url', 'like', '%'.$this->search.'%'))
            ->when($this->filter === 'problems', fn ($q) => $q->whereIn('status', ['offline', 'maintenance']))
            ->when($this->filter === 'offline',  fn ($q) => $q->where('status', 'offline'))
            ->orderByRaw("FIELD(status,'offline','maintenance','unknown','online')")
            ->get();

        return view('livewire.cockpit.seiten', compact('sites'));
    }
}
