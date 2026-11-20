<?php

namespace Tests\Feature;

use App\Models\Contact;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ImportPartnersTest extends TestCase
{
    public function test_import_legt_an_und_doppelt_nicht(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'partner').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($file);
        $writer->addRow(Row::fromValues(['Ansprechpartner/ Lieferanten ', '', '', '', 'P.R.']));
        $writer->addRow(Row::fromValues(['', 'Firma', 'Ansprechpartner', 'Telefonnummer', 'E-Mail']));
        $writer->addRow(Row::fromValues(['EDV u. Telefon', 'EDV Beispiel', 'Frau Muster', '0981 123', 'edv@example.org']));
        $writer->addRow(Row::fromValues(['Berufsgenossenschaft ', 'BGW', '', '040 456', '']));
        $writer->close();

        $this->artisan('mip:import-partner', ['file' => $file])->assertSuccessful();
        $this->artisan('mip:import-partner', ['file' => $file])->assertSuccessful();
        $this->artisan('mip:import-partner', ['file' => $file, '--standort' => 'Roth'])->assertSuccessful();

        $this->assertSame(2, Contact::where('type', 'external')->count(), 'zweiter Lauf aktualisiert nur');
        $edv = Contact::where('name', 'EDV Beispiel')->first();
        $this->assertSame('Frau Muster', $edv->contact_person);
        $this->assertSame([$this->location('Roth')->id], $edv->locations->pluck('id')->all());
        $this->assertSame('Berufsgenossenschaft', Contact::where('name', 'BGW')->value('responsibility'));
        unlink($file);
    }
}
