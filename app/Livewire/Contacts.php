<?php

namespace App\Livewire;

use App\Models\Contact;
use App\Models\Location;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Ansprechpartner')]
class Contacts extends Component
{
    #[Url]
    public string $location = '';

    /** leer = intern und extern (Standard) */
    #[Url]
    public string $type = '';

    public function render()
    {
        return view('livewire.contacts', [
            'contacts' => Contact::with('location', 'media')
                ->when(array_key_exists($this->type, Contact::TYPES), fn ($q) => $q->where('type', $this->type))
                ->when($this->location, fn ($q) => $q->where(fn ($q) => $q->whereNull('location_id')->orWhere('location_id', $this->location)))
                ->orderBy('sort')->get(),
            'locations' => Location::where('active', true)->orderBy('sort')->get(),
        ]);
    }
}
