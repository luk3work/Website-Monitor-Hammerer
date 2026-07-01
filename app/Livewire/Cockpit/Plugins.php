<?php

namespace App\Livewire\Cockpit;

use App\Models\Package;
use App\Models\Plugin;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.cockpit')]
class Plugins extends Component
{
    public string $tab = 'own';   // own | external

    public bool   $showModal = false;
    public string $newName = '';
    public string $newSlug = '';
    public string $newVersion = '';
    public string $newHomepage = '';
    public string $newRepoUrl = '';
    public string $newPackageKey = '';
    public string $newNotes = '';

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['own', 'external'], true) ? $tab : 'own';
    }

    public function openModal(): void
    {
        $this->resetNew();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function createPlugin(): void
    {
        $this->validate([
            'newName'       => 'required|string|max:255',
            'newHomepage'   => 'nullable|url',
            'newRepoUrl'    => 'nullable|url',
        ]);

        Plugin::create([
            'type'            => $this->tab,
            'name'            => $this->newName,
            'slug'            => $this->newSlug ?: Str::slug($this->newName),
            'current_version' => $this->newVersion ?: null,
            'homepage'        => $this->newHomepage ?: null,
            'repo_url'        => $this->newRepoUrl ?: null,
            'package_key'     => $this->newPackageKey ?: null,
            'notes'           => $this->newNotes ?: null,
            'is_active'       => true,
        ]);

        $this->closeModal();
        session()->flash('plugin', 'Plugin gespeichert.');
    }

    public function deletePlugin(int $id): void
    {
        Plugin::query()->whereKey($id)->delete();
    }

    private function resetNew(): void
    {
        $this->newName = $this->newSlug = $this->newVersion = '';
        $this->newHomepage = $this->newRepoUrl = $this->newPackageKey = $this->newNotes = '';
    }

    public function render()
    {
        $own      = Plugin::query()->where('type', 'own')->orderBy('name')->get();
        $external = Plugin::query()->where('type', 'external')->orderBy('name')->get();
        $packages = Package::query()->orderBy('name')->get(['key', 'name']);

        return view('livewire.cockpit.plugins', compact('own', 'external', 'packages'));
    }
}
