<?php

namespace App\Filament\Widgets;

use App\Models\Form;
use App\Models\Post;
use App\Support\AdminScope;
use Filament\Widgets\Widget;

/** Rücklauf offener Rückmeldungen und Lesebestätigungen auf einen Blick */
class RedaktionOverview extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.redaktion-overview';

    protected function getViewData(): array
    {
        $admin = auth()->user();

        $forms = AdminScope::content(Form::query(), $admin)->published()
            ->where(fn ($q) => $q->whereNull('deadline')->orWhere('deadline', '>', now()->subDays(3)))
            ->withCount('responses')->orderBy('deadline')->limit(5)->get()
            ->map(fn (Form $f) => [
                'title' => $f->title,
                'url' => route('filament.admin.resources.forms.edit', $f),
                'done' => $f->responses_count,
                'total' => max(1, $f->audienceMembers()->count()),
                'meta' => $f->deadline ? 'bis '.$f->deadline->format('d.m.') : 'ohne Frist',
            ]);

        $posts = AdminScope::content(Post::query(), $admin)->published()->where('requires_confirmation', true)
            ->latest('published_at')->limit(5)->get()
            ->map(fn (Post $p) => [
                'title' => $p->title,
                'url' => route('filament.admin.resources.posts.edit', $p),
                'done' => $p->reads()->whereNotNull('confirmed_at')->count(),
                'total' => max(1, $p->audienceMembers()->count()),
                'meta' => $p->published_at->format('d.m.'),
            ]);

        return ['forms' => $forms, 'posts' => $posts];
    }
}
