<?php

namespace App\Livewire\News;

use App\Models\Post;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('News')]
class Index extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    public int $limit = 12;

    public function more(): void
    {
        $this->limit += 12;
    }

    public function render()
    {
        $user = auth()->user();
        $query = Post::published()->visibleTo($user)->with('reads')
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$this->search.'%')->orWhere('body', 'like', '%'.$this->search.'%')))
            ->orderByDesc('pinned')->orderByDesc('published_at');

        return view('livewire.news.index', [
            'posts' => (clone $query)->limit($this->limit)->get(),
            'total' => $query->count(),
        ]);
    }
}
