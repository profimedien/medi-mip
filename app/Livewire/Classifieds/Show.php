<?php

namespace App\Livewire\Classifieds;

use App\Models\Classified;
use Livewire\Component;

class Show extends Component
{
    public Classified $classified;

    public function mount(Classified $classified): void
    {
        abort_unless($classified->expires_at->isFuture() || $classified->user_id === auth()->id() || auth()->user()->isAdmin(), 404);
    }

    public function extend(): void
    {
        abort_unless($this->classified->user_id === auth()->id(), 403);
        if (! $this->classified->extended) {
            $this->classified->update(['expires_at' => now()->addDays(Classified::RUNTIME_DAYS), 'extended' => true]);
        }
    }

    public function remove()
    {
        abort_unless($this->classified->user_id === auth()->id() || auth()->user()->isAdmin(), 403);
        $this->classified->delete();

        return $this->redirectRoute('classifieds', navigate: true);
    }

    public function render()
    {
        return view('livewire.classifieds.show', [
            'photos' => $this->classified->getMedia('photos'),
            'seller' => $this->classified->user->load('location'),
            'more' => Classified::running()->where('id', '!=', $this->classified->id)->where('category', $this->classified->category)->with('media')->latest()->limit(3)->get(),
        ])->title($this->classified->title);
    }
}
