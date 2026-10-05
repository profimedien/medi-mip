<?php

namespace App\Exports;

use App\Models\Form;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Antworten einer Rückmeldung als Excel-Datei, inklusive Personen ohne Antwort */
class FormResponsesExport
{
    public function __construct(private Form $form) {}

    public function download(): StreamedResponse
    {
        $form = $this->form->load('fields');
        $name = 'rueckmeldung-'.$form->id.'-'.now()->format('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($form) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(array_merge(
                ['Nachname', 'Vorname', 'Standort', 'Antwort'],
                $form->fields->pluck('label')->all(),
                ['Unterschrift', 'Beantwortet am'],
            )));

            $responses = $form->responses()->get()->keyBy('user_id');
            foreach ($form->audienceMembers()->with('location')->orderBy('last_name')->get() as $user) {
                $r = $responses->get($user->id);
                $answers = $form->fields->map(function ($f) use ($r) {
                    $a = $r?->answers[$f->id] ?? '';

                    return is_array($a) ? implode(', ', $a) : (is_bool($a) ? ($a ? 'Ja' : 'Nein') : (string) $a);
                })->all();
                $writer->addRow(Row::fromValues(array_merge(
                    [$user->last_name, $user->first_name, $user->location?->short_name ?? '', $r ? ($r->choice ?: 'abgegeben') : 'keine Antwort'],
                    $answers,
                    [$r?->signature_path ? 'ja' : '', $r?->updated_at?->format('d.m.Y H:i') ?? ''],
                )));
            }
            $writer->close();
        }, $name, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
