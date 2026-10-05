<?php

namespace Tests\Feature;

use App\Livewire\Forms\Show;
use App\Models\Form;
use App\Models\FormResponse;
use Livewire\Livewire;
use Tests\TestCase;

class FormResponseTest extends TestCase
{
    private function form(array $attrs = []): Form
    {
        $form = Form::create($attrs + ['title' => 'Sommerfest', 'choices' => ['zusage', 'absage'], 'audience_all' => true, 'published_at' => now()->subMinute(), 'deadline' => now()->addWeek()]);
        $form->fields()->create(['type' => 'number', 'label' => 'Begleitpersonen', 'required' => true, 'sort' => 0]);

        return $form;
    }

    public function test_zusage_mit_pflichtfeld(): void
    {
        $form = $this->form();
        $user = $this->person();
        $field = $form->fields->first();

        Livewire::actingAs($user)->test(Show::class, ['form' => $form])
            ->set('choice', 'zusage')->call('submit')->assertHasErrors('answers.'.$field->id)
            ->set('answers.'.$field->id, 2)->call('submit')->assertHasNoErrors();

        $r = FormResponse::where('form_id', $form->id)->where('user_id', $user->id)->first();
        $this->assertSame('zusage', $r->choice);
        $this->assertEquals(2, $r->answers[$field->id]);
    }

    public function test_absage_braucht_keine_zusatzfragen(): void
    {
        $form = $this->form();
        Livewire::actingAs($this->person())->test(Show::class, ['form' => $form])
            ->set('choice', 'absage')->call('submit')->assertHasNoErrors();
    }

    public function test_unterschrift_ist_pflicht_wenn_verlangt(): void
    {
        $form = Form::create(['title' => 'Hygiene', 'choices' => ['kenntnis'], 'requires_signature' => true, 'audience_all' => true, 'published_at' => now()->subMinute()]);
        $png = 'data:image/png;base64,'.base64_encode(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));

        Livewire::actingAs($this->person())->test(Show::class, ['form' => $form])
            ->call('submit')->assertHasErrors('signature')
            ->set('signature', $png)->call('submit')->assertHasNoErrors();
        $this->assertNotNull(FormResponse::first()->signature_path);
    }

    public function test_nicht_adressierte_person_sieht_formular_nicht(): void
    {
        $form = $this->form(['audience_all' => false, 'audience_locations' => [$this->location('Roth')->id]]);
        $this->actingAs($this->person('Fürth'))->get(route('forms.show', $form))->assertNotFound();
    }
}
