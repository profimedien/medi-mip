<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Einmaliger Import des Mitarbeiterstamms aus dem Export der Zeiterfassung (Excel oder CSV).
 * Erwartete Spalten (Kopfzeile, Reihenfolge egal): Vorname, Nachname, Personalnummer, E-Mail,
 * Standort, Abteilung (mehrere mit Komma), Funktion, Eintrittsdatum, Geburtstag.
 * Abgleich über Personalnummer, sonst über Vor- und Nachname.
 */
class UserImport
{
    public const COLUMNS = ['Vorname', 'Nachname', 'Personalnummer', 'E-Mail', 'Standort', 'Abteilung', 'Funktion', 'Eintrittsdatum', 'Geburtstag'];

    public array $report = ['created' => 0, 'updated' => 0, 'errors' => []];

    public function run(string $path, bool $dryRun = false): array
    {
        $rows = $this->read($path);
        $header = array_map(fn ($h) => mb_strtolower(trim((string) $h)), array_shift($rows) ?? []);
        $idx = fn (string $col) => array_search(mb_strtolower($col), $header, true);

        foreach (['Vorname', 'Nachname', 'Standort'] as $required) {
            if ($idx($required) === false) {
                $this->report['errors'][] = "Spalte \"$required\" fehlt in der Kopfzeile.";

                return $this->report;
            }
        }

        $locations = Location::all();
        foreach ($rows as $n => $row) {
            $line = $n + 2;
            $get = fn (string $col) => ($i = $idx($col)) === false ? null : (is_string($row[$i] ?? null) ? trim($row[$i]) : ($row[$i] ?? null));
            $first = $get('Vorname');
            $last = $get('Nachname');
            if (! $first && ! $last) {
                continue;
            }

            $locName = (string) $get('Standort');
            $location = $locations->first(fn ($l) => strcasecmp($l->short_name, $locName) === 0 || strcasecmp($l->name, $locName) === 0);
            if (! $location) {
                $this->report['errors'][] = "Zeile $line ($first $last): Standort \"$locName\" unbekannt.";

                continue;
            }

            $deptNames = array_filter(array_map('trim', explode(',', (string) $get('Abteilung'))));
            $deptIds = [];
            foreach ($deptNames as $d) {
                $dept = Department::whereRaw('lower(name) = ?', [mb_strtolower($d)])->first();
                if (! $dept) {
                    $this->report['errors'][] = "Zeile $line ($first $last): Abteilung \"$d\" unbekannt, bitte zuerst anlegen.";

                    continue 2;
                }
                $deptIds[] = $dept->id;
            }

            $birthday = $this->date($get('Geburtstag'));
            $attrs = array_filter([
                'first_name' => $first,
                'last_name' => $last,
                'personnel_number' => $get('Personalnummer') ? (string) $get('Personalnummer') : null,
                'email' => $get('E-Mail') ? mb_strtolower((string) $get('E-Mail')) : null,
                'job_title' => $get('Funktion'),
                'entry_date' => $this->date($get('Eintrittsdatum')),
                'birthday_day' => $birthday?->day,
                'birthday_month' => $birthday?->month,
                'location_id' => $location->id,
            ], fn ($v) => $v !== null && $v !== '');

            $user = ($attrs['personnel_number'] ?? null)
                ? User::where('personnel_number', $attrs['personnel_number'])->first()
                : User::where('first_name', $first)->where('last_name', $last)->first();

            if ($dryRun) {
                $user ? $this->report['updated']++ : $this->report['created']++;

                continue;
            }

            if ($user) {
                $user->update($attrs);
                $this->report['updated']++;
            } else {
                $user = User::create($attrs + [
                    'username' => User::makeUsername($first, $last),
                    'status' => 'invited',
                    'start_code' => User::generateStartCode(),
                    'must_change_password' => true,
                ]);
                $user->assignRole('mitarbeiter');
                $this->report['created']++;
            }
            if ($deptIds) {
                $user->departments()->sync($deptIds);
            }
        }

        if (! $dryRun) {
            app(ChatGroupSync::class)->run();
        }

        return $this->report;
    }

    private function read(string $path): array
    {
        $isCsv = str_ends_with(strtolower($path), '.csv') || str_ends_with(strtolower($path), '.txt');
        if ($isCsv) {
            $first = (string) fgets(fopen($path, 'r'));
            $options = new CsvOptions;
            $options->FIELD_DELIMITER = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
            $reader = new CsvReader($options);
        } else {
            $reader = new XlsxReader;
        }
        $reader->open($path);
        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
            break;
        }
        $reader->close();

        return $rows;
    }

    private function date(mixed $value): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }
        if (! $value) {
            return null;
        }
        foreach (['d.m.Y', 'Y-m-d', 'd.m.y'] as $format) {
            try {
                return Carbon::createFromFormat($format, (string) $value)->startOfDay();
            } catch (\Throwable) {
            }
        }

        return null;
    }
}
