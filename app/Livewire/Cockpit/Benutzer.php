<?php

namespace App\Livewire\Cockpit;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.cockpit')]
class Benutzer extends Component
{
    public function render()
    {
        $users = User::query()->orderBy('name')->get();
        return view('livewire.cockpit.benutzer', compact('users'));
    }
}
