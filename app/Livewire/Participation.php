<?php

namespace App\Livewire;

use App\Models\Location;
use App\Models\MaterialRequest;
use App\Models\Referral;
use App\Models\Setting;
use App\Models\Suggestion;
use App\Notifications\InboxNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Mitmachen')]
class Participation extends Component
{
    #[Url]
    public string $tab = 'vorschlag';

    public array $suggestion = ['topic' => '', 'body' => ''];

    public array $material = ['item' => '', 'quantity' => '', 'location_id' => '', 'needed_by' => '', 'note' => ''];

    public array $referral = ['candidate_name' => '', 'candidate_contact' => '', 'position' => '', 'note' => ''];

    public ?string $done = null;

    public function mount(): void
    {
        $this->material['location_id'] = (string) auth()->user()->location_id;
    }

    public function updatedTab(): void
    {
        $this->done = null;
        $this->resetValidation();
    }

    public function sendSuggestion(): void
    {
        $data = $this->validate([
            'suggestion.topic' => ['required', 'string'],
            'suggestion.body' => ['required', 'string', 'min:10', 'max:5000'],
        ], ['suggestion.topic.required' => 'Bitte ein Thema auswählen.'], ['suggestion.topic' => 'Thema', 'suggestion.body' => 'Vorschlag'])['suggestion'];
        Suggestion::create($data + ['user_id' => auth()->id()]);
        $this->notify('suggestion_recipients', 'Neuer Verbesserungsvorschlag: '.$data['topic'], $data['body'], 'suggestions');
        $this->reset('suggestion');
        $this->done = 'Danke für Ihren Vorschlag! Er ist bei den Zuständigen angekommen.';
    }

    public function sendMaterial(): void
    {
        $data = $this->validate([
            'material.item' => ['required', 'string', 'max:255'],
            'material.quantity' => ['nullable', 'string', 'max:100'],
            'material.location_id' => ['nullable', 'exists:locations,id'],
            'material.needed_by' => ['nullable', 'date', 'after_or_equal:today'],
            'material.note' => ['nullable', 'string', 'max:2000'],
        ], [], ['material.item' => 'Material', 'material.needed_by' => 'Datum'])['material'];
        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);
        $req = MaterialRequest::create($data + ['user_id' => auth()->id()]);
        $loc = $req->location?->label ?? 'ohne Standort';
        $this->notify('material_recipients', 'Materialanfrage: '.$data['item'],
            "Menge: {$req->quantity}\nStandort: {$loc}\nBenötigt bis: ".($req->needed_by?->format('d.m.Y') ?? 'offen')."\n\n".$req->note, 'material-requests');
        $this->reset('material');
        $this->mount();
        $this->done = 'Ihre Materialanfrage ist verschickt.';
    }

    public function sendReferral(): void
    {
        $data = $this->validate([
            'referral.candidate_name' => ['required', 'string', 'max:255'],
            'referral.candidate_contact' => ['required', 'string', 'max:255'],
            'referral.position' => ['nullable', 'string', 'max:255'],
            'referral.note' => ['nullable', 'string', 'max:2000'],
        ], [], ['referral.candidate_name' => 'Name', 'referral.candidate_contact' => 'Kontakt'])['referral'];
        Referral::create($data + ['user_id' => auth()->id()]);
        $this->notify('referral_recipients', 'Empfehlung: '.$data['candidate_name'],
            "Kontakt: {$data['candidate_contact']}\nStelle: ".($data['position'] ?: 'offen')."\n\n".($data['note'] ?? ''), 'referrals');
        $this->reset('referral');
        $this->done = 'Danke für Ihre Empfehlung! Die Personalabteilung meldet sich.';
    }

    private function notify(string $setting, string $subject, string $body, string $adminPath): void
    {
        $recipients = Setting::emails($setting);
        if ($recipients) {
            Notification::route('mail', $recipients)->notify(new InboxNotification($subject, 'Von '.auth()->user()->name.':', $body, url('/admin/'.$adminPath)));
        }
    }

    public function render()
    {
        return view('livewire.participation', [
            'topics' => array_filter(array_map('trim', explode("\n", (string) Setting::get('suggestion_topics', 'Sonstiges')))),
            'locations' => Location::where('active', true)->orderBy('sort')->get(),
            'referralText' => Setting::get('referral_text', ''),
        ]);
    }
}
