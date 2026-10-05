<?php

namespace App\Livewire\Forms;

use App\Models\Form;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Rückmeldungen')]
class Index extends Component
{
    public function render()
    {
        $user = auth()->user();
        $forms = Form::published()->visibleTo($user)
            ->with(['responses' => fn ($q) => $q->where('user_id', $user->id)])
            ->orderByRaw('deadline is null')->orderBy('deadline')->get();

        return view('livewire.forms.index', [
            'open' => $forms->filter(fn ($f) => $f->responses->isEmpty() && $f->isOpen()),
            'done' => $forms->filter(fn ($f) => $f->responses->isNotEmpty() || ! $f->isOpen()),
        ]);
    }
}
