<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\Location;
use Illuminate\Console\Command;
use OpenSpout\Reader\XLSX\Reader;

/**
 * Kooperationspartner aus der Excel-Liste von medi übernehmen (Liste vom 20.11.2026).
 * Erwartet eine Kopfzeile mit "Firma" in Spalte B. Spalten: A Bereich, B Firma, C Ansprechpartner, D Telefon, E E-Mail.
 * Wiederholbar: Firma plus Bereich ist der Schlüssel, vorhandene Einträge werden aktualisiert.
 */
class ImportPartners extends Command
{
    protected $signature = 'mip:import-partner {file} {--standort=alle : Kurzname der Einrichtung oder "alle"}';

    protected $description = 'Kooperationspartner (extern) aus einer Excel-Liste anlegen oder aktualisieren';

    public function handle(): int
    {
        $locationIds = [];
        if ($this->option('standort') !== 'alle') {
            $location = Location::where('short_name', $this->option('standort'))->first();
            if (! $location) {
                $this->error('Einrichtung nicht gefunden: '.$this->option('standort'));

                return self::FAILURE;
            }
            $locationIds = [$location->id];
        }

        $reader = new Reader;
        $reader->open($this->argument('file'));
        $created = $updated = 0;
        $headerFound = false;
        $sort = (int) Contact::max('sort');

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = array_map(fn ($v) => trim((string) $v), array_pad($row->toArray(), 5, ''));
                [$area, $company, $person, $phone, $email] = array_slice($cells, 0, 5);

                if (! $headerFound) {
                    $headerFound = $company === 'Firma';

                    continue;
                }
                if ($company === '' || $area === '') {
                    continue;
                }

                $contact = Contact::firstOrNew(['type' => 'external', 'name' => $company, 'responsibility' => $area]);
                $contact->exists ? $updated++ : $created++;
                $contact->fill([
                    'contact_person' => $person ?: null,
                    'phone' => $phone ?: null,
                    'email' => $email ?: null,
                ]);
                $contact->sort ??= ++$sort;
                $contact->save();
                $contact->locations()->sync($locationIds);
            }
            break; // nur das erste Blatt
        }
        $reader->close();

        if (! $headerFound) {
            $this->error('Keine Kopfzeile mit "Firma" in Spalte B gefunden.');

            return self::FAILURE;
        }
        $this->info("Kooperationspartner: {$created} neu, {$updated} aktualisiert.");

        return self::SUCCESS;
    }
}
