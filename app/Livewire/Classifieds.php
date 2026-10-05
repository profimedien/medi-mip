<?php

namespace App\Livewire;

use App\Models\Classified;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Kleinanzeigen')]
class Classifieds extends Component
{
    use WithFileUploads;

    #[Url]
    public string $category = '';

    #[Url]
    public string $topic = '';

    public bool $showForm = false;

    public array $ad = ['category' => 'private', 'topic' => '', 'title' => '', 'body' => '', 'price' => ''];

    public array $photos = [];

    public function save(): void
    {
        $data = $this->validate([
            'ad.category' => ['required', 'in:private,business'],
            'ad.topic' => ['required', 'in:'.implode(',', Classified::topics())],
            'ad.title' => ['required', 'string', 'max:120'],
            'ad.body' => ['required', 'string', 'max:3000'],
            'ad.price' => ['nullable', 'string', 'max:50'],
            'photos' => ['array', 'max:4'],
            'photos.*' => ['image', 'max:8192'],
        ], [], ['ad.topic' => 'Kategorie', 'ad.title' => 'Titel', 'ad.body' => 'Beschreibung', 'photos.*' => 'Foto'])['ad'];

        $classified = Classified::create($data + ['user_id' => auth()->id(), 'expires_at' => now()->addDays(Classified::RUNTIME_DAYS)]);
        foreach ($this->photos as $photo) {
            $classified->addMedia($photo->getRealPath())->usingFileName($photo->getClientOriginalName())->toMediaCollection('photos');
        }
        $this->reset('ad', 'photos', 'showForm');
        session()->flash('status', 'Ihre Anzeige ist online und läuft '.Classified::RUNTIME_DAYS.' Tage.');
    }

    public function extend(int $id): void
    {
        $c = Classified::where('user_id', auth()->id())->findOrFail($id);
        if (! $c->extended) {
            $c->update(['expires_at' => now()->addDays(Classified::RUNTIME_DAYS), 'extended' => true]);
        }
    }

    public function remove(int $id): void
    {
        $c = Classified::findOrFail($id);
        abort_unless($c->user_id === auth()->id() || auth()->user()->isAdmin(), 403);
        $c->delete();
    }

    public function render()
    {
        return view('livewire.classifieds', [
            'ads' => Classified::running()->with('user', 'media')
                ->when($this->category, fn ($q) => $q->where('category', $this->category))
                ->when($this->topic, fn ($q) => $q->where('topic', $this->topic))
                ->latest()->get(),
            'mine' => Classified::where('user_id', auth()->id())->latest()->get(),
            'topics' => Classified::topics(),
            'counts' => Classified::running()->when($this->category, fn ($q) => $q->where('category', $this->category))
                ->selectRaw('topic, count(*) as n')->groupBy('topic')->pluck('n', 'topic'),
        ]);
    }
}
