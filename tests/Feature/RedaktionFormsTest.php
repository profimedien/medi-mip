<?php

namespace Tests\Feature;

use App\Filament\Resources\Contacts\Pages\ManageContacts;
use App\Filament\Resources\Events\Pages\ManageEvents;
use App\Filament\Resources\Faqs\Pages\ManageFaqs;
use App\Filament\Resources\Forms\Pages\CreateForm;
use App\Filament\Resources\Locations\Pages\ManageLocations;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Location;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

/** Redaktionsformulare aus dem Feedback 11.11.2026: speichern ohne Fehler */
class RedaktionFormsTest extends TestCase
{
    private function admin()
    {
        $admin = $this->person('Verwaltung', ['Verwaltung']);
        $admin->assignRole('gesamt-admin');
        Filament::setCurrentPanel('admin');
        $this->actingAs($admin);

        return $admin;
    }

    public function test_ansprechpartner_intern_mit_person(): void
    {
        $this->admin();
        $colleague = $this->person();
        Livewire::test(ManageContacts::class)->callAction('create', data: [
            'type' => 'internal', 'user_id' => $colleague->id, 'name' => 'Petra Intern', 'responsibility' => 'Personal',
        ])->assertHasNoErrors();
        $this->assertSame($colleague->id, Contact::where('name', 'Petra Intern')->value('user_id'));
    }

    public function test_termin_als_fortbildung(): void
    {
        $this->admin();
        Livewire::test(ManageEvents::class)->callAction('create', data: [
            'title' => 'Kinesio-Taping', 'category' => 'training', 'starts_at' => now()->addDay()->format('Y-m-d H:i'), 'audience_all' => true,
        ])->assertHasNoErrors();
        $this->assertSame('training', Event::where('title', 'Kinesio-Taping')->value('category'));
    }

    public function test_faq_mit_video_link(): void
    {
        $this->admin();
        $cat = FaqCategory::create(['name' => 'Personal']);
        Livewire::test(ManageFaqs::class)->callAction('create', data: [
            'faq_category_id' => $cat->id, 'question' => 'Wie geht Urlaub?', 'answer' => '<p>So.</p>', 'video_url' => 'https://example.org/video',
        ])->assertHasErrors();
        Livewire::test(ManageFaqs::class)->callAction('create', data: [
            'faq_category_id' => $cat->id, 'question' => 'Wie geht Urlaub?', 'answer' => '<p>So.</p>', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ])->assertHasNoErrors();
        $this->assertSame('https://youtu.be/dQw4w9WgXcQ', Faq::where('question', 'Wie geht Urlaub?')->value('video_url'));
    }

    public function test_standort_mit_kalenderfarbe(): void
    {
        $this->admin();
        Livewire::test(ManageLocations::class)->callAction('create', data: [
            'name' => 'Teststandort', 'short_name' => 'Test', 'color' => '#9f1239', 'active' => true,
        ])->assertHasNoErrors();
        $this->assertSame('#9f1239', Location::where('short_name', 'Test')->value('color'));
    }

    public function test_neue_rueckmeldung_fragt_bild_und_ton_ab(): void
    {
        $this->admin();
        Livewire::test(CreateForm::class)->fillForm(['title' => 'Sommerfest', 'audience_all' => true])->call('create')->assertHasNoErrors();
        $this->assertSame([FormField::MEDIA_CONSENT], Form::where('title', 'Sommerfest')->first()->fields->pluck('label')->all());
    }
}
