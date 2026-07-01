<?php

namespace App\Livewire\Cockpit;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.cockpit')]
class Benutzer extends Component
{
    use WithFileUploads;

    public $avatar = null;

    /** Bei Auswahl sofort speichern (einfaches UX). */
    public function updatedAvatar(): void
    {
        $this->validate(['avatar' => 'image|max:4096']); // max 4 MB

        $user = auth()->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->avatar_path = $this->avatar->store('avatars', 'public');
        $user->save();

        $this->avatar = null;
        session()->flash('profile', 'Profilbild aktualisiert.');
    }

    public function removeAvatar(): void
    {
        $user = auth()->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
            $user->save();
        }
    }

    public function render()
    {
        $users = User::query()->orderBy('name')->get();

        return view('livewire.cockpit.benutzer', compact('users'));
    }
}
