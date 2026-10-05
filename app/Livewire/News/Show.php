<?php

namespace App\Livewire\News;

use App\Models\Post;
use App\Models\PostRead;
use Livewire\Component;

class Show extends Component
{
    public Post $post;

    public function mount(Post $post): void
    {
        $user = auth()->user();
        abort_unless($post->published_at && $post->published_at->isPast() && $post->isVisibleTo($user), 404);

        PostRead::firstOrCreate(['post_id' => $post->id, 'user_id' => $user->id], ['read_at' => now()]);
        $user->unreadNotifications()->where('data->post_id', $post->id)->update(['read_at' => now()]);
    }

    public function confirm(): void
    {
        PostRead::where('post_id', $this->post->id)->where('user_id', auth()->id())
            ->whereNull('confirmed_at')->update(['confirmed_at' => now()]);
    }

    public function render()
    {
        return view('livewire.news.show', [
            'read' => PostRead::where('post_id', $this->post->id)->where('user_id', auth()->id())->first(),
            'attachments' => $this->post->getMedia('attachments'),
            'image' => $this->post->getFirstMedia('image'),
        ])->title($this->post->title);
    }
}
