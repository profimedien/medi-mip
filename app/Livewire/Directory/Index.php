<?php

namespace App\Livewire\Directory;

use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Verzeichnis')]
class Index extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $location = '';

    #[Url]
    public string $department = '';

    public int $limit = 30;

    public function updated(): void
    {
        $this->limit = 30;
    }

    public function render()
    {
        $query = User::query()->where('status', 'active')->where('profile_visible', true)->with('location', 'departments', 'media')
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('first_name', 'like', "%{$this->search}%")
                ->orWhere('last_name', 'like', "%{$this->search}%")->orWhere('job_title', 'like', "%{$this->search}%")
                ->orWhere('qualifications', 'like', "%{$this->search}%")))
            ->when($this->location, fn ($q) => $q->where('location_id', $this->location))
            ->when($this->department, fn ($q) => $q->whereHas('departments', fn ($d) => $d->where('departments.id', $this->department)))
            ->orderBy('last_name')->orderBy('first_name');

        $dept = $this->department ? Department::find($this->department) : null;

        return view('livewire.directory.index', [
            'people' => (clone $query)->limit($this->limit)->get(),
            'total' => $query->count(),
            'locations' => Location::where('active', true)->orderBy('sort')->get(),
            'departments' => Department::orderBy('name')->get(),
            'currentDepartment' => $dept,
        ]);
    }
}
