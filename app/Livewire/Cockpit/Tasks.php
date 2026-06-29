<?php

namespace App\Livewire\Cockpit;

use App\Models\Site;
use App\Models\Task;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.cockpit')]
class Tasks extends Component
{
    public string $search       = '';
    public string $filterStatus = '';
    public string $filterSev    = '';
    public ?int   $filterUser   = null;

    public bool   $showModal    = false;
    public string $newTitle     = '';
    public int    $newSiteId    = 0;
    public string $newSeverity  = 'warning';
    public string $newType      = 'manual';
    public string $newDue       = '';
    public ?int   $newAssignee  = null;
    public string $newDesc      = '';

    public function updatedSearch(): void { }
    public function updatedFilterStatus(): void { }
    public function updatedFilterSev(): void { }

    public function openModal(): void { $this->showModal = true; $this->resetNew(); }
    public function closeModal(): void { $this->showModal = false; }

    public function createTask(): void
    {
        $this->validate([
            'newTitle'    => 'required|string|max:255',
            'newSeverity' => 'required|in:info,warning,critical',
            'newType'     => 'required|string',
        ]);

        Task::create([
            'title'       => $this->newTitle,
            'description' => $this->newDesc ?: null,
            'site_id'     => $this->newSiteId ?: null,
            'severity'    => $this->newSeverity,
            'type'        => $this->newType,
            'status'      => 'open',
            'due_date'    => $this->newDue ?: null,
            'assigned_to' => $this->newAssignee ?: null,
        ]);

        $this->closeModal();
        session()->flash('created', 'Aufgabe erstellt.');
    }

    public function updateStatus(int $taskId, string $status): void
    {
        $task = Task::findOrFail($taskId);
        $task->update([
            'status'      => $status,
            'resolved_at' => in_array($status, ['done', 'dismissed']) ? now() : null,
        ]);
    }

    private function resetNew(): void
    {
        $this->newTitle = $this->newDesc = $this->newDue = '';
        $this->newSiteId = 0;
        $this->newSeverity = 'warning';
        $this->newType = 'manual';
        $this->newAssignee = null;
    }

    public function render()
    {
        $query = Task::query()->with(['site.customer', 'assignee']);

        if ($this->search) {
            $query->where('title', 'like', "%{$this->search}%");
        }
        if ($this->filterStatus) {
            $this->filterStatus === 'open'
                ? $query->whereIn('status', ['open', 'in_progress', 'blocked'])
                : $query->where('status', $this->filterStatus);
        }
        if ($this->filterSev)  { $query->where('severity', $this->filterSev); }
        if ($this->filterUser) { $query->where('assigned_to', $this->filterUser); }

        $tasks = $query->orderByRaw("FIELD(severity,'critical','warning','info')")
            ->orderByRaw("FIELD(status,'open','in_progress','blocked','done','dismissed')")
            ->orderBy('due_date')
            ->paginate(30);

        $openCount  = Task::whereIn('status', ['open','in_progress','blocked'])->count();
        $critCount  = Task::whereIn('status', ['open','in_progress','blocked'])->where('severity','critical')->count();
        $doneToday  = Task::where('status','done')->whereDate('resolved_at', today())->count();
        $sites      = Site::where('is_archived', false)->with('customer')->orderBy('label')->get();
        $users      = User::orderBy('name')->get();

        return view('livewire.cockpit.tasks', compact('tasks', 'openCount', 'critCount', 'doneToday', 'sites', 'users'));
    }
}
