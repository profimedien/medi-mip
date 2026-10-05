<?php

namespace App\Livewire;

use App\Models\OrgUnit;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Organigramm')]
class OrgChart extends Component
{
    public function render()
    {
        return view('livewire.orgchart', [
            'roots' => OrgUnit::whereNull('parent_id')->with('head', 'children.head', 'children.children.head', 'children.children.children')->orderBy('sort')->get(),
        ]);
    }
}
