<?php

namespace Tests\Feature;

use App\Livewire\News\Index;
use App\Livewire\News\Show;
use App\Models\Post;
use App\Models\PostRead;
use Livewire\Livewire;
use Tests\TestCase;

class NewsReadTest extends TestCase
{
    public function test_oeffnen_markiert_gelesen_und_ungelesen_ist_moeglich(): void
    {
        $user = $this->person();
        $post = Post::create(['title' => 'Info', 'audience_all' => true, 'published_at' => now()->subMinute()]);

        Livewire::actingAs($user)->test(Show::class, ['post' => $post]);
        $this->assertTrue(PostRead::where('post_id', $post->id)->where('user_id', $user->id)->exists());

        Livewire::actingAs($user)->test(Show::class, ['post' => $post])->call('markUnread');
        $this->assertFalse(PostRead::where('post_id', $post->id)->where('user_id', $user->id)->exists());
    }

    public function test_bestaetigte_news_bleibt_gelesen(): void
    {
        $user = $this->person();
        $post = Post::create(['title' => 'Pflicht', 'requires_confirmation' => true, 'audience_all' => true, 'published_at' => now()->subMinute()]);
        Livewire::actingAs($user)->test(Show::class, ['post' => $post])->call('confirm')->call('markUnread');
        $this->assertNotNull(PostRead::where('post_id', $post->id)->first()?->confirmed_at);
    }

    public function test_alle_als_gelesen_markieren(): void
    {
        $user = $this->person();
        Post::create(['title' => 'A', 'audience_all' => true, 'published_at' => now()->subMinute()]);
        Post::create(['title' => 'B', 'audience_all' => true, 'published_at' => now()->subMinute()]);
        Livewire::actingAs($user)->test(Index::class)->call('markAllRead');
        $this->assertSame(2, PostRead::where('user_id', $user->id)->count());
    }
}
