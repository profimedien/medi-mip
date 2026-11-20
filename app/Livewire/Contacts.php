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
    /** Einrichtungs-ID oder "alle". Standard: eigene Einrichtung der Person */
    #[Url]
    public string $location = '';

    /** leer = intern und extern (Standard) */
    #[Url]
    public string $type = '';

    public function mount(): void
    {
        if ($this->location === '') {
            $this->location = (string) (auth()->user()?->location_id ?? 'alle');
        }
    }

    public function render()
    {
        return view('livewire.contacts', [
            'contacts' => Contact::with('locations', 'media')
                ->when(array_key_exists($this->type, Contact::TYPES), fn ($q) => $q->where('type', $this->type))
                ->when(ctype_digit($this->location), fn ($q) => $q->forLocation((int) $this->location))
                ->orderBy('sort')->get(),
            'locations' => Location::where('active', true)->orderBy('sort')->get(),
        ]);
    }
}
