<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Kalendereinträge für einen Zeitraum: Termine (blau), Jubiläen (grün), Geburtstage (orange).
 * Geburtstage nur mit Zustimmung der Person, ohne Alter. Jubiläen ab 5 Jahren im 5-Jahres-Takt.
 */
class CalendarFeed
{
    public function between(User $viewer, CarbonImmutable $from, CarbonImmutable $to, ?int $locationId = null): Collection
    {
        $entries = collect();

        $events = Event::visibleTo($viewer)
            ->where('starts_at', '<=', $to->endOfDay())
            ->where(fn ($q) => $q->where('starts_at', '>=', $from->startOfDay())->orWhere('ends_at', '>=', $from->startOfDay()))
            ->orderBy('starts_at')->get();
        foreach ($events as $event) {
            $entries->push([
                'type' => 'event', 'date' => CarbonImmutable::parse($event->starts_at)->startOfDay(),
                'title' => $event->title, 'time' => $event->all_day ? null : $event->starts_at->format('H:i'),
                'place' => $event->place, 'description' => $event->description,
            ]);
        }

        $people = User::where('status', '!=', 'disabled')
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->with('location')->get();

        foreach ($people as $person) {
            foreach ($this->yearsInRange($from, $to) as $year) {
                if ($person->show_birthday && $person->birthday_day && $person->birthday_month
                    && checkdate($person->birthday_month, $person->birthday_day, $year)) {
                    $date = CarbonImmutable::create($year, $person->birthday_month, $person->birthday_day);
                    if ($date->betweenIncluded($from->startOfDay(), $to->endOfDay())) {
                        $entries->push(['type' => 'birthday', 'date' => $date, 'title' => 'Geburtstag: '.$person->name,
                            'place' => $person->location?->label, 'user_id' => $person->id]);
                    }
                }
                if ($person->show_anniversary && $person->entry_date) {
                    $years = $year - $person->entry_date->year;
                    $date = CarbonImmutable::create($year, $person->entry_date->month, min($person->entry_date->day, 28));
                    if ($years >= 5 && $years % 5 === 0 && $date->betweenIncluded($from->startOfDay(), $to->endOfDay())) {
                        $entries->push(['type' => 'anniversary', 'date' => $date, 'title' => $years.' Jahre bei medi: '.$person->name,
                            'place' => $person->location?->label, 'user_id' => $person->id]);
                    }
                }
            }
        }

        return $entries->sortBy([['date', 'asc'], ['time', 'asc']])->values();
    }

    private function yearsInRange(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return range($from->year, $to->year);
    }
}
