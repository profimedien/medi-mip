<?php

namespace Database\Seeders;

use App\Models\ChatGroup;
use App\Models\ChatMessage;
use App\Models\Classified;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Frischt die Demodaten für Vorführungen auf, ohne den Rest anzufassen (mehrfach ausführbar):
 * Beispiel-Unterhaltungen im Medigram, Geburtstage und Jubiläen im laufenden Monat je Standort,
 * Kategorien der Demo-Kleinanzeigen.
 * Testsystem: php84 artisan db:seed --class=DemoRefreshSeeder --force
 */
class DemoRefreshSeeder extends Seeder
{
    private const CHATS = [
        'Ansbach Reha' => [
            'Guten Morgen zusammen! Kaffeemaschine im Sozialraum ist wieder repariert.',
            'Super, danke fürs Bescheid geben!',
            'Wer kann am Freitag die Spätschicht an der Rezeption übernehmen? Ich tausche gern.',
            'Ich kann, schreib mir kurz die Uhrzeit.',
            'Danke Dir, 14 bis 19 Uhr.',
        ],
        'Fürth' => [
            'Die Parkplätze hinten sind ab Montag wegen Bauarbeiten gesperrt, bitte vorne parken.',
            'Weiß jemand, wie lange das dauert?',
            'Laut Hausverwaltung etwa zwei Wochen.',
        ],
        'Physiotherapie' => [
            'Die neuen Therapiebänder sind da, liegen im Lager Regal 3.',
            'Top, danke! Gibt es auch die extra starken?',
            'Ja, zwei Packungen. Bitte nicht alle auf einmal mitnehmen',
        ],
        'Rezeption' => [
            'Kurze Info: Das Kartenlesegerät an Platz 2 spinnt, bitte Platz 1 nutzen.',
            'Habe die Haustechnik informiert, kommt heute Nachmittag.',
        ],
        'Fahrdienst' => [
            'Bus 2 steht heute in der Werkstatt, Touren laufen über Bus 4.',
            'Alles klar, ich passe meine Tour an.',
        ],
    ];

    private const ANNOUNCEMENT = 'Erinnerung: Die Betriebsversammlung findet am Freitag um 15 Uhr statt. Infos im Kalender.';

    public function run(): void
    {
        $this->chats();
        $this->birthdaysAndAnniversaries();
        $this->classifiedTopics();
    }

    private function chats(): void
    {
        $start = now()->subDays(2)->setTime(8, 0);
        foreach (self::CHATS as $name => $lines) {
            $group = ChatGroup::where('name', $name)->whereIn('type', ['location', 'department'])->first();
            if (! $group || $group->messages()->count() >= count($lines)) {
                continue;
            }
            $authors = $group->users()->where('status', 'active')->inRandomOrder()->limit(3)->get();
            if ($authors->count() < 2) {
                continue;
            }
            foreach ($lines as $i => $body) {
                $this->message($group, $authors[$i % $authors->count()], $body, $start->copy()->addHours($i * 3 + rand(0, 40) / 60));
            }
            $start->addHours(5);
        }

        $announcement = ChatGroup::where('type', 'announcement')->first();
        $admin = User::where('username', 'profimedien')->first();
        if ($announcement && $admin && ! $announcement->messages()->where('body', self::ANNOUNCEMENT)->exists()) {
            $this->message($announcement, $admin, self::ANNOUNCEMENT, now()->subHours(3));
        }
    }

    private function message(ChatGroup $group, User $user, string $body, $at): void
    {
        $msg = ChatMessage::create(['chat_group_id' => $group->id, 'user_id' => $user->id, 'body' => $body]);
        $msg->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
        if (! $group->last_message_at || $group->last_message_at->lt($at)) {
            $group->update(['last_message_at' => $at]);
        }
    }

    /** Je Standort zwei Geburtstage und ein Jubiläum im laufenden Monat, ein Geburtstag im nächsten (nur Personen mit Opt-in) */
    private function birthdaysAndAnniversaries(): void
    {
        $month = now()->month;
        $next = now()->addMonthNoOverflow()->month;
        foreach (Location::all() as $location) {
            $people = User::where('location_id', $location->id)->where('status', 'active')
                ->whereDoesntHave('roles', fn ($q) => $q->where('name', '!=', 'mitarbeiter'));
            $missing = 2 - (clone $people)->where('show_birthday', true)->where('birthday_month', $month)->count();
            foreach ((clone $people)->where('birthday_month', '!=', $month)->inRandomOrder()->limit(max(0, $missing))->get() as $u) {
                $u->forceFill(['show_birthday' => true, 'birthday_month' => $month, 'birthday_day' => rand(1, 28)])->save();
            }
            if (! (clone $people)->where('show_birthday', true)->where('birthday_month', $next)->exists()) {
                (clone $people)->where('birthday_month', '!=', $month)->inRandomOrder()->first()
                    ?->forceFill(['show_birthday' => true, 'birthday_month' => $next, 'birthday_day' => rand(1, 28)])->save();
            }
            if (! (clone $people)->where('show_anniversary', true)->whereMonth('entry_date', $month)->exists()) {
                (clone $people)->inRandomOrder()->first()
                    ?->forceFill(['show_anniversary' => true, 'entry_date' => now()->subYears([5, 10, 15][rand(0, 2)])->day(rand(1, 28))])->save();
            }
        }
    }

    private function classifiedTopics(): void
    {
        foreach (['Kinderfahrrad' => 'Verkaufen', 'Mitfahrgelegenheit' => 'Mitfahrgelegenheit', 'Therapiebänder' => 'Therapiematerial'] as $title => $topic) {
            Classified::whereNull('topic')->where('title', 'like', $title.'%')->update(['topic' => $topic]);
        }
    }
}
