<?php

namespace App\Livewire\Forms;

use App\Models\Form;
use App\Models\FormResponse;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Show extends Component
{
    public Form $form;

    public ?string $choice = null;

    public array $answers = [];

    public ?string $signature = null;

    public bool $editing = false;

    public function mount(Form $form): void
    {
        abort_unless($form->published_at && $form->published_at->isPast() && $form->isVisibleTo(auth()->user()), 404);
        $existing = $this->response();
        if ($existing) {
            $this->choice = $existing->choice;
            $this->answers = $existing->answers ?? [];
        } else {
            $this->editing = true;
            if (count($form->choices ?? []) === 1) {
                $this->choice = $form->choices[0];
            }
        }
        auth()->user()->unreadNotifications()->where('data->form_id', $form->id)->update(['read_at' => now()]);
    }

    private function response(): ?FormResponse
    {
        return FormResponse::where('form_id', $this->form->id)->where('user_id', auth()->id())->first();
    }

    public function submit(): void
    {
        abort_unless($this->form->isOpen(), 403);

        $rules = [];
        if ($this->form->choices) {
            $rules['choice'] = ['required', \Illuminate\Validation\Rule::in($this->form->choices)];
        }
        // Zusatzfragen nur bei Zusage bzw. wenn es keine Zu-/Absage gibt
        $needsFields = ! $this->form->choices || ! Form::isDecline($this->choice);
        foreach ($this->form->fields as $field) {
            $key = 'answers.'.$field->id;
            $rules[$key] = match ($field->type) {
                'number' => [$field->required && $needsFields ? 'required' : 'nullable', 'integer', 'min:0', 'max:999'],
                'multiselect' => [$field->required && $needsFields ? 'required' : 'nullable', 'array'],
                'boolean' => [$field->required && $needsFields ? 'accepted' : 'nullable'],
                default => [$field->required && $needsFields ? 'required' : 'nullable', 'string', 'max:2000'],
            };
        }
        if ($this->form->requires_signature && ! $this->response()?->signature_path) {
            $rules['signature'] = ['required', 'string', 'starts_with:data:image/png;base64,'];
        }
        $this->validate($rules, ['signature.required' => 'Bitte unterschreiben Sie im Feld.', 'choice.required' => 'Bitte wählen Sie eine Antwort.'],
            ['answers.*' => 'Antwort']);

        $data = ['choice' => $this->choice, 'answers' => $this->answers];
        if ($this->signature) {
            $png = base64_decode(substr($this->signature, strlen('data:image/png;base64,')));
            abort_if(strlen($png) > 500_000, 422);
            $path = 'signatures/form-'.$this->form->id.'-user-'.auth()->id().'-'.now()->timestamp.'.png';
            Storage::disk('local')->put($path, $png);
            $data['signature_path'] = $path;
        }

        FormResponse::updateOrCreate(['form_id' => $this->form->id, 'user_id' => auth()->id()], $data);
        $this->editing = false;
        $this->signature = null;
        session()->flash('status', 'Danke, Ihre Rückmeldung ist gespeichert.');
    }

    public function render()
    {
        return view('livewire.forms.show', [
            'response' => $this->response(),
            'image' => $this->form->getFirstMedia('image'),
            'attachments' => $this->form->getMedia('attachments'),
        ])->title($this->form->title);
    }
}
