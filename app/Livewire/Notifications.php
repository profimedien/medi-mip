<?php

namespace App\Livewire;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Mitteilungen')]
class Notifications extends Component
{
    public function open(string $id)
    {
        $n = auth()->user()->notifications()->findOrFail($id);
        $n->markAsRead();

        return $this->redirect($n->data['url'] ?? route('home'), navigate: true);
    }

    public function readAll(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        return view('livewire.notifications', [
            'items' => auth()->user()->notifications()->latest()->limit(50)->get(),
        ]);
    }
}
