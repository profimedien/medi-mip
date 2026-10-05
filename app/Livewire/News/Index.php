<?php

namespace App\Livewire\News;

use App\Models\Post;
use App\Models\PostRead;
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

    public function markAllRead(): void
    {
        $user = auth()->user();
        Post::published()->visibleTo($user)->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id')->each(fn ($id) => PostRead::create(['post_id' => $id, 'user_id' => $user->id, 'read_at' => now()]));
        $user->unreadNotifications()->whereNotNull('data->post_id')->update(['read_at' => now()]);
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
            'unread' => (clone $query)->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))->count(),
        ]);
    }
}
