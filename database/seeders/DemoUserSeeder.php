<?php

namespace Database\Seeders;

use App\Models\AdminScope;
use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo-Personen für den Prototyp, keine echten Mitarbeiterdaten.
 * Rund 160 Personen, davon etwa 70 % ohne eigene E-Mail-Adresse (Kundenangabe).
 *
 * Testkonten (nur Testsystem):
 *  profimedien       Gesamt-Admin      Passwort: smoker88.,
 *  admin.fuerth      Standort-Admin Fürth            Passwort: DEMO_PASSWORD
 *  admin.physio      Abteilungs-Admin Physiotherapie Passwort: DEMO_PASSWORD
 *  demo.mitarbeiter  Mitarbeiter ohne E-Mail         Passwort: DEMO_PASSWORD
 *  demo.start        noch nicht angemeldet           Startcode: DEMO_START_CODE
 */
class DemoUserSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'medi-demo-2026';

    public const DEMO_START_CODE = 'MEDI-2026';

    private ?string $demoHash = null;

    public function run(): void
    {
        // einmal hashen statt 150-mal (die hashed-Cast erkennt fertige Hashes)
        $this->demoHash = Hash::make(self::DEMO_PASSWORD);
        $faker = fake('de_DE');
        $fuerth = Location::where('short_name', 'Fürth')->first();
        $neuendettelsau = Location::where('short_name', 'Neuendettelsau')->first();
        $verwaltung = Location::where('short_name', 'Verwaltung')->first();
        $physio = Department::where('name', 'Physiotherapie')->first();

        $admin = $this->person('Marco', 'Zinsmeister', 'profimedien', 'info@profimedien.net', $verwaltung, ['Geschäftsleitung'], 'smoker88.,', 'Agentur · Testzugang');
        $admin->assignRole('gesamt-admin');

        $fuerthAdmin = $this->person('Sabine', 'Krämer', 'admin.fuerth', null, $fuerth, ['Rezeption'], self::DEMO_PASSWORD, 'Standortleitung');
        $fuerthAdmin->assignRole('standort-admin');
        AdminScope::create(['user_id' => $fuerthAdmin->id, 'location_id' => $fuerth->id]);

        $physioAdmin = $this->person('Thomas', 'Weber', 'admin.physio', null, $neuendettelsau, ['Physiotherapie'], self::DEMO_PASSWORD, 'Fachbereichsleitung Physiotherapie');
        $physioAdmin->assignRole('abteilungs-admin');
        AdminScope::create(['user_id' => $physioAdmin->id, 'department_id' => $physio->id]);

        $this->person('Lena', 'Hofmann', 'demo.mitarbeiter', null, $neuendettelsau, ['Physiotherapie'], self::DEMO_PASSWORD, 'Physiotherapeutin')
            ->assignRole('mitarbeiter');

        $start = $this->person('Jonas', 'Becker', 'demo.start', null, $fuerth, ['Ergotherapie'], null, 'Ergotherapeut');
        $start->forceFill(['start_code' => self::DEMO_START_CODE, 'status' => 'invited', 'must_change_password' => true])->save();
        $start->assignRole('mitarbeiter');

        // Weitere Demo-Personen, verteilt auf die Standorte
        $locations = Location::with('departments')->where('id', '!=', $verwaltung->id)->get();
        $jobTitles = [
            'Physiotherapie' => 'Physiotherapeut/in', 'Ergotherapie' => 'Ergotherapeut/in', 'Logopädie' => 'Logopäde/Logopädin',
            'Podologie' => 'Podologe/Podologin', 'Rehabilitation' => 'Sporttherapeut/in', 'Medizinische Trainingstherapie' => 'Trainingstherapeut/in',
            'Arzt' => 'Ärztin/Arzt', 'Massage' => 'Masseur/in', 'Rezeption' => 'Empfang', 'Fahrdienst' => 'Fahrer/in',
            'Haustechnik' => 'Haustechniker/in', 'Reinigung' => 'Reinigungskraft', 'Sozialpädagogik' => 'Sozialpädagoge/in',
            'Psychologie' => 'Psychologe/Psychologin', 'Ernährung' => 'Ernährungsberater/in',
        ];

        for ($i = 0; $i < 150; $i++) {
            $location = $i < 8 ? $verwaltung->load('departments') : $locations->random();
            $deps = $location->departments->random(min($location->departments->count(), $faker->boolean(15) ? 2 : 1));
            $first = $faker->firstName();
            $last = $faker->lastName();
            $hasMail = $faker->boolean(30);
            $user = $this->person(
                $first, $last, User::makeUsername($first, $last),
                $hasMail ? User::makeUsername($first, $last).'@example.org' : null,
                $location, $deps->pluck('name')->all(), $this->demoHash,
                $jobTitles[$deps->first()->name] ?? $deps->first()->name,
            );
            if ($faker->boolean(12)) {
                $user->forceFill(['status' => 'invited', 'password' => null, 'start_code' => User::generateStartCode()])->save();
            }
            $user->assignRole('mitarbeiter');
        }
    }

    private function person(string $first, string $last, string $username, ?string $email, Location $location, array $departments, ?string $password, string $job): User
    {
        $faker = fake('de_DE');
        $birthday = $faker->dateTimeBetween('-60 years', '-20 years');

        $user = User::create([
            'first_name' => $first,
            'last_name' => $last,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'must_change_password' => false,
            'status' => 'active',
            'personnel_number' => (string) $faker->unique()->numberBetween(1000, 9999),
            'job_title' => $job,
            'entry_date' => $faker->dateTimeBetween('-25 years', '-1 month'),
            'birthday_day' => (int) $birthday->format('j'),
            'birthday_month' => (int) $birthday->format('n'),
            'location_id' => $location->id,
            'profile_visible' => $faker->boolean(70),
            'show_birthday' => $faker->boolean(40),
            'show_anniversary' => true,
            'phone_extension' => $faker->boolean(50) ? (string) $faker->numberBetween(100, 399) : null,
            'qualifications' => $faker->boolean(30) ? $faker->randomElement(['Manuelle Therapie', 'Lymphdrainage', 'Bobath', 'KG-Gerät', 'Sportphysiotherapie']) : null,
        ]);
        $user->departments()->sync(Department::whereIn('name', $departments)->pluck('id'));

        return $user;
    }
}
