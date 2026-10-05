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

    public function mount(): void
    {
        $this->month = $this->month ?: now()->format('Y-m');
    }

    public function shift(int $months): void
    {
        $this->month = CarbonImmutable::createFromFormat('Y-m', $this->month)->startOfMonth()->addMonths($months)->format('Y-m');
    }

    public function render()
    {
        $user = auth()->user();
        $start = CarbonImmutable::createFromFormat('Y-m', $this->month)->startOfMonth();
        $locationId = $this->location === 'mine' ? $user->location_id : ($this->location === 'all' ? null : (int) $this->location);

        $entries = app(CalendarFeed::class)->between($user, $start, $start->endOfMonth(), $locationId)
            ->whereIn('type', $this->types)
            ->groupBy(fn ($e) => $e['date']->format('Y-m-d'));

        return view('livewire.calendar', [
            'start' => $start,
            'days' => $entries,
            'locations' => Location::where('active', true)->orderBy('sort')->get(),
        ]);
    }
}
