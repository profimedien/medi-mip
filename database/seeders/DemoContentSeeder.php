<?php

namespace Database\Seeders;

use App\Models\ChatGroup;
use App\Models\ChatMessage;
use App\Models\Classified;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Event;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Form;
use App\Models\Gallery;
use App\Models\Location;
use App\Models\OnboardingSection;
use App\Models\OrgUnit;
use App\Models\Post;
use App\Models\Suggestion;
use App\Models\User;
use App\Services\ChatGroupSync;
use Illuminate\Database\Seeder;

/** Beispielinhalte, damit der Prototyp beim ersten Besuch nicht leer wirkt. Alle Texte sind Platzhalter. */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('username', 'profimedien')->first();
        $fuerth = Location::where('short_name', 'Fürth')->first();
        $physio = Department::where('name', 'Physiotherapie')->first();
        $rezeption = Department::where('name', 'Rezeption')->first();

        // News
        $posts = [
            ['Willkommen im MIP, Ihrem Mitarbeiter Informationsportal', 'Ab sofort finden Sie hier News, Termine, Ansprechpartner und vieles mehr an einem Ort.', true, false, ['audience_all' => true]],
            ['Neue Hygieneregeln ab November', 'Bitte lesen und bestätigen: Die überarbeiteten Hygieneregeln gelten ab 01.11. an allen Standorten.', false, true, ['audience_all' => true]],
            ['Teambesprechung Physiotherapie', 'Die nächste Fachbereichsbesprechung findet online statt. Einladung folgt im Kalender.', false, false, ['audience_all' => false, 'audience_departments' => [$physio->id]]],
            ['Fürth: Neue Geräte im Trainingsbereich', 'Im Trainingsbereich stehen ab Montag zwei neue Geräte bereit. Einweisung jeweils dienstags.', false, false, ['audience_all' => false, 'audience_locations' => [$fuerth->id]]],
            ['Dienstrad-Leasing: jetzt informieren', 'Alle Infos zum Dienstrad finden Sie im Onboarding-Bereich unter Benefits.', false, false, ['audience_all' => true]],
            ['Empfang: neue Telefonzeiten', 'Ab dem kommenden Monat gelten am Empfang neue Telefonzeiten.', false, false, ['audience_all' => false, 'audience_departments' => [$rezeption->id]]],
        ];
        foreach ($posts as $i => [$title, $teaser, $pinned, $confirm, $audience]) {
            Post::create([
                'title' => $title,
                'teaser' => $teaser,
                'body' => '<p>'.$teaser.'</p><p>Dies ist ein Beispielbeitrag für den Prototyp. Die echten Inhalte pflegt die Redaktion von medi.</p>',
                'pinned' => $pinned,
                'requires_confirmation' => $confirm,
                'author_id' => $admin->id,
                'published_at' => now()->subDays($i * 2),
                'notified_at' => now()->subDays($i * 2),
            ] + $audience);
        }

        // Termine
        $events = [
            ['Sommerfest der medi Gruppe', 40, 'Ansbach, Reha-Zentrum', true],
            ['Erste-Hilfe-Auffrischung', 12, 'Fürth, Schulungsraum', false],
            ['Betriebsversammlung', 25, 'Ansbach', false],
            ['Fortbildung Manuelle Therapie', 18, 'online', false],
            ['Weihnachtsfeier', 70, 'wird bekannt gegeben', true],
        ];
        foreach ($events as [$title, $days, $place, $allDay]) {
            Event::create([
                'title' => $title, 'place' => $place, 'all_day' => $allDay,
                'starts_at' => now()->addDays($days)->setTime(15, 0), 'ends_at' => now()->addDays($days)->setTime(18, 0),
                'description' => 'Beispieltermin für den Prototyp.', 'audience_all' => true, 'author_id' => $admin->id,
            ]);
        }

        // FAQ
        $faqs = [
            'Personal' => [['Wie reiche ich Urlaub ein?', 'Über den Urlaubsantrag im Onboarding-Bereich. Die Freigabe erfolgt durch Ihre Leitung.'], ['Was mache ich bei Krankheit?', 'Bitte am ersten Tag vor Dienstbeginn am Standort melden und die Krankmeldung einreichen.']],
            'IT und Zugänge' => [['Ich habe mein Passwort vergessen', 'Wenden Sie sich an Ihre Standortleitung. Sie erhalten einen neuen Startcode.'], ['Wie installiere ich das Portal als App?', 'Im Browser auf "Zum Home-Bildschirm hinzufügen" tippen. Eine Anleitung finden Sie in Ihrem Profil.']],
            'Arbeitsplatz' => [['Wo bekomme ich Therapiematerial?', 'Über die Materialanfrage im Bereich Beteiligung.']],
        ];
        $sort = 0;
        foreach ($faqs as $category => $items) {
            $cat = FaqCategory::create(['name' => $category, 'sort' => $sort++]);
            foreach ($items as $j => [$q, $a]) {
                Faq::create(['faq_category_id' => $cat->id, 'question' => $q, 'answer' => '<p>'.$a.'</p>', 'sort' => $j]);
            }
        }

        // Ansprechpartner
        foreach ([
            ['Personalabteilung', 'Verträge, Lohn, Urlaub', null],
            ['Qualitätsmanagement', 'Hygiene, Dokumentation', null],
            ['IT-Ansprechpartner', 'Zugänge, Geräte, Portal', null],
            ['Standortleitung Fürth', 'Alles rund um den Standort Fürth', $fuerth->id],
        ] as $i => [$resp, $desc, $loc]) {
            Contact::create(['name' => fake('de_DE')->name(), 'responsibility' => $resp.' · '.$desc, 'location_id' => $loc,
                'phone' => '0981 '.fake()->numberBetween(10000, 99999), 'email' => 'beispiel'.$i.'@example.org', 'sort' => $i]);
        }

        // Organigramm (Beispielstruktur)
        $gl = OrgUnit::create(['name' => 'Geschäftsleitung', 'head_name' => 'N. N.', 'sort' => 0]);
        foreach (['Verwaltung', 'Qualitätsmanagement', 'Standorte Ansbach', 'Standort Fürth', 'Standorte Region'] as $i => $name) {
            $unit = OrgUnit::create(['name' => $name, 'parent_id' => $gl->id, 'head_name' => 'N. N.', 'sort' => $i]);
            if ($name === 'Standorte Ansbach') {
                foreach (['Reha-Zentrum', 'Podologie', 'Ergotherapie/Logopädie', 'Draisstraße', 'Brodswinden'] as $j => $sub) {
                    OrgUnit::create(['name' => $sub, 'parent_id' => $unit->id, 'sort' => $j]);
                }
            }
        }

        // Onboarding
        foreach ([
            ['Zeiterfassung', 'clock'], ['Krankmeldung', 'heart'], ['Urlaubsantrag', 'sun'],
            ['Fortbildungsantrag', 'academic-cap'], ['Benefits', 'gift'], ['Dienstrad', 'bolt'],
        ] as $i => [$title, $icon]) {
            OnboardingSection::create(['title' => $title, 'icon' => $icon, 'sort' => $i,
                'body' => '<p>Hier steht künftig, wie '.$title.' bei medi funktioniert: Ablauf, Ansprechpartner und Formulare zum Download.</p>']);
        }

        // Rückmeldungen
        $sommerfest = Form::create([
            'title' => 'Sommerfest: Wer ist dabei?',
            'description' => '<p>Bitte geben Sie bis zur Frist Bescheid, ob Sie dabei sind.</p>',
            'choices' => ['zusage', 'absage'], 'deadline' => now()->addDays(20), 'audience_all' => true,
            'author_id' => $admin->id, 'published_at' => now()->subDay(), 'notified_at' => now()->subDay(),
        ]);
        $sommerfest->fields()->createMany([
            ['type' => 'number', 'label' => 'Anzahl Begleitpersonen', 'sort' => 0],
            ['type' => 'select', 'label' => 'Essenswunsch', 'options' => ['Fleisch', 'Vegetarisch', 'Vegan'], 'sort' => 1],
            ['type' => 'text', 'label' => 'Unverträglichkeiten', 'sort' => 2],
        ]);
        Form::create([
            'title' => 'Hygieneregeln: Kenntnisnahme',
            'description' => '<p>Bitte bestätigen Sie mit Ihrer Unterschrift, dass Sie die neuen Hygieneregeln gelesen haben.</p>',
            'choices' => ['kenntnis'], 'requires_signature' => true, 'deadline' => now()->addDays(14),
            'audience_all' => true, 'author_id' => $admin->id, 'published_at' => now(), 'notified_at' => now(),
        ]);

        // Beteiligung und Kleinanzeigen
        $someone = User::where('username', 'demo.mitarbeiter')->first();
        Suggestion::create(['user_id' => $someone->id, 'topic' => 'Ausstattung', 'body' => 'Zweiter Wasserspender im Wartebereich wäre super.']);
        foreach ([
            ['private', 'Kinderfahrrad 20 Zoll', 'Gut erhalten, Abholung in Ansbach.', '40 €'],
            ['private', 'Mitfahrgelegenheit Nürnberg–Fürth', 'Fahre Mo bis Fr gegen 7 Uhr, zwei Plätze frei.', null],
            ['business', 'Therapiebänder abzugeben', 'Restbestand aus Neuendettelsau, gerne an andere Standorte.', null],
        ] as [$cat, $title, $body, $price]) {
            Classified::create(['user_id' => $someone->id, 'category' => $cat, 'title' => $title, 'body' => $body, 'price' => $price, 'expires_at' => now()->addDays(Classified::RUNTIME_DAYS)]);
        }

        // Galerie mit erzeugten Platzhalterbildern
        $gallery = Gallery::create(['title' => 'Eröffnung Fürth', 'description' => 'Platzhalterbilder für den Prototyp.', 'event_date' => now()->subMonths(2), 'audience_all' => true]);
        foreach ([[0, 50, 186], [30, 136, 210], [0, 61, 143], [224, 0, 58]] as $i => $rgb) {
            $gallery->addMedia($this->placeholderImage($rgb, 'Foto '.($i + 1)))->toMediaCollection('photos');
        }

        // Chatgruppen anlegen und mit Beispielnachrichten füllen
        app(ChatGroupSync::class)->run();
        $announcement = ChatGroup::where('type', 'announcement')->first();
        $this->message($announcement, $admin, 'Herzlich willkommen im Medigram! Hier kommen wichtige Ankündigungen für alle.');
        $fuerthGroup = ChatGroup::where('type', 'location')->where('location_id', $fuerth->id)->first();
        $fuerthAdmin = User::where('username', 'admin.fuerth')->first();
        $this->message($fuerthGroup, $fuerthAdmin, 'Guten Morgen Fürth! Denkt bitte an die Geräteeinweisung am Dienstag.');
        $physioGroup = ChatGroup::where('type', 'department')->where('department_id', $physio->id)->first();
        $this->message($physioGroup, User::where('username', 'admin.physio')->first(), 'Fortbildung Manuelle Therapie: Wer hat Interesse?');
        $this->message($physioGroup, $someone, 'Ich wäre dabei!');
    }

    private function message(ChatGroup $group, User $user, string $body): void
    {
        $msg = ChatMessage::create(['chat_group_id' => $group->id, 'user_id' => $user->id, 'body' => $body]);
        $group->update(['last_message_at' => $msg->created_at]);
    }

    private function placeholderImage(array $rgb, string $label): string
    {
        $img = imagecreatetruecolor(1200, 800);
        imagefill($img, 0, 0, imagecolorallocate($img, ...$rgb));
        imagestring($img, 5, 560, 390, $label, imagecolorallocate($img, 255, 255, 255));
        $path = tempnam(sys_get_temp_dir(), 'mip').'.jpg';
        imagejpeg($img, $path, 85);
        imagedestroy($img);

        return $path;
    }
}
