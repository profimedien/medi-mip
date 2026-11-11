<?php

namespace App\Livewire;

use App\Models\Location;
use App\Services\CalendarFeed;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Kalender')]
class Calendar extends Component
{
    #[Url]
    public string $month = '';

    #[Url]
    public string $location = 'mine';

    public const TYPES = ['event', 'training', 'anniversary', 'birthday'];

    public array $types = self::TYPES;

    public ?string $day = null;

    public function mount(): void
    {
        // gemerkte Filter der Person, sofern der Aufruf nicht selbst einen Standort mitbringt
        $saved = auth()->user()->calendar_filter ?? [];
        if (! request()->has('location') && isset($saved['location'])) {
            $this->location = (string) $saved['location'];
        }
        if (isset($saved['types']) && is_array($saved['types'])) {
            $this->types = array_values(array_intersect(self::TYPES, $saved['types']));
        }
        $this->month = $this->month ?: now()->format('Y-m');
        $this->day = now()->format('Y-m') === $this->month ? now()->toDateString() : null;
    }

    /** Filter je Person merken (Feedback 11.11.2026: nicht auf Standard zurückfallen) */
    public function updated(string $property): void
    {
        if ($property === 'location' || str_starts_with($property, 'types')) {
            $this->types = array_values(array_intersect(self::TYPES, $this->types));
            auth()->user()->update(['calendar_filter' => ['location' => $this->location, 'types' => $this->types]]);
        }
    }

    public function shift(int $months): void
    {
        $this->month = CarbonImmutable::createFromFormat('Y-m-d', $this->month.'-01')->addMonths($months)->format('Y-m');
        $this->day = null;
    }

    public function today(): void
    {
        $this->month = now()->format('Y-m');
        $this->day = now()->toDateString();
    }

    public function select(string $date): void
    {
        $this->day = $date;
    }

    public function render()
    {
        $user = auth()->user();
        $start = CarbonImmutable::createFromFormat('Y-m-d', $this->month.'-01')->startOfDay();
        $gridStart = $start->startOfWeek(CarbonImmutable::MONDAY);
        $gridEnd = $start->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);
        $locationId = $this->location === 'mine' ? $user->location_id : ($this->location === 'all' ? null : (int) $this->location);

        $entries = app(CalendarFeed::class)->between($user, $gridStart, $gridEnd, $locationId)
            ->whereIn('type', $this->types)
            ->groupBy(fn ($e) => $e['date']->toDateString());

        $weeks = [];
        for ($d = $gridStart; $d <= $gridEnd; $d = $d->addDay()) {
            $weeks[intdiv($gridStart->diffInDays($d), 7)][] = $d;
        }

        $selected = $this->day ? CarbonImmutable::parse($this->day) : null;
        $locations = Location::where('active', true)->orderBy('sort')->get();

        return view('livewire.calendar', [
            'start' => $start,
            'weeks' => $weeks,
            'entries' => $entries,
            'selected' => $selected,
            'selectedEntries' => $selected ? ($entries[$selected->toDateString()] ?? collect()) : collect(),
            'monthEntries' => $entries->filter(fn ($v, $k) => str_starts_with($k, $this->month))->flatten(1),
            'locations' => $locations,
            'locationColors' => $locations->pluck('color', 'id')->filter()->all(),
        ]);
    }
}
