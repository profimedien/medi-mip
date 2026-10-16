<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Link;
use App\Models\Location;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Echte Struktur laut Kundenfeedback zum Fragenkatalog (05.10.2026).
 * Annahme: Sozialpädagogik, Psychologie, Ernährung gehören zu den Reha-Zentren Ansbach und Fürth.
 */
class StructureSeeder extends Seeder
{
    public const LOCATIONS = [
        ['Ansbach Präventiv- und Reha-Zentrum', 'Ansbach Reha', 'Heilig-Kreuz-Str. 4', '91522', 'Ansbach',
            ['Rehabilitation', 'Physiotherapie', 'Medizinische Trainingstherapie', 'Arzt', 'Massage', 'Rezeption', 'Fahrdienst', 'Haustechnik', 'Sozialpädagogik', 'Psychologie', 'Ernährung']],
        ['Ansbach Praxis für Podologie', 'Ansbach Podologie', 'Heilig-Kreuz-Str. 6', '91522', 'Ansbach', ['Podologie']],
        ['Ansbach Praxis für Ergotherapie und Logopädie', 'Ansbach Ergo/Logo', 'Heilig-Kreuz-Str. 2', '91522', 'Ansbach', ['Ergotherapie', 'Logopädie']],
        ['Ansbach Praxis für Physiotherapie', 'Ansbach Draisstraße', 'Draisstraße 2', '91522', 'Ansbach',
            ['Physiotherapie', 'Medizinische Trainingstherapie', 'Rezeption', 'Reinigung']],
        ['Ansbach Praxis Physiofit Brodswinden', 'Brodswinden', 'Gottlieb-Daimler-Str. 10', '91522', 'Ansbach', ['Physiotherapie']],
        ['Fürth Ambulantes Reha- und Gesundheitszentrum', 'Fürth', 'Dr.-Mack-Straße 83', '90762', 'Fürth',
            ['Rehabilitation', 'Physiotherapie', 'Ergotherapie', 'Logopädie', 'Medizinische Trainingstherapie', 'Arzt', 'Massage', 'Rezeption', 'Sozialpädagogik', 'Psychologie', 'Ernährung']],
        ['Neuendettelsau Praxis für Physiotherapie, Ergotherapie und Logopädie', 'Neuendettelsau', 'Heilsbronner Str. 44', '91564', 'Neuendettelsau',
            ['Physiotherapie', 'Ergotherapie', 'Logopädie', 'Medizinische Trainingstherapie', 'Rezeption', 'Massage', 'Reinigung']],
        ['Forchheim Praxis für Ergotherapie und Physiotherapie', 'Forchheim', 'Sattlertorstr. 48', '91301', 'Forchheim',
            ['Physiotherapie', 'Ergotherapie', 'Rezeption']],
        ['Roth Praxis für Physiotherapie', 'Roth', 'Friedrich-Ebert-Str. 8', '91154', 'Roth', ['Physiotherapie', 'Rezeption']],
        ['Verwaltung und Geschäftsleitung', 'Verwaltung', 'Heilig-Kreuz-Str. 4', '91522', 'Ansbach', ['Verwaltung', 'Geschäftsleitung']],
    ];

    /** Nachtrag medi 09.10.2026: an allen Standorten zuordenbar, Admins räumen bei Bedarf auf. */
    public const EVERYWHERE = ['People & Culture', 'Küche', 'RV Fit', 'Praktikantinnen und Praktikanten', 'Fachliche Leitungen', 'Abteilungsleitungen'];

    public function run(): void
    {
        foreach (['gesamt-admin', 'standort-admin', 'abteilungs-admin', 'mitarbeiter'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        foreach (self::LOCATIONS as $i => [$name, $short, $street, $zip, $city, $departments]) {
            $location = Location::updateOrCreate(['name' => $name], [
                'short_name' => $short, 'street' => $street, 'zip' => $zip, 'city' => $city, 'sort' => $i,
            ]);
            $ids = [];
            foreach ([...$departments, ...self::EVERYWHERE] as $dep) {
                $ids[] = Department::firstOrCreate(['name' => $dep])->id;
            }
            $location->departments()->sync($ids);
        }

        // Weiterleitungen laut medi (Mails vom 09.10. und 16.10.2026).
        // DATEV: Einstiegsadresse statt des gemailten Links, der eine einmalige Anmelde-Sitzung enthält.
        foreach ([
            ['Time Card', 'https://medi.timecard365.de/', 'Ein- und auschecken, Urlaub eintragen'],
            ['DATEV Arbeitnehmer online', 'https://apps.datev.de/ano/', 'Gehaltsabrechnung abrufen'],
            ['Benefits App', 'https://ihre.mitarbeiterangebote.de/login', 'Mitarbeiterangebote'],
            ['Givve Card', 'https://card.givve.com/login', 'Guthabenkarte'],
            ['medi-Website', 'https://www.medi-ansbach.de', 'Unser Auftritt für Patienten'],
        ] as $i => [$title, $url, $desc]) {
            Link::updateOrCreate(['title' => $title], ['url' => $url, 'description' => $desc, 'sort' => $i]);
        }

        foreach ([
            'suggestion_topics' => "Abläufe\nAusstattung\nIT\nArbeitsplatz\nSonstiges",
            'suggestion_recipients' => 'info@profimedien.net',
            'material_recipients' => 'info@profimedien.net',
            'referral_recipients' => 'info@profimedien.net',
            'faq_recipients' => 'info@profimedien.net',
            'referral_text' => "Du kennst jemanden, der gut zu uns passt? Empfiehl uns weiter!\n\nFür jede erfolgreiche Einstellung nach Probezeit gibt es eine Prämie. Höhe und Bedingungen ergänzt die Personalabteilung.",
        ] as $key => $value) {
            Setting::set($key, $value);
        }
    }
}
