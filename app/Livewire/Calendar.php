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

    public array $types = ['event', 'anniversary', 'birthday'];

    public ?string $day = null;

    public function mount(): void
    {
        $this->month = $this->month ?: now()->format('Y-m');
        $this->day = now()->format('Y-m') === $this->month ? now()->toDateString() : null;
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

        return view('livewire.calendar', [
            'start' => $start,
            'weeks' => $weeks,
            'entries' => $entries,
            'selected' => $selected,
            'selectedEntries' => $selected ? ($entries[$selected->toDateString()] ?? collect()) : collect(),
            'monthEntries' => $entries->filter(fn ($v, $k) => str_starts_with($k, $this->month))->flatten(1),
            'locations' => Location::where('active', true)->orderBy('sort')->get(),
        ]);
    }
}
