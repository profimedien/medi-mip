<?php

namespace App\Livewire\Galleries;

use App\Models\Gallery;
use Livewire\Component;

class Show extends Component
{
    public Gallery $gallery;

    public function mount(Gallery $gallery): void
    {
        abort_unless($gallery->published && $gallery->isVisibleTo(auth()->user()), 404);
    }

    public function render()
    {
        return view('livewire.galleries.show', ['photos' => $this->gallery->getMedia('photos')])->title($this->gallery->title);
    }
}
