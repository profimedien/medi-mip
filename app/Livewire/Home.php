<?php

namespace App\Livewire;

use App\Models\Form;
use App\Models\Link;
use App\Models\Post;
use App\Services\CalendarFeed;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Start')]
class Home extends Component
{
    public function render()
    {
        $user = auth()->user();

        return view('livewire.home', [
            'user' => $user,
            'posts' => Post::published()->visibleTo($user)->with('reads')->orderByDesc('pinned')->orderByDesc('published_at')->limit(4)->get(),
            'openForms' => Form::published()->visibleTo($user)
                ->where(fn ($q) => $q->whereNull('deadline')->orWhere('deadline', '>', now()))
                ->whereDoesntHave('responses', fn ($q) => $q->where('user_id', $user->id))
                ->orderBy('deadline')->get(),
            'upcoming' => app(CalendarFeed::class)->between($user, CarbonImmutable::today(), CarbonImmutable::today()->addDays(14), $user->location_id)->take(5),
            'links' => Link::orderBy('sort')->get(),
        ]);
    }
}
