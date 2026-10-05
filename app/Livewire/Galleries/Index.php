<?php

namespace App\Livewire\Galleries;

use App\Models\Gallery;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Galerien')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.galleries.index', [
            'galleries' => Gallery::visibleTo(auth()->user())->where('published', true)->with('media')->orderByDesc('event_date')->get(),
        ]);
    }
}
