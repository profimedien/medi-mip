<?php

namespace App\Livewire\Directory;

use App\Models\User;
use Livewire\Component;

class Show extends Component
{
    public User $user;

    public function mount(User $user): void
    {
        abort_unless($user->status === 'active' && ($user->profile_visible || $user->id === auth()->id()), 404);
    }

    public function render()
    {
        return view('livewire.directory.show')->title($this->user->name);
    }
}
