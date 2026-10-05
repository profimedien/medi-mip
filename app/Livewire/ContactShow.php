<?php

namespace App\Livewire;

use App\Models\Contact;
use Livewire\Component;

class ContactShow extends Component
{
    public Contact $contact;

    public function render()
    {
        return view('livewire.contact-show', [
            'photo' => $this->contact->getFirstMedia('photo'),
            'others' => Contact::where('id', '!=', $this->contact->id)
                ->where(fn ($q) => $q->whereNull('location_id')->orWhere('location_id', $this->contact->location_id))
                ->orderBy('sort')->limit(4)->get(),
        ])->title($this->contact->name);
    }
}
